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
	global $get_image, $defaultNAImage, $defaultNAImageWidth, $defaultNAImageHeight;
	extract($options);
	unset($get_image);
	if(!$precedence)$precedence=array('png','jpg','gif','svg');

	if($externalImageFunction){
		//Added 2008-10-31 - this allows an external function to process, it must globalize $get_image - with nodes of name (case-sensitive), width, and height.  The source returned must be the actual path to the image plus name
		return $externalImageFunction($name, $images='', $options);
	}
	//assume image array = $images if not explicitly passed
	if(!$images)global $images;
	switch(true){
		case $a=$images[strtolower($imagePrefix).strtolower($name).strtolower($imageSuffix).'.'.strtolower($precedence[0])]:
			$get_image=$a;
		break;
		case $a=$images[strtolower($imagePrefix).strtolower($name).strtolower($imageSuffix).'.'.strtolower($precedence[1])]:
			$get_image=$a;
		break;
		case $a=$images[strtolower($imagePrefix).strtolower($name).strtolower($imageSuffix).'.'.strtolower($precedence[2])]:
			$get_image=$a;
		break;
		case $a=$images[strtolower($imagePrefix).strtolower($name).strtolower($imageSuffix).'.'.strtolower($precedence[3])]:
			$get_image=$a;
		break;
		default:
			$get_image=($NAImage ? $NAImage : array(
				'name'=>($defaultNAImage ? $defaultNAImage : 'spacer.gif'),
				'width'=>($defaultNAImageWidth ? $defaultNAImageWidth : '250'),
				'height'=>($defaultNAImageHeight ? $defaultNAImageHeight : '250'),
				'extension'=>($defaultNAImageExt ? $defaultNAImageExt : 'gif'),
				'status'=>'not available'
			));
	}
	preg_match('/\.[a-z]+$/i',$get_image['name'],$b);
	$get_image['extension']=str_replace('.','',strtolower($b[0]));
	if($returnmethod=='array'){
		return $get_image;
	}else{
		return $get_image['name'];
	}
}
?>