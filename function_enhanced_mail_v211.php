<?php
/*******************************************************
v2.11 - 2012-11-25: long time since modifying this!
	* began replacing \n with \r\n
	* cleaned up headers build and here are headers set:
		From
		MIME-Version
		Content-Type (2 lines)
		*X-Priority
		*X-MSMail-Priority
		*Importance
		X-Mailer
		
v210 - 2010-02-22: added maillog to database & better return
added emTest and emTestAction
v200 - 2006-12-06: resolving the "ghost" attachment seen 


* new way to call is $result=enhanced_mail($options=array(
 	'to'=> $to,
	'subject'=> $subject,
	'body'=> $out,
	'from'=> $from,
	'mode'=> 'html', [default; or plain/plaintext]
	'fileArray'=> [array of absolute file paths],
	'important=> [1|0: default 0]
	'preHeaders'=> [optional]
	'postHeaders=> [optional]
	'output=>'mail' [default] || queue
	'fSwitchEmail'=> [default=same as $from]
	
	-- new options --
	'emTest' => [1 - treat as a test and then reset the value to 0 | 2 - treat as a test and do NOT reset the value]
	'emTestAction' => [returnParams - return the params passed to the function itself except body | returnParamsAll - same as returnParams but with body included | shunt=someone@email.com]
	'creator'=> [varies by application used in]
	'cnx'=> [optional: e.g. array(host,username,password,database), if !specified defCnxMethod will be used]
	'logmail'=> [true - not needed if you set $enhanced_mail['logmail']=true globally]
	'mailedBy'=> can be session.admin.username, PHP_AUTH_USER, etc.
	'maillogNotes'=> 
	'templateSource'=>
	'maillogTable'=>[default relatebase_maillog]
 ));


THIS IS THE MOST COMPACT SOLUTION FOR SENDING ATTACHMENTS, AND 
HTML OR PLAIN EMAIL, AND ADDING IMPORTANT IF NECESSARY. I GOT
THIS ORIGINALLY FROM KEVIN YANK ON 2003-08-08.

THIS IS ALSO THE FIRST TIME I'VE REALLY UNDERSTOOD THE CONCEPTS...

Here's the function to come out of this:
rb_mail($to, $subj, $body, $from, $mode=plain, $fileArray='', $important=0, $preHeaders='', $postHeaders='', $output='mail');


BTW here's a sample use of Content-base: and Content-location:
Content-base: "http://relatebase.com/"
Content-location: "new/"
This places all the links and images relative to this /new/ directory
-- HAVEN'T TESTED THIS IN OUTLOOK



To do:
1. handle improper email address being passed
2. can I actually do Bcc?
3. handle bad file names gracefully, and report something -- get a reporting system
4. if $output=='string', then a global $mailString will be declared, but the function will still report IAW the system.
*******************************************************/

/*****
$fileArray[]='index.php';
$fileArray[]='emerge.php';
$fileArray[]='2000StockPlanRev1-0.doc';
$fileArray[]='index.gif';
$fileArray[]='JohnWest.mov';
$fileArray[]='mustReviewThis.css';
$fileArray[]='putty.exe';
unset($fileArray);
$fileArray[]='ReferralAgreement.pdf';
$fileArray[]='ReferralAgreement2.pdf';
$fileArray[]='ReferralAgreement3.pdf';
$fileArray[]='ReferralAgreement4.pdf';


for($i=1;$i<=10;$i++){
	//send out emails to me and luanne
	echo date('His')."<br>";
	#enhanced_mail('luanne@relatebase.com','test sam\'s mail program','information <b>here</b>','form@relatebase.com','plain', $fileArray, 0);
	
	enhanced_mail('reroute@compasspointmedia.com','test sam\'s mail program','information <b>here</b>','form@relatebase.com','plain', $fileArray, 0);

}
*****/

