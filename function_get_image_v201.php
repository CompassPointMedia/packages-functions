<?php
$functionVersions['get_image']=1.00;
function get_image($name, $images='', $options=array()){
	/*
	2010-04-21: 
	* added $NAImage available to be passed in options, must have following nodes:
		name
		width
		height
		extension
	* added var $precedence, default = png,jpg,gif,svg
	* returnmethod - default=name
	
	2008-06-30: gets image from get_file_assets() array - by precedence
	options
	imagePrefix
	imageSuffix
	externalImageFunction
	*/
	global $get_image, $defaultNAImage, $defaultNAImageWidth, $defaultNAImageHeight,$test;
	extract($options);
	unset($get_image);
	if(!$precedence)$precedence=array('png','jpg','gif','svg');
	if(!isset($regexFilter))$regexFilter='[^-a-z0-9_]+';
	if(!isset($setGetImageDefault))$setGetImageDefault=true;

	if($externalImageFunction){
		//Added 2008-10-31 - this allows an external function to process, it must globalize $get_image - with nodes of name (case-sensitive), width, and height.  The source returned must be the actual path to the image plus name
		return $externalImageFunction($name, $images='', $options);
	}
	//assume image array = $images if not explicitly passed
	if(!$images)global $images;
	if(!count($images))return;
	foreach($images as $n=>$v){
		//n in form of full file name
		if(!preg_match('/\.(gif|jpg|png|svg|jpeg)$/i',$n))continue;
		$key=preg_replace('/\.(gif|jpg|png|svg|jpeg)$/i', '', $n);
		if($regexFilter){
			$key=preg_replace('/'.$regexFilter.'/i','',$key);
			$name=preg_replace('/'.$regexFilter.'/i','',$name);
		}
		if(strtolower($key)==strtolower($name)){
			$get_image=$v;
			$get_image['array_key']=$n;
			break;
		}
	}	
	if(!$get_image && $setGetImageDefault){
		$get_image=($NAImage ? $NAImage : array(
			'name'=>($defaultNAImage ? $defaultNAImage : 'spacer.gif'),
			'width'=>($defaultNAImageWidth ? $defaultNAImageWidth : '250'),
			'height'=>($defaultNAImageHeight ? $defaultNAImageHeight : '250'),
			'extension'=>($defaultNAImageExt ? $defaultNAImageExt : 'gif'),
			'status'=>'not available'
		));
	}
	$get_image['extension']=end(explode('.',$get_image['name']));
	if($returnmethod=='array'){
		return $get_image;
	}else{
		return $get_image['name'];
	}
}
?>