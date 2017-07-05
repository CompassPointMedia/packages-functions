<?php
$functionVersions['misc_functions']=0.01;
$functionVersions['error_alert']=0.01;
$functionVersions['unhtmlentities']=0.01;
$functionVersions['js_safe_01']=0.01;
$functionVersions['valid_email_01']=0.01;
if(!function_exists('misc_functions')){
	//just kind of a namespace for this file
	function misc_functions(){ }
}
if(!function_exists('error_alert')){
	function error_alert($x,$continue=false){
		global $assumeErrorState;
		?><script defer>alert('<?php echo $x?>');</script><?php
		if(!$continue){
			$assumeErrorState=false;
			exit;
		}
	}
}
if(!function_exists('unhtmlentities')){
	function unhtmlentities($string) {
	   $trans_tbl = get_html_translation_table(HTML_ENTITIES);
	   $trans_tbl = array_flip($trans_tbl);
	   return strtr($string, $trans_tbl);
	}
}
if(!function_exists('js_safe_01')){
	function js_safe_01($string){
		$string=str_replace('\\','\\\\',$string);
		$string=str_replace("'",'\\'."'",$string);
		$string=str_replace(chr(10),'\\'.'n',$string);
		$string=str_replace(chr(13),'\\'.'r',$string);
		return $string;
	}
}
if(!function_exists('valid_email_01')){
	function valid_email_01($x){
		global $valid_email_01;
		if(!preg_match('/^[-_a-z0-9]+(\.[-_a-z0-9]+)*@[-a-z0-9]+(\.[-a-z0-9]+)+$/i',$x)){
			return false;
		}
		return true;
	}
}
if(!function_exists('parse_number')){
	function parse_number($n,$options=array()){
		for($i=0;$i<strlen($n);$i++){
			if(!is_numeric(substr($n,$i,1))){
				if($haveNumber){
					break;
				}else{
					continue;
				}
			}
			$str.=substr($n,$i,1);
			$haveNumber=true;
		}
		return ltrim($str,'0');
	}
}
?>