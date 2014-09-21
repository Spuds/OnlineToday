<?php

/**
 * Users online today
 *
 * @name      Users online today
 * @copyright Users online today contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.2.0
 *
 */

/**
 * Handles the visualization of members that have been on-line "today"
 *
 */
class Online_Today
{
	private static $_instance = null;
	private $_show_since = 0;
	private $sort_by = '';
	private $sort_dir = 'asc';
	private $state = 0;
	private $_sorting_vals = array();
	private $_buddies = array();
	private $_num_buddies = 0;
	private $_num_hidden = 0;
	private $_users_list = array();

	public function __construct($state, $sorting)
	{
		$this->state = (int) $state;
		$this->_sorting_vals = array(
			0 => '',
			1 => 'real_name',
			2 => 'real_name',
			3 => 'last_login',
			4 => 'last_login',
		);
		$sorting = (int) $sorting;
		$this->sort_by = isset($this->_sorting_vals[$sorting]) ? $this->_sorting_vals[$sorting] : '';
		$this->sort_dir = in_array($sorting, array(1, 3)) ? 'asc' : 'desc';
	}

	public function populate()
	{
		global $txt;

		// Empty setting means off
		if ($this->state === 0)
			return;

		loadTemplate('OnlineToday');
		loadLanguage('OnlineToday');

		// Last 24 hours
		if ($this->state === 1)
		{
			$txt['onlinetoday_users_active'] = $txt['onlinetoday_users_active_day'];
			$this->_show_since = time() - 86400;
		}
		// Last week
		elseif ($this->state === 2)
		{
			$txt['onlinetoday_users_active'] = $txt['onlinetoday_users_active_week'];
			$this->_show_since = time() - 604800;
		}
		// Midnight
		elseif ($this->state === 3)
		{
			$txt['onlinetoday_users_active'] = $txt['onlinetoday_users_active_mid'];
			$this->_show_since = strtotime(date('Y-m-d'));
		}

		$this->_cansee_hidden = allowedTo('moderate_forum');

		$this->_getUsers();

		return $this->_prepareContext();
	}

	public function numHidden()
	{
		return $this->_num_hidden;
	}

	public function numBuddies()
	{
		return $this->_num_buddies;
	}

	public function setBuddies($buddies)
	{
		$this->_buddies = $buddies;
	}

	private function _prepareContext()
	{
		global $scripturl, $txt;

		$users = array();
		foreach ($this->_users_list as $user)
		{
			$link = '<a ' . (!empty($user['color']) ? 'style="color: ' . $user['color'] . '" ' : '') . 'href="' . $scripturl . '?action=profile;u=' . $user['id_member'] . '">' . $user['real_name'] . '</a>';

			if ($user['is_buddy'])
				$link = '<strong>' . $link . '</strong>';

			if (!$user['show_online'])
				$link = '<em>' . $link . '</em>';

			if ($this->sort_by === '')
				$users[] = $link;
			else
				$users[$user[$this->sort_by]] = $link;
		}
		if ($this->sort_dir === 'asc')
			ksort($users);
		else
			krsort($users);
		return $users;
	}

	private function _getUsers()
	{
		$db = database();

		$query = $db->query('', '
			SELECT m.id_member, m.real_name, m.show_online, m.id_group, m.additional_groups, m.last_login,
				mg.online_color as mg_color, pg.online_color as pg_color
			FROM {db_prefix}members AS m
				LEFT JOIN {db_prefix}membergroups AS mg ON (m.id_group = mg.id_group)
				LEFT JOIN {db_prefix}membergroups AS pg ON (m.id_post_group = pg.id_group)
			WHERE m.last_login > {int:lastlogin}',
			array(
				'lastlogin' => $this->_show_since,
			)
		);

		$this->_users_list = array();
		while ($row = $db->fetch_assoc($query))
		{
			$row['is_buddy'] = in_array($row['id_member'], $this->_buddies);
			$row['color'] = empty($row['mg_color']) ? $row['pg_color'] : $row['mg_color'];

			if (!empty($this->_buddies) && $row['is_buddy'])
				$this->_num_buddies++;

			if (empty($row['show_online']))
				$this->_num_hidden++;

			if (!empty($row['show_online']) || $this->_cansee_hidden)
				$this->_users_list[] = $row;
		}
		$db->free_result($query);
	}
}

class Online_Today_Integrate
{
	public static function get()
	{
		global $modSettings, $user_info, $context;

		// Empty setting means off
		if (empty($modSettings['onlinetoday']))
			return;

		loadTemplate('OnlineToday');
		loadLanguage('OnlineToday');
		$sort = !empty($modSettings['onlinetoday_sort']) ? $modSettings['onlinetoday_sort'] : 0;

		$context['info_center_callbacks'] = elk_array_insert($context['info_center_callbacks'], 'show_users', array('onlinetoday'), 'after', false);

		$instance = new Online_Today($modSettings['onlinetoday'], $sort);

		if (!empty($user_info['buddies']))
			$instance->setBuddies($user_info['buddies']);

		$context['onlinetoday'] = $instance->populate();
		$context['num_onlinetoday'] = count($context['onlinetoday']);
		$context['num_onlinetoday_buddies'] = $instance->numBuddies();
		$context['num_users_hidden'] = $instance->numHidden();


	}

	public static function settings(&$config_vars)
	{
		global $txt;

		loadLanguage('OnlineToday');
		$config_vars[] = array(
			'select',
			'onlinetoday',
			array(
				$txt['onlinetoday_off'],
				$txt['onlinetoday_day'],
				$txt['onlinetoday_week'],
				$txt['onlinetoday_mid'],
			)
		);
		$config_vars[] = array(
			'select',
			'onlinetoday_sort',
			array(
				$txt['onlinetoday_sort_no'],
				$txt['onlinetoday_sort_name_asc'],
				$txt['onlinetoday_sort_name_desc'],
				$txt['onlinetoday_sort_lastseen_asc'],
				$txt['onlinetoday_sort_lastseen_desc'],
			)
		);
	}
}
