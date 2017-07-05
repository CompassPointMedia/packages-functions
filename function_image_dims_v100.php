<?php
$functionVersions['image_dims']=1.00;
function image_dims($getimagesize, $boxwidth='', $boxheight='', $ratio=1.294, $heightThreshold=45, $widthThreshold=45){
	//2005-11-14: this function returns image dims for a specified box; if you give width and height, ratio is recalc'ed, i.e if you give any of the two the third is calc'ed.  Eventually this can handle creating thumbnails and resizing also. Default ratio of 1.294 is that of an 8.5x11 sheet of paper
	//optimize image dimensions
	if($boxwidth)$params++;
	if($boxheight)$params++;
	if($ratio)$params++;
	if($params<2 && !$ratio)exit('Must pass two parameters to this function (one if you will accept height ratio of 1.294)');
	switch(true){
		case $boxwidth && $boxheight:
			$ratio=$boxheight/$boxwidth;
		break;
		case $boxwidth && $ratio:
			$boxheight=$ratio * $boxwidth;
		break;
		case $boxheight && $ratio:
			$boxwidth=$boxheight/$ratio;
		break;
	}
	$width=$getimagesize[0]; $height=$getimagesize[1];
	if(!$width || !$height) return;
	if($width>0 && $height>0){
		if($width>$boxwidth || $height>$boxheight){
			if($width/$boxwidth > $height/$boxheight){
				//object is width-heavy, make sure height threshold is not too low
				if( $height * ($boxwidth/$width) < $heightthreshold){
					$dims='width="'. round($width*($heightthreshold/$height)) .'" height="'.round($heightthreshold).'"';
				}else{
					$dims='width="'.round($boxwidth).'" height="'. round($height*($boxwidth/$width)) .'"';
				}
			}else{
				//object is height-heavy
				if( $width * ($boxheight/$height) < $widththreshold){
					$dims='width="'.round($widththreshold).'" height="'. round($height*($widththreshold/$width)) .'"';
				}else{
					$dims='width="'. round($width*($boxheight/$height)) .'" height="'.round($boxheight).'"';
				}
			}
		}else{
			$dims=$getimagesize[3];
		}
	}
	return $dims;
}
?>