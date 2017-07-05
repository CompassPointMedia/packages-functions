<?php
$functionVersions['subkey_sort']=3.00;
function subkey_sort($a, $key, $options=array()){
	/*
	//2013-02-14: v3.00; allowing now for multiple subkey sorts
		subkey_sort($names, $key=array(LastName, FirstName), $options);
		function will sort by the last key, remove it and then call itself again :)
	
	//2010-06-13: v2.03; bugger didn't work in php 5 due to the behavior of array_merge()
	//2009-12-31: v2.02; had an error in thought, divided into TWO vars
		sort: asc and desc
		sortType: standard and natural(default)
	//2009-08-17: v2.01;
	#changed default sort to natural
	//2008-09-29: ability to reindex the array from start value of 1
	//must have array with values
	*/
	
	global $fromHdrBugs,$developerEmail;
	
	//grab thisKey as last element
	if(!is_array($key))$key=array($key);
	$thisKey=array_pop($key);
	if(!empty($key))$reindex=true;
	
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
	$pad=strlen(count($a));	
	foreach($a as $n=>$v){
		$i++;
		$_n[$i]=$n; $_v[$i]=$v;
		if(!$v[$thisKey]){
			//for nodes without the subkey - this may not be desirable - same as sorting by alpha, blank values show first
			$append[$n]=$v;
			continue;
		}
		$ref[$i]=strtolower($v[$thisKey]).($suppressSortFix?'':'-'.str_pad($n,$pad,'0',STR_PAD_LEFT));
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
			#ob_start();
			@natcasesort($ref);
			#print_r($ref);
			//1. keep in this order
			foreach($ref as $n=>$v){
				$z[]=array($n,$v);
			}
			//2. resort
			for($i=count($z)-1; $i>=0; $i--){
				$y[]=$z[$i];
			}
			//3. rebuild
			unset($ref);
			foreach($y as $v)$ref[$v[0]]=$v[1];
			#print_r($ref);
			#$out=ob_get_contents();
			#ob_end_clean();
			#mail($developerEmail, 'reverse natural case sort fixed; notice file '.__FILE__.', line '.__LINE__,get_globals($out."\n\n"),$fromHdrBugs);
		}
	}
	ob_start();
	foreach($ref as $n=>$v)  $b[$_n[$n]]=$_v[$n];
	$err=ob_get_contents();
	ob_end_clean();
	if(count($append)){
		if($sort!=='desc' && $sort!=='descending' && $sort!==-1){
			if(count($b)){
				//main b array gets added on to append
				foreach($b as $n=>$v) $append[$n]=$v;
			}
			$b=$append;
		}else{
			foreach($append as $n=>$v) $b[$n]=$v;
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
	if(empty($key))return $b;
	return subkey_sort($b,$key,$options);
}
?>