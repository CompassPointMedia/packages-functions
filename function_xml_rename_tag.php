<?php
/*><script>*/
$functionVersions['xml_rename_tag']=1.00;
function xml_rename_tag($string,$new){
	/***
	2004-03-22: very simple function, seems to work no problems
	***/
	global $xml_rename_tag;
	$string=trim($string);
	if(!preg_match('/<[a-z_0-9]+[^>]*\/*>/i',$string,$a)){
		$xml_rename_tag[err]='Tag not found';
		return false;
	}
	$initialString=$a[0];
	$initialString=preg_replace('/^<[a-z0-9_]+/i','<'.$new,$initialString);
	$remainder=substr($string,strlen($initialString),strlen($string)-strlen($initialString));
	if(substr($initialString,-2)!=='/>'){
		$buffer=$remainder;
		$remainder = preg_replace('/<\/[a-z0-9_]+>$/i','</'.$new.'>',$remainder);
		if($buffer!=$remainder){$xml_rename_tag['notice']='No nesting tag name present';}
	}	
	return $initialString.$remainder;
}
?>