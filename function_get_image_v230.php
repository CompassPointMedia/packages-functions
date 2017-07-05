<?php
$functionVersions['get_image']=2.30;
function get_image($names, $images=array(), $options=array()){
	/*
	2013-10-13: UGH..
	normalize - regex WITH the // - This is what operates on the name passed
	targetReplace - regex WITHOUT the // - this is what operates on the files in the array
	
	this function does all kinds of weird things, but the purpose is to find an image file in an array created by get_file_assets. From v2.3 on, you can pass multiple names (without an extension) as an array.  The function returns all matches of the FIRST name match only as a multi-array by default. Or it can return the first node of the first match, or simply the file name that matches.  If the latter it sets get_imagex['params'] for use outside the function
	
	version 2.30:
	2013-05-01: reviewed this function, used again in the products suite for components-juliet.
	* you can now pass $names as a 0-based array of names vs. string - function calls itself recursively till all names are tested
	* does a good job at intuitive translation and preservation of valuable information.
	* this got complicated.  Removed $get_imageReturnMethod.  We now have 3 return methods:
	return:
		1) multi [default]:
		array(
			file1.jpg=>array(
				'name'=>'File1.jpg',
				'width'=> 350, ..
			),
			file1_juliet.jpg=>array(
				'name'=>'FILE1.Juliet.jpg',
				'width'=> 350, ..
				'description'=>'juliet',
			),
		)
		2) string: File1.jpg (first instance)
		3) array: return first node above (file1.jpg) and set $get_imagex['params']

	*options:
		normalize: false, true (converted to regex), or custom regex
		appendage: _something expressed as regex

	2012-05-29: pared down greatly from previous versions
	get_imageReturnMethod=array, string-based lower case just like $images array
	*/
	global $FUNCTION_ROOT, $get_imagex;
	extract($options);
	//2013-05-01: much at names as array until complete
	if(is_array($names)){
		$name=current($names); //string
		foreach($names as $n=>$v){
			unset($names[$n]);
			break;
		}
	}else{
		$name=$names;
		$names=array();//empty it
	}
	if(!isset($targetReplace))$targetReplace=' ';
	if(!strlen($name))return;
	
	if(!$return)$return=($get_imagex['return'] ? $get_imagex['return'] : 'multi');
	
	if(!$precedence)$precedence=array('png','jpg','jpeg','gif','svg');
	if(!$regexFilter)$regexFilter='[^-a-z0-9_]+';
	if($appendage=='_')$appendage='(_(.+))*';

	if(is_string($images)){
		if($a=$get_imagex['locations'][$images]){
			$images=$a;
		}else{
			$images=$get_imagex['locations'][$images]=get_file_assets($images,array('positiveFilters'=>'\.(jpg|gif|png|svg)$',));
		}
	}
	if(@empty($images))return;

	if($externalImageFunction){
		//Added 2008-10-31 - this allows an external function to process, it must globalize $get_image - with nodes of name (case-sensitive), width, and height.  The source returned must be the actual path to the image plus name
		return $externalImageFunction($names, $images, $options);
	}	
	
	if(!($normalize===false))$normalize=(is_bool($normalize) ? '/[^-a-zA-Z0-9]/' : $normalize);
	if($normalize)$name=preg_replace($normalize,'',strtolower($name));

	
	//don't forget about this
	$appendage; //normally (_(.+))*
	if(!$appendageIndex)$appendageIndex=2;
	
	foreach($precedence as $ext){
		//handle special characters
		$str=str_replace('+','\+',$name);
		$str=str_replace('.','\.',$str);
		
		$nameRegex='/^'.$str.$appendage.'\.'.$ext.'$/i';
		foreach($images as $n=>$v){
			if($debug)prn("nameRegex=$nameRegex\ntargetReplace=$targetReplace\nresult=".preg_replace('/'.$targetReplace.'/','',$n));
			if(!preg_match($nameRegex,preg_replace('/'.$targetReplace.'/','',$n),$m))continue;
			if($debug)error_alert('match');
			//note what we use for the key - we switch to case-sensitive
			$get_image[$v['name']]=$v;
			$get_image[$v['name']]['length']=strlen($v['name']);
			if(strlen($m[$appendageIndex]))$get_image[$v['name']]['description']=$m[$appendageIndex];
		}
	}
	if($debug)error_alert(look);
	if(count($get_image)){
		if(!function_exists('subkey_sort'))require($FUNCTION_ROOT.'/function_array_subkey_sort_v203.php');
		$get_image=subkey_sort($get_image,'length');
		//return methods here
		if($return=='multi'){
			return $get_image;
		}else if($return=='string'){
			$get_imagex['params']=current($get_image);
			$a=current($get_image);
			return $a['name'];
		}else if($return=='array'){
			return current($get_image);
		}else exit('improper return method for get_image v2.30');
	}
	
	//2013-05-01: call function recursively with next names present
	if(count($names))return get_image($names, $images, $options);
}
?>