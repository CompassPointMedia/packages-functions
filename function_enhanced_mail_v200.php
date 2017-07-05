<?php
/*******************************************************
v200 - 2006-12-06: resolving the "ghost" attachment seen 





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
$fileArray[]='FullmanLaw.JPG';
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
	
	enhanced_mail('sam-git@compasspointmedia.com','test sam\'s mail program','information <b>here</b>','form@relatebase.com','plain', $fileArray, 0);

}
*****/

//maybe the server is combining the messages because it makes more sense to do so!
$functionVersions['enhanced_mail']=2.00;
function enhanced_mail($to, $subject, $body, $from, $mode='html', $fileArray='', $important=0, $preHeaders='', $postHeaders='', $output='mail', $fSwitchEmail=''){
	global $mimeTypes, $fileMemory, $enhanced_mail;
	$mode=strtolower($mode);
	if($mode=='plain' && !$enhanced_mail['warningSent']){
		$enhanced_mail['warningSent']=true;
		ob_start();
		print_r($GLOBALS);
		$out=ob_get_contents();
		ob_end_clean();
		mail('sam-git@compasspointmedia.com','plain text sent!','A plain-text email was sent out, see below'."\n\n$out",'From: bugreports@relatebase.com');
	}
	//mime types, incomplete list
	$mimeTypes = array(
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
			
			if(!$fileMemory[$fileatt[$i]['full']]){
				$fp=@fopen($v,'r');
				$data=@fread($fp,filesize($v));
				$data = chunk_split(base64_encode($data));
				@fclose($fp);
				$fileatt[$i]['data'] = $data;
				$fileMemory[$fileatt[$i]['full']]=$data;
			}else{
				$fileatt[$i]['data'] = $fileMemory[$fileatt[$i]['full']];
			}
		}	
	}

	// STEP #1. Add the headers for a file attachment type email -- appears to work OK if no file attachment as well
	if($preHeaders){
		//postHeaders can be multi-line but should carry their own \r\n between lines
		$headers .= trim($preHeaders) . "\r\n";
	}
	$headers .= "From: $from";
	$headers .= "\nMIME-Version: 1.0\n" . 
		 "Content-Type: multipart/mixed;\n" . 
		 " boundary=\"{$mime_boundary}\"";
	if($important){
		$headers .= "\r\nX-Priority: 1 (Highest)";
		$headers .= "\r\nX-MSMail-Priority: High";
		$headers .= "\r\nImportance: High\r\n";
	}
	$headers .= "\r\nX-Mailer: PHP/" . phpversion(). "\r\n";
	if($postHeaders){
		//postHeaders can be multi-line but should carry their own \r\n between lines
		$headers .= trim($postHeaders) . "\r\n";
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
	
	// STEP #5. Send the email
	if($fSwitchEmail){
		$result = @mail($to, $subject, $message, $headers, "-f $fSwitchEmail"); 
	}else{
		$result = @mail($to, $subject, $message, $headers); 
	}
	$result;
}
?>