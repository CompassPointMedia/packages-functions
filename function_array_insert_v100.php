<?php
function array_insert($array, $keyafter, $insert, $options=array()){
	/* Created 2010-09-13 by Samuel */
	//handle options
	extract($options);
	if(!$increment)$increment=1;
	if(!isset($appendToEnd))$appendToEnd=true;
	
	//handle trivial conditions
	if(!is_array($array))return $array;
	if(!count($array)){
		return array(1=>$insert);
	}
	//insert the node
	foreach($array as $n=>$v){
		$a[$n + ($doIncrement ? $increment : 0)]=$v;
		if($n==$keyafter){
			$doIncrement=$increment;
			$a[$n+$increment]=$insert;
		}
	}
	//append to end if $keyafter not reached
	if(!$doIncrement && $appendToEnd)$a[$n + $increment]=$v;
	return $a;
}
?>
