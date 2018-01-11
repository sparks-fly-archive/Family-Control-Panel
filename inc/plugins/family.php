<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.");
}

$plugins->add_hook("member_profile_end", "family_member_profile_end");
$plugins->add_hook("showthread_start", "family_showthread_start");

function family_info()
{
	return array(
		"name"			=> "Family Control Panel",
		"description"	=> "Durch diese Erweiterung können Mitglieder umfangreiche Einstellungen im Hinblick auf die Familien ihrer Charaktere vornehmen. Familien können erstellt und individuell gestaltet werden; zudem entsteht eine Datenbank zum Durchsuchen für Mitglieder, Reservierungen durch Gäste und User + automatisches Generieren von Gesuchvorlagen.",
		"website"		=> "http://github.com/user/its-sparks-fly",
		"author"		=> "sparks fly",
		"authorsite"	=> "http://github.com/user/its-sparks-fly",
		"version"		=> "1.0",
		"compatibility" => "*"
	);
}

function family_install()
{
	global $db, $mybb;

	// database changes
	if(!$db->table_exists("families")) {
		$db->query("CREATE TABLE `mybb_families` (
  				`fid` int(11) NOT NULL AUTO_INCREMENT,
  				`uid` int(11) NOT NULL,
  				`lastname` text NOT NULL,
  				`description` text NOT NULL,
  				`class` text NOT NULL,
  				`founder` int(1) NOT NULL,
  				`tid` int(1) NOT NULL,
  				PRIMARY KEY (`fid`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");
	}

	if(!$db->table_exists("families_members")) {
		$db->query("CREATE TABLE `mybb_families_members` (
  				`fmid` int(11) NOT NULL AUTO_INCREMENT,
  				`fid` int(11) NOT NULL,
  				`uid` int(11) NOT NULL,
  				`fullname` text NOT NULL,
  				`gender` int(1) NOT NULL,
  				`generation` int(1) NOT NULL,
  				`position` text NOT NULL,
  				`age` int(3) NOT NULL,
 				`description` text NOT NULL,
 				`picture` text NOT NULL,
 				`playable` int(1) NOT NULL,
 				`claim_username` text NOT NULL,
 				`claim_timestamp` text NOT NULL,
  				PRIMARY KEY (`fmid`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");
	}
}

function family_is_installed()
{
    global $db;

    if($db->table_exists("families")) {
        return true;
    }
    return false;
}

function family_uninstall()
{
	global $db;

	if($db->table_exists("families")) {
		$db->query("DROP TABLE `mybb_families`");
	}

	if($db->table_exists("families_members")) {
		$db->query("DROP TABLE `mybb_families_members`");
	}

}

function family_activate()
{
	global $db, $mybb;

		// create settinggroup
	$setting_group = array(
    	'name' => 'familycp',
    	'title' => 'Family Control Panel',
    	'description' => 'Einstellungen für das Family Control Panel-Plugin.',
    	'disporder' => -1, // The order your setting group will display
    	'isdefault' => 0
	);

	// insert settinggroup into database
	$gid = $db->insert_query("settinggroups", $setting_group);

	// create settings
	$setting_array = array(
    	'familycp_username' => array(
        	'title' => 'Spitznamen-Profilfeld',
        	'description' => 'Hier die Field-ID des Spielernamen-Feld angeben.',
        	'optionscode' => 'text',
        	'value' => '', // Default
        	'disporder' => 1
    	),
    	'familycp_fid' => array(
        	'title' => 'Familiengesuche-Unterforum',
        	'description' => 'In welches Forum (Foren-ID angeben!) sollen die Familiengesuche gepostet werden?',
        	'optionscode' => 'text',
        	'value' => '', // Default
        	'disporder' => 2
    	),
	);

	// insert settings into database
	foreach($setting_array as $name => $setting)
	{
    	$setting['name'] = $name;
    	$setting['gid'] = $gid;

    	$db->insert_query('settings', $setting);
	}

	// Don't forget this!
	rebuild_settings();

}

function family_deactivate()
{

	global $db;

	// drop settings
	$db->delete_query('settings', "name LIKE '%familycp_%");
	$db->delete_query('settinggroups', "name = 'familycp'");

	// Don't forget this
	rebuild_settings();

}

function family_member_profile_end()
{
	global $mybb, $db, $templates, $lang, $memprofile, $member_profile_family;
	$lang->load("family");
	$uid = $mybb->user['uid'];

	// decide which family is the user's family => default: where user is member. if user as created own family, this one will be his/her main family!
	$query = $db->query("SELECT fid FROM ".TABLE_PREFIX."families WHERE uid = '$memprofile[uid]'");
	$fid = $db->fetch_field($query, "fid");
	if(empty($fid)) {
		$query = $db->query("SELECT fid FROM ".TABLE_PREFIX."families_members WHERE uid = '$memprofile[uid]'
			ORDER BY fmid DESC
			LIMIT 1");
		$fid = $db->fetch_field($query, "fid");
	}

	// get family matching fid
	$query = $db->query("SELECT * FROM ".TABLE_PREFIX."families
		WHERE fid = '$fid'");
	$family = $db->fetch_array($query);

	// only team and family's author can edit
	$edit_family = "";
	if($mybb->usergroup['cancp'] == "1" || $uid == $family['uid']) {
		$edit_family = "<div class=\"tcat edit-family\"><a href=\"family.php?action=editfamily&id={$family['fid']}\">{$lang->family_edit_text}</a></div>";
	}

	// get statistics / numbers
	$class = $family['class'];
	$classes = array("1" => "{$lang->family_class_under}", "2" => "{$lang->family_class_middle}", "3" => "{$lang->family_class_high}");
	$query = $db->query("SELECT COUNT(*) as members FROM ".TABLE_PREFIX."families_members 
		WHERE fid = '$fid'");
	$members = $db->fetch_field($query, "members");
	$query = $db->query("SELECT COUNT(*) as members FROM ".TABLE_PREFIX."families_members 
		WHERE fid = '$fid'
		AND playable = '1'");
	$members_playable = $db->fetch_field($query, "members");
	$query = $db->query("SELECT COUNT(*) as members FROM ".TABLE_PREFIX."families_members 
		WHERE fid = '$fid'
		AND playable = '1'
		AND uid = '0'");
	$members_free = $db->fetch_field($query, "members");
	$query = $db->query("SELECT COUNT(*) as members FROM ".TABLE_PREFIX."families_members 
		WHERE fid = '$fid'
		AND gender = '1'");
	$members_male = $db->fetch_field($query, "members");
	$query = $db->query("SELECT COUNT(*) as members FROM ".TABLE_PREFIX."families_members 
		WHERE fid = '$fid'
		AND gender = '2'");
	$members_female = $db->fetch_field($query, "members");
	$query = $db->query("SELECT COUNT(*) as members FROM ".TABLE_PREFIX."families_members 
		WHERE fid = '$fid'
		AND gender = '3'");
	$members_diff = $db->fetch_field($query, "members");

	// each generation gets it's own section ;) 
	$generations = $db->query("SELECT DISTINCT generation FROM ".TABLE_PREFIX."families_members
		WHERE fid = '$fid'
		ORDER BY generation ASC");
	while($generation = $db->fetch_array($generations)) {

		// get family members matching selected generation
		$fammember_bit = "";
		$i = "";
		$query = $db->query("SELECT * FROM ".TABLE_PREFIX."families_members
			WHERE fid = '$fid'
			AND generation = '$generation[generation]'
			ORDER BY CAST(age AS signed) DESC, fullname ASC");
		$membersnum = mysqli_num_rows($query);
		$counter = "";
		while($fammember = $db->fetch_array($query)) {
			
			// generate new table row for each 4 family members
			$counter++;
			$open_tablerow = "";
			$close_tablerow = "";
			if(empty($i)) {
				$open_tablerow = "<tr>";
			}
			$i++;
			if($i % 4 == "0" || $counter == $membersnum) {
				$close_tablerow = "</tr>";
				$i = "";
			}

			$fammember['claimed'] = "";
			$famuser = "";

			// check if already registered...
			$famuser = get_user($fammember['uid']);

			if($fammember['gender'] == "1") {
				$gender_border = "#BCD8E6";
			} elseif($fammember['gender'] == "2") {
				$gender_border = "#DFBCE6";
			}
			else {
				$gender_border = "#E6CABC";
			}

			// not registered yet?
			if($famuser['uid'] == "0" OR empty($famuser)) {
				// generate picture from database url (if set), otherwise use set default image
				$img_class = "free-img";
				if(empty($fammember['picture'])) {
					$fammember['img'] = "<div class=\"{$img_class}\" style=\"background: {$gender_border}\"><a href=\"#description{$fammember['fmid']}\"><img src=\"images/dark/noava.png\" class=\"family-picture\" /></a></div>";	
				}
				else {
					$fammember['img'] = "<div class=\"{$img_class}\" style=\"background: {$gender_border}\"><a href=\"#description{$fammember['fmid']}\"><img src=\"{$fammember['picture']}\" class=\" family-picture\" /></a></div>";
				}

				// check if family member is already claimed
				if(empty($fammember['claim_username']) && $fammember['playable'] == "1") {
					// guests can't take a family member
					if(!empty($uid)) {
						eval("\$fammember['claimed'] = \"".$templates->get("family_claim")."\";");
					}
					else { 
						eval("\$fammember['claimed'] = \"".$templates->get("family_claim_guest")."\";");
					}
				}
				elseif($fammember['playable'] == "0") {
					eval("\$fammember['claimed'] = \"".$templates->get("family_claim_unplayable")."\";");	
				}
				elseif(!empty($fammember['claim_username'])) {
					$field_username = $mybb->settings['familycp_username'];
					$username = $mybb->user['fid'.$field_username];
					$fammember['take'] = "";
					if($username == $fammember['claim_username'] && !empty($uid)) {
						eval("\$fammember['take'] = \"".$templates->get("family_claimed_take")."\";");
					}
					eval("\$fammember['claimed'] = \"".$templates->get("family_claimed")."\";");	
				}
			}
			else {
				// ignore family member's name and username and get matching user's names! 
				$fammember['fullname'] = build_profile_link($famuser['username'], $famuser['uid']);
				// generate picture from user avatar
				$img_class ="taken-img";
				$fammember['img'] = "<a href=\"member.php?action=profile&uid={$fammember['uid']}\"><img src=\"{$famuser['avatar']}\" class=\"family-picture\" /></a></div>";
				$fammember['take'] = "";
				eval("\$fammember['claimed'] .= \"".$templates->get("family_claimed")."\";");
			}

			// only team and family's author can edit
			$edit_fammember = "";
			if($mybb->usergroup['cancp'] == "1" || $uid == $family['uid']) {
				$edit_fammember = "<div class=\"thead edit-fammember\" style=\"margin-bottom: 0px;\"><a href=\"family.php?action=editmember&id={$fammember['fmid']}\">{$lang->family_edit_text}</a>";

				// author can delete claims
				if(($fammember['uid'] != "0" || $fammember['claim_username'] != "") && $fammember['uid'] != $uid) {
					 $edit_fammember .= "<a href=\"family.php?action=free&id={$fammember['fmid']}\">{$lang->family_set_free}</a>";
				}
				$edit_fammember .= "</div>";
			}
			eval("\$fammember_bit .= \"".$templates->get("family_view_generations_member")."\";");
		}

		// generation seperator
		eval("\$generations_bit .= \"".$templates->get("family_view_generations")."\";");
	}

	// set template
	$member_profile_family = "";
	if(!empty($fid)) {
		eval("\$member_profile_family = \"".$templates->get("member_profile_family")."\";");
	}
}

function family_showthread_start()
{

	global $db, $mybb, $lang, $templates, $thread, $showthread_family;
	$lang->load("family");
	$family_fid = $mybb->settings['familycp_fid'];
	$uid = $mybb->user['uid'];
	if($thread['fid'] == $family_fid) {
		// get family matching thread tid
		$query = $db->query("SELECT * FROM ".TABLE_PREFIX."families
			WHERE tid = '$thread[tid]'");
		$family = $db->fetch_array($query);
		$family_id = $family['fid'];
		
		// only team and family's author can edit
		$edit_family = "";
		if($mybb->usergroup['cancp'] == "1" || $uid == $family['uid']) {
			$edit_family = "<div class=\"tcat edit-family\"><a href=\"family.php?action=editfamily&id={$family['fid']}\">{$lang->family_edit_text}</a></div>";
		}

		// get statistics / numbers
		$class = $family['class'];
		$classes = array("1" => "{$lang->family_class_under}", "2" => "{$lang->family_class_middle}", "3" => "{$lang->family_class_high}");
		$query = $db->query("SELECT COUNT(*) as members FROM ".TABLE_PREFIX."families_members 
			WHERE fid = '$family_id'");
		$members = $db->fetch_field($query, "members");
		$query = $db->query("SELECT COUNT(*) as members FROM ".TABLE_PREFIX."families_members 
			WHERE fid = '$family_id'
			AND playable = '1'");
		$members_playable = $db->fetch_field($query, "members");
		$query = $db->query("SELECT COUNT(*) as members FROM ".TABLE_PREFIX."families_members 
			WHERE fid = '$family_id'
			AND playable = '1'
			AND uid = '0'");
		$members_free = $db->fetch_field($query, "members");
		$query = $db->query("SELECT COUNT(*) as members FROM ".TABLE_PREFIX."families_members 
			WHERE fid = '$family_id'
			AND gender = '1'");
		$members_male = $db->fetch_field($query, "members");
		$query = $db->query("SELECT COUNT(*) as members FROM ".TABLE_PREFIX."families_members 
			WHERE fid = '$family_id'
			AND gender = '2'");
		$members_female = $db->fetch_field($query, "members");
		$query = $db->query("SELECT COUNT(*) as members FROM ".TABLE_PREFIX."families_members 
			WHERE fid = '$family_id'
			AND gender = '3'");
		$members_diff = $db->fetch_field($query, "members");

		// each generation gets it's own section ;) 
			$generations = $db->query("SELECT DISTINCT generation FROM ".TABLE_PREFIX."families_members
				LEFT JOIN ".TABLE_PREFIX."families ON ".TABLE_PREFIX."families_members.fid = ".TABLE_PREFIX."families.fid
				WHERE tid = '$thread[tid]'
				ORDER BY generation ASC");
			while($generation = $db->fetch_array($generations)) {
			// get family members matching selected generation
			$fammember_bit = "";
			$i = "";
			$query = $db->query("SELECT * FROM ".TABLE_PREFIX."families_members
				WHERE fid = '$family_id'
				AND generation = '$generation[generation]'
				ORDER BY CAST(age AS signed) DESC, fullname ASC");
			$membersnum = mysqli_num_rows($query);
			$counter = "";
			while($fammember = $db->fetch_array($query)) {
				
				// generate new table row for each 4 family members
				$counter++;
				$open_tablerow = "";
				$close_tablerow = "";
				if(empty($i)) {
					$open_tablerow = "<tr>";
				}
				$i++;
				if($i % 4 == "0" || $counter == $membersnum) {
					$close_tablerow = "</tr>";
					$i = "";
				}

				$fammember['claimed'] = "";
				$famuser = "";

				// check if already registered...
				$famuser = get_user($fammember['uid']);

				if($fammember['gender'] == "1") {
					$gender_border = "#BCD8E6";
				} elseif($fammember['gender'] == "2") {
					$gender_border = "#DFBCE6";
				}
				else {
					$gender_border = "#E6CABC";
				}

				// not registered yet?
				if($famuser['uid'] == "0" OR empty($famuser)) {
					// generate picture from database url (if set), otherwise use set default image
					$img_class = "free-img";
					if(empty($fammember['picture'])) {
						$fammember['img'] = "<div class=\"{$img_class}\" style=\"background: {$gender_border}\"><a href=\"#description{$fammember['fmid']}\"><img src=\"images/dark/noava.png\" class=\"family-picture\" /></a></div>";	
					}
					else {
						$fammember['img'] = "<div class=\"{$img_class}\" style=\"background: {$gender_border}\"><a href=\"#description{$fammember['fmid']}\"><img src=\"{$fammember['picture']}\" class=\" family-picture\" /></a></div>";
					}

					// check if family member is already claimed
					if(empty($fammember['claim_username']) && $fammember['playable'] == "1") {
						// guests can't take a family member
						if(!empty($uid)) {
							eval("\$fammember['claimed'] = \"".$templates->get("family_claim")."\";");
						}
						else { 
							eval("\$fammember['claimed'] = \"".$templates->get("family_claim_guest")."\";");
						}
					}
					elseif($fammember['playable'] == "0") {
						eval("\$fammember['claimed'] = \"".$templates->get("family_claim_unplayable")."\";");	
					}
					elseif(!empty($fammember['claim_username'])) {
						$field_username = $mybb->settings['familycp_username'];
						$username = $mybb->user['fid'.$field_username];
						$fammember['take'] = "";
						if($username == $fammember['claim_username'] && !empty($uid)) {
							eval("\$fammember['take'] = \"".$templates->get("family_claimed_take")."\";");
						}
						eval("\$fammember['claimed'] = \"".$templates->get("family_claimed")."\";");	
					}
				}
				else {
					// ignore family member's name and username and get matching user's names! 
					$fammember['fullname'] = build_profile_link($famuser['username'], $famuser['uid']);
					// generate picture from user avatar
					$img_class ="taken-img";
					$fammember['img'] = "<a href=\"member.php?action=profile&uid={$fammember['uid']}\"><img src=\"{$famuser['avatar']}\" class=\"family-picture\" /></a></div>";
					$fammember['take'] = "";
					eval("\$fammember['claimed'] .= \"".$templates->get("family_claimed")."\";");
				}

				// only team and family's author can edit
				$edit_fammember = "";
				if($mybb->usergroup['cancp'] == "1" || $uid == $family['uid']) {
					$edit_fammember = "<div class=\"thead edit-fammember\" style=\"margin-bottom: 0px;\"><a href=\"family.php?action=editmember&id={$fammember['fmid']}\">{$lang->family_edit_text}</a>";

					// author can delete claims
					if(($fammember['uid'] != "0" || $fammember['claim_username'] != "") && $fammember['uid'] != $uid) {
						 $edit_fammember .= "<a href=\"family.php?action=free&id={$fammember['fmid']}\">{$lang->family_set_free}</a>";
					}
					$edit_fammember .= "</div>";
				}
				eval("\$fammember_bit .= \"".$templates->get("family_view_generations_member")."\";");
			}

			// generation seperator
			eval("\$generations_bit .= \"".$templates->get("family_view_generations")."\";");
		}

		// set template
		$showthread_family = "";
		eval("\$showthread_family = \"".$templates->get("showthread_family")."\";");
	}
}

?>