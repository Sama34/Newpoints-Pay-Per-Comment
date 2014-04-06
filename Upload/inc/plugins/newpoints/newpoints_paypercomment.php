<?php

/***************************************************************************
 *
 *   Newpoints PayPerComment plugin (/inc/plugins/newpoints/newpoints_paypercomment.php)
 *	 Author: Sama34 (Omar G.)
 *   
 *   Website: http://udezain.com.ar
 *
 *   Allows administrators and global moderator to edit points without accessing the ACP.
 *
 ***************************************************************************/

/****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// Add the hooks we are going to use.
if(!defined("IN_ADMINCP"))
{
	$plugins->add_hook('global_end', 'newpoints_pm_payoff');
}

/*** Newpoints ACP side. ***/
function newpoints_paypercomment_info()
{
	global $mybb, $lang;
	newpoints_lang_load("newpoints_paypercomment");
	if($mybb->input['module'] == 'newpoints-plugins')
	{
		$desc = $lang->paypercomment_acp_desc;
	}
	else
	{
		$desc = $lang->paypercomment_acp_desc_short;
	}

	return array(
		'name'			=> $lang->paypercomment_acp_title,
		'description'	=> $desc,
		'website'		=> 'http://udezain.com.ar',
		'author'		=> 'Omar G.',
		'authorsite'	=> 'http://udezain.com.ar',
		'version'		=> '1.0',
		'compatibility'	=> '16*',
		'codename'		=> 'paypercomment',
	);
}
function newpoints_paypercomment_activate()
{
	global $mybb, $lang;
	newpoints_lang_load("newpoints_paypercomment");

	// Add the requered settings.
	newpoints_add_setting("newpoints_paypercomment_on", "newpoints_paypercomment", $lang->paypercomment_acp_on_n, $lang->paypercomment_acp_on_d, "yesno", "0", "1");
	newpoints_add_setting("newpoints_paypercomment_points", "newpoints_paypercomment", $lang->paypercomment_acp_points_n, $lang->paypercomment_acp_points_d, "text", "5", "2");
	newpoints_add_setting("newpoints_paypercomment_self", "newpoints_paypercomment", $lang->paypercomment_acp_self_n, $lang->paypercomment_acp_self_d, "yesno", "1", "3");
	rebuild_settings();
}
function newpoints_paypercomment_deactivate()
{
	global $mybb;

	// Remove the plugin settings.
	newpoints_remove_settings("'newpoints_paypercomment_on', 'newpoints_paypercomment_points', 'newpoints_paypercomment_self'");
	rebuild_settings();

	// Clean any logs from this plugin.
	newpoints_remove_log(array("comment"));
}

/*** Forum side. ***/
function newpoints_pm_payoff()
{
	global $mybb, $current_page, $lang, $db;
	newpoints_lang_load("newpoints_paypercomment");

	if($current_page)
	{
		$script = $current_page;
	}
	elseif(THIS_SCRIPT)
	{
		$script = THIS_SCRIPT;
	}
	else
	{
		$script = basename(trim($_SERVER['SCRIPT_NAME']));
	}
	$puser = get_user(intval($mybb->input['uid']));

	if($mybb->settings['newpoints_paypercomment_self'] != 0)
	{
		if($script == "member.php" && $mybb->input['action'] == 'profile' && $mybb->input['op'] == 'new' && $mybb->request_method == 'post' && $mybb->usergroup['cansendcomments'] == '1' && $puser['uid'] == $mybb->user['uid'] && $mybb->input['message'])
		{
			error($lang->paypercomment_error_self_profile);
		}
	}
	if($mybb->settings['newpoints_paypercomment_on'] != 0)
	{

		if($script == "member.php" && $mybb->input['action'] == 'profile' && $mybb->input['op'] == 'new' && $mybb->request_method == 'post' && $mybb->usergroup['cansendcomments'] == '1' && $puser['uid'] != $mybb->user['uid'])
		{
			$points = floatval(intval($mybb->settings['newpoints_paypercomment_points']));
			if($mybb->user['newpoints'] < $points)
			{
				$points = newpoints_format_points($points);
				$mybb->user['newpoints'] = newpoints_format_points($mybb->user['newpoints']);
				error($lang->sprintf($lang->paypercomment_error_no_points, $points, $mybb->user['newpoints']));
			}
			else
			{
				newpoints_addpoints($mybb->user['uid'], -$points);
				$username = $lang->sprintf($lang->paypercomment_logging, $puser['username']);
				newpoints_log("comment", $username, $mybb->user['username'], $mybb->user['uid']);
			}
		}
	}
}
?>