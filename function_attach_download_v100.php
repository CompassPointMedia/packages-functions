<?php
$functionVersions['attach_download']=1.00;
function attach_download($file='', $string='', $nameAs='', $type='', $options=array()){
	/*
	2012-09-07:
	introduced options
		suppressExit default false
	2012-05-15: globalized $suppressNormalIframeShutdownJS, $assumeErrorState so this is a one-step end to a code block;
	*/
	extract($options);
	global $attach_download, $suppressNormalIframeShutdownJS, $assumeErrorState;
;
	$mimeTypes = array(
		/*********** NOT SEPCIFIED **********/
		'unspecified' => 'application/octet-stream',
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
		'exe' => 'application/msdownload',
		'iif' => 'text/plain'
	);
	if(!($file || ($string && $nameAs))){
		$attach_download['err']='Must pass either a file path(1) or string(2) with a nameAs variable(3)';
	}
	if($file){
		ob_start();
		$fp = fopen($file, "r");  
		$fileSize=@filesize($file);
		$filedata = @fread($fp, $fileSize);  
		@fclose($fp);
		$stream=ob_get_contents();
		ob_end_clean();
		unset($fp);
		!$string?$attach_download['err']='Unable to open specified file, or zero length':'';
		!$string?mail('sam-git@samuelfullman.com','Failed to open file (function attach_download())','function_attach_download','From: reroute@compasspoint-sw.com'):'';
	}else if($string){
		$stream=$string;
		$fileSize=strlen($stream);
	}
	if(!$nameAs && $file){
		$a=explode('/',$file);
		$nameAs=$a[count($a)-1];
	}
	if(!$headerSet){
		header ("Accept-Ranges: bytes");  
		header ("Connection: close");  
		header ("Content-type: $fileType");  
		header ("Content-Length: ". $fileSize);   
		header ("Content-Disposition: attachment; filename=\"$nameAs\"");
	}
	if(!$suppressExit){
		$suppressNormalIframeShutdownJS=true;
		$assumeErrorState=false;
	}
	echo $stream;
	if(!$suppressExit)exit;
}
?>