<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.");
}

$plugins->add_hook("member_profile_end", "family_member_profile_end");
$plugins->add_hook("showthread_start", "family_showthread_start");
$plugins->add_hook("index_start", "family_index_start");
if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
	$plugins->add_hook("global_start", "family_alerts");
}

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
 				`wanted` text NOT NULL,
  				PRIMARY KEY (`fmid`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");
	}

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
      	'familycp_default_img' => array(
        	'title' => 'Standardbild für Familienmitglieder',
        	'description' => 'Volle URL zum Standardbild für freie Familienmitglieder',
        	'optionscode' => 'text',
        	'value' => '', // Default
        	'disporder' => 3
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

	// myalerts integration
	if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
    	$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

    	if (!$alertTypeManager) {
    	    $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
    	}

   	 	 $alertType = new MybbStuff_MyAlerts_Entity_AlertType();
   		 $alertType->setCode('claimed');
   		 $alertType->setEnabled(true);
   		 $alertType->setCanBeUserDisabled(true);

   		 $alertTypeManager->add($alertType);
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

	// drop settings
	$db->delete_query('settings', "name LIKE '%familycp_%'");
	$db->delete_query('settinggroups', "name = 'familycp'");

	// delete alert types
    if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
        $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

        if (!$alertTypeManager) {
            $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
        }

        $alertTypeManager->deleteByCode('claimed');
    } 

}

