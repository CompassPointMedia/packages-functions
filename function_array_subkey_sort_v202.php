<?php
$functionVersions['subkey_sort']=2.02;
function subkey_sort($a, $key, $options=array()){
	/*
	//2009-12-31: v2.02; had an error in thought, divided into TWO vars
		sort: asc and desc
		sortType: standard and natural(default)
	//2009-08-17: v2.01;
	#changed default sort to natural
	//2008-09-29: ability to reindex the array from start value of 1
	//must have array with values
	*/
	
	global $fromHdrBugs,$developerEmail;
	
	//handle 3rd-parameter=sort legacy
	if(!is_array($options)){
		$sort=$options;
	}else{
		extract($options);
	}
	if(!$sort)$sort='asc';
	if(!$sortType)$sortType='natural';
	
	if(!$a || !is_array($a) || !count($a))return $a;
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
		if($sortType=='standard'){
			asort($ref);
		}else{
			@natcasesort($ref);
		}
	}else{
		if($sortType=='standard'){
			arsort($ref);
		}else{
			@natcasesort($ref);
			mail($developerEmail,'reverse natural case sort not developed, error file '.__FILE__.', line '.__LINE__,get_globals(),$fromHdrBugs);
		}
	}
	ob_start();
	foreach($ref as $n=>$v)  $b[$_n[$n]]=$_v[$n];
	$err=ob_get_contents();
	ob_end_clean();
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
?>