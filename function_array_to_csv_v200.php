<?php
$functionVersions['array_to_csv']=2.00;
function array_to_csv($array, $showHeaders=true, $options=array()){
	/*
	2012-07-05: initiated options:
		delimiter (default ,)
		lastCol=[int] - if the array has more columns than we want, truncate the right side
		firstCol=[int] - similarly, truncate the left side
		function=trim for example
	*/
	extract($options);
	global $array_to_csv;
	if($array_to_csv['always_trim'] && !$function)$function='trim';

	if(!isset($qt))$qt='"';
	if(!isset($escQt))$escQt=$qt.$qt; //double it
	if(!isset($delimiter))$delimiter=',';
	if(!isset($nl))$nl="\n";
	
	foreach($array as $idx=>$row){
		$i++;
		if($i==1 && $showHeaders){
			//insert headers
			$j=0;
			foreach($row as $idx2=>$field){
				$j++;
				if($firstCol && $j<$firstCol)continue;
				if($lastCol && $j>$lastCol)break;
				$hbuffer[]=(is_numeric($idx2) || $suppressQuote ? $idx2 : $qt.str_replace($qt,$escQt,$idx2).$qt);
			}
			$output.=implode($delimiter,$hbuffer).$nl;
		}
		if($i>1)$output.=$nl;
		unset($buffer);
		$j=0;
		foreach($row as $idx2=>$field){
			$j++;
			if($firstCol && $j<$firstCol)continue;
			if($lastCol && $j>$lastCol)break;
			//this can be used to trim
			if($function)$field=$function($field);
			$buffer[]=(is_numeric($field) || $suppressQuote ? $field : $qt.str_replace($qt,$escQt,$field).$qt);
		}
		$output.=implode($delimiter,$buffer);
	}
	return $output;
}
?>