<?php
/*
2010-08-29: WOW! 5+ years - this is now a group_ file 
*/
$functionVersions['colors']=1.10;
function color_read($x, $mode='rgb',$output='array'){
	/************
	2005-03-07:
	improved range of color notation:
	
	#allowed separators are a space or comma
	#we read the left side of the string and ignore everything after
	1. efc592 or 482941 - 6 numbers in a row is always rbg (base 16)
	2. 360 .5 .25 - this is hsv because of decimal
	3. 360 or ef5 - if no pair of numbers after this is read as 336600 or eeff55
	4. 1,128,255 - rgb (base 10) because 128 and 255 are bigger than 1
	5. 1 .128 .255 - hsv because of the decimals
	6. 1 10% 10% - hsv because of the percentages
	7. goldenrod, mintcream, white - recognized color words (array in this file)
	8. 1 1 1 - would be indefinite (hsv or rgb?)
	***********/
	global $recCol;
	switch(true){
		case preg_match('/^#*([a-f0-9]{2})([a-f0-9]{2})([a-f0-9]{2})(\s|$)/i',$x,$a):
			//6 numbers, read as RRGGBB
			$r=$a[1];
			$g=$a[2];
			$b=$a[3];
		break;
		case preg_match('/^\s*[a-z]{4,}(\s|$)/i',$x,$a) OR preg_match('/^red/i',$x,$a) OR preg_match('/^tan/i',$x,$a):
			//recognized color word
			if(!$recCol){
				exit('Recognized color library not present');
			}
			$a[0]=trim(strtolower($a[0]));
			if($recCol[$a[0]]){
				$r=strtoupper(substr($recCol[$a[0]],0,2));
				$g=strtoupper(substr($recCol[$a[0]],2,2));
				$b=strtoupper(substr($recCol[$a[0]],4,2));
			}
		break;
		case preg_match( '/^(hsv|rgb)*:*\s*([0-9]{1,3})(\s|,)+([.0-9]+%*)(\s|,)+([.0-9]+%*)(\s|$)/i',$x,$a ):
			//most other cases
			$c1=$a[2];
			$c2=$a[4];
			$c3=$a[6];
			switch(true){
				case strlen(stristr($c2.$c3,'%'))>0:
					//convert percentages
					$c2=str_replace('%','',$c2)/100;
					$c3=str_replace('%','',$c3)/100;
				case strlen(stristr($c2.$c3,'.'))>0:
					$mode='hsv';
					$c1=fmod($c1,360);
					$c2>1?$c2=1:'';
					$c3>1?$c3=1:'';
					$c=hsv2rgb($c1,$c2,$c3);
					$r=$c[0];
					$g=$c[1];
					$b=$c[2];
				break;
				default:
					$mode='rgb'; 
					$c1>255?$c1=255:'';
					$c2>255?$c2=255:'';
					$c3>255?$c3=255:'';
					$r=str_pad(base_convert($c1,10,16),2,'0',STR_PAD_LEFT);
					$g=str_pad(base_convert($c2,10,16),2,'0',STR_PAD_LEFT);
					$b=str_pad(base_convert($c3,10,16),2,'0',STR_PAD_LEFT);
				break;
			}
		default:
			//any other cases
			if(preg_match('/^#*([0-9a-f])([0-9a-f])([0-9a-f])(\s|$)/i',$x,$a)){
				//color shortcut (hexadecimal)
				$r=str_repeat($a[1],2);
				$g=str_repeat($a[2],2);
				$b=str_repeat($a[3],2);
			}
		break;
	}
	// evaluate for good values
	if(!strlen($r) || strlen(!$g) || strlen(!$b))return;
	return array($r,$g,$b);
}

