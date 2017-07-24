<?php
$functionVersions['get_image']=2.20;
function get_image($name, $images=array(), $options=array()){
	/*
	2012-05-29: pared down greatly from previous versions
	get_imageReturnMethod=array, string-based lower case just like $images array
	*/
	global $FUNCTION_ROOT, $get_imagex;
	extract($options);
	
	if(!isset($get_imageReturnMethod))global $get_imageReturnMethod;
	
	if(!$precedence)$precedence=array('png','jpg','gif','svg');
	if(!$regexFilter)$regexFilter='[^-a-z0-9_]+';

	if($externalImageFunction){
		//Added 2008-10-31 - this allows an external function to process, it must globalize $get_image - with nodes of name (case-sensitive), width, and height.  The source returned must be the actual path to the image plus name
		return $externalImageFunction($name, $images=array(), $options);
	}
	if(is_string($images)){
		if($a=$get_imagex['locations'][$images]){
			$images=$a;
		}else{
			$images=$get_imagex['locations'][$images]=get_file_assets($images,array('positiveFilters'=>'\.(jpg|gif|png|svg)$',));			
		}
	}
	if(@empty($images))return;
	if($get_imageReturnMethod=='array'){
		foreach($images as $n=>$v){
			$str1='/^'.preg_replace('/[^-.a-z0-9]/i','',strtolower($name)).'[^-a-z0-9]/i';
			$str2=preg_replace('/[^-_.a-z0-9]/i','',$n);
			if(preg_match($str1,$str2)){
				$get_image[$n]=$v;
			}
		}
		if($get_image){
			ksort($get_image);
			return $get_image;
		}
	}else{
		foreach($precedence as $ext){
			foreach($images as $o=>$w){
				if(preg_match('/^'.preg_replace('/ /','',$name).'(_(.+))*\.'.$ext.'$/i',$o,$m)){
					$possible[$w['name']]=$w;
					$possible[$w['name']]['length']=strlen($w['name']);
					$possible[$w['name']]['description']=$m[2];
				}
			}
		}
		if(!$possible)return;
		if(!function_exists('subkey_sort'))require($FUNCTION_ROOT.'/function_array_subkey_sort_v300.php');
		$possible=subkey_sort($possible,'length');
		if($get_imageReturnMethod=='string'){
			return current(array_keys($possible));
		}else{
			return current($possible);
		}
	}	
}
?>