<?php
$functionVersions['mysql_declare_table_rtcs']=2.00;
function mysql_declare_table_rtcs($db, $table, $reload=false, $options=array()){
	//2006-02-24: converts an entire mysql table into an RTCS array which can be put into a db.  This is my first "lingua franca" use of RTCS without putting it somewhere.
	/***
	2006-02-24 to do: need to get the root information for complete entry - later..
	
	***/
	global $mysql_declare_table_rtcs, $dbTypeArray, $typeFlip;
	@extract($options);
	if(!$cnx) $cnx=C_MASTER;
	if(!$db || !$table)return;
	if($mysql_declare_table_rtcs[$db][$table] && !$mysql_declare_table_rtcs['{RB_OVERRIDE}']){
		//array is .root and .fields
		#we're concerned with root primarily right now
		$idx=get_table_indexes($db,$table,$cnx,true);
		$fields=q("EXPLAIN `$db`.`$table`", O_ARRAY, $cnx);
		$i=0;
		foreach($fields as $field){
			extract($field);
			unset($b);
			$i++;
			//----------------------------------------------------
			preg_match('/^([a-z]+)(\(([0-9, ]+)\))*/',$Type,$a);
			//13 field attributes total..
			$b['DNAME']=$Field;
			$b['DTYPE']=$typeFlip[$a[1]];
			$b['DCOMMENT']='Field $Field, $Type, *RTCS version 2.0, by mysql_declare_table_rtcs v200, line '.__LINE__;
			$b['DATTRIB']=$a[3];
			$b['DDEFAULT']=($Null=='YES' ? NULL : $Default);
			$b['DNULL']=($Null=='YES' ? 1 : 0);
			$b['DAUTOINC']=($Extra=='auto_increment'? 1 : 0);
			$b['DUNSIGNED']=(preg_match('/unsigned/i',$Type)? 1 : 0);
			$b['DZEROFILL']=(preg_match('/zerofill/i',$Type)? 1: 0);
			$b['DPRIMARY']=($Key=='PRI'? 1 : 0);
			$b['DUNIQUE']=($singleIdx[$Field]['Non_unique']==='0' && $Key!=='PRI' ? ($singleIdx[$Field]['Sub_part'] ? $singleIdx[$Field]['Sub_part'] : 1) : 0);
			$b['DINDEX']=($singleIdx[$Field]['Non_unique']==='1' && $Key!=='PRI' ? ($singleIdx[$Field]['Sub_part'] ? $singleIdx[$Field]['Sub_part'] : 1) : 0);
			$b['DBINARY']=(preg_match('/binary/i',$Type)?1:0);
			//----------------------------------------------------
			$b['Idx']=$i;
			$RTCS[strtolower($field)]=$b;
		}
		$mysql_declare_table_rtcs[$db][$table]['fields']=$RTCS;
		
	}else{
		return $mysql_declare_table_rtcs[$db][$table];
	}

}

function rtcs_declare_field_attributes_mysql($a, $includeName=true, $useRBVars=true, $options=array()){
	/**
	created 2005-11-09: this works with RTCS version 2.0 to output a mysql field declaration.  Uses only the D region of the schema.  No options developed at this point, sometimes I deserve a quick function :-).
	2006-02-19: this function should also analyze what's being passed.  A lot of alter commands get "rewritten" by mysql, such as just specifying TINYBLOB without adding BINARY- binary will be added automatically, so we need to back-update $a to be EXACTLY WHAT THE MYSQL DATABASE HAS.
	**/
	//no globals needed but certain values passed to rb_vars() if called
	if(!$a) return;
	@extract($a);
	global $dbTypeArray;
	if($includeName) $s='`'.$DNAME.'` ';
	if(strtolower($dbTypeArray[$DTYPE])=='enum' || strtolower($dbTypeArray[$DTYPE])=='set'){
		mail($adminEmail,'use of enum and set not developed in rtcs_declare_field_type_mysql','File: '.$_SERVER['PHP_SELF'].', line: '.__LINE__, ' Table: '.$table. ', Parent DB: '. $db. ' Target DB: '.$targetDb,'From:bugreports@relatebase.com');
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