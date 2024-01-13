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
 * Show the online users online in the last 24 hours of week in the info center
 */
function template_ic_onlinetoday()
{
	global $context, $txt;

	// "Users online" - in order of activity.
	echo '
			<li class="board_row">
				<h3 class="ic_section_header">
					<i class="icon i-users"></i>', $txt['onlinetoday'], '
					', comma_format($context['num_onlinetoday']), ' ', $context['num_onlinetoday'] == 1 ? $txt['user'] : $txt['users'];

	// Handle hidden users and buddies.
	$bracketList = array();
	if ($context['show_buddies'])
	{
		$bracketList[] = comma_format($context['num_onlinetoday_buddies']) . ' ' . ($context['num_onlinetoday_buddies'] == 1 ? $txt['buddy'] : $txt['buddies']);
	}

	if (!empty($context['num_users_hidden']))
	{
		$bracketList[] = comma_format($context['num_users_hidden']) . ' ' . ($context['num_spiders'] == 1 ? $txt['hidden'] : $txt['hidden_s']);
	}

	if (!empty($bracketList))
	{
		echo ' (' . implode(', ', $bracketList) . ')';
	}

	echo '
				</h3>
				<p class="inline">', $txt['onlinetoday_users_active'], ' ', implode(', ', $context['onlinetoday']), '</p>';

	// Showing membergroups?
	if (!empty($context['membergroups']))
	{
		echo '
			<p class="inline membergroups">[' . implode(',&nbsp;', $context['membergroups']) . ']</p>';
	}

	echo '
			</li>';
}
