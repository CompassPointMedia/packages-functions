<?php
$functionVersions['pk_decode']=1.00;
function pk_decode($pk){
	$pk=base64_decode(str_replace('_','=',$pk));
	$pka=explode('.',$pk);
	foreach($pka as $n=>$v){
		$pka_1base[count($pka_1base)+1]=str_replace('&amp;','&',str_replace('&#046;','.',$v));
	}
	return $pka_1base;
}
function pk_encode($pka){
	if(!is_array($pka))$pka=array($pka);
	foreach($pka as $n=>$v){
		$pka[$n]=str_replace('.','&#046;',str_replace('&','&amp;',$v));
	}
	$pk=str_replace('=','_',base64_encode(implode('.',$pka)));
	return $pk;
}
?>