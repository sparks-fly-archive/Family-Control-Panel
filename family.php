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

// get family belonging to online user
$query = $db->query("SELECT * FROM ".TABLE_PREFIX."families WHERE uid = '$uid'");
$ownfam = $db->fetch_array($query);
$ufid = $ownfam['fid'];

// add a breadcrumb
add_breadcrumb('Familien', "family.php");

// set navigation so only members can see certain options
$family_nav_member = "";
if(!empty($uid)) {
	if(empty($ufid)) {
		$addfamily = "<tr>
		<td class=\"trow1 smalltext\"><a href=\"family.php?action=addfamily\">{$lang->family_add_family}</a></td>
		</tr>";
	}
	else {
		$addfamily = "<tr>
		<td class=\"trow1 smalltext\"><a href=\"family.php?action=addmember\">{$lang->family_add_member}</a></td>
		</tr>";
	}
	eval("\$family_nav_member = \"".$templates->get("family_navigation_member")."\";");	
}

// set navigation
eval("\$family_nav = \"".$templates->get("family_navigation")."\";");

// landing page
if(empty($action)) {
	
	// set template
	eval("\$page = \"".$templates->get("family")."\";");
	output_page($page);
}

// faq page to help members out ;)
if($action == "faq") {

	// set template
	eval("\$page = \"".$templates->get("family_faq")."\";");
	output_page($page);	
}

// add family
if($action == "addfamily") {

	// only members can add families
	if(empty($uid)) {
		error_no_permission();
	}

	// set template
	eval("\$page = \"".$templates->get("family_addfamily")."\";");
	output_page($page);
}

// add family backend
if($action == "do_addfamily") {

	// insert family into database
	$new_record = array(
		"uid" => (int)$uid,
		"lastname" => $db->escape_string($mybb->get_input('lastname')),
		"description" => $db->escape_string($mybb->get_input('description')),
		"class" => $db->escape_string($mybb->get_input('class'))
	);
	$db->insert_query("families", $new_record);

	$query = $db->query("SELECT fid FROM ".TABLE_PREFIX."families
		ORDER BY fid DESC
		LIMIT 1");
	$fid = $db->fetch_field($query, "fid");
	
	redirect("family.php?action=addmember", "{$lang->family_family_added}");
}

// edit family
if($action == "editfamily") {

	$fid = $mybb->input['id'];

	// get family matching fid
	$query = $db->query("SELECT * FROM ".TABLE_PREFIX."families
		WHERE fid = '$fid'");
	$family = $db->fetch_array($query);

	// only team members and family's author can edit family
	if($mybb->usergroup['cancp'] == "0" || $uid != $family['uid']) {
		error_no_permission();
	}

	// select matching class option
	$classes = array("1" => "{$lang->family_class_under}", "2" => "{$lang->family_class_middle}", "3" => "{$lang->family_class_high}");
	foreach($classes as $key => $value) {
		$checked = "";
		if($key == $family['class']) {
			$checked = "selected";
		}
		$class_bit .= "<option value=\"{$key}\" {$checked}>{$value}</option>";
	}


	// set template
	eval("\$page = \"".$templates->get("family_editfamily")."\";");
	output_page($page);
}

// edit family backend
if($action == "do_editfamily") {

	$fid = $mybb->get_input('fid');

	// insert family into database
	$new_record = array(
		"lastname" => $db->escape_string($mybb->get_input('lastname')),
		"description" => $db->escape_string($mybb->get_input('description')),
		"class" => $db->escape_string($mybb->get_input('class'))
	);
	$db->update_query("families", $new_record, "fid = '$fid'");;
	
	redirect("family.php?action=view&id={$fid}", "{$lang->family_family_added}");
}

// add family member 
if($action == "addmember") {

	// only members can add family members!
	if(empty($uid)) {
		error_no_permission();
	}

	// if this is the first family member, add yourself first!
	$query = $db->query("SELECT COUNT(*) AS members FROM ".TABLE_PREFIX."families_members WHERE fid = '$ufid'");
	$members = $db->fetch_field($query, "members");
	if($members == "0") {
		$fullname = "<strong>{$mybb->user['username']}</strong> {$lang->family_add_yourself}";
	}
	else {
		$fullname = "<input type=\"text\" class=\"textbox\" name=\"fullname\" id=\"fullname\" size=\"40\" maxlength=\"1155\" style=\"width: 340px;\" />";
	}

	// set template
	eval("\$page = \"".$templates->get("family_addmember")."\";");
	output_page($page);
}

// add family member backend 
if($action == "do_addmember") {

	// if this is the first family member, add yourself first!
	$query = $db->query("SELECT COUNT(*) AS members FROM ".TABLE_PREFIX."families_members WHERE fid = '$ufid'");
	$members = $db->fetch_field($query, "members");
	if($members == "0") {
		$fullname = $mybb->user['username'];
		$fmuid = $uid;
	}
	else {
		$fullname = $mybb->get_input('fullname');
	}
	
	// insert family member into database
	$new_record = array(
		"fullname" => $db->escape_string($fullname),
		"gender" => (int)$mybb->get_input('gender'),
		"generation" => (int)$mybb->get_input('generation'),
		"position" => $mybb->get_input('position'),
		"age" => (int)$mybb->get_input('age'),
		"description" => $db->escape_string($mybb->get_input('description')),
		"picture" => $db->escape_string($mybb->get_input('picture')),
		"playable" => (int)$mybb->get_input('playable'),
		"fid" => (int)$ufid,
		"uid" => (int)$fmuid
	);
	$db->insert_query("families_members", $new_record);
	
	redirect("family.php?action=addmember", "{$lang->family_member_added}");
}


// own family overview
if($action == "view") {

	// if there's no specific family you're looking at, get your own
	$fid = $mybb->get_input('fid');
	if(empty($fid)) {
		$fid = $ufid;
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
			if(empty($famuser)) {

				// generate picture from database url (if set), otherwise use set default image
				$img_class = "free-img";
				if(empty($fammember['picture'])) {
					$fammember['img'] = "<div class=\"{$img_class}\" style=\"background: {$gender_border}\"><img src=\"images/dark/noava.png\" class=\"family-picture\" /></div>";	
				}
				else {
					$fammember['img'] = "<div class=\"{$img_class}\" style=\"background: {$gender_border}\"><img src=\"{$fammember['picture']}\" class=\" family-picture\" /></div>";
				}

			}
			else {

				// generate picture from user avatar
				$img_class ="taken-img";
				$fammember['img'] = "<div class=\"{$img_class}\" style=\"background: {$gender_border}\"><img src=\"{$famuser['avatar']}\" class=\"family-picture\" /></div>";
			}

			// only team and family's author can edit
			$edit_fammember = "";
			if($mybb->usergroup['cancp'] == "1" || $uid == $family['uid']) {
				$edit_fammember = "<div class=\"thead edit-fammember\"><a href=\"family.php?action=editmember&id={$fammember['fmid']}\">{$lang->family_edit_text}</a></div>";
			}

			eval("\$fammember_bit .= \"".$templates->get("family_view_generations_member")."\";");
		}

		// generation seperator
		eval("\$generations_bit .= \"".$templates->get("family_view_generations")."\";");
	}

	// set template
	eval("\$page = \"".$templates->get("family_view")."\";");
	output_page($page);
}
?>