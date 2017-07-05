<?php
$functionVersions['human_height']=1.00;
function human_height($in, $return='inch'){
	//2008-06-28 $return= inch | feet
	/*
	read formats (cool thing about height is that it starts around 14 inches and nobody is 14 feet tall)
	6' = 6 foot
	36, 36" = 36 inches
	72, 72" = 72 inches
	5 6, 5ft. 6in., 5'6", 5' 6" = 5 foot, 6 inches
	
	
	*/
	global $human_height;
	unset($human_height);
	$in=trim($in);
	$return=strtolower($return);
	if(preg_match('/^([.0-9]+)"$/',$in,$a)){
		$feet=array(floor($inch/12), fmod($inch,12));
	}else if(preg_match('/^([.0-9]+)\'$/',$in,$a)){
		$inch=$a[1]*12;
		$feet=array($a[1], 0);
	}else if(is_numeric($in)){
		$inch=$in;
		$feet=array(floor($inch/12), fmod($inch,12));
	}else{
		$in=preg_replace('/(ft\.|foot|feet|\')/i','\' ',$in);
		$in=preg_replace('/(in\.|inch|inches|")/i','" ',$in);
		if($a=preg_split('/[^0-9\'"]+/',$in)){
			foreach($a as $n=>$v)$a[$n]=preg_replace('/[^0-9]+/','',$v);
			$inch=$a[0]*12 + $a[1];
			$feet=array($a[0],$a[1]);
		}else{
			$human_height['warning']='Height not readable';
			return false;
		}
	}
	//evaluate
	if($inch<14 || $inch>108){
		//out of range
		$human_height['warning']='Height out of range, must be between 14 and 108 inches (9 feet)';
		return false;
	}else{
		if($return=='inch'){
			return $inch;
		}else{
			return implode("' ",$feet).'"';
		}
	}
}

?>