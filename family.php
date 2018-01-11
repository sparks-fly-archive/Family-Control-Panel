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
		</tr>
		<tr>
		   <td class=\"trow2 smalltext\"><a href=\"family.php?action=view\">{$lang->family_edit}</a></td>
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
		"class" => $db->escape_string($mybb->get_input('class')),
		"founder" => (int)$founder
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
	if($uid != $family['uid']) {
		if($mybb->usergroup['cancp'] == "0") {
			error_no_permission();
		}
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

	$founders = array("1" => "{$lang->family_playable_yes}", "0" => "{$lang->family_playable_no}");
	foreach($founders as $key => $value) {
		$checked = "";
		if($family['founder'] == $key) {
			$checked = "selected";
		}
		$founder_bit .= "<option value=\"{$key}\" {$checked}>{$value}</option>";
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
		"class" => $db->escape_string($mybb->get_input('class')),
		"founder" => (int)$founder
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

// edit family member 
if($action == "editmember") {
	
	$fmid = $mybb->input['id'];

	// get family member matching fid
	$query = $db->query("SELECT * FROM ".TABLE_PREFIX."families_members
		WHERE fmid = '$fmid'");
	$fammember = $db->fetch_array($query);
	$fid = $fammember['fid'];

	// only team members and family's author can edit family
	if(($uid != $family['uid']  || $uid != $fammember['uid']) && $mybb->usergroup['cancp'] == "0") {
		error_no_permission();
	}
	
	$genders = array("1" => "{$lang->family_gender_male}", "2" => "{$lang->family_gender_female}", "3" => "{$lang->gender_diff}");
	$playable = array("1" => "{$lang->family_playable_yes}", "0" => "{$lang->family_playable_no}");
	$founders = array("1" => "{$lang->family_playable_yes}", "0" => "{$lang->family_playable_no}");
	
	foreach($genders as $key => $value) {
		$checked = "";
		if($fammember['gender'] == $key) {
			$checked = "selected";
		}
		$gender_bit .= "<option value=\"{$key}\" {$checked}>{$value}</option>";
	}
	
	foreach($playable as $key => $value) {
		$checked = "";
		if($fammember['playable'] == $key) {
			$checked = "selected";
		}
		$playable_bit .= "<option value=\"{$key}\" {$checked}>{$value}</option>";
	}

	// set template
	eval("\$page = \"".$templates->get("family_editmember")."\";");
	output_page($page);
}

// edit family member backend 
if($action == "do_editmember") {

	$fmid = $mybb->get_input('fmid');
	$fid = $mybb->get_input('fid');
	
	// insert family member into database
	$new_record = array(
		"fullname" => $db->escape_string($mybb->get_input('fullname')),
		"gender" => (int)$mybb->get_input('gender'),
		"generation" => (int)$mybb->get_input('generation'),
		"position" => $db->escape_string($mybb->get_input('position')),
		"age" => (int)$mybb->get_input('age'),
		"description" => $db->escape_string($mybb->get_input('description')),
		"picture" => $db->escape_string($mybb->get_input('picture')),
		"playable" => (int)$mybb->get_input('playable'),
	);
	$db->update_query("families_members", $new_record, "fmid = '{$fmid}'");
	
	redirect("family.php?action=view&id={$fid}", "{$lang->family_member_added}");
}

// family overview
if($action == "view") {

	// if there's no specific family you're looking at, get your own
	$fid = $mybb->get_input('id');
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
	eval("\$page = \"".$templates->get("family_view")."\";");
	output_page($page);
}

// filter families 
if($action == "filter_families") {
	
	$class = $db->escape_string($mybb->get_input('class'));
	if(empty($class)) {
		$class = "%";
	}

	$founder = $db->escape_string($mybb->get_input('founder'));
	if(empty($founder)) {
		$founder = "%";
	}


	$classes = array("1" => "{$lang->family_class_under}", "2" => "{$lang->family_class_middle}", "3" => "{$lang->family_class_high}");
	foreach($classes as $key => $value) {
		$class_bit .= "<option value=\"{$key}\">{$value}</option>";	
	}

	$founders = array("1" => "{$lang->family_playable_yes}", "0" => "{$lang->family_playable_no}");
	foreach($founders as $key => $value) {
		$founder_bit .= "<option value=\"{$key}\">{$value}</option>";	
	}
	
	// get familys matching filter
	$query_family = $db->query("SELECT * FROM ".TABLE_PREFIX."families
		WHERE class LIKE '{$class}'
		AND founder LIKE '{$founder}'
		ORDER BY lastname ASC");
	$familiescount = mysqli_num_rows($query_family);
	while($family = $db->fetch_array($query_family)) {
		$fid = $family['fid'];
		$class_bit = "";
		$founder_bit = "";
		
		// format link to family's page
		$family['family_link'] = "<a href=\"family.php?action=view&id={$fid}\" target=\"blank_\">{$family['lastname']}</a>";
		
		$famclass = $family['class'];
		
		foreach($classes as $key => $value) {
			$checked = "";
			if($key == $class) {
				$checked = "selected";
			}
			$class_bit .= "<option value=\"{$key}\" {$checked}>{$value}</option>";	
		}

		foreach($founders as $key => $value) {
			$checked = "";
			if($key == $founder) {
				$checked = "selected";
			}
			$founder_bit .= "<option value=\"{$key}\" {$checked}>{$value}</option>";	
		}
			
		
		// get statistics
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
		
		eval("\$family_bit .= \"".$templates->get("family_filter_families_bit")."\";");
	}
	
	// set template
	eval("\$page = \"".$templates->get("family_filter_families")."\";");
	output_page($page);
}

if($action == "filter_member") {
	
	$class = $db->escape_string($mybb->get_input('class'));
	if(empty($class)) {
		$class = "%";
	}
	$gender = $db->escape_string($mybb->get_input('gender'));
	if(empty($gender)) {
		$gender = "%";
	}
	$age_start = $db->escape_string($mybb->get_input('age_start'));
	$age_end = $db->escape_string($mybb->get_input('age_end'));
	if(empty($age_start) || empty($age_end)) {
		$age_sql = "";
	}
	else {
		$age_sql = "AND age BETWEEN '$age_start' AND '$age_end'";
	}
	
	$classes = array("1" => "{$lang->family_class_under}", "2" => "{$lang->family_class_middle}", "3" => "{$lang->family_class_high}");
	foreach($classes as $key => $value) {
		$class_bit .= "<option value=\"{$key}\">{$value}</option>";	
	}
	
	$genders = array("1" => "{$lang->family_gender_male}", "2" => "{$lang->family_gender_female}", "3" => "{$lang->family_gender_diff}");
	foreach($genders as $key => $value) {
		$gender_bit .= "<option value=\"{$key}\">{$value}</option>";
	}
	
	// get family members matching filter
	$query = $db->query("SELECT * FROM ".TABLE_PREFIX."families_members
		LEFT JOIN ".TABLE_PREFIX."families ON ".TABLE_PREFIX."families_members.fid = ".TABLE_PREFIX."families.fid
		WHERE class LIKE '$class'
		AND gender LIKE '$gender'
		AND playable = '1'
		$age_sql
		AND ".TABLE_PREFIX."families_members.uid = '0'
		ORDER BY CAST(age AS signed) ASC, fullname ASC");

	$memberscount = mysqli_num_rows($query);
	while($fammember = $db->fetch_array($query)) {
		$gender_bit = "";
		$class_bit = "";
		$famclass = $family['class'];
		$famgender = $fammember['gender'];
		
		foreach($classes as $key => $value) {
			$checked = "";
			if($key == $class) {
				$checked = "selected";
			}
			$class_bit .= "<option value=\"{$key}\" {$checked}>{$value}</option>";	
		}
		
		foreach($genders as $key => $value) {
			$checked = "";
			if($gender == $key) {
				$checked = "selected";
			}
			$gender_bit .= "<option value=\"{$key}\" {$checked}>{$value}</option>";
		}
		
		$family['family_link'] = "<a href=\"family.php?action=view&id={$fammember['fid']}\" target=\"blank_\">{$fammember['lastname']}</a>";
		
		eval("\$members_bit .= \"".$templates->get("family_filter_members_bit")."\";");
	}
	
	// set template
	eval("\$page = \"".$templates->get("family_filter_members")."\";");
	output_page($page);	
}

// claim character
if($action == "claim") {
	$fmid = $mybb->get_input('id');
	$query = $db->query("SELECT fid FROM ".TABLE_PREFIX."families_members 
		WHERE fmid = '$fmid'");
	$fid = $db->fetch_field($query, "fid");
	// as member
	if(!empty($uid)) {
		$field_username = $mybb->settings['familycp_username'];
		$username = $mybb->user['fid'.$field_username];
	}
	//as guest
	else {
		$guest = $mybb->get_input('guest');		
	}
	$new_record = array(
		"claim_username" => $db->escape_string($guest),
		"claim_timestamp" => TIME_NOW
	);
	$db->update_query("families_members", $new_record, "fmid = '$fmid'");
	redirect("family.php?action=view&id={$fid}", "{$lang->family_claimed}");
}

// delete claim or member from family
if($action == "free") {
	$fmid = $mybb->get_input('id');
	$field_username = $mybb->settings['familycp_username'];
	$username = $mybb->user['fid'.$field_username];

	$query = $db->query("SELECT fid FROM ".TABLE_PREFIX."families_members 
		WHERE fmid = '$fmid'");
	$fid = $db->fetch_field($query, "fid");

	// only author, claimed user or team can delete claim
	$query = $db->query("SELECT fid FROM ".TABLE_PREFIX."families_members
		WHERE fmid = '$fmid'");
	$fid = $db->fetch_field($query, "fid");
	$query = $db->query("SELECT uid FROM ".TABLE_PREFIX."families
		WHERE fid = '$fid'");
	$query = $db->query("SELECT claim_username FROM ".TABLE_PREFIX."families_members
		WHERE fmid = '$fmid'");
	$claimed_username = $db->fetch_field($query, "claim_username");
	$family_uid = $db->fetch_field($query, "uid");
	if($uid == $family_uid || $mybb->usergroup['cancp'] == "1" || $username == $claimed_username) {
		$new_record = array(
			"uid" => (int)"0",
			"claim_username" => "",
			"claim_timestamp" => ""
		);
		$db->update_query("families_members", $new_record, "fmid = '$fmid'");
	}
	redirect("family.php?action=view&id={$fid}", "{$lang->family_claim_deleted}");
}	

// you're already member of this family?
if($action == "take") {
	$fmid = $mybb->get_input('id');
	$field_username = $mybb->settings['familycp_username'];
	$username = $mybb->user['fid'.$field_username];

	$query = $db->query("SELECT fid FROM ".TABLE_PREFIX."families_members 
		WHERE fmid = '$fmid'");
	$fid = $db->fetch_field($query, "fid");

	$new_record = array(
		"uid" => (int)$uid,
		"claim_username" => $db->escape_string($username),
		"claim_timestamp" => TIME_NOW
	);
	$db->update_query("families_members", $new_record, "fmid = '$fmid'");
	redirect("family.php?action=view&id={$fid}", "{$lang->family_claimed}");	
}

?>