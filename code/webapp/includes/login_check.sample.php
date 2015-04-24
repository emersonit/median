<?php
/*

    MEDIAN LOGIN/AUTHENTICATION CHECKING

    this utilizes the SimpleSAMLphp library: https://simplesamlphp.org/
    
    it also uses the group membership returned from the SAML identity provider
    to determine what user level a person should have
    
    you will probably want to edit this extensively for your own environment

*/

require_once('/median-webapp/lib/simplesaml/lib/_autoload.php');
$saml_auth_service = new SimpleSAML_Auth_Simple('default-sp');

// the default "current user" info
$current_user = array(
    'loggedin' => false,
    'userid' => 0,
    'username' => 'nobody',
    'userlevel' => 6
);

// if you want to require login for a page, just set $login_required = true somewhere before you load this
if ((isset($login_required) && $login_required == true) || $saml_auth_service->isAuthenticated()) {
    $saml_auth_service->requireAuth();
    $saml_user_attributes = $saml_auth_service->getAttributes();
    $current_user['loggedin'] = true;
    
    // this uses Active Directory's sAMAccountName for the username
    $current_user['username'] = strtolower(trim($saml_user_attributes['sAMAccountName'][0]));
    
    // this uses Active Directory's group membership
    if (isset($saml_user_attributes['memberOf']) && is_array($saml_user_attributes['memberOf'])) {
        $saml_user_groups = $saml_user_attributes['memberOf'];
    } else if (isset($saml_user_attributes['memberof']) && is_array($saml_user_attributes['memberof'])) {
        $saml_user_groups = $saml_user_attributes['memberof'];
    } else {
        $saml_user_groups = array(); // user has no groups, it seems
    }

    // determine what AD groups they're in, which will indicate their population
    // you will want to customize this
    $ad_usergroups = array();
    $ad_staff_array = array('CN=Staff,OU=All Groups,DC=emerson,DC=edu');
    $ad_student_array = array('CN=Students,OU=All Groups,DC=emerson,DC=edu');
    $ad_faculty_array = array('CN=Faculty,OU=All Groups,DC=emerson,DC=edu');
    $ad_alumni_array = array('CN=Alumni,OU=All Groups,DC=emerson,DC=edu');

    foreach ($ad_staff_array as $staffgroup) {
        if (in_array($staffgroup, $saml_user_groups)) {
            array_push($ad_usergroups, 'staff');
            break;
        }
    }

    foreach ($ad_student_array as $studentgroup) {
        if (in_array($studentgroup, $saml_user_groups)) {
            array_push($ad_usergroups, 'student');
            break;
        }
    }

    foreach ($ad_faculty_array as $facultygroup) {
        if (in_array($facultygroup, $saml_user_groups)) {
            array_push($ad_usergroups, 'faculty');
            break;
        }
    }

    foreach ($ad_alumni_array as $alumnigroup) {
        if (in_array($alumnigroup, $saml_user_groups)) {
            array_push($ad_usergroups, 'alumni');
            break;
        }
    }
    
    // now check to see what they are, and what their user level should be
    // based on group membership
    
    if (in_array('staff', $ad_usergroups) || in_array('student', $ad_usergroups)) {
        $current_user['userlevel'] = 5; // considered "part of the community"
    }

    if (in_array('faculty', $ad_usergroups)) {
        $current_user['userlevel'] = 4; // faculty only
    }

    // get user info from mongodb
    // and if they have no user info, provision a new set of metadata for them

    require_once('/median-webapp/includes/dbconn_mongo.php');

    $user_info = $mdb->users->findOne(array('ecnet' => $current_user['username']));
    if ($user_info != null) {
        // got user
        $current_user['userid'] = (int) $user_info['uid'] * 1;
        // update user level in mongodb
        if (!isset($user_info['ul']) || $user_info['ul'] * 1 != $current_user['userlevel']) {
            try {
                $update_user_result = $mdb->users->update(array('ecnet' => $current_user['username']), array('$set' => array('ul' => $current_user['userlevel'])), array('w' => 1));
            } catch(MongoCursorException $e) {
                writeToErrorLog('error updating user level to mongo', $user_info['uid'], null, print_r($e, true));
            }
        }
        // check for a user level override in mongodb
        if (isset($user_info['o_ul'])) {
            $current_user['userlevel'] = (int) $user_info['o_ul'] * 1;
        }
    } else {
        require_once('/median-webapp/includes/user_functions.php');
        // no user -- add a new record of them to the database
        $new_uid = generateNewUserRecord($current_user['username'], $current_user['userlevel']);
        if ($new_uid == false) {
            bailout('Sorry, there was an error adding you as a user.', null, null, $current_user['username']);
        }
        $current_user['userid'] = $new_uid;
    }

    // ok all set...!

}
