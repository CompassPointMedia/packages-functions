<?php
$functionVersions['rtcs_declare_field_attributes_mysql']=2.00;
function rtcs_declare_field_attributes_mysql($a, $includeName=true, $useRBVars=true, $options=array()){
	/**
	2009-05-31: moved over as file from production
	created 2005-11-09: this works with RTCS version 2.0 to output a mysql field declaration.  Uses only the D region of the schema.  No options developed at this point, sometimes I deserve a quick function :-).
	2006-02-19: this function should also analyze what's being passed.  A lot of alter commands get "rewritten" by mysql, such as just specifying TINYBLOB without adding BINARY- binary will be added automatically, so we need to back-update $a to be EXACTLY WHAT THE MYSQL DATABASE HAS.
	**/
	//no globals needed but certain values passed to rb_vars() if called
	if(!$a) return;
	@extract($a);
	global $dbTypeArray;
	if($includeName) $s='`'.$DNAME.'` ';
	if(strtolower($dbTypeArray[$DTYPE])=='enum' || strtolower($dbTypeArray[$DTYPE])=='set'){
		mail($adminEmail,'use of enum and set not developed in rtcs_declare_field_type_mysql','File: '.$_SERVER['PHP_SELF'].', line: '.__LINE__, ' Table: '.$table. ', Parent DB: '. $db. ' Target DB: '.$targetDb,'From:bugreports@compasspoint-sw.com');
		$s.='char(255)';
	}else{
		$s.=$dbTypeArray[$DTYPE];
	}
	if($DATTRIB && $dbTypeArray[$DTYPE]!=='text' && $dbTypeArray[$DTYPE]!=='datetime') $s.='('.$DATTRIB.')';
	if($DUNSIGNED) $s.=' UNSIGNED';
	if($DZEROFILL) $s.=' ZEROFILL';
	if($DNULL){
		$s.=' NULL';
	}else{
		if(!$DBINARY)$s.=' NOT NULL';
	}
	if(strlen($DDEFAULT)) $s.=' DEFAULT \''.($useRBVars ? rb_vars($DDEFAULT) : $DDEFAULT). '\'';
	if($DAUTOINC) $s.=' AUTO_INCREMENT';
	if($DBINARY) $s.=' BINARY';
	return $s;
}
?>