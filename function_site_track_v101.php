<?php
$functionVersions['site_track']=1.01;
function site_track(){
/*
GENERAL_VERSION:Function Description Parameters Version 1.0 8-5-2002;
GENERAL_FUNCTION_NAME:site_track;
GENERAL_FUNCTION_TITLE:Site Tracker;
GENERAL_BRIEF_DESCRIPTION:enters events into a database such as term reception, cartAdd, and purchase(checkout);
GENERAL_FUNCTION_AUTHOR:Samuel Fullman;
GENERAL_SERIAL_NUMBER:1001;
GENERAL_CREATE_DATE:2002-08-01;
GENERAL_INSTANCE:01;
GENERAL_PROTOCOL_LOCATION:unspecified;
LINK_RETURNS_POSSIBLE_VALUES:unspecified;
LINK_MODIFIES_PASSED_VALUES:unspecified;
LINK_MODIFIES_EXTERNAL_VARIABLES:unspecified;
LINK_MODIFIES_EXTERNAL_ARRAYS:unspecified;
LINK_REQUIRED_EXTERNAL_VARIABLES:unspecified;
LINK_REQUIRED_EXTERNAL_ARRAYS:unspecified;
LINK_REFERENCES_EXTERNAL_FUNCTIONS:unspecified;
LINK_REFERENCES_EXTERNAL_FILES:unspecified;
LINK_DEPENDENT_EXTERNAL_FUNCTIONS:unspecified;
MODE1_COMMENTS:no modes present;
MODE2_COMMENTS:unspecified;
MODE3_COMMENTS:unspecified;
MODE4_COMMENTS:unspecified;
MODE5_COMMENTS:unspecified;
MODE6_COMMENTS:unspecified;
MODE7_COMMENTS:unspecified;
MODE8_COMMENTS:unspecified;
COMMENTS:woo-wee!  this works great.  The input includes $cartParams['orderTotal'], with other cartParams items available because of the array, it also relies on the following cookies: visitor = yes/blank;; fullName = 'whatever';;  It relies on the session variable 'Login' = 'guest' or Administrator;;  Creates a concept called "IDLevel" 0 = unknown, 1 = visitor, 2=known or presumed, but logged out, 3=known and logged in (implies we need a fullname to allow someone into the system);
TO_DO:unspecified;
GOTCHAS:unspecified;
*/


	global $siteRoot, $_settings;
	$vorder = array("localEvent", "localTerm", "localPID", "cartParams");
	$arg_list = func_get_args();
	for($i=0; $i < func_num_args(); $i++){
		eval('$' . $vorder[$i] . '=$arg_list[$i];');
	}

//look first in passed variable, then get, then in cookie, then in session, for the term
if($localTerm){
	//we're good to go
	//attempt to set the term in cookie
	setcookie('term',$localTerm,time()+3600*24*2);
}elseif($localTerm = $_GET['term']){
	//attempt to set the term in cookie
	setcookie('term',$_GET['term'],time()+3600*24*2);
}elseif($localTerm = $_COOKIE['term']){
	//we're good to go
}elseif($localTerm = $_SESSION['term']){
	//this would happen if IE lets the PHPSESSID be set but nothing else
	$cookieTest = 1; #we should perform a cookie test here but I'm not going to develop that yet
}
	
//now, same thing for the PID of the item if that's there
if($localPID){
	//we're good to go
	//attempt to set the PID in cookie
	setcookie('PID',$localPID,time()+3600*24*2);
}elseif($localPID = $_GET['PID']){
	//attempt to set the term in cookie
	setcookie('PID',$_GET['PID'],time()+3600*24*2);
}elseif($localPID = $_COOKIE['PID']){
	//we're good to go
}elseif($localPID = $_SESSION['PID']){
	//this would happen if IE lets the PHPSESSID be set but nothing else
	$cookieTest = 1; #we should perform a cookie test here but I'm not going to develop that yet
}

//get the cart value
if($localEvent==ONCARTSUBMIT or $localEvent==ONORDERPLACE or $localEvent ==ONORDERAPPROVE){$eventValue = $cartParams['orderTotal'];}


//get the IDLevel
if($_SESSION['Login'] and $_SESSION['Login']<>'anonymous'){
	//they are logged in, no need the check cookies (should already be there)
	$IDLevel=3; //at least guest
	$fullName = trim($_SESSION['FirstName'] . ' ' . $_SESSION['LastName']);
}elseif($_COOKIE['fullName'] and !$_SESSION['Login']){
	//they are known but not logged in
	$IDLevel=2; //anonymouse but tracked and presumed
	$fullName = trim($_SESSION['FirstName'] . ' ' . $_SESSION['LastName']);
}elseif($_COOKIE['visitor']=='yes'){
	//they are a site visitor, note 'visitor' doesn't need to be unset after they are identified based on the logical order
	$IDLevel=1; //anonymous but tracked
}else{
	//for this call IDLevel = 0, attempt to set a cookie for next call
	$IDLevel=0; //completely anonymous, i.e. in stateless protocol
	setcookie ('visitor','yes',time()+3600*24*60);
}

	//make the database entry
	$sqlString = "INSERT INTO " . $_settings['siteLog'] . " set 
	SESSID = '" .  $GLOBALS['PHPSESSID'] . "',
	IP = '" . $_SERVER['REMOTE_ADDR'] . "',
	Agent = '" . $_SERVER['HTTP_USER_AGENT'] . "',
	Timestamp = '" . time() . "',
	Term = '$localTerm',
	ID = '$localPID',
	Event = '$localEvent',
	EventValue = '$eventValue',
	Name = '$Name',
	IDLevel = '$IDLevel',
	PageURL = '".$_SERVER['PHP_SELF']."?" . $_SERVER['QUERY_STRING'] . "'";
	$db_cnx = mysqli_connect($_settings['hostName'], $_settings['userName'], $_settings['password']);
	mysqli_select_db($db_cnx, $_settings['databaseName']);
	$queryResult = mysqli_query($db_cnx, $sqlString) or die(mysqli_error($db_cnx));
	//send any emails when appropriate

}//end site_track()

