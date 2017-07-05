<?php
$functionVersions['iframe_shutdown']=1.00;
function iframe_shutdown(){
	global $store_html_output, $assumeErrorState, $developerEmail, $fromHdrBugs;
	?><script language="javascript" type="text/javascript">
	//notify the waiting parent of success, prevent timeout call of function
	window.parent.submitting=false;
	try{
		window.parent.document.getElementById('SubmitStatus1').innerHTML='&nbsp;';
	}catch(e){ }
	try{
		window.parent.document.getElementById('uploadStatus1').innerHTML='&nbsp;';
	}catch(e){ }
	</script><?php

	if(!$assumeErrorState){
		flush();
		return false; //that's all, folks
	}

	//handle errors
	?><script language="javascript" type="text/javascript">
	//for the end user - you can improve this rather scary-sounding message
	alert('We\'re sorry but there has been an abnormal error while submitting your information.  Please refresh the page by pressing F5 or Ctrl-R and try again');
	</script><?php

	//we also mail that this has happened
	$mail='File: '.__FILE__."\n".'Line: '.__LINE__."\n";
	$mail.="There has been an abnormal shutdown in this page.  Attached are the environment variables:\n\n";
	if($_GET){
		ob_start();
		echo "Query String Values:\n";
		print_r($_POST);
		$mail.=ob_get_contents() . "\n\n";
		ob_end_clean();
	}
	if($_POST){
		ob_start();
		echo "Form Post:\n";
		print_r($_POST);
		$mail.=ob_get_contents() . "\n\n";
		ob_end_clean();
	}
	//Page Output - normally we print out results after each SQL query for example
	if($store_html_output){
		$mail.=$store_html_output . "\n\n";
	}
	//Globals - you may find this unnecessary if your process outputting was good
	$printGlobals=true;
	if($printGlobals){
		ob_start();
		echo "Global Environment Variables:\n";
		print_r($GLOBALS);
		$mail.=ob_get_contents() . "\n\n";
		ob_end_clean();
	}
	
	//send email notification
	mail($developerEmail,'Abnormal shutdown', $mail, $fromHdrBugs);
	return true;
}
function store_html_output($buffer){
	//PHP sends the output buffer before shutting down (error or otherwise).  This catches the buffer prior to shutdown
	global $store_html_output;
	$store_html_output=$buffer;
	return $buffer;
}
?>