//maybe the server is combining the messages because it makes more sense to do so!
$functionVersions['enhanced_mail']=2.10;
function enhanced_mail($to, $subject='', $body='', $from='', $mode='html', $fileArray='', $important=0, $preHeaders='', $postHeaders='', $output='mail', $fSwitchEmail=''){
	global $mimeTypes, $enhanced_mail;
	global $qr, $qx, $fl, $ln, $developerEmail, $fromHdrBugs;
	$a=func_get_args();
	if(count($a)==1){
		//passing new options method
		extract($a[0]);
		if(!$output)$output='mail';
		if(!$mode)$mode='html';
	}
	//2010-02-22: get logmail var, true=log send in the database
	if(isset($logmail)){
		//OK
	}else if(isset($enhanced_mail['logmail'])){
		$logmail=$enhanced_mail['logmail'];
	}
	unset($enhanced_mail['errors'], $enhanced_mail['notices']);

	$mode=strtolower($mode);

	//mime types, incomplete list
	if(!$mimeTypes)$mimeTypes = array(
		/*********** NOT SEPCIFIED **********/
		'(unspecified)' => 'application/octet-stream',
		/*********** basic files **********/
		'txt' => 'text/plain',
		'php' => 'application/octet-stream',
		'htm' => 'text/html',
		'html' => 'text/html',
		'css' => 'text/css',
		'js' => 'application/octet-stream',
		/*********** image files **********/
		'gif' => 'image/gif',
		'jpg' => 'image/jpg',
		'jpe' => 'image/jpg',
		'png' => 'image/png',
		'tif' => 'image/tiff',
		'tiff' => 'image/tiff',
		/*********** applications **********/
		'xls' => 'application/vnd.ms-excel',
		'doc' => 'application/msword',
		'mov' => 'video/quicktime',
		'pdf' => 'application/pdf',
		'exe' => 'application/msdownload'
	);
	//for future encoding for quoted-printable
	$quotedPrintable=array('txt','php','htm','html','css','js');

	//mime boundary
	$mime_boundary = "==Multipart_Boundary_x".md5(time())."x"; 

	//NOTE: added for the send_mail final script --------------
	global $fileArrayName;

	//attachments
	if($fileArray){
		if(is_array($fileArray)){
			$fArray=$fileArray;
		}else{
			$fArray[]=$fileArray;	
		}
		//now set everything in array
		foreach($fArray as $v){
			//filter blank values
			if(!trim($v))continue;
			$i++;
			#get file name and path
			if(stristr($v,'/')){
				$g=strrpos($v,'/');
				$fileName=substr($v,$g+1,strlen($v)-$g);
				$path=substr($v,0,$g);
			}else{
				$fileName=$v;
			}
			#get extension
			$h=strrpos($fileName,'.');
			
			if(strlen($h)){ //it can even be at position zero
				$ext=substr($fileName,-(strlen($fileName)-$h-1));
			}else{
				$ext='(unspecified)';
			}
			
			//NOTE: added for the send_mail final script (note $fileArrayName) --------------
			$fileatt[$i]['name'] = ($fileArrayName[$v]?$fileArrayName[$v]:$fileName) ;

			//NOTE: added for the send_mail final script --------------
			if(trim($fileArrayName[$v])!=''){
				$a=explode('.',$fileArrayName[$v]);
				$ext=$a[count($a)-1];
			}

			$fileatt[$i]['ext']  = $ext;
			$fileatt[$i]['type'] = $mimeTypes[$ext];
			$fileatt[$i]['path'] = $path;
			$fileatt[$i]['full'] = $v;
			
			if(!$enhanced_mail['fileMemory'][$fileatt[$i]['full']]){
				ob_start();
				$fp=@fopen($v,'r');
				$data=@fread($fp,filesize($v));
				$err=ob_get_contents();
				ob_end_clean();
				if($err)mail($developerEmail, 'Error file '.__FILE__.', line '.__LINE__,get_globals('Unable to open file attachment '. $v),$fromHdrBugs);
				$data = chunk_split(base64_encode($data));
				@fclose($fp);
				$fileatt[$i]['data'] = $data;
				$enhanced_mail['fileMemory'][$fileatt[$i]['full']]=$data;
			}else{
				$fileatt[$i]['data'] = $enhanced_mail['fileMemory'][$fileatt[$i]['full']];
			}
		}	
	}

	/* STEP #1. Add the headers for a file attachment type email -- appears to work OK if no file attachment as well.
	
		
	*/
	$headers=''; //initial condition
	if($preHeaders){
		//postHeaders can be multi-line but should carry their own \r\n between lines
		$headers .= trim($preHeaders) . "\r\n";
		if(preg_match('/reply-to:(.+)/i',$preHeaders,$a)){
			$replyTo=trim($a[1]);
		}
	}
	$headers .= "From: ".preg_replace('/^From:\s+/i','',trim($from))."\r\n";
	$headers .= "MIME-Version: 1.0\r\n" . 
		 "Content-Type: multipart/mixed;\r\n" . 
		 " boundary=\"{$mime_boundary}\"\r\n";
	if($important){
		$headers .= "X-Priority: 1 (Highest)\r\n";
		$headers .= "X-MSMail-Priority: High\r\n";
		$headers .= "Importance: High\r\n";
	}
	$headers .= "X-Mailer: PHP/" . phpversion(). "\r\n";
	if($postHeaders){
		//postHeaders can be multi-line but should carry their own \r\n between lines
		$headers .= trim($postHeaders) . "\r\n";
		if(preg_match('/reply-to:(.+)/i',$postHeaders,$a)){
			$replyTo=trim($a[1]);
		}
	}
		 
	// STEP #2. add a multipart boundary above the plain message 
	$message = "This is a multi-part message in MIME format.\n\n" . 
					"--{$mime_boundary}\n" . 
					"Content-Type: text/$mode; charset=\"iso-8859-1\"\n" . 
					"Content-Transfer-Encoding: 7bit\n\n" . 
					$body . "\n\n";
	
	// STEP #3. add file attachments to the message 
	if(is_array($fileatt)){
		foreach($fileatt as $index=>$v){
			$message .=  "--{$mime_boundary}\n" .
							 "Content-Type: {$v['type']};\n" . 
							 " name=\"{$v['name']}\"\n" . 
							 "Content-Disposition: attachment;\n" . 
							 " filename=\"{$v['name']}\"\n" . 
							 "Content-Transfer-Encoding: base64\n\n" . 
							 $v['data'] . "\n\n"; 
		}
	}
	
	// STEP #4. cap the message off - key is the '--' at the end
	$message .=  "--{$mime_boundary}--";

	// STEP #5. testing parameters
	if($emTest){
		if(preg_match('/^returnParams(All)*/',$emTestAction,$a)){
			//add to r
			$r['args']=func_get_arg(0);
			if($a[1])unset($r['args']['body']);
			$outcome='testing, action='.$emTestAction;
		}else if(preg_match('/^shunt=/',$emTestAction)){
			$shunt=preg_replace('/^shunt=/','',$emTestAction);
		}
		if($emTest==1){
			//one-time action, reset emTest
			global $emTest;
			$emTest=0;
		}
	}
	
	// STEP #6. Send the email
	if(!$outcome){
		ob_start();	
		if($output=='queue'){
			$Mailqueue_ID=q("INSERT INTO relatebase_mailqueue SET
			To='".addslashes($to)."',
			Subject='".addslashes($subject)."',
			Message='".addslashes($message)."',
			Headers='".addslashes($headers)."',
			FSwitchEmail='".addslashes($fSwitchEmail)."',
			QueueTime=NOW()", O_INSERTID);
		}else{
			if($fSwitchEmail){
				$outcome = @mail(($shunt ? $shunt : $to), $subject, $message, $headers, "-f $fSwitchEmail"); 
			}else{
				$outcome = @mail(($shunt ? $shunt : $to), $subject, $message, $headers); 
			}
		}
		$err=ob_get_contents();
		ob_end_clean();
	}
	if(!$outcome){
		$enhanced_mail['errors']['send']=$err;
		return false;
	}else{
		$enhanced_mail['idx']++;
		$r['idx']=$enhanced_mail['idx'];
		if($output=='queue')$r['queued']=true;
		$r['sendtime']=time();
		$r['sent']=$outcome;

		if($logmail && !preg_match('/testing/',$outcome)){
			$to=explode('<',rtrim($to,'>'));
			if(count($to)>2){
				mail($developerEmail, 'Error file '.__FILE__.', line '.__LINE__,get_globals(),$fromHdrBugs);
				$MailedToEmail=implode('<',$to);
			}else if(count($to)==2){
				$MailedToName=trim($to[0]);
				$MailedToEmail=trim($to[1]);
			}else{
				$MailedToEmail=implode('',$to);
			}
						
			ob_start();
			$fl=__FILE__;
			$ln=__LINE__+1;
			$r['maillog']['ID']=q("INSERT INTO ".($maillogTable ? $maillogTable : ($enhanced_mail['maillogTable'] ? $enhanced_mail['maillogTable'] : 'relatebase_maillog'))." SET 
			MailedToName ='".addslashes($MailedToName)."',
			MailedToEmail ='".addslashes($MailedToEmail)."',
			".($shunt ? "ShuntedToEmail = '".addslashes($shunt)."'," : '')."
			MailedBy = '".addslashes($mailedBy)."', /* this must be passed in options array */
			Subject = '".addslashes($subject)."',
			Content = '".addslashes($message)."',
			FromAs = '".addslashes($fSwitchEmail)."',
			ReplyTo = '".addslashes($replyTo)."',
			SendMethod = '".($mode=='plain' || $mode=='plaintext' ? 'Plaintext' : 'HTML')."',
			Attachments = '".addslashes(@implode("\n",$fArray))."',
			Notes = '".addslashes($maillogNotes)."',
			TemplateSource='".addslashes($templateSource)."',
			CreateDate=NOW(),
			EditDate=NOW(),
			Creator='".
			($creator ? $creator :
			($_SESSION['admin']['userName'] ? $_SESSION['admin']['userName'] : 
			($_SESSION['systemUserName'] ? $_SESSION['systemUserName'] : 
			($_SERVER['PHP_AUTH_USER'] ? $_SERVER['PHP_AUTH_USER'] : 
			 'unknown'))))."'", O_INSERTID, ERR_ECHO, ($cnx ? $cnx : $qx['defCnxMethod']));
			$err=ob_get_contents();
			ob_end_clean();
			if($err){
				$r['errors']['maillog']=$err;
				mail($developerEmail, 'Error file '.__FILE__.', line '.__LINE__,get_globals('Error entering maillog entry: '.$err),$fromHdrBugs);
			}
			$r['maillog']['query']=$qr['query'];
		}
		return $r;
	}
}
?>