function hsv2rgb($h,$s,$v){
	/**
	Created 2005-02-21 from a function found on the net.  This function presumes it will have values of s and v above and below 1 and 0 because colors are being shifted through these values for color charts.  If the values drop out of range they are set to 1 or 0 whichever is closest.
	h is 0-360 normally and s,v are 0-1
	**/
	//handle negative or over 360 values for hue
	//NOTE: MUST PASS BLANK IF HUE IS UNKNOWN!  PASSING -1 OR ANY NUMBER WILL PHASE SHIFT TO 0-360
	if(is_array($h)){
		$h=$h[0];
		$s=$h[1];
		$v=$h[2];
	}
	if(!strlen($h) || is_null($h)){
		$h=-1;
	}else
	if($h<0){
		while(true){
			$fail++; if($fail>100)break;
			$h+=360;
			if($h>=0)break;
		}
	}
	$h=fmod($h,360);
	//clean up s and v
	$v<0?$v=0:'';
	$v>1?$v=1:'';
	$s<0?$s=0:'';
	$s>1?$s=1:'';
	
	 if( $s == 0.0 || $h == -1.0) // s==0? Totally unsaturated = grey so R,G and B all equal value
    {
		$r = $g = $b = str_pad(base_convert(round($v*255),10,16),2,'0',STR_PAD_LEFT);
		$a=array($r,$g,$b);
      return $a;
    }
    $hTemp = $h/60.0;	//sam: was 60.0f don't know what that means, maybe float
    $i = floor($hTemp);                 // which sector
    $f = $hTemp - $i;                      // how far through sector
    $p = $v * ( 1 - $s );
    $q = $v * ( 1 - $s * $f );
    $t = $v * ( 1 - $s * ( 1 - $f ) );
 
    switch( $i ) 
    {
    case 0:{$r = $v;$g = $t;$b = $p;break;}
    case 1:{$r = $q;$g = $v;$b = $p;break;}
    case 2:{$r = $p;$g = $v;$b = $t;break;}
    case 3:{$r = $p;$g = $q;$b = $v;break;} 
    case 4:{$r = $t;$g = $p;$b = $v;break;}
    case 5:{$r = $v;$g = $p;$b = $q;break;}
    }
	 $a=array(
	 	str_pad(base_convert(round($r*255),10,16),2,'0',STR_PAD_LEFT),
		str_pad(base_convert(round($g*255),10,16),2,'0',STR_PAD_LEFT),
		str_pad(base_convert(round($b*255),10,16),2,'0',STR_PAD_LEFT)
	 );
	 return $a;
}

function rgb2hsv($r, $g, $b){
	/**
	Created 2005-02-21 from a function found on the net.  values above ff are fixed at 255; values below 0 are fixed at zero.  presumption is that values are hexadecimal
	**/
    //values passed as hexidecimal
	if(is_array($r)){
		$r=$r[0];
		$g=$r[1];
		$b=$r[2];
	}
	$r=base_convert($r,16,10)/255;
	$g=base_convert($g,16,10)/255;
	$b=base_convert($b,16,10)/255;
	 //normalize out of range values
	 $r<0?$r=0:'';
	 $r>255?$r=255:'';
	 $g<0?$g=0:'';
	 $g>255?$g=255:'';
	 $b<0?$b=0:'';
	 $b>255?$b=255:'';

    $mn=$r;$mx=$r;
    $maxVal=0;
 
    if ($g > $mx){ $mx=$g;$maxVal=1;}
    if ($b > $mx){ $mx=$b;$maxVal=2;} 
    if ($g < $mn) $mn=$g;
    if ($b < $mn) $mn=$b; 

    $delta = $mx - $mn;
 
    $v = $mx; 
    if( $mx != 0 )
      $s = $delta / $mx; 
    else 
    {
		$s = 0;
		$h = 0;
		$h=round($h,4);
		$s=round($s,4);
		$v=round($v,4);
		$a=array($h,$s,$v);
		return $a;
    }
    if ($s==0.0)
    {
      $h=-1;
		$h=round($h,4);
		$s=round($s,4);
		$v=round($v,4);
		$a=array($h,$s,$v);
      return $a;
    }
    else
    { 
      switch ($maxVal)
      {
      case 0:{$h = ( $g - $b ) / $delta;break;}         // yel < h < mag
      case 1:{$h = 2 + ( $b - $r ) / $delta;break;}     // cyan < h < yel
      case 2:{$h = 4 + ( $r - $g ) / $delta;break;}     // mag < h < cyan
      }
    }
    $h *= 60;
    if( $h < 0 ) $h += 360;
	 $h=round($h,4);
	 $s=round($s,4);
	 $v=round($v,4);
	 $a=array($h,$s,$v);
	 return $a;
}
function color_mix_phase($r1,$g1,$b1, $r2,$g2,$b2, $step=3){
	$x=1;
	$a[$x]=array($r1,$g1,$b1);
	//presume hex color notation
	$r1=base_convert($r1,16,10);
	$r2=base_convert($r2,16,10);
	$g1=base_convert($g1,16,10);
	$g2=base_convert($g2,16,10);
	$b1=base_convert($b1,16,10);
	$b2=base_convert($b2,16,10);
	
	$rd=($r2-$r1)/($step-1);
	$gd=($g2-$g1)/($step-1);
	$bd=($b2-$b1)/($step-1);
	for($i=0;$i<$step-1;$i++){
		$x++;
		$r1+=$rd;
		$g1+=$gd;
		$b1+=$bd;
		$a[$x]=array(
			str_pad(base_convert(round($r1),10,16),2,'0',STR_PAD_LEFT),
			str_pad(base_convert(round($g1),10,16),2,'0',STR_PAD_LEFT),
			str_pad(base_convert(round($b1),10,16),2,'0',STR_PAD_LEFT)
		);
	}
	return $a;
}
function color_shift_hsv($r,$g,$b, $h='',$s='',$v=''){
	//will shift an rgb color using hsv components
	/***
	shifting hue would be -20 or +20 for example; 
	shifting saturation would be either 75% or 125% though positive percents would go above the desired 1.0
	alternate notation is 0->1.0 for actual superceding value
	same previous methods on saturation apply to value
	***/
	$a=rgb2hsv($r,$b,$b);
	//hue
	if(preg_match('/^(\+-)/',$h)){
		$a[0] += $h;
	}else if(is_numeric($h)){
		$a[0] = $h;
	}
	//saturation
	if(strstr( $s,'%')){
		$a[1] *= str_replace('%','',$s)*.01;
	}else if(is_numeric($s) && $s >= 0 && $s <= 1.0000000000000){
		$a[1] = $s;
	}
	//value
	if(strstr( $v,'%')){
		$a[2] *= str_replace('%','',$v)*.01;
	}else if(is_numeric($v) && $v >= 0 && $v <= 1.0000000000000){
		$a[2] = $v;
	}
	$b=hsv2rgb($a[0],$a[1],$a[2]);
	return $b;
} 

