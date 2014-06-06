<?php

/**
 * Users online today
 *
 * @name      Users online today
 * @copyright Users online today contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.1
 *
 */

/**
 * Handles the visualization of members that have been on-line "today"
 *
 */
class OnlineToday
{
	private static $_instance = null;
	private $_show_since = 0;
	private $_buddies = array();
	private $_num_buddies = 0;
	private $_num_hidden = 0;
	private $_users_list = array();

	public function __construct()
	{
		global $modSettings, $context, $txt, $user_info;

		// Empty setting means off
		if (empty($modSettings['onlinetoday']))
			return;

		loadTemplate('OnlineToday');
		loadLanguage('OnlineToday');

		// Last 24 hours
		if ($modSettings['onlinetoday'] == 1)
		{
			$txt['onlinetoday_users_active'] = $txt['onlinetoday_users_active_day'];
			$this->_show_since = time() - 86400;
		}
		// Last week
		elseif ($modSettings['onlinetoday'] == 2)
		{
			$txt['onlinetoday_users_active'] = $txt['onlinetoday_users_active_week'];
			$this->_show_since = time() - 604800;
		}
		// Midnight
		else
		{
			$txt['onlinetoday_users_active'] = $txt['onlinetoday_users_active_mid'];
			$this->_show_since = time() - strtotime(date('Y-m-d'));
		}

		$this->_buddies = $user_info['buddies'];

		$context['info_center_callbacks'] = elk_array_insert($context['info_center_callbacks'], 'show_users', array('onlinetoday'), 'after', false);
		$this->_cansee_hidden = allowedTo('moderate_forum');

		$this->_getUsers();
		$context['onlinetoday'] = $this->_prepareContext();
		$context['num_onlinetoday'] = count($context['onlinetoday']);
		$context['num_onlinetoday_buddies'] = $this->_num_buddies;
		$context['num_users_hidden'] = $this->_num_hidden;
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

			$users[] = $link;
		}
		return $users;
	}

	private function _getUsers()
	{
		$db = database();

		$query = $db->query('', '
			SELECT m.id_member, m.real_name, m.show_online, m.id_group, m.additional_groups,
				mg.online_color as mg_color, pg.online_color as pg_color
			FROM {db_prefix}members AS m
				LEFT JOIN {db_prefix}membergroups AS mg ON (m.id_group = mg.id_group)
				LEFT JOIN {db_prefix}membergroups AS pg ON (m.id_post_group = pg.id_group)
			WHERE last_login > {int:lastlogin}',
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

	public static function get()
	{
		if (self::$_instance === null)
			self::$_instance = new OnlineToday();

		return self::$_instance;
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
			)
		);
	}
}