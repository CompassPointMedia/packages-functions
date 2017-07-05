<?php
$functionVersions['quasi_resource_generic']=2.01;
function quasi_resource_generic($db, $table, $ResourceToken, /** remaining fields normally default to vector value **/ $typeField='ResourceType', $sessionKeyField='sessionKey', $resourceTokenField='ResourceToken', $primary='ID', $creatorField='Creator', $createDateField='CreateDate', $cnx='', $options=array()){
	/***
	011-11-02:
		* NOTE: if two people are simultaneously logged in as the same user on 2 computers, this could mess with what they are doing - the resource when submitted  may not be present in the database
		* removed same-session requirement from the select query - only resource token is considered, and system checks if there are more than one of a "unique" resource token

	2010-03-23: 
		* Further cleanup, allowed for $options to be the 4th parameter or the last - only first three params are really variable, i.e. database, table and ResourceToken.
		* Allowed for $su to be passed explicitly
	2009-06-11: No longer exit if not _SESSION[sessionKeyField] || _SESSION[systemUserName], instead it is set
	2007-01-29: Added the ability to add other fields.  Wow this function is nice! pulled over from mail profile and worked perfect in adding a prognote for giocosa
	2006-09-08: This function is radically different.  REQUIRES a client-side UNIQUE SessionToken to be passed for each instance of the object (each window, like a new Word doc window), and either creates a well-formed "pending" resource (with SessionType=NULL) including the Creator value, or returns the id of the object which was created before.
	
	In this way multiple windows can be opened (mail profiles), and they can be refreshed without any problem.  Also with correct coding outside you can check and see if the object (which would initially be new) has been saved.
	
	Function by default purges objects BY THIS USER from another session (typically means, they were not completed)
	
	options(
		insertFields = array(Field1, Field2, ..),
		insertValues = array(value1, value2, ..)
	)
	
	***/
	global $qr,$qx, $developerEmail, $fromHdrBugs, $fromHdrNotices, $dateStamp, $fl, $ln, $MASTER_DATABASE;
	$a=func_get_args();
	if(count($a)==4){
		@extract($typeField);
		$typeField='ResourceType';
	}else{
		@extract($options);
	}
	if(!$cnx)$cnx=$qx['defCnxMethod'];
	if(!$ResourceToken){
		mail($developerEmail, 'Error file '.__FILE__.', line '.__LINE__,get_globals(),$fromHdrBugs);
		error_alert('Function quasi_resource_generic() called without unique identifier (ResourceToken) for the object');
	}
	if(!($key=substr($_SESSION[$sessionKeyField], 0, 8))){
		$key=$_SESSION[$sessionKeyField]=md5($GLOBALS['PHPSESSID'].time());
		mail($developerEmail, 'notice file '.__FILE__.', line '.__LINE__,get_globals('Function quasi_resource_generic() called without Session.SessionKey present.  This typically means the user has been signed out; a session key was set based on md5(phpsessid . time())'),$fromHdrNotices);
	}
	if($su){
		//OK - passed in options
	}else if(!($su=$_SESSION['systemUserName'])){
		$su=($_SESSION['admin']['userName'] ? $_SESSION['admin']['userName'] : $_SERVER['PHP_AUTH_USER']);
		mail($developerEmail, 'notice file '.__FILE__.', line '.__LINE__,get_globals('Function quasi_resource_generic() called without Session.SessionKey present.  This typically means the user has been signed out; a session key was set based on md5(phpsessid . time())'),$fromHdrNotices);
		if(!$su){
			//exit at this point
			exit('Function quasi_resource_generic() called without a username set (either in session or in AUTH_USER) and can not be used');
		}
	}
	
	//we purge null type resources BY THIS USER from another session (means, not completed) - this can be skipped
	//2011-11-02 NOTE: if two people are simultaneously logged in as the same user on 2 computers, this could mess with what they are doing - the resource when submitted  may not be present in the database
	if(!isset($purgePreviousSessions))$purgePreviousSessions=true;
	if($purgePreviousSessions){
		q("DELETE FROM `$db`.`$table` WHERE $typeField IS NULL AND $sessionKeyField !='$key' AND $creatorField='$su'", $cnx);
		if($testingQuasiResource)prn($qr);
	}
	
	//version 2.0 requires that a UID be passed from the start - means now that pages must be opened client-side with the UID suggested
	
	//Note: I used to check for "WHERE $typeField IS NULL but now I no longer do; the object returned may have been saved in the window during the course of the user interaction.
	//previous: $id=q("SELECT $primary FROM `$db`.`$table` WHERE $sessionKeyField='$key' AND $resourceTokenField='$ResourceToken'",O_VALUE);
	$ids=q("SELECT $primary FROM `$db`.`$table` WHERE $resourceTokenField='$ResourceToken' ORDER BY IF($sessionKeyField='$key',1,2)",O_COL);
	if($testingQuasiResource)prn($qr);
	

	if(!$ids){
		if($insertFields && $insertValues){
			foreach($insertFields as $n=>$v)	$str.=",\n".$v."='".addslashes($insertValues[$n])."'";
		}
		$ids[1]=q("INSERT INTO `$db`.`$table` SET
		$sessionKeyField='$key',
		$resourceTokenField='$ResourceToken',
		$typeField=NULL,
		$createDateField='$dateStamp',
		$creatorField='$su' $str", O_INSERTID, $cnx);
		if($testingQuasiResource)prn($qr);
	}
	if(count($ids)>1)mail($developerEmail, 'Error in '.$MASTER_DATABASE.':'.end(explode('/',__FILE__)).', line '.__LINE__,get_globals($err='ResourceToken '.$ResourceToken.' has multiple instances present'),$fromHdrBugs);
	return $ids[1];
}
?>