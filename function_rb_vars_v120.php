<?php
$functionVersions['rb_vars']=1.20;
function rb_vars($var){
	/***
	2006-04-16: this is a semi-major revision from 1.00 - it now handles multi instances in the string
	***/
	
	global $rb_vars; //this function
	global $RBVARS, $adminEmail;
	foreach($RBVARS as $n=>$v) $regex[]=$n;
	if(!preg_match_all( '/\{('.implode('|',$regex).')\}/i', $var, $a))return $var;
	foreach($a[1] as $v){
		//replace each instance in the case it is in
		$uv=strtoupper($v);
		$severity=$RBVARS[$uv]['severity'];
		global $$uv;
		if(strlen($$uv)){
			$pattern=$RBVARS[$uv]['pattern'];
			if($pattern && !preg_match($pattern, $$uv)){
				if($severity==3){
					ob_start();
					print_r($GLOBALS);
					$x=ob_get_contents();
					ob_end_clean();
					mail($adminEmail,'Bracket Var present but value didn\'t match pattern',$x,'From: notices@relatebase.com');
					exit('Script terminated: value for RB Bracket Variable {'.$var.'} does not match pattern '.
					$pattern);
				}else if($severity==2){
					$rb_vars['warning'][$var]='Non-matching string for this Bracket Variable';
				}else if($severity==1){
					//not developed
				}
			}else{
				$var=str_replace('{'.$v.'}', $$uv, $var);
			}
		}else if($severity==3){
			//mail me
			ob_start();
			print_r($GLOBALS);
			$x=ob_get_contents();
			ob_end_clean();
			mail($adminEmail,'Bracket value not present',$x,'From: notices@relatebase.com');
			exit('Script terminated: lacking value for RB Bracket Variable {'.$v.'}');
		}else if($severity==2){
			$rb_vars['warning'][$var]='Empty string for this Bracket Variable';
		}else if($severity==1){
			//not developed
		}
	}
	return $var;
}
?>