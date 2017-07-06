<?php
$functionVersions['finan']=2.00;
function finan(){
	//idler
}
function finan_get_set_record($options){
	//2013-05-29
	global $finan_get_set_record, $developerEmail, $MASTER_USERNAME, $fromHdrBugs, $qr, $qx;
	extract($options);
	if(!isset($notify))$notify=true;
	if(!$key)$key='ID';
	foreach($search as $n=>$v){
		$a[]=$n.'=\''.addslashes($v).'\'';
	}
	if($ID=q("SELECT $key FROM $table WHERE ".implode(' AND ',$a), O_VALUE))
	return $ID;
	//notify anyone?
	if($notify){
		mail($developerEmail, 'Record created in '.$table.' for '.$MASTER_USERNAME.':'.end(explode('/',__FILE__)).', line '.__LINE__,get_globals('using get_set_records'),$fromHdrBugs);
	}
	foreach($a as $n=>$v)if(in_array($n))unset($a[$n]);
	$sql="INSERT INTO $table SET ".implode(', ',$a);
	if($set)foreach($set as $n=>$v)$sql.=', '.$n.'=\''.addslashes($v).'\'';
	if(!($a=$finan_get_set_record[$table])){
		$finan_get_set_records[$table]=q("EXPLAIN $table",O_ARRAY);
		foreach($finan_get_set_records[$table] as $n=>$v){
			$finan_get_set_records[$table][strtolower($v['Field'])]=$v;
			unset($finan_get_set_records[$table][$n]);
		}
	}
	foreach($finan_get_set_records[$table] as $n=>$v){
		if(preg_match('/createdate$/i',$n))$sql.=', '.$v['Field'].'=NOW()';
		if(preg_match('/creator$/i',$n))$sql.=', '.$v['Field'].'=\''.sun().'\'';
	}
	
	return q($sql, O_INSERTID);
}
?>