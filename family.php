<?php

// set some useful constants that the core may require or use
define("IN_MYBB", 1);
define('THIS_SCRIPT', 'family.php');

// including global.php gives us access to a bunch of MyBB functions and variables
require_once "./global.php";

// load language-settings
$lang->load('family');

// shorten variables
$action = $mybb->input['action'];
$uid = $mybb->user['uid'];

// add a breadcrumb
add_breadcrumb('Familien', "family.php");

// set navigation
eval("\$family_nav = \"".$templates->get("family_navigation")."\";");

// set navigation so only team members can see certain options
$timeline_nav_team = "";
if($mybb->usergroup['cancp'] == "1") {
	eval("\$family_nav_team = \"".$templates->get("family_navigation_team")."\";");	
}

// landing page
if(empty($action)) {
	
	// set landing page-template
	eval("\$page = \"".$templates->get("family")."\";");
	output_page($page);
}

// add family
if($action == "addfamily") {
	
}

// add family backend
if($action == "do_addfamily") {

	// insert family into database
	$db->insert_query("families", $new_record);
	
	redirect("family.php", "{$lang->family_family_added}");
}

// add family member 
if($action == "addmember") {

}

// add family member backend 
if($action == "do_addmember") {
	
	// insert family member into database
	$db->insert_query("families_members", $new_record);
	
	redirect("family.php", "{$lang->family_member_added}");
}
?>