function color_mix($base, $overlay, $opacity=.5){
	/* modified 2010-08-30: returns rgb hex array */
	if(!is_array($base))$base=color_read($base);
	if(!is_array($overlay))$overlay=color_read($overlay);
	foreach($base as $n=>$v)$base[$n]=base_convert($v,16,10);
	foreach($overlay as $n=>$v)$overlay[$n]=base_convert($v,16,10);
	$result[0]=str_pad(base_convert(round($base[0]+($overlay[0]-$base[0])*$opacity),10,16),2,'0',STR_PAD_LEFT);
	$result[1]=str_pad(base_convert(round($base[1]+($overlay[1]-$base[1])*$opacity),10,16),2,'0',STR_PAD_LEFT);
	$result[2]=str_pad(base_convert(round($base[2]+($overlay[2]-$base[2])*$opacity),10,16),2,'0',STR_PAD_LEFT);
	return array($result[0],$result[1],$result[2]);
}
#see http://en.wikipedia.org/wiki/Web_colors#X11_color_names
$recCol['aliceblue']='f0f8ff';
$recCol['antiquewhite']='faebd7';
$recCol['aqua']='00ffff';
$recCol['aquamarine']='7fffd4';
$recCol['azure']='f0ffff';
$recCol['beige']='f5f5dc';
$recCol['bisque']='ffe4c4';
$recCol['black']='000000';
$recCol['blanchedalmond']='ffebcd';
$recCol['blue']='0000ff';
$recCol['blueviolet']='8a2be2';
$recCol['brown']='a52a2a';
$recCol['burlywood']='deb887';
$recCol['cadetblue']='5f9ea0';
$recCol['chartreuse']='7fff00';
$recCol['chocolate']='d2691e';
$recCol['coral']='ff7f50';
$recCol['cornflowerblue']='6495ed';
$recCol['cornsilk']='fff8dc';
$recCol['crimson']='dc143c';
$recCol['cyan']='00ffff';
$recCol['darkblue']='00008b';
$recCol['darkcyan']='008b8b';
$recCol['darkgoldenrod']='b8860b';
$recCol['darkgray']='a9a9a9';
$recCol['darkgreen']='006400';
$recCol['darkkhaki']='bdb76b';
$recCol['darkmagenta']='8b008b';
$recCol['darkolivegreen']='556b2f';
$recCol['darkorange']='ff8c00';
$recCol['darkorchid']='9932cc';
$recCol['darkred']='8b0000';
$recCol['darksalmon']='e9967a';
$recCol['darkseagreen']='8fbc8f';
$recCol['darkslateblue']='483d8b';
$recCol['darkslategray']='2f4f4f';
$recCol['darkturquoise']='00ced1';
$recCol['darkviolet']='9400d3';
$recCol['deeppink']='ff1493';
$recCol['deepskyblue']='00bfff';
$recCol['dimgray']='696969';
$recCol['dodgerblue']='1e90ff';
$recCol['firebrick']='b22222';
$recCol['floralwhite']='fffaf0';
$recCol['forestgreen']='228b22';
$recCol['fuchsia']='ff00ff';
$recCol['gainsboro']='dcdcdc';
$recCol['ghostwhite']='f8f8ff';
$recCol['gold']='ffd700';
$recCol['goldenrod']='daa520';
$recCol['gray']='808080';
$recCol['green']='008000';
$recCol['greenyellow']='adff2f';
$recCol['honeydew']='f0fff0';
$recCol['hotpink']='ff69b4';
$recCol['indianred']='cd5c5c';
$recCol['indigo']='4b0082';
$recCol['ivory']='fffff0';
$recCol['khaki']='f0e68c';
$recCol['lavender']='e6e6fa';
$recCol['lavenderblush']='fff0f5';
$recCol['lawngreen']='7cfc00';
$recCol['lemonchiffon']='fffacd';
$recCol['lightblue']='add8e6';
$recCol['lightcoral']='f08080';
$recCol['lightcyan']='e0ffff';
$recCol['lightgoldenrodyellow']='fafad2';
$recCol['lightgreen']='90ee90';
$recCol['lightgrey']='d3d3d3';
$recCol['lightpink']='ffb6c1';
$recCol['lightsalmon']='ffa07a';
$recCol['lightseagreen']='20b2aa';
$recCol['lightskyblue']='87cefa';
$recCol['lightslategray']='778899';
$recCol['lightsteelblue']='b0c4de';
$recCol['lightyellow']='ffffe0';
$recCol['lime']='00ff00';
$recCol['limegreen']='32cd32';
$recCol['linen']='faf0e6';
$recCol['magenta']='ff00ff';
$recCol['maroon']='800000';
$recCol['mediumauqamarine']='66cdaa';
$recCol['mediumblue']='0000cd';
$recCol['mediumorchid']='ba55d3';
$recCol['mediumpurple']='9370d8';
$recCol['mediumseagreen']='3cb371';
$recCol['mediumslateblue']='7b68ee';
$recCol['mediumspringgreen']='00fa9a';
$recCol['mediumturquoise']='48d1cc';
$recCol['mediumvioletred']='c71585';
$recCol['midnightblue']='191970';
$recCol['mintcream']='f5fffa';
$recCol['mistyrose']='ffe4e1';
$recCol['moccasin']='ffe4b5';
$recCol['navajowhite']='ffdead';
$recCol['navy']='000080';
$recCol['oldlace']='fdf5e6';
$recCol['olive']='808000';
$recCol['olivedrab']='688e23';
$recCol['orange']='ffa500';
$recCol['orangered']='ff4500';
$recCol['orchid']='da70d6';
$recCol['palegoldenrod']='eee8aa';
$recCol['palegreen']='98fb98';
$recCol['paleturquoise']='afeeee';
$recCol['palevioletred']='d87093';
$recCol['papayawhip']='ffefd5';
$recCol['peachpuff']='ffdab9';
$recCol['peru']='cd853f';
$recCol['pink']='ffc0cb';
$recCol['plum']='dda0dd';
$recCol['powderblue']='b0e0e6';
$recCol['purple']='800080';
$recCol['red']='ff0000';
$recCol['rosybrown']='bc8f8f';
$recCol['royalblue']='4169e1';
$recCol['saddlebrown']='8b4513';
$recCol['salmon']='fa8072';
$recCol['sandybrown']='f4a460';
$recCol['seagreen']='2e8b57';
$recCol['seashell']='fff5ee';
$recCol['sienna']='a0522d';
$recCol['silver']='c0c0c0';
$recCol['skyblue']='87ceeb';
$recCol['slateblue']='6a5acd';
$recCol['slategray']='708090';
$recCol['snow']='fffafa';
$recCol['springgreen']='00ff7f';
$recCol['steelblue']='4682b4';
$recCol['tan']='d2b48c';
$recCol['teal']='008080';
$recCol['thistle']='d8bfd8';
$recCol['tomato']='ff6347';
$recCol['turquoise']='40e0d0';
$recCol['violet']='ee82ee';
$recCol['wheat']='f5deb3';
$recCol['white']='ffffff';
$recCol['whitesmoke']='f5f5f5';
$recCol['yellow']='ffff00';
$recCol['yellowgreen']='9acd32';
?>