<?php
$functionVersions['quasi_resource']=1.00;
function quasi_resource(){
	/* 2009-05-13 - this function is only used to my knowledge for LA Classic estates as legacy */
	global $qx, $dbPfx;
	$qx[defCnxMethod]=C_DEFAULT;
	//returns a quasi resource for the user based on this session
	if(!$_SESSION[admin][userName])exit('Function quasi_resource() called without Session.admin.userName set and cannot be used');
	if(!$_SESSION[sessionKey] || !$_SESSION['admin'][currentResourceIndex])exit('Function quasi_resource called without Session.sessionKey or Session.admin.currentResourceIndex');
	$key=substr($_SESSION[sessionKey],0,8);
	//we purge null type resources BY THIS USER from another session (means, not completed)
	q("DELETE FROM bais_resources WHERE re_rtid IS NULL AND re_creator='".$_SESSION[admin][userName]."' AND re_session !='$key'");
	//we should not need to purge previous resources in this session
	if($a=q("SELECT re_id FROM bais_resources WHERE re_creator='".$_SESSION[admin][userName]."' AND re_rtid IS NULL AND re_session ='$key' AND re_sessionidx!='".$_SESSION[admin][currentResourceIndex]."'",O_VALUE)){
		mail('reroute@compasspointmedia.com','Bad inter-session resource for user '.$_SESSION[systemUserName],'File '.__FILE__.', line: '.__LINE__.', \n This means that a previous resource never got a type (re_rtid) assigned and can be presumed incomplete\n Total count: '.$qr['count'],'From: bugreports@reasons.org');
	}
	if(!($re_id=q("SELECT re_id FROM bais_resources WHERE re_session='$key' AND re_sessionidx='".$_SESSION['admin'][currentResourceIndex]."' AND re_creator='".$_SESSION[admin][userName]."'",O_VALUE))){
		//insert this session user
		$re_id=q("INSERT INTO bais_resources SET
		re_session='$key',
		re_sessionidx='".$_SESSION['admin'][currentResourceIndex]."',
		re_rtid=NULL,
		re_name='[pending resource for: ".addslashes($_SESSION[admin][firstName] . ' ' . $_SESSION[admin][lastName]). "]',
		re_createDate='$dateStamp',
		re_creator='".$_SESSION[admin][userName]."'", O_INSERTID);
	}
	return $re_id;
}
?>