function family_activate()
{
	global $db, $mybb;

	// create templates
	  $insert_array = array(
		'title'		=> 'family',
		'template'	=> $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->family}</title>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
<td width="23%" valign="top">
{$family_nav}
</td>
<td valign="top">
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="{$colspan}"><strong>{$lang->family}</strong></td>
</tr>
<tr>
<td class="trow2" style="padding: 10px; text-align: justify;">
<div style="width: 95%; margin: auto; padding: 8px;  font-size: 12px; line-height: 1.5em;" class="trow1">
 {$lang->family_welcome}
</div>
</td>
</tr>
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	  $insert_array = array(
		'title'		=> 'family_addfamily',
		'template'	=> $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->family}</title>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
<td width="23%" valign="top">
{$family_nav}
</td>
<td valign="top">
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="{$colspan}"><strong>{$lang->family}</strong></td>
</tr>
<tr>
<td class="trow2" style="padding: 10px; text-align: justify;">
<div style="width: 95%; margin: auto; padding: 8px;  font-size: 12px; line-height: 1.5em;" class="trow1">
 {$lang->family_add_family_desc}<br /><br />
		<form method="post" action="family.php" id="addfamily">
	<table cellspacing="3" cellpadding="3" class="tborder" style="width: 90%";>
		<tr>
			<td class="tcat" colspan="2">
				{$lang->family_add_family}
			</td>
		</tr>
		<tr>
			<td class="trow1">
				<strong>{$lang->family_lastname}:</strong>
			</td>
			<td class="trow1">
				<input type="text" class="textbox" name="lastname" id="lastname" size="40" maxlength="1155" style="width: 340px;" />
			</td>
		</tr>
		<tr>
			<td class="trow2">
				<strong>{$lang->family_class}:</strong>
			</td>
			<td class="trow2">
					<select name="class">
						<option value="1">{$lang->family_class_under}</option>
						<option value="2">{$lang->family_class_middle}</option>
						<option value="3">{$lang->family_class_high}</option>
					</select> 
			</td>
		</tr>
		<tr>
			<td class="trow1">
				<strong>{$lang->family_founder}:</strong>
			</td>
			<td class="trow1">
					<select name="founder">
						<option value="0">{$lang->family_playable_no}</option>
						<option value="1">{$lang->family_playable_yes}</option>
					</select> 
			</td>
		</tr>
		<tr>
			<td class="trow2">
				<strong>{$lang->family_description}:</strong>
			</td>
			<td class="trow2">
				<textarea name="description" id="description" style="height: 100px; width: 340px;"></textarea>
			</td>
		</tr>
		<tr>
			<td class="trow1" colspan="2" align="center">
				<input type="hidden" name="action" value="do_addfamily" />
				<input type="submit" name="submit" id="submit" class="button" value="{$lang->family_add_family}" />
			</td>
		</tr>
	</table>
	</form>
	<br />
</div>
</td>
</tr>
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	  $insert_array = array(
		'title'		=> 'family_addmember',
		'template'	=> $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->family_add_member}</title>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
<td width="23%" valign="top">
{$family_nav}
</td>
<td valign="top">
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="{$colspan}"><strong>{$lang->family_add_member}</strong></td>
</tr>
<tr>
<td class="trow2" style="padding: 10px; text-align: justify;">
<div style="width: 95%; margin: auto; padding: 8px;  font-size: 12px; line-height: 1.5em;" class="trow1">
 {$lang->family_add_family_desc}<br /><br />
		<form method="post" action="family.php" id="addfamily">
	<table cellspacing="3" cellpadding="3" class="tborder" style="width: 90%";>
		<tr>
			<td class="tcat" colspan="2">
				{$lang->family_add_member}
			</td>
		</tr>
		<tr>
			<td class="trow1">
				<strong>{$lang->family_fullname}:</strong>
			</td>
			<td class="trow1">
				{$fullname}
			</td>
		</tr>
		<tr>
			<td class="trow2">
				<strong>{$lang->family_gender}:</strong>
			</td>
			<td class="trow2">
					<select name="gender">
						<option value="1">{$lang->family_gender_male}</option>
						<option value="2">{$lang->family_gender_female}</option>
						<option value="3">{$lang->family_gender_diff}</option>
						<option value="4">{$lang->family_gender_indiff}</option>
					</select> 
			</td>
		</tr>
		<tr>
			<td class="trow1">
				<strong>{$lang->family_generation}:</strong>
			</td>
			<td class="trow1">
				<input type="text" class="textbox" name="generation" id="generation" size="40" maxlength="1155" style="width: 20px;" />
			</td>
		</tr>
		<tr>
			<td class="trow2">
				<strong>{$lang->family_position}:</strong>
			</td>
			<td class="trow2">
				<input type="text" class="textbox" name="position" id="position" size="40" maxlength="1155" style="width: 340px;" />
			</td>
		</tr>
		<tr>
			<td class="trow1">
				<strong>{$lang->family_age}:</strong>
			</td>
			<td class="trow1">
				<input type="text" class="textbox" name="age" id="age" size="40" maxlength="1155" style="width: 20px;" />
			</td>
		</tr>
		<tr>
			<td class="trow1">
				<strong>{$lang->family_description}:</strong>
			</td>
			<td class="trow1">
				<textarea name="description" id="description" style="height: 100px; width: 340px;"></textarea>
			</td>
		</tr>
		<tr>
			<td class="trow2">
				<strong>{$lang->family_picture}:</strong>
			</td>
			<td class="trow2">
				<input type="text" class="textbox" name="picture" id="picture" size="40" maxlength="1155" style="width: 340px;" />
			</td>
		</tr>
		<tr>
			<td class="trow2">
				<strong>{$lang->family_wanted_link}:</strong>
			</td>
			<td class="trow2">
				<input type="text" class="textbox" name="wanted" id="wanted" size="40" maxlength="1155" style="width: 340px;" />
			</td>
		</tr>
		<tr>
			<td class="trow1">
				<strong>{$lang->family_playable}</strong>
			</td>
			<td class="trow2">
					<select name="playable">
						<option value="1">{$lang->family_playable_yes}</option>
						<option value="0">{$lang->family_playable_no}</option>
					</select> 
			</td>
		</tr>
		<tr>
			<td class="trow1" colspan="2" align="center">
				<input type="hidden" name="action" value="do_addmember" />
				<input type="submit" name="submit" id="submit" class="button" value="{$lang->family_add_member}" />
			</td>
		</tr>
	</table>
	</form>
	<br />
</div>
</td>
</tr>
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	  $insert_array = array(
		'title'		=> 'family_claim',
		'template'	=> $db->escape_string('<div class="claim-fammember trow1"> <a href="family.php?action=claim&id={$fammember[\'fmid\']}">{$lang->family_info_free}</a><br /><a href="family.php?action=take&id={$fammember[\'fmid\']}">{$lang->family_info_take}</a></div>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	  $insert_array = array(
		'title'		=> 'family_claimed',
		'template'	=> $db->escape_string('<div class="claim-fammember trow1"> {$lang->family_info_claimed} {$fammember[\'claim_username\']} <br />{$fammember[\'claim_timestamp\']} {$fammember[\'take\']}</div>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	  $insert_array = array(
		'title'		=> 'family_claimed_take',
		'template'	=> $db->escape_string('<br /><a href="family.php?action=take&id={$fammember[\'fmid\']}">{$lang->family_info_take}</a>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	  $insert_array = array(
		'title'		=> 'family_claim_guest',
		'template'	=> $db->escape_string('<div class="claim-fammember trow1">
	<form action="family.php" method="post" id="claim_guest">
		<input type="hidden" name="action" id="action" value="claim" />
		<input type="hidden" name="id" id="id" value="{$fammember[\'fmid\']}" />
		<input type="text" name="guest" id="guest" style="width: 45px !important; height: 7px !important; font-size: 8px;" value="Spitzname" />
		<input type="submit" id="submit" name="submit" value="{$lang->family_claim}" style="width: 60px !important; font-size: 8px;" />
	</form>
</div>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	  $insert_array = array(
		'title'		=> 'family_claim_unplayable',
		'template'	=> $db->escape_string('<div class="claim-fammember trow1"> {$lang->family_info_unplayable}</div>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	  $insert_array = array(
		'title'		=> 'family_editfamily',
		'template'	=> $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->family}</title>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
<td width="23%" valign="top">
{$family_nav}
</td>
<td valign="top">
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="{$colspan}"><strong>{$lang->family}</strong></td>
</tr>
<tr>
<td class="trow2" style="padding: 10px; text-align: justify;">
<div style="width: 95%; margin: auto; padding: 8px;  font-size: 12px; line-height: 1.5em;" class="trow1"><br />
		<form method="post" action="family.php" id="addfamily">
	<table cellspacing="3" cellpadding="3" class="tborder" style="width: 90%";>
		<tr>
			<td class="tcat" colspan="2">
				{$lang->family_edit}
			</td>
		</tr>
		<tr>
			<td class="trow1">
				<strong>{$lang->family_lastname}:</strong>
			</td>
			<td class="trow1">
				<input type="text" class="textbox" name="lastname" id="lastname" size="40" maxlength="1155" style="width: 340px;" value="{$family[\'lastname\']}" />
			</td>
		</tr>
		<tr>
			<td class="trow2">
				<strong>{$lang->family_class}:</strong>
			</td>
			<td class="trow2">
					<select name="class">
						{$class_bit}
					</select> 
			</td>
		</tr>
		<tr>
			<td class="trow2">
				<strong>{$lang->family_founder}:</strong>
			</td>
			<td class="trow2">
					<select name="founder">
						{$founder_bit}
					</select> 
			</td>
		</tr>
		<tr>
			<td class="trow1">
				<strong>{$lang->family_description}:</strong>
			</td>
			<td class="trow1">
				<textarea name="description" id="description" style="height: 100px; width: 340px;">{$family[\'description\']}</textarea>
			</td>
		</tr>
		<tr>
			<td class="trow1" colspan="2" align="center">
				<input type="hidden" name="action" value="do_editfamily" />
				<input type="hidden" name="fid" value="{$fid}" />
				<input type="submit" name="submit" id="submit" class="button" value="{$lang->family_add_family}" />
			</td>
		</tr>
	</table>
	</form>
	<br />
</div>
</td>
</tr>
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	  $insert_array = array(
		'title'		=> 'family_editmember',
		'template'	=> $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->family_add_member}</title>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
<td width="23%" valign="top">
{$family_nav}
</td>
<td valign="top">
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="{$colspan}"><strong>{$lang->family_add_member}</strong></td>
</tr>
<tr>
<td class="trow2" style="padding: 10px; text-align: justify;">
<div style="width: 95%; margin: auto; padding: 8px;  font-size: 12px; line-height: 1.5em;" class="trow1">
 {$lang->family_add_family_desc}<br /><br />
		<form method="post" action="family.php" id="editfamily">
	<table cellspacing="3" cellpadding="3" class="tborder" style="width: 90%";>
		<tr>
			<td class="tcat" colspan="2">
				{$lang->family_add_member}
			</td>
		</tr>
		<tr>
			<td class="trow1">
				<strong>{$lang->family_fullname}:</strong>
			</td>
			<td class="trow1">
				<input type="text" class="textbox" name="fullname" id="fullname" size="40" maxlength="1155" style="width: 340px;" value="{$fammember[\'fullname\']}" />
			</td>
		</tr>
		<tr>
			<td class="trow2">
				<strong>{$lang->family_gender}:</strong>
			</td>
			<td class="trow2">
					<select name="gender">
						{$gender_bit}
					</select> 
			</td>
		</tr>
		<tr>
			<td class="trow1">
				<strong>{$lang->family_generation}:</strong>
			</td>
			<td class="trow1">
				<input type="text" class="textbox" name="generation" id="generation" size="40" maxlength="1155" style="width: 20px;" value="{$fammember[\'generation\']}" />
			</td>
		</tr>
		<tr>
			<td class="trow2">
				<strong>{$lang->family_position}:</strong>
			</td>
			<td class="trow2">
				<input type="text" class="textbox" name="position" id="position" size="40" maxlength="1155" style="width: 340px;" value="{$fammember[\'position\']}" />
			</td>
		</tr>
		<tr>
			<td class="trow1">
				<strong>{$lang->family_age}:</strong>
			</td>
			<td class="trow1">
				<input type="text" class="textbox" name="age" id="age" size="40" maxlength="1155" style="width: 20px;" value="{$fammember[\'age\']}" />
			</td>
		</tr>
		<tr>
			<td class="trow1">
				<strong>{$lang->family_description}:</strong>
			</td>
			<td class="trow1">
				<textarea name="description" id="description" style="height: 100px; width: 340px;">{$fammember[\'description\']}"</textarea>
			</td>
		</tr>
		<tr>
			<td class="trow2">
				<strong>{$lang->family_picture}:</strong>
			</td>
			<td class="trow2">
				<input type="text" class="textbox" name="picture" id="picture" size="40" maxlength="1155" style="width: 340px;" value="{$fammember[\'picture\']}" />
			</td>
		</tr>
		<tr>
			<td class="trow2">
				<strong>{$lang->family_wanted_link}:</strong>
			</td>
			<td class="trow2">
				<input type="text" class="textbox" name="wanted" id="wanted" size="40" maxlength="1155" style="width: 340px;" value="{$fammember[\'pwanted\']}" />
			</td>
		</tr>
		<tr>
			<td class="trow1">
				<strong>{$lang->family_playable}</strong>
			</td>
			<td class="trow2">
					<select name="playable">
						{$playable_bit}
					</select> 
			</td>
		</tr>
		<tr>
			<td class="trow1" colspan="2" align="center">
				<input type="hidden" name="fmid" value="{$fmid}" />
				<input type="hidden" name="fid" value="{$fid}" />
				<input type="hidden" name="action" value="do_editmember" />
				<input type="submit" name="submit" id="submit" class="button" value="{$lang->family_add_member}" />
			</td>
		</tr>
	</table>
	</form>
	<br />
</div>
</td>
</tr>
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	  $insert_array = array(
		'title'		=> 'family_filter_families',
		'template'	=> $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->family_filter_families}</title>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
<td width="23%" valign="top">
{$family_nav}
</td>
<td valign="top">
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="{$colspan}"><strong>{$lang->family_filter_families}</strong></td>
</tr>
<tr>
<td class="trow2" style="padding: 10px; text-align: justify;">
<div style="width: 95%; margin: auto; padding: 8px;  font-size: 12px; line-height: 1.5em;" class="trow1">
 		<form method="get" action="family.php" id="addfamily">
			<input type="hidden" name="action" value="filter_families" />
	<table cellspacing="3" cellpadding="3" class="tborder">
		<tr>
			<td class="tcat" colspan="2">
				{$lang->family_filter_families}
			</td>
		</tr>
		<tr>
			<td class="trow2">
				<strong>{$lang->family_class}:</strong>
			</td>
			<td class="trow2">
					<select name="class">
						<option value="">{$lang->family_class}</option>
						{$class_bit}
					</select> 
			</td>
		</tr>
		<tr>
			<td class="trow2">
				<strong>{$lang->family_founder}:</strong>
			</td>
			<td class="trow2">
					<select name="founder">
						{$founder_bit}
					</select> 
			</td>
		</tr>
		<tr>
			<td class="trow1" colspan="2" align="center">
				<input type="submit"  class="button" value="{$lang->family_filter_families}" />
			</td>
		</tr>
	</table>
	</form>
	<br />
	<table cellspacing="3" cellpadding="3" class="tborder smalltext">
		<tr>
			<td class="thead" colspan="6">
				{$familiescount} {$lang->family_families_found}
			</td>
		</tr>
		<tr>
			<td class="tcat">
				{$lang->family_lastname}
			</td>
			<td class="tcat">
				{$lang->family_class}
			</td>
			<td class="tcat">
				{$lang->family_gender_male}
			</td>
			<td class="tcat">
				{$lang->family_gender_female}
			</td>
			<td class="tcat">
				{$lang->family_gender_diff}
			</td>
			<td class="tcat">
				{$lang->family_playable}
			</td>
		</tr>
		{$family_bit}
	</table>
</div>
</td>
</tr>
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	  $insert_array = array(
		'title'		=> 'family_filter_families_bit',
		'template'	=> $db->escape_string('<tr>
	<td class="trow1" align="center">
		{$family[\'family_link\']}
	</td>
	<td class="trow1" align="center">
		{$classes[$famclass]}
	</td>
	<td class="trow1" align="center">
		{$members_male}
	</td>
	<td class="trow1" align="center">
		{$members_female}
	</td>
	<td class="trow1" align="center">
		{$members_diff}
	</td>
	<td class="trow1" align="center">
		{$members_free}
	</td>
</tr>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	  $insert_array = array(
		'title'		=> 'family_filter_members',
		'template'	=> $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->family_filter_member}</title>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
<td width="23%" valign="top">
{$family_nav}
</td>
<td valign="top">
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="{$colspan}"><strong>{$lang->family_filter_member}</strong></td>
</tr>
<tr>
<td class="trow2" style="padding: 10px; text-align: justify;">
<div style="width: 95%; margin: auto; padding: 8px;  font-size: 12px; line-height: 1.5em;" class="trow1">
 		<form method="get" action="family.php" id="filter_members">
			<input type="hidden" name="action" value="filter_member" />
	<table cellspacing="3" cellpadding="3" class="tborder">
		<tr>
			<td class="tcat" colspan="2">
				{$lang->family_filter_families}
			</td>
		</tr>
		<tr>
			<td class="trow2">
				<strong>{$lang->family_class}:</strong>
			</td>
			<td class="trow2">
					<select name="class">
						<option value="">{$lang->family_class}</option>
						{$class_bit}
					</select> 
			</td>
		</tr>
		<tr>
			<td class="trow2">
				<strong>{$lang->family_gender}:</strong>
			</td>
			<td class="trow2">
					<select name="gender">
						<option value="">{$lang->family_gender}</option>
						{$gender_bit}
					</select> 
			</td>
		</tr>
		<tr>
			<td class="trow2">
				<strong>{$lang->family_age}</strong>
			</td>
			<td class="trow2">
							{$lang->family_age_start} <input type="text" class="textbox" name="age_start" id="age_start" size="40" maxlength="1155" style="width: 20px;" value="{$age_start}" /> {$lang->family_age_end} <input type="text" class="textbox" name="age_end" id="age_end" size="40" maxlength="1155" style="width: 20px;" value="{$age_end}" /> {$lang->family_full_age}
			</td>
		</tr>
		<tr>
			<td class="trow1" colspan="2" align="center">
				<input type="submit"  class="button" value="{$lang->family_filter_member}" />
			</td>
		</tr>
	</table>
	</form>
	<br />
	<table cellspacing="3" cellpadding="3" class="tborder smalltext">
		<tr>
			<td class="thead" colspan="6">
				{$memberscount} {$lang->family_member_found}
			</td>
		</tr>
		<tr>
			<td class="tcat">
				{$lang->family_fullname}
			</td>
			<td class="tcat">
				{$lang->family_age}
			</td>
			<td class="tcat">
				{$lang->family_gender}
			</td>
			<td class="tcat">
				{$lang->family_claim}
			</td>
			<td class="tcat">
				{$lang->family_family}
			</td>
		</tr>
		{$members_bit}
	</table>
</div>
</td>
</tr>
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	  $insert_array = array(
		'title'		=> 'family_filter_members_bit',
		'template'	=> $db->escape_string('<tr>
	<td class="trow1" align="center">
		{$fammember[\'fullname\']}
	</td>
	<td class="trow1" align="center">
		{$fammember[\'age\']} {$lang->family_full_age}
	</td>
	<td class="trow1" align="center">
		{$genders[$famgender]}
	</td>
	<td class="trow1" align="center">
		{$lang->family_claim}
	</td>
		<td class="trow1" align="center">
		{$family[\'family_link\']}
	</td>
</tr>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	  $insert_array = array(
		'title'		=> 'family_navigation',
		'template'	=> $db->escape_string('<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tbody>
	<tr>
		<td class="thead"><strong>{$lang->family_navigation}</strong></td>
	</tr>
	<tr>
		<td class="trow2 smalltext"><a href="family.php">{$lang->family_start}</a></td>
	</tr>
	<tr>
		<td class="trow1 smalltext"><a href="family.php?action=faq">{$lang->family_faq}</a></td>
	</tr>
	<tr>
		<td class="trow2 smalltext"><a href="family.php?action=filter_families">{$lang->family_filter_families}</a></td>
	</tr>
	<tr>
		<td class="trow1 smalltext"><a href="family.php?action=filter_member">{$lang->family_filter_member}</a></td>
	</tr>
	{$family_nav_member}
</tbody>
</table>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	  $insert_array = array(
		'title'		=> 'family_navigation_member',
		'template'	=> $db->escape_string('	<tr>
		<td class="tcat"><strong>{$lang->family_control}</strong></td>
	</tr>

{$addfamily}'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	  $insert_array = array(
		'title'		=> 'family_view',
		'template'	=> $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->family}</title>
<script defer src="https://use.fontawesome.com/releases/v5.0.3/js/all.js"></script>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
<td width="23%" valign="top">
{$family_nav}
</td>
<td valign="top">
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="{$colspan}"><strong>{$lang->family}</strong></td>
</tr>
<tr>
<td class="trow2" style="padding: 10px; text-align: justify;">
<div style="width: 95%; margin: auto; padding: 8px;  font-size: 12px; line-height: 1.5em;" class="trow1">
	<center>
		<h1>
			{$lang->family_family} {$family[\'lastname\']}
		</h1>
	</center>
	<table cellspacing="5" cellpadding="5" class="tborder">
		<tr>
			<td class="thead" width="35%">{$lang->family_numbers}</td>
			<td class="thead">{$lang->family_description}</td>
		</tr>
		<tr>
			<td class="trow1" valign="top">
				<div class="family_numbers trow2 smalltext"><strong>{$classes[$class]}</strong></div>
				<div class="family_numbers trow2 smalltext"><strong>{$members}</strong> {$lang->family_members}</div>
				<div class="family_numbers trow2 smalltext"><strong>{$members_male}</strong> {$lang->family_gender_male}</div>
				<div class="family_numbers trow2 smalltext"><strong>{$members_female}</strong> {$lang->family_gender_female}</div>
				<div class="family_numbers trow2 smalltext"><strong>{$members_diff}</strong> {$lang->family_gender_diff}</div>
				<div class="family_numbers trow2 smalltext"><strong>{$members_playable}</strong> {$lang->family_playable}</div>
				<div class="family_numbers trow2 smalltext"><strong>{$members_free}</strong> {$lang->family_free}</div>
			</td>
			<td class="trow1" align="justify" valign="top">{$family[\'description\']}</td>
		</tr>
	</table>
	{$edit_family}
<br />
	{$generations_bit}
</div>
</td>
</tr>
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	  $insert_array = array(
		'title'		=> 'family_view_generations',
		'template'	=> $db->escape_string('<div class="thead">{$lang->family_generation} {$generation[\'generation\']}</div>
<table cellspacing="4" cellpadding="4" class="tborder">
{$fammember_bit}
</table>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	  $insert_array = array(
		'title'		=> 'family_view_generations_member',
		'template'	=> $db->escape_string('{$open_tablerow}
<td align="center" class="trow2">
	<div class="family-fullname tcat">{$fammember[\'fullname\']}</div>
	{$fammember[\'img\']}
	<div class="family-fullname tcat">{$fammember[\'position\']}</div>
	{$edit_fammember}
	{$fammember[\'claimed\']}
	{$fammember[\'wanted_link\']}
</td>
{$close_tablerow}

<div id="description{$fammember[\'fmid\']}" class="family-pop">
	<div class="pop trow2 smalltext"><div class="tcat">{$fammember[\'fullname\']} / <strong>{$fammember[\'age\']} {$lang->family_full_age}</strong></div>
		<div class="description">{$fammember[\'description\']}</div></div><a href="#closepop" class="closepop"></a>
</div>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	  $insert_array = array(
		'title'		=> 'index_family',
		'template'	=> $db->escape_string('<table class="tborder" cellspacing="5" cellpadding="5">
	<tr>
		<td class="thead" colspan="3">{$lang->family_claims}</td>
	</tr>
	<tr>
		<td class="tcat">{$lang->family_fullname}</td> 
		<td class="tcat">{$lang->family_family}</td> 
		<td class="tcat">{$lang->family_date}</td>
	</tr>
	{$index_family_bit}
</table>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	  $insert_array = array(
		'title'		=> 'index_family_bit',
		'template'	=> $db->escape_string('<tr>
	<td class="trow1">{$claim[\'fullname\']}</td>
	<td class="trow1">{$lang->family_family} {$claim[\'family_link\']}</td>
	<td class="trow1">{$claim[\'claim_timestamp\']}</td>
</tr>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'		=> 'member_profile_family',
		'template'	=> $db->escape_string('<script defer src="https://use.fontawesome.com/releases/v5.0.3/js/all.js"></script>
			<table class="tborder" cellspacing="5" cellpadding="5">
	<tr>
		<td class="trow2">
	<center>
		<h1>
			{$lang->family_family} {$family[\'lastname\']}
		</h1>
	</center>
	<table cellspacing="5" cellpadding="5" class="tborder">
		<tr>
			<td class="thead" width="35%">{$lang->family_numbers}</td>
			<td class="thead">{$lang->family_description}</td>
		</tr>
		<tr>
			<td class="trow1" valign="top">
				<div class="family_numbers trow2 smalltext"><strong>{$classes[$class]}</strong></div>
				<div class="family_numbers trow2 smalltext"><strong>{$members}</strong> {$lang->family_members}</div>
				<div class="family_numbers trow2 smalltext"><strong>{$members_male}</strong> {$lang->family_gender_male}</div>
				<div class="family_numbers trow2 smalltext"><strong>{$members_female}</strong> {$lang->family_gender_female}</div>
				<div class="family_numbers trow2 smalltext"><strong>{$members_diff}</strong> {$lang->family_gender_diff}</div>
				<div class="family_numbers trow2 smalltext"><strong>{$members_playable}</strong> {$lang->family_playable}</div>
				<div class="family_numbers trow2 smalltext"><strong>{$members_free}</strong> {$lang->family_free}</div>
			</td>
			<td class="trow1" align="justify" valign="top">{$family[\'description\']}</td>
		</tr>
	</table>
	{$edit_family}
<br />
	{$generations_bit}
		</td>
	</tr>
</table>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'		=> 'showthread_family',
		'template'	=> $db->escape_string('<script defer src="https://use.fontawesome.com/releases/v5.0.3/js/all.js"></script>
			<table class="tborder" cellspacing="5" cellpadding="5">
	<tr>
		<td class="trow2">
	<center>
		<h1>
			{$lang->family_family} {$family[\'lastname\']}
		</h1>
	</center>
	<table cellspacing="5" cellpadding="5" class="tborder">
		<tr>
			<td class="thead" width="35%">{$lang->family_numbers}</td>
			<td class="thead">{$lang->family_description}</td>
		</tr>
		<tr>
			<td class="trow1" valign="top">
				<div class="family_numbers trow2 smalltext"><strong>{$classes[$class]}</strong></div>
				<div class="family_numbers trow2 smalltext"><strong>{$members}</strong> {$lang->family_members}</div>
				<div class="family_numbers trow2 smalltext"><strong>{$members_male}</strong> {$lang->family_gender_male}</div>
				<div class="family_numbers trow2 smalltext"><strong>{$members_female}</strong> {$lang->family_gender_female}</div>
				<div class="family_numbers trow2 smalltext"><strong>{$members_diff}</strong> {$lang->family_gender_diff}</div>
				<div class="family_numbers trow2 smalltext"><strong>{$members_playable}</strong> {$lang->family_playable}</div>
				<div class="family_numbers trow2 smalltext"><strong>{$members_free}</strong> {$lang->family_free}</div>
			</td>
			<td class="trow1" align="justify" valign="top">{$family[\'description\']}</td>
		</tr>
	</table>
	{$edit_family}
<br />
	{$generations_bit}
		</td>
	</tr>
</table>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	// CSS	
	$css = array(
		'name' => 'familycp.css',
		'tid' => 1,
		"stylesheet" =>	'/* FAMILY STATISTICS */

.family_numbers {
	display: block;
	box-sizing: border-box;
	width: 100%;
	padding: 3px;
	margin: 2px auto;
	text-transform: uppercase;
	text-align: center;
	letter-spacing: 1px;
}

/* FORMATE FAMILY MEMBERS PICTURE */

.family-picture { 
	width: 96px;
	height: 85px;
	margin: 3px auto;
}

.taken-img {
	position: relative;
	box-sizing: border-box;
	width: 100px;
	padding: 2px;
}

.taken-img img {
	opacity: 1;	
	margin: 0px auto;
}

.free-img {
	position: relative;
	box-sizing: border-box;
	width: 100px;
	padding: 1px 2px;
}

.free-img img {
	opacity: 1;
	-webkit-filter: grayscale(100%); /* Safari 6.0 - 9.0 */
    filter: grayscale(100%);
	margin: 1px auto;
}

/* TEXTAREAS */ 

.family-fullname {
	box-sizing: border-box;
	width: 100px;
	margin: 0px auto;
	padding: 4px;
	text-align: center;
	letter-spacing: 1px;
	font-size: 8px;
	text-transform: uppercase;
	line-height: 1.1em;
}

.edit-fammember {
	box-sizing: border-box;
	width: 100px;
	margin: 0px auto;
	margin-bottom: 3px;
	padding: 3px;
}

.claim-fammember {
	padding: 3px;
	text-transform: uppercase;
	font-size: 7px;
	text-align: center;
	line-height: 1.1em;
	margin-top: 5px;
	letter-spacing: 1px;
}

/* FAMILY DESCRIPTION POPUP */
.family-pop { 
	position: fixed; 
	top: 0; 
	right: 0; 
	bottom: 0; 
	left: 0; 
	background: hsla(0, 0%, 0%, 0.5);
	z-index: 1; opacity:0; 
	-webkit-transition: .5s ease-in-out; 
	-moz-transition: .5s ease-in-out; 
	transition: .5s ease-in-out; pointer-events: none; 
} 

.family-pop:target { 
	opacity:1; 
	pointer-events: auto; 
} 

.family-pop > .pop { 
	width: 300px; 
	position: relative; 
	margin: 10% auto; 
	padding: 25px; 
	z-index: 3; 
} 

.family-pop > .pop > .description {
	margin: 10px auto;
	max-height: 130px;
	overflow: auto;
	line-height: 1.3em;
}

.closepop { 
	position: absolute; 
	right: -5px; 
	top:-5px; 
	width: 100%; 
	height: 100%; 
	z-index: 2; 
}',
		'cachefile' => $db->escape_string(str_replace('/', '', familycp.css)),
		'lastmodified' => time()
	);

	require_once MYBB_ADMIN_DIR."inc/functions_themes.php";

	$sid = $db->insert_query("themestylesheets", $css);
	$db->update_query("themestylesheets", array("cachefile" => "css.php?stylesheet=".$sid), "sid = '".$sid."'", 1);

	$tids = $db->simple_select("themes", "tid");
	while($theme = $db->fetch_array($tids)) {
		update_theme_stylesheet_list($theme['tid']);
	}

 	// edit templates
 	include MYBB_ROOT."/inc/adminfunctions_templates.php";
 	find_replace_templatesets("index", "#".preg_quote('{$header}')."#i", '{$header} {$index_family}');
	find_replace_templatesets("member_profile", "#".preg_quote('{$awaybit}')."#i", '{$awaybit} {$member_profile_family}');
	find_replace_templatesets("showthread", "#".preg_quote('<tr><td id="posts_container">')."#i", '{$showthread_family}<tr><td id="posts_container">');

}

function family_deactivate()
{

	global $db;

	// drop css
	require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
	$db->delete_query("themestylesheets", "name = 'family.css'");
	$query = $db->simple_select("themes", "tid");
	while($theme = $db->fetch_array($query)) {
		update_theme_stylesheet_list($theme['tid']);
	}

	// edit templates
	require MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("index", "#".preg_quote('{$index_family}')."#i", '', 0);
	find_replace_templatesets("member_profile", "#".preg_quote('{$member_profile_family}')."#i", '', 0);
	find_replace_templatesets("showthread", "#".preg_quote('{$showthread_family}')."#i", '', 0);

	// drop templates
  	$db->delete_query("templates", "title LIKE '%family%'");

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
					$fammember['img'] = "<div class=\"{$img_class}\" style=\"background: {$gender_border}\"><a href=\"#description{$fammember['fmid']}\"><img src=\"{$mybb->settings['familycp_default_img']}\" class=\"family-picture\" /></a></div>";	
				}
				else {
					$fammember['img'] = "<div class=\"{$img_class}\" style=\"background: {$gender_border}\"><a href=\"#description{$fammember['fmid']}\"><img src=\"{$fammember['picture']}\" class=\" family-picture\" /></a></div>";
				}

				// check if family member is already claimed
				if(empty($fammember['claim_username']) && $fammember['playable'] == "1") {
					// guests can't take a family member
					// generate wanted url 
					if(!empty($fammember['wanted'])) {
						$fammember['wanted_link'] = "<a href=\"{$fammember['wanted']}\" target=\"blank_\">{$lang->family_wanted}</a>";
					}
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
					if(!empty($fammember['claim_timestamp'])) {
						$fammember['claim_timestamp'] = my_date("relative", $fammember['claim_timestamp']);
						$fammember['claim_timestamp'] = "(".$fammember['claim_timestamp'].")";
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
				$field_username = "fid".$mybb->settings['familycp_username'];
				$username = $db->query("SELECT $field_username FROM ".TABLE_PREFIX."userfields WHERE ufid = '$fammember[uid]'");
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
						$fammember['img'] = "<div class=\"{$img_class}\" style=\"background: {$gender_border}\"><a href=\"#description{$fammember['fmid']}\"><img src=\"{$mybb->settings['familycp_default_img']}\" class=\"family-picture\" /></a></div>";	
					}
					else {
						$fammember['img'] = "<div class=\"{$img_class}\" style=\"background: {$gender_border}\"><a href=\"#description{$fammember['fmid']}\"><img src=\"{$fammember['picture']}\" class=\" family-picture\" /></a></div>";
					}

					// check if family member is already claimed
					if(empty($fammember['claim_username']) && $fammember['playable'] == "1") {
					// generate wanted url 
					if(!empty($fammember['wanted'])) {
						$fammember['wanted_link'] = "<a href=\"{$fammember['wanted']}\" target=\"blank_\">{$lang->family_wanted}</a>";
					}
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
						if(!empty($fammember['claim_timestamp'])) {
							$fammember['claim_timestamp'] = my_date("relative", $fammember['claim_timestamp']);
							$fammember['claim_timestamp'] = "(".$fammember['claim_timestamp'].")";
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
					$field_username = "fid".$mybb->settings['familycp_username'];
					$username = $db->query("SELECT $field_username FROM ".TABLE_PREFIX."userfields WHERE ufid = '$fammember[uid]'");
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

function family_index_start() {
	global $db, $mybb, $templates, $lang, $index_family;
	$lang->load("family");
	$field_username = "fid".$mybb->settings['familycp_username'];
	$username = $db->escape_string($mybb->user[$field_username]);

	// get claims of online user and show them on index page
	$query = $db->query("SELECT * FROM ".TABLE_PREFIX."families_members
		LEFT JOIN ".TABLE_PREFIX."families ON ".TABLE_PREFIX."families_members.fid = ".TABLE_PREFIX."families.fid
		WHERE claim_username = '$username'
		ORDER BY claim_timestamp ASC");
	while($claim = $db->fetch_array($query)) {
		$fid = $claim['fid'];
		$claim['claim_timestamp'] = my_date("relative", $claim['claim_timestamp']);
		$claim['family_link'] = "<a href=\"family.php?action=view&id={$fid}\" target=\"blank_\">{$claim['lastname']}</a>";
		eval("\$index_family_bit .= \"".$templates->get("index_family_bit")."\";");		
	}

	// set template
	$index_family = "";
	if(mysqli_num_rows($query) >= "1") {
		eval("\$index_family = \"".$templates->get("index_family")."\";");	
	}
}

function family_alerts() {
    global $mybb, $lang;
  $lang->load('family');
    /**
     * Alert formatter for my custom alert type.
     */
    class MybbStuff_MyAlerts_Formatter_ClaimedFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
    {
        /**
         * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
         *
         * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
         *
         * @return string The formatted alert string.
         */
        public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
        {
            return $this->lang->sprintf(
                $this->lang->family_alert_claimed,
                $outputAlert['from_user'],
                $alertContent['fullname'],
                $outputAlert['dateline']
            );
        }

        /**
         * Init function called before running formatAlert(). Used to load language files and initialize other required
         * resources.
         *
         * @return void
         */
        public function init()
        {
            if (!$this->lang->family) {
                $this->lang->load('family');
            }
        }

        /**
         * Build a link to an alert's content so that the system can redirect to it.
         *
         * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
         *
         * @return string The built alert, preferably an absolute link.
         */
        public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
        {
            return $this->mybb->settings['bburl'] . '/' . 'family.php?action=view&id=' . $alert->getObjectId();
        }
    }

    if (class_exists('MybbStuff_MyAlerts_AlertFormatterManager')) {
        $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

        if (!$formatterManager) {
            $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
        }

        $formatterManager->registerFormatter(
                new MybbStuff_MyAlerts_Formatter_ClaimedFormatter($mybb, $lang, 'claimed')
        );
    }

} 

?>