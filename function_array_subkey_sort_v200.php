<?php
$functionVersions['subkey_sort']=2.00;
function subkey_sort($a, $key, $sort='ASC',$reindex=''){
	//2008-09-29: ability to reindex the array from start value of 1
	//must have array with values
	if(!$a || !is_array($a) || !count($a))return false;
	$append=array();
	foreach($a as $n=>$v){
		$i++;
		$_n[$i]=$n; $_v[$i]=$v;
		if(!$v[$key]){
			//for nodes without the subkey - this may not be desirable - same as sorting by alpha, blank values show first
			$append[$n]=$v;
			continue;
		}
		$ref[$i]=strtolower($v[$key]);
	}
	$sort=strtolower($sort);
	if($sort!=='desc' && $sort!=='descending' && $sort!==-1){
		asort($ref);
	}else{
		arsort($ref);
	}
	foreach($ref as $n=>$v)  $b[$_n[$n]]=$_v[$n];
	if(count($append)){
		if($sort!=='desc' && $sort!=='descending' && $sort!==-1){
			$b=array_merge($append,$b);
		}else{
			$b=array_merge($b,$append);
		}
	}
	if($reindex){
		$i=0;
		foreach($b as $v){
			$i++;
			$c[$i]=$v;
		}
		$b=$c;
	}
	return $b;
}
