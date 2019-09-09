<?php
$functionVersions['machine_identification']=1.00;
function machine_identification($postTime, $browser){
	//######################### NOTE!!!! #########################
	//
	//		this script is also found on the relatebase console 
	//		login now as of 2013-06-26
	//		any changes to one should be made to the other
	//
	//############################################################
	//added temp here - this is going into system_machines but eventually needs to go into bais_logs
	global $FUNCTION_ROOT, $parse_javascript_gmt_date, $environment;
	if(!function_exists('parse_javascript_gmt_date'))require($FUNCTION_ROOT.'/function_parse_javascript_gmt_date_v120.php');
	if($postLoginTime=parse_javascript_gmt_date($postTime, $_SERVER['HTTP_USER_AGENT'])){
		$_SESSION['loginTimeFulltext']=date('Y-m-d H:i:s',$postLoginTime);
		$_SESSION['loginTime']=preg_replace('/[^0-9]*/','',$_SESSION['loginTimeFulltext']);
		$_SESSION['loginTimeTZ']=$parse_javascript_gmt_date['TZ'];
		$_SESSION['loginTimeTZString']=$parse_javascript_gmt_date['TZString'];
		$_SESSION['loginTimeSource']='User Agent';
		$loginTimeVariance =
		$_SESSION['env']['timeVariance']=
			$postLoginTime - $parse_javascript_gmt_date['TZ']*3600 - /*adjusted post time */
			(time() - ($RelateBaseServerTZDifference * 3600)) /* adjusted server time */;
	}else{
		//can't set time zone, and use system datestamp as login time
		$_SESSION['loginTimeFulltext']=$dateStamp;
		$_SESSION['loginTime']=$timeStamp;
		$_SESSION['loginTimeTZ']='';
		$_SESSION['loginTimeTZString']='';
		$_SESSION['loginTimeSource']='Server';
		$_SESSION['env']['timeVariance']='unknown';
	}
	
	
	/*
	//---------------------- handle machine identification ----------------------
	2011-07-25: what I refer to as a "machine" is actually drilled all the way down to a browser VERSION.  And to make it really tough, sometimes I use one monitor and sometimes I use two.  A same person might have several m cookies on browsers and several m entries, but it is from multiple browsers or browser versions.  But bais_UniversalMachines merges multiple machines as one person, even if they don't represent the same computer.  There is NO REAL WAY TO DO THAT, unless the user positively identifies that computer.
	*/
	if(($m=$_COOKIE['m']) && ($Machines_ID=q("SELECT ID FROM system_machines WHERE UniqueKey='$m'", O_VALUE))){
		//OK - there is a match - we are synching up
		$updateMachine=true;
	}else{
		if($m=$_COOKIE['m']){
			//reconstitute it; where did it go - THIS PROCESS COULD BE ITERATIVE BASED ON HAVING THEM IDENTIFY - do we have a machine with the same IP address and a screen match (better) and browser match (best)
			if(false)mail($developerEmail, 'cookies.m is present but no longer matches; notice file '.__FILE__.', line '.__LINE__,get_globals(),$fromHdrBugs);
		}
		if(!$_COOKIE['m']){
			$m=md5(time().rand(1,1000000));
			setcookie('m',$m,time()+(24*3600*180),'/' /*not used ,'.'.$GCUserName.'.simple-fostercare.com'*/);
		}
		if($Machines_ID=q("SELECT ID FROM system_machines WHERE UniqueKey='$m'", O_VALUE)){
			//OK
			$updateMachine=true;
			#mail($developerEmail,'machine key found',$qr['query'],$fromHdrBugs);
		}else if($Machines_ID=q("SELECT ID FROM system_machines WHERE UserAgent='".addslashes($GLOBALS['HTTP_USER_AGENT'])."' AND IPAddress='".$GLOBALS['REMOTE_ADDR']."' AND MonitorResolution='".$environment."'", O_VALUE)){
			//OK
			#mail($developerEmail,'machine key found by environment',$qr['query'],$fromHdrBugs);
			$updateMachine=true;
		}else{ /* would like to know if values are physically passed/javascript works */
			if(!$environment)mail($developerEmail, 'Error file '.__FILE__.', line '.__LINE__,get_globals(),$fromHdrBugs);
			//note that here we can proliferate machines - they can be at a remote office with a different IP, use a dual monitor, delete or expire cookies, etc.
			$Machines_ID=q("INSERT INTO system_machines SET
			UniqueKey='$m',
			IPAddress='".$GLOBALS['REMOTE_ADDR']."',
			UserAgent='".addslashes($GLOBALS['HTTP_USER_AGENT'])."',
			MonitorResolution='".$environment."',
			".($loginTimeVariance ? "TimeVariance='$loginTimeVariance',":'')."
			".($parse_javascript_gmt_date['TZ'] ? "TimeZone='".$parse_javascript_gmt_date['TZ']."',":'')."
			Comments='Added by login/machine_identification.php line ".__LINE__."'",O_INSERTID);
			#mail($developerEmail,'machine key added',$qr['query'],$fromHdrBugs);
		}
	}
	if($updateMachine){
		q("UPDATE system_machines SET
		IPAddress='".$GLOBALS['REMOTE_ADDR']."',
		UserAgent='".addslashes($GLOBALS['HTTP_USER_AGENT'])."',
		MonitorResolution='$environment',
		".($loginTimeVariance ? "TimeVariance='$loginTimeVariance',":'')."
		".($parse_javascript_gmt_date['TZ'] ? "TimeZone='".$parse_javascript_gmt_date['TZ']."',":'')."
		Comments='Updated by login/index.php line ".__LINE__."'
		WHERE ID=$Machines_ID");
	}
	//join machine to user
	q("REPLACE INTO bais_UniversalMachines SET ma_unusername='".sun()."', Machines_ID=$Machines_ID, Comments='SQL REPLACE; login/machine_identification.php line ".__LINE__."'");
	//--------------------------------- end machine identification ----------------------------------
}
?>