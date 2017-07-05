<?php
$functionVersions['mail_merge_logic_i1']=2.00;
function mail_merge_logic_i1($local){
	global $rd, $mailMergeError, $developerEmail, $fromHdrBugs;
	ob_start();
	eval( '$x=('.$local['argument'].');' );
	$err=ob_get_contents();
	ob_end_clean();
	if($err){
		//globalize error condition so mail is not sent out
		$mailMergeError=true;
		return '[CODING ERROR!!! SEE NOTES]<!-- coding: '.$local['argument'].'--><!-- server response: '.
		str_replace('<b>','',
		str_replace('</b>','',
		str_replace('<br />','',
			$err
		))).'-->';
	}else{
		$condition=($x?'ifTrue':'ifFalse');
		return string_analyzer_i1($local[$condition]);
	}
}
?>