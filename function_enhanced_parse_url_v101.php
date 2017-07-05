<?php
$functionVersions['enhanced_parse_url']=1.01;
function enhanced_parse_url($parse,$options=array()){
	/**
	2008-07-19: 
	version 1.01.  big changes in the parsing order; also files assumed to be files based on having an extension like html, php, etc.
	**/
	
	global $enhanced_parse_url;
	$enhanced_parse_url=array();
	$enhanced_parse_url['rawdata']=$parse;
	extract($options);
	if($parse==''){
		return false;
	}else if($parse=='.'){
		$enhanced_parse_url['file']='';
		$enhanced_parse_url['path']='';
		return true;
	}else if($parse=='..'){
		$enhanced_parse_url['file']='';
		$enhanced_parse_url['uplevel']=1;
		return true;
	}
	// handle fragment
	$result= strrpos($parse, '#');
	if(strlen($result)){
		$pos= -(strlen($parse)-$result);
		if($pos+1<0){
			$enhanced_parse_url['fragment']=substr($parse,$pos+1);
			$parse=substr($parse,0,$pos);
		}else{
			//0
			$parse=substr($parse,0,strlen($parse)-1);
		}
	}
	
	// handle querystring
	$result= strpos($parse, '?');
	if(strlen($result)){
		if($result+1==strlen($parse)){
			//no query string
			$parse=substr($parse,0,strlen($parse));
		}else{
			$enhanced_parse_url['query']=substr($parse,$result+1);
			$parse=substr($parse,0,$result);
		}
	}

	//remove the protocol and domain from the front
	#note: regex for file: needs to handle a triple-backslash character as well
	$schema=array(
		'file:///'=>'/^file:((\/\/\/)|((\\\){3}))/i',
		'http://'=>'/^http:\/\//i',
		'https://'=>'/^https:\/\//i',
		'ftp://'=>'/^ftp:\/\//i',
		'mailto:'=>'/^mailto:/i'
	);
	$schemaL=array(
		'file:///'=>8,
		'http://'=>7,
		'https://'=>8,
		'ftp://'=>6,
		'mailto:'=>7
	);
	foreach($schema as $n=>$v){
		if(preg_match($v,$parse)){
			$enhanced_parse_url['protocol']=$n;
			$parse=substr($parse, $schemaL[$n], strlen($parse)-$schemaL[$n]);
			break;
		}
	}
	if($enhanced_parse_url['protocol']=='http://' || $enhanced_parse_url['protocol']=='https://' || $enhanced_parse_url['protocol']=='ftp://'){
		//extract the domain name
		if($pos=strpos($parse,'/')){
			if($stripWWW){
				$enhanced_parse_url['domain']=preg_replace('/^www\./i','',strtolower(substr($parse,0,$pos)));
				if(preg_match('/^www\./i',$parse))$enhanced_parse_url['www_flag']=1;
			}else{
				$enhanced_parse_url['domain']=strtolower(substr($parse,0,$pos));
			}
			//this leaves the forward slash for uniformity
			$parse=substr($parse,$pos,4096);
		}else{
			//only domain remains
			$enhanced_parse_url['domain']=strtolower($parse);
			return true;
		}
	}else if($enhanced_parse_url['protocol']=='mailto:'){
		//this seems appropriate at this point, should not be any more components
		$enhanced_parse_url['email']=trim($parse);
		$enhanced_parse_url['identity']='Email address';
		return true;
	}

	/******************
	we could have:
	file.php
	/file.php
	./file.php
	../file.php
	../../file.php
	path/to/file.php
	/path/to/file.php
	./path/to/file.php
	../path/to/file.php
	../../path/to/file.php
	path/to/
	path/to 
	if the last character is a /, the file is default (directory_default)
	******************/
	
	//remove the level indicators
	if(preg_match('/^([.\/]+)/',$parse,$a)){
		$enhanced_parse_url['level_indicators']=$a[1];
		if(substr($a[1],0,1)=='/'){
			$enhanced_parse_url['positioning']='absolute';
		}else if(substr($a[1],0,3)=='../'){
			$enhanced_parse_url['positioning']='relative';
		}else{
			$enhanced_parse_url['positioning']='samefolder';
		}
		switch(true){
			case $a[1]=='./':
				$parse=substr($parse,2,strlen($parse)-2);
				break;
			case $a[1]=='/':
				$parse=substr($parse,1,strlen($parse)-1);
				break;
			default:
				// ../../../ etc.
				$enhanced_parse_url['uplevel']=strlen($a[1])/3;
				$parse=substr($parse,strlen($a[1]),strlen($parse)-strlen($a[1]));
		}
	}
	
	//now we are left with path and file
	if(strlen($parse)==0){
		$enhanced_parse_url['file']='(directory_default)';
		return true;
	}else if(preg_match('/\/$/',$parse)){
		$enhanced_parse_url['file']='(directory_default)';
		$parse=substr($parse,0,strlen($parse)-1);
		$haveFile=true;
	}
	if($haveFile){
		$enhanced_parse_url['path']=$parse;
		return true;
	}
	//analyze final position
	$a=explode('/',$parse);
	$possible=$a[count($a)-1];
	if($enhanced_parse_url['query'] || $enhanced_parse_url['fragment'] || preg_match('/\.(htm|html|asp|php|jsp|asp|txt|pdf|xls|jpg|gif|png|css|js)([0-9]*)$/i',$possible)){
		$enhanced_parse_url['file']=array_pop($a);
		$f=explode('.',$enhanced_parse_url['file']);
		if(count($f)>1)$enhanced_parse_url['extension']=strtolower($f[count($f)-1]);
		if(count($a)>0)$enhanced_parse_url['path']=implode('/',$a);
		return true;
	}
	$enhanced_parse_url['path']=$parse;
	return true;
}
?>