<?php

/**
 * Users online today
 *
 * @name      Users online today
 * @copyright Users online today contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.2.2
 *
 */

/**
 * Handles the visualization of members that have been on-line "today"
 */
class Online_Today
{
	private $_show_since = 0;
	private $sort_by;
	private $sort_dir;
	private $state;
	private $_cansee_hidden;
	private $_buddies = array();
	private $_num_buddies = 0;
	private $_num_hidden = 0;
	private $_users_list = array();

	public function __construct($state, $sorting)
	{
		$this->state = (int) $state;
		$sorting_vals = array(
			0 => '',
			1 => 'real_name',
			2 => 'real_name',
			3 => 'last_login',
			4 => 'last_login',
		);
		$sorting = (int) $sorting;
		$this->sort_by = isset($sorting_vals[$sorting]) ? $sorting_vals[$sorting] : '';
		$this->sort_dir = in_array($sorting, array(1, 3)) ? 'asc' : 'desc';
	}

	public function populate()
	{
		global $txt;

		// Empty setting means off
		if ($this->state === 0)
		{
			return;
		}

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
		global $scripturl;

		$users = array();
		foreach ($this->_users_list as $user)
		{
			$title = !empty($user['last_login']) ? 'title="' . strip_tags(standardTime($user['last_login'])) . '" ' : '';
			$color = !empty($user['color']) ? 'style="color: ' . $user['color'] . '" ' : '';
			$link = '<a ' . $title . $color . 'href="' . $scripturl . '?action=profile;u=' . $user['id_member'] . '">' . $user['real_name'] . '</a>';

			if ($user['is_buddy'])
			{
				$link = '<strong>' . $link . '</strong>';
			}

			if (!$user['show_online'])
			{
				$link = '<em>' . $link . '</em>';
			}

			if ($this->sort_by === '')
			{
				$users[] = $link;
			}
			else
			{
				$users[$user[$this->sort_by]] = $link;
			}
		}

		if ($this->sort_dir === 'asc')
		{
			ksort($users);
		}
		else
		{
			krsort($users);
		}

		return $users;
	}

	private function _getUsers()
	{
		$db = database();

		$query = $db->query('', '
			SELECT 
				m.id_member, m.real_name, m.show_online, m.id_group,
				m.additional_groups, m.last_login,
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
			$row['is_buddy'] = in_array($row['id_member'], $this->_buddies, true);
			$row['color'] = empty($row['mg_color']) ? $row['pg_color'] : $row['mg_color'];

			if (!empty($this->_buddies) && $row['is_buddy'])
			{
				$this->_num_buddies++;
			}

			if (empty($row['show_online']))
			{
				$this->_num_hidden++;
			}

			if (!empty($row['show_online']) || $this->_cansee_hidden)
			{
				$this->_users_list[] = $row;
			}
		}
		$db->free_result($query);
	}

	/**
	 * This is a pain to do, we should add a the id_group to the array returned
	 * by cache_getMembergroupList
	 */
	public function sortMembergroups($groups, $sorting_order = '')
	{
		$key_groups = $this->extractKeys($groups);
		$sort_array = array_map('trim', explode(',', $sorting_order));

		$groups_tosort = array();
		$groups_unsorted = array();
		foreach ($key_groups as $key => $val)
		{
			if (in_array($key, $sort_array))
			{
				$groups_tosort[$key] = $val;
			}
			else
			{
				$groups_unsorted[$key] = $val;
			}
		}

		$groups_sorted = array();
		foreach ($sort_array as $key)
		{
			if (isset($groups_tosort[$key]))
			{
				$groups_sorted[] = $groups_tosort[$key];
			}
		}

		return array_merge($groups_sorted, $groups_unsorted);
	}

	protected function extractKeys($groups)
	{
		$key_group = array();
		$unsorted = array();

		foreach ($groups as $group_link)
		{
			$id = $this->extractGroupId($group_link);

			if ($id !== false)
			{
				$key_group[$id] = $group_link;
			}
			else
			{
				$unsorted[] = $group_link;
			}
		}

		foreach ($unsorted as $val)
		{
			$key_group[] = $val;
		}

		return $key_group;
	}

	protected function extractGroupId($link)
	{
		preg_match('~;group=(\d+)~', $link, $match);

		if (isset($match[1]))
		{
			return $match[1];
		}

		return false;
	}
}

class Online_Today_Integrate
{
	public static function get()
	{
		global $modSettings, $user_info, $context;

		// Empty setting means off
		if (empty($modSettings['onlinetoday']))
		{
			return;
		}

		$sort = !empty($modSettings['onlinetoday_sort']) ? $modSettings['onlinetoday_sort'] : 0;

		$instance = new Online_Today($modSettings['onlinetoday'], $sort);

		if (!empty($user_info['buddies']))
		{
			$instance->setBuddies($user_info['buddies']);
		}

		$context['onlinetoday'] = $instance->populate();

		// Well, at least one should always be online... I guess
		if (!empty($context['onlinetoday']))
		{
			loadTemplate('OnlineToday');
			loadLanguage('OnlineToday');

			$context['info_center_callbacks'] = elk_array_insert($context['info_center_callbacks'], 'show_users', array('onlinetoday'), 'after', false);
			$context['num_onlinetoday'] = count($context['onlinetoday']);
			$context['num_onlinetoday_buddies'] = $instance->numBuddies();
			$context['num_users_hidden'] = $instance->numHidden();

			if (!empty($modSettings['onlinetoday_sortgroups']) && !empty($context['membergroups']))
			{
				$context['membergroups'] = $instance->sortMembergroups($context['membergroups'], $modSettings['onlinetoday_sortgroups']);
			}
		}
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
		$config_vars[] = array(
			'text',
			'onlinetoday_sortgroups',
		);
	}
}
