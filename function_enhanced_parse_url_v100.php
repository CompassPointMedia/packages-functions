<?php
$functionVersions['enhanced_parse_url']=1.00;
function enhanced_parse_url($parse){
	/**
	2007-01-14: this is put into service as v1.0 as-is.  I'm using it to handle traffic stats based on understanding that ALL strings will be http: or https:, and also that Apache is going to be adding a / for when we do e.g. relatebase.com/client[/]
	**/
	#known bugs: 
	#.htaccess will put the value in $enhanced_parse_url[rawurl]
	# a folder path mammals/quadrupeds/canines/irish_setters will put "irish_setters" down as the file if no / at the end.  However this is confusing for browsers too.
	
	global $enhanced_parse_url, $sp;
	if($parse=='.'){
		$enhanced_parse_url['file']='';
		$enhanced_parse_url['ext']='';
		$enhanced_parse_url['level']=0;
		$enhanced_parse_url['path']='';
		return 1;
	}elseif($parse=='..'){
		$enhanced_parse_url['file']='';
		$enhanced_parse_url['ext']='';
		$enhanced_parse_url['level']=-1;
		return 1;
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
	/******************
	we could have:
	http://www.relatebase.com/file.php
	file:///D|relatebase.com/file.php
	file.php
	if the last character is a /, the file is default (unknown)
	******************/
	
	//handle file name and extension
	#sp=spacer, this can be enhanced to decide but here the default is /
	!$sp?$sp='/':'';
	$result= strrpos($parse, $sp);
	if(strlen($result)){
		$pos= -(strlen($parse)-$result);
		//account for blank file
		if($pos==-1){
			$enhanced_parse_url['file']='(folder_default)';
			$enhanced_parse_url['extension']='';
		}else{
			$enhanced_parse_url['file']= substr($parse,$pos+1);
			$result= strrpos($parse, '.');
			if(strlen($result)){
				$xpos= -(strlen($parse)-$result);
				$enhanced_parse_url[extension]= strtolower(substr($parse,$xpos+1));
			}else{
				$enhanced_parse_url[extension]='';
			}
			$parse= substr($parse,0,$pos+1);
		}
	
	//this seems appropriate at this point, should not be any more components
	}elseif(strtolower(substr($parse,0,7))=='mailto:'){
		$enhanced_parse_url['scheme']='mailto:';
		$enhanced_parse_url[email]=substr($parse,7,strlen($parse)-7);
		$enhanced_parse_url['identity']='Email address';
		$email=1;
	}
	
	//handle scheme
	#note: regex for file: needs to handle a triple-backslash character as well
	if(!$email){
		$schema=array(
			'file:///'=>'/file:((\/\/\/)|((\\\){3}))/i',
			'http://'=>'/http:\/\//i',
			'https://'=>'/https:\/\//i',
			'ftp://'=>'/ftp:\/\//i'
		);
		$schemaL=array(
			'file:///'=>8,
			'http://'=>7,
			'https://'=>8,
			'ftp://'=>6
		);
		foreach($schema as $n=>$v){
			if(preg_match($v,$parse)){
				$enhanced_parse_url['scheme']=$n;
				$parse=substr($parse, $schemaL[$n], strlen($parse)-$schemaL[$n]);
			}
		}
	}


	#if we have a scheme per the array above, the assumption is the first string is a domain, otherwise it's a path and we'll get to it in the next step
	#note that the lack of a spacer would produce an error -- try just http://www.amazon.com
	#we leave the leading / on to indicate this is root
	#note that for example file:///c:/winnt, it will pull c: as a "domain"
	if($enhanced_parse_url['scheme'] && $enhanced_parse_url['scheme']!='mailto:'){
		$result= strpos($parse, $sp);
		if(strlen($result)){
			$pos= -(strlen($parse)-$result);
			$enhanced_parse_url[domain]= substr($parse,0,$pos);
			$parse= substr($parse,$pos);
		}
	}
	
	//everything else is a path
	#this could include ../{n} or ./{n}
	$enhanced_parse_url['rawpath']=$parse;
	
	#level, interpretation:
	#1. unset = unknown
	#2. -1, -2, etc. 1 or 2 levels above the file containing the href
	#3. +1, +2, etc. 1 or 2 levels down from the file containing the href
	
	#we assume one of the following cases for the path (all I've ever done certainly):
	#1. ../[../]*path1/path2/pathN/ 
	#2. ./[./]*path1/path2/pathN/ -- not used often but it's legal with IE
	#3. path1/path2 -- relative
	#4. /path1/path2 -- absolute
	
	#this only accounts for forward slashes
	if(preg_match('/^(\/[^\/]*)*\//',$parse)){
		if($parse=='/'){
			$enhanced_parse_url[root]=0;
		}else{
			$parse=substr($parse,1,strlen($parse)-1);
			$parse=substr($parse,0,strlen($parse)-1);
			$s=explode('/',$parse);
			$levels=count($s);
			$enhanced_parse_url[root]=$levels;
		}
	}elseif(preg_match('/^([^\/]*\/)*/',$parse)){
		$parse=substr($parse,0,strlen($parse)-1);
		$s=explode('/',$parse);
		//handle ../ instructions
		$levels=0;
		foreach($s as $v){
			if($v=='..'){
				$levels--;
			}elseif($v!=='.'){ //since ./ would do nothing to go above or below
				$levels++;
			}
		}
		$enhanced_parse_url[sub]=$levels;
		return 1;
	}	
}
?>