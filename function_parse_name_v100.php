<?php
error_alert('exit');
function parse_name($name,$options=array()){
	/* 2010-09-23 by Samuel - really thought I had a more complex version of this function - got this from giocosa */
	extract($options);
	if($prefix)$prefix='';
	$a=explode(' ',trim($name));
   	if(count($a)==2){
		$r[$prefix.'FirstName']=str_replace('.','',$a[0]);
		$r[$prefix.'LastName']=str_replace('.','',$a[1]);
	}else{
		$r[$prefix.'FirstName']=str_replace('.','',$a[0]);
		$r[$prefix.'MiddleName']=str_replace('.','',$a[1]);
		$r[$prefix.'LastName']=str_replace('.','',$a[2]);	
	}
	return $r;
}
?>