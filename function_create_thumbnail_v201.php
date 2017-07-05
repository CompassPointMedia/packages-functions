<?php
define('CREATE_THUMBNAIL_DEBUG',1); //email if problems
$functionVersions['create_thumbnail_getneededmemory']=2.00;
function create_thumbnail_getneededmemory($width, $height, $truecolor=1){
	/* this function adjusts memory limit up to max memory allowable in config.php */
	global $create_thumbnail;
	
  return $width*$height*(2.2+($truecolor*3));
}
$functionVersions['create_thumbnail']=2.00;
function create_thumbnail($file, $shrink='', $crop='', $location='', $options=array()){
	/*
	2011-08-20 - added option dimsOnly=true - returns the actual pixel size calc only
	2009-03-24 - modified successful return of create thumbnail to be array(w,h) vs just true
	2008-10-28 - too beautiful to touch - this thing is great!  But I can't do a 2-wall box in one operation, so it is done in two calls to this.  Did do some slight coding mods
	* added option stretchImage for when the image is smaller than the box
	Created 2008-02-11 - bit more of the promised land with image manipulation incl. cropping; this is  a radical departure from v1.00 - parameters passed are different

	if (location=returnresource), the function returns a resource which if passed to the function again, will be sufficient to perform a 2nd operation on the same image. The goal here is to have this function do all the major features that Picasa does, and then store a history of mods to an image somewhere, say in session.  We then can go forward or back through the process via the steps :)

	region is (x,y) for the top left and (x,y) for the bottom right of a crop area - ON THE ORIGINAL DIMENSIONS, not the resized dimensions
	shrink can be a containing box (150,200) or a percentage to shrink (.75)
	*/
	global $cttest, $create_thumbnail, $developerEmail, $fromHdrBugs, $test;
	@extract($options);
	if(is_array($file)){
		$gis=$file[1];
	}else{
		if(!($gis=@getimagesize($file))){
			if(CREATE_THUMBNAIL_DEBUG)mail($developerEmail,'Error file '.__FILE__.', line '.__LINE__,get_globals(),$fromHdrBugs);
			return false;
		}
	}
	//this is an image
	$contentType=$gis['mime'];
	$mime=explode('/',$contentType);
	$mime=$mime[1]; //lower case
	$createFrom='imagecreatefrom'.$mime;
	$output='image'.$mime;
	$width=$gis[0];
	$height=$gis[1];

	// Get box dimensions from $shrink variable
	if(is_array($shrink)){
		$boxWidth=$shrink[0];
		$boxHeight=$shrink[1];
	}else if(preg_match('/^[0-9]+,[0-9]+$/',$shrink)){
		$a=explode(',',$shrink);
		$boxWidth=$a[0];
		$boxHeight=$a[1];
	}else{
		if(!$shrink)$shrink=1;
		//normally a decimal like .33
		$boxWidth=round($width * $shrink);
		$boxHeight=round($height * $shrink);
	}
	//buffer against division by zero
	ob_start();
	if($height > $boxHeight || $width > $boxWidth || $stretchImage){
		if(@$height/$boxHeight > $width/$boxWidth){
			//the object is more prominent y-wise than x-wise compared to the box; use the box height as a basis
			$newHeight=$boxHeight;
			$newWidth= floor($width * ($boxHeight/$height));
			$shrink=round($boxWidth/$width, 5);
		}else{
			//the object is more prominent x-wise than y-wise compared to the box
			$newWidth=$boxWidth;
			$newHeight= floor($height * ($boxWidth/$width));
			$shrink=round($boxHeight/$height, 5);
		}
	}else{
		//we could include a setting here to expand the image to fit the box
		$shrink=1;
		$newHeight=$height;
		$newWidth=$width;
	}
	$err=ob_get_contents();
	ob_end_clean();
	if($err && CREATE_THUMBNAIL_DEBUG)mail($developerEmail,'Error file '.__FILE__.', line '.__LINE__,get_globals(),$fromHdrBugs);

	//2011-08-20
	if($dimsOnly) return array($newWidth,$newHeight,$shrink);
	
	$a=$b=0; //upper left corner
	if($crop){
		if(!is_array($crop)){
			$crop=explode(',',$crop);
			foreach($crop as $n=>$v)$crop[$n]=trim($v);
		}
		list($a,$b,$c,$d)=$crop;
		$x=round(($c-$a) * $shrink);
		$y=round(($d-$b) * $shrink);

		//create new image resource, and modify the getimagesize parameters
		$result = imagecreatetruecolor($x, $y);
		$white = imagecolorallocate($result, 255,255,255);
		imagefill($result, 0,0, $white);
		$gis[0]=$x;
		$gis[1]=$y;
	}else{
		$result = @imagecreatetruecolor($newWidth, $newHeight);
		$white = @imagecolorallocate($result, 255,255,255);
		@imagefill($result, 0,0, $white);
		$gis[0]=$newWidth;
		$gis[1]=$newHeight;
	}
	//----------------------------------
	if(!function_exists($createFrom)){
		mail($developerEmail,'Unrecognized function in create_thumbnail file '.__FILE__.', line '.__LINE__,get_globals(), $fromHdrBugs);
		return false;
	}
	// Resample
	if(is_array($file)){
		$source = $file[0];
	}else{
		if($width*$height * 6.6 > 110*1024*1024){
			echo $width . ':' . $height;
			echo 'cannot allocate memory('.$width*$height * 6.6 . ')';
			return false;
		}else if($width*$height * 6.6 > 40*1024*1024){
			$revertMemory=ini_get('memory_limit');
			ini_set('memory_limit','110M');
		}
		$source = $createFrom($file);
	}
	@$r=imagecopyresampled($result, $source, 0, 0, $a, $b, $newWidth, $newHeight, $width, $height);
	if($revertMemory){
		mail($developerEmail,'Memory limit issue file '. __FILE__.', line '.__LINE__,'Memory usage: '.memory_get_usage()."\n\n".get_globals(),$fromHdrBugs);
		ini_set('memory_limit',$revertMemory);
	}

	// Output
	if($location=='returnresource'){
		//return the modifed image pointer, and the parameters
		return array($result, $gis);
	}else if($location){
		//jpeg, gif, png
		$output($result,$location);
		if(file_exists($location)) return array($newWidth, $newHeight);
		return false;
	}else{
		header('Content-type: '.$contentType); //jpeg, gif, png
		$output($result, null, 100);
		return array($newWidth, $newHeight);
	}
}

