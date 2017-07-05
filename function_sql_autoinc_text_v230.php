<?php
$functionVersions['sql_autoinc_text']=2.30;
function sql_autoinc_text($table, $field, $root, $where='', $pad=0, $cnx='', $leftSep='',$rightSep=''){
	//2007-01-23: switched over to q() for the query and can now pass the connection
	//note the pad value, if 3, would produce 001, 002, 003, 004, 005, etc.  Numbers above 999 would still increment normally however
	if(!$cnx){
		mail('reroute@compasspointmedia.com','autoinc failure file: '.__FILE__.', line: '.__LINE__,'fix it','From: bugreports@relatebase.com');
		global $db_cnx;
		if($db_cnx){
			$cnx=$db_cnx;
		}else{
			global $MASTER_HOSTNAME, $MASTER_USERNAME, $MASTER_PASSWORD, $MASTER_DATABASE;
			$cnx=array($MASTER_HOSTNAME, $MASTER_USERNAME, $MASTER_PASSWORD, $MASTER_DATABASE);
		}
	}
	//number to increment by
	$increment=1;
	$sql="SELECT $field from $table WHERE $field like '$root%'";
	//where clause allows more targeted selection for the increment
	if(trim($where)) $sql.=" AND ".trim(preg_replace('/^\s*AND\s+/i','',$where));
	$sql.=" ORDER BY $field";
	if($a=q($sql, $cnx, O_COL)){
		$len=strlen($root);
		foreach($a as $r){
			$y= strlen($r[$field])- $len;
			if($y==0){
				if(!isset($max))$max=2; //so jsmith autoincrement would be jsmith 2
				continue;
			}
			//this is the remainder
			$x= substr($r[$field],-$y);
			//alpha strings are ignored (e.g. jsmithe not a threat to jsmith)
			if(!preg_match('/^[0-9]+$/',$x))continue;
			//convert to an integer
			$x= intval($x);
			if($x>=$max) $max=$x+$increment;
		}
		//alter jamesbond to jamesbond007 (padding 7 to three digits)
		$root.= $leftSep . str_pad($max, $pad, '0', STR_PAD_LEFT) . $rightSep;
	}
	return $root;
}
?>