<?php
/*
function generic5t(&$str, $rev=true)
//Generic 5x-reverse-base64 Encryption v1.0 9/25/09 -AJ
{
	if($rev==true){
	  for($i=0; $i<5;$i++){
    	$str=strrev(base64_encode($str)); //encode - apply base64 first and then reverse the string
  		}
  		return $str;
	}else{
		for($i=0; $i<5;$i++){
    	$str=base64_decode(strrev($str)); //decode - apply base64 first and then reverse the string
  		}
		global $MASTER_PASSWORD;
		$MASTER_PASSWORD = $str;	
	}
}
*/


function generic5t($str, $mode='assign',$options=array()){
	//Generic 5x-reverse-base64 Encryption v1.0 2009-10-28 -SF
	extract($options);
	if($mode=='help'){
		echo 'use "assign" (default) to set the first (encoded) parameter as the master password<br />
use "decode" to echo the decoded version of the string; <br />
and use "encode" to encode and return the encoded value';
	}
	if($mode=='assign' || $mode=='decode'){
		for($i=0; $i<5; $i++){
			$str=base64_decode(strrev($str)); //decode - apply base64 first and then reverse the string
		}
		if($mode=='assign'){
			global $MASTER_PASSWORD;
			$MASTER_PASSWORD = $str;
			if($super){
				global $SUPER_MASTER_PASSWORD;
				$SUPER_MASTER_PASSWORD = $str;
			}	
		}else{
			return $str;
		}
	}else{
		//encode and print
		for($i=0; $i<5; $i++) $str=strrev(base64_encode($str)); //encode - apply base64 first and then reverse the string
		return $str;
	}
}

?>