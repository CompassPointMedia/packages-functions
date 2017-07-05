<?php
$functionVersions['string_analyzer_i1']=1.00;
function string_analyzer_i1(){
/*

MODE3_COMMENTS:all modes above this are just additional variables to make global;
COMMENTS:thanks to Tim (Majik Sheff) for this function, it's really sweet and lean;
TO_DO:there is really no need to have two parameters for string vs. file.  Get the function to where if the string passed looks like a file, it's interpreted as such, or otherwise it's interpreted as a string.  2) it might be better to have the third parameter be 1 which means make everything global, vs. passing a long list of variables and arrays to make global (you're quite likely to miss one, I've done it several times);
GOTCHAS:Just as spec'd above -- make sure you declare all the globals;
*/

//comments:This is an improvement on the file_analyzer function.  Default is to interpret as a string, 1 in second parameter interprets as a FILE location, each additional parameter (no real limit) indicates a variable or array which can be declared global.
	#the following four groups are always global
	global $HTTP_POST_VARS, $HTTP_SESSION_VARS, $recordData, $_settings, $mm_logic;
	
	#get arguments
	$arg_list=func_get_args();	
	
	#declare any additional globals
	if(func_num_args()>2){
		$arg_list=func_get_args();
		for($ijk=2;$ijk<func_num_args();$ijk++){
			eval('global $' . $arg_list[$ijk] . ';');
		}
	}
	
	if(func_num_args()>1 and $arg_list[1]==1){
		#interpret the first variable as a filename (must be absolute path to utilize php code)
		$fp = fopen($arg_list[0], "r"); 
		$analyzeContents = '?'.'>' . fread($fp, filesize($arg_list[0])) . '<'.'?php '; 
		fclose ($fp);
	}else{
		#interpret the first variable as an ASCII string (php tags will be executed)
		$analyzeContents = '?'.'>' .$arg_list[0] . '<'.'?php ';
	}

	ob_start(); 
	eval($analyzeContents); 
	$analyzeReturn = ob_get_contents(); 
	ob_end_clean();
	return $analyzeReturn;
}
?>