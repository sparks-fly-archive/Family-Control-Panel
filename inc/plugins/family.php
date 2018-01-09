<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.");
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
	global $db;

	if(!$db->table_exists("families")) {
		$db->query("CREATE TABLE `mybb_families` (
  				`fid` int(11) NOT NULL AUTO_INCREMENT,
  				`uid` int(11) NOT NULL,
  				`lastname` text NOT NULL,
  				`description` text NOT NULL,
  				`class` text NOT NULL,
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

}

function family_deactivate()
{

}

?>