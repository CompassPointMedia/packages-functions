<?php
$functionVersions['is_logical']=1.0;
$functionVersions['read_logical']=1.0;
$functionVersions['output_logical']=1.0;
function is_logical($x){
	//whether a value can be interpreted as a logical equivalent
	//note blank is interpreted as false
	if(in_array(strtolower(trim($x)),array(/*true: */'y','yes','1','true','t', /*false: */'n','no','0','false','f','')))return true;
}
function read_logical($x){
	//return true if logical true, false if logical false, null otherwise
	$x=strtolower(trim($x));
	if(!is_logical($x))return NULL;
	$r=(in_array($x,array(/*true: */'y','yes','1','true','t'))?true:false);
	return $r;
}
function output_logical($x,$format=10){
	//created 2009-12-05 by Samuel
	//-NO  or -N or -0 that format only when value=false, nothing for true
	//-YES or -yes or -Y or -1 would only show that format when value=true
	$x=read_logical($x);
	if(is_null($x))return NULL;
	switch(true){
		case $format==10:
			return ($x?1:0);
		case $format=='yn':
			return ($x?'y':'n');
		case $format=='YN':
			return ($x?'Y':'N');
		case $format=='yesno':
			return ($x?'yes':'no');
		case $format=='YESNO':
			return ($x?'YES':'NO');
		case $format=='tf':
			return ($x?'t':'f');
		case $format=='TF':
			return ($x?'T':'F');
		case $format=='truefalse':
			return ($x?'true':'false');
		case $format=='TRUEFALSE':
			return ($x?'TRUE':'FALSE');
		case preg_match('/^-(0|n|f)/i',$format):
			return ($x?'':ltrim($format,'-')); 
		case preg_match('/^-(1|y|t)/i',$format):
			return ($x?ltrim($format,'-'):''); 
		default:
			return ($x?$format:'');
	}
}
?>