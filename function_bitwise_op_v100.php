<?php
$functionVersions['bitwise_op']=1.00;
function bitwise_op($body, $place, $op='write'){
	/**
	2006-04-20, writes in or checks for bitwise place presence. 2nd number must be 1,2,4,8,16,32 - a binary even number of 1 + n zeros; positive adds it in if not there, negative subtracts it if it is there.  Reading returns true if present and false if not present
	possible enhancements: ability to write in multiple places at once
	**/
	$bin=strrev(base_convert($body, 10, 2));
	$binPlace=strlen(base_convert(abs($place), 10, 2));
	if($op=='write'){
		if($place>0){
			return substr($bin, $binPlace-1, 1)==0 ? $body+$place : $body;
		}else{
			return substr($bin, $binPlace-1, 1)==1 ? $body+$place : $body;
		}
	}else{
		return substr($bin, $binPlace-1, 1)==1 ? true : false;	
	}
}
?>