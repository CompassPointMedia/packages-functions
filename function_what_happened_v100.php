<?php
function what_happened(){
	/* developed 2011-06-22 - getting tired of these emails */
	//start w/nothing
	//what is the user agent
	$typicalBrowsers=array(
	
	);

	//public data database connection
	global $public_cnx, $MASTER_HOSTNAME, $MASTER_USERNAME, $MASTER_PASSWORD, $MASTER_DATABASE;

	$cnx=array( $MASTER_HOSTNAME, $MASTER_USERNAME, $MASTER_PASSWORD, $MASTER_DATABASE );

	if(!function_exists('q')) require($_SERVER['DOCUMENT_ROOT'] . '/functions/function_q_v140.php');
	ob_start();
	print_r($GLOBALS);
	$out=ob_get_contents();
	ob_end_clean();
	if($u=$_SERVER['HTTP_USER_AGENT']){
		if($a=q("SELECT ID AS Browserstrings_ID, Bot FROM aux_browserstrings WHERE Name='".addslashes(substr($u,0,85))."'", O_ROW, $public_cnx, $public_cnx[3])){
			//ok
			extract($a);
			$isBot='IF IT IS A BOT: http://relatebase:secretPassword@relatebase-rfm.com/admin/isbot.php?Browserstrings_ID='.$Browserstrings_ID.'&isbot=1';
			$isNotBot='IF NOT: http://relatebase:secretPassword@relatebase-rfm.com/admin/isbot.php?Browserstrings_ID='.$Browserstrings_ID.'&isbot=0';
			if($Bot){
				//nothing
				mail('sam-git@samuelfullman.com','(DEVELOP THIS NODE) Abnormal error script '.$_SERVER['SCRIPT_FILENAME'].', line '.__LINE__.', file '.__FILE__,$isBot . "\n\n". $isNotBot . "\n\n" . $out,'From: bugreports@'.$_SERVER['HTTP_HOST']);
			}else{
				mail('sam-git@samuelfullman.com','(DEVELOP THIS NODE) Abnormal error script '.$_SERVER['SCRIPT_FILENAME'].', line '.__LINE__.', file '.__FILE__,$isBot . "\n\n". $isNotBot . "\n\n" . $out,'From: bugreports@'.$_SERVER['HTTP_HOST']);
			}
		}else{
			//see if this is a bot
			$Bot=(preg_match('/http(s*):\/\//i',$u)?1:NULL);
			$Browserstrings_ID=q("INSERT INTO aux_browserstrings SET Name='".addslashes(substr($u,0,85))."', Bot='".$Bot."', CreateDate=NOW()", O_INSERTID, $cnx, $public_cnx[3]);
			$isBot='IF IT IS A BOT: http://relatebase:secretPassword@relatebase-rfm.com/admin/isbot.php?Browserstrings_ID='.$Browserstrings_ID.'&isbot=1';
			$isNotBot='IF NOT: http://relatebase:secretPassword@relatebase-rfm.com/admin/isbot.php?Browserstrings_ID='.$Browserstrings_ID.'&isbot=0';
			mail('sam-git@samuelfullman.com','see if this is a bot',$isBot."\n\n".$isNotBot."\n\n".$out,'From: bugreports@'.$_SERVER['HTTP_HOST']);
		}
		q("INSERT INTO aux_browserstrings_hits SET Browserstrings_ID='$Browserstrings_ID', REMOTE_ADDR='".$_SERVER['REMOTE_ADDR']."', GLOBALS='".addslashes($out)."'", $public_cnx[3], $cnx);
		
	}else if(!empty($_SESSION)){
		//someone was logged in
		mail('sam-git@samuelfullman.com','(DEVELOP THIS NODE) Abnormal error script '.$_SERVER['SCRIPT_FILENAME'].', line '.__LINE__.', file '.__FILE__,$out,'From: bugreports@'.$_SERVER['HTTP_HOST']);
	}else if($_SERVER['PHP_AUTH_USER'] && $_SERVER['PHP_AUTH_PW']){
		//in protected folder
		mail('sam-git@samuelfullman.com','(DEVELOP THIS NODE) Abnormal error script '.$_SERVER['SCRIPT_FILENAME'].', line '.__LINE__.', file '.__FILE__,$out,'From: bugreports@'.$_SERVER['HTTP_HOST']);
	}
}
?>