$functionVersions['create_thumbnail_scalecrop']=1.00;
function create_thumbnail_scalecrop($path,$g,$w_h,$el_1,$tee_1,$el_2,$tee_2,$options=array()){
	/*
	created 2012-08-07 SF - combo scale and crop function
	location=returnresource || filename || stream(default)
	NOTE that this does not check for valid image, or valid dimensions
	*/
	global $suppressNormalIframeShutdownJS, $assumeErrorState;
	extract($options);
	if ($w_x && $w_x < $g[0]){
		$w=$w_x;
		$h=round($w_x / $g[0] * $g[1], 0);
	}else{
		$w=$g[0];
		$h=$g[1];
	}
	$scaled = imagecreatetruecolor($w, $h); 
	$source = imagecreatefromjpeg($_SERVER['DOCUMENT_ROOT'].$path);
	imagecopyresized($scaled, $source, 0, 0, 0, 0, $w, $h, $g[0], $g[1]);
	//prn(($el_2-$el_1) . ':' .($tee_2 - $tee_1),1);
	$cropped = imagecreatetruecolor($el_2 - $el_1, $tee_2 - $tee_1);
	imagecopy($cropped, $scaled, 0, 0, $el_1, $tee_1, $el_2, $tee_2);
	if(!$location){
		header('Content-Type: image/jpeg'); 
		imagejpeg($cropped); 
		$suppressNormalIframeShutdownJS=true;
		$assumeErrorState=false;
		exit;
	}else if($location=='returnresource'){
		error_alert('not developed');
	}else{
		if(imagejpeg($cropped,$location)) return true;
		return false;
	}
}


/* -------------------------

example:
// The file
$file = '/home/rbase/public_html/admin/file_explorer.1.0.00/balancevases.jpg';
if(!$shrink)$shrink = 1;
if(!$crop)$crop = '160,120,410,370';

//pass 1: crop the image first - easiest on memory
$pass1= create_thumbnail($file,1,$crop,'returnresource');
//pass 2: resize and save
create_thumbnail($pass1,$shrink,'','myfile.jpg');

---------------------------- */
?>