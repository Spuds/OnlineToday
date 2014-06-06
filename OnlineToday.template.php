<?php

/**
 * Users online today
 *
 * @name      Users online today
 * @copyright Users online today contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * This software is a derived product, based on:
 *
 * ElkArte Forum
 * copyright: ElkArte Forum contributors
 * license:   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * Simple Machines Forum (SMF)
 * copyright:	2011 Simple Machines (http://www.simplemachines.org)
 * license:  	BSD, See included LICENSE.TXT for terms and conditions.
 *
 * @version 0.1
 *
 */

/**
 * Show the online users online in the last 24 hours of week in the info center
 */
function template_ic_onlinetoday()
{
	global $context, $txt, $scripturl, $settings, $modSettings;

	// "Users online" - in order of activity.
	echo '
			<li class="board_row">
				<h3 class="ic_section_header">
					<img class="icon" src="', $settings['images_url'], '/icons/online.png', '" alt="" />', $txt['onlinetoday'], '
					', comma_format($context['num_onlinetoday']), ' ', $context['num_onlinetoday'] == 1 ? $txt['user'] : $txt['users'];

	// Handle hidden users and buddies.
	$bracketList = array();
	if ($context['show_buddies'])
		$bracketList[] = comma_format($context['num_onlinetoday_buddies']) . ' ' . ($context['num_onlinetoday_buddies'] == 1 ? $txt['buddy'] : $txt['buddies']);

	if (!empty($context['num_users_hidden']))
		$bracketList[] = comma_format($context['num_users_hidden']) . ' ' . ($context['num_spiders'] == 1 ? $txt['hidden'] : $txt['hidden_s']);

	if (!empty($bracketList))
		echo ' (' . implode(', ', $bracketList) . ')';

	echo '
				</h3>';

	// Assuming there ARE users online... each user in users_online has an id, username, name, group, href, and link.
	if (!empty($context['onlinetoday']))
	{
		echo '
				<p class="inline">', $txt['onlinetoday_users_active'], ' ', implode(', ', $context['onlinetoday']), '</p>';
	}
	echo '
			</li>';
}