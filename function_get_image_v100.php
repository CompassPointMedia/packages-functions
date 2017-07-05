<?php
$functionVersions['get_image']=1.00;
function get_image($seed,$list='',$options=array()){
	/*
	2009-08-17: designed to find an image in a file array based on a relationship
	options:
	--------
	return - single|multiple, default=single; if multiple we return all (and if relationship not specified, including _text images with a flag alternate=(text) included)
	comparison - can be a regular expression or a recognized keyword; if blank we look for an exact match
	*/
	global $get_image;
	extract($options);
	if(!$defaultImgArray)$defaultImgArray='pictures';
	if(!$imgExtensions)$imgExtensions='(png|jpg|gif)';
	if(!$ignoreCharacters)$ignoreCharacters=' .,';
	if(!$return)$return='single';
	if(!is_array($list) && strlen($list)){
		$get_image['source_dir']=$list;
		$list=get_file_assets($list);
	}else if(count($list)){
		//OK
	}else{
		global $$defaultImgArray;
		$list=$$defaultImgArray;
	}
	if(!count($list))return false;
	
	foreach($list as $n=>$v){
		if(!preg_match("/^([^_]+)(_(.*))*\.$imgExtensions$/",$n,$a))continue;
		$base=$a[1] . ($ignoreAlternates ? $a[2] : '');
		if(preg_replace('/['.$ignoreCharacters.']/','',strtolower($seed))==preg_replace('/['.$ignoreCharacters.']/','',strtolower($base))){
			$imgs[$n]=$v;
			if($a[2])$imgs[$n]['alternate']=substr($a[2],1);
		}
	}
	if($imgs){
		if(count($imgs)>1)$imgs=subkey_sort($imgs,'alternate');
	}
	if($return=='multiple'){
		return $imgs;
	}else if(count($imgs)){
		$a=current($imgs);
		return $a['name'];
	}
	return false;
}
?>