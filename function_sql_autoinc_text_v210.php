<?php
function sql_autoinc_text($table, $field, $root, $where='', $pad=0){
	//note the pad value, if 3, would produce 001, 002, 003, 004, 005, etc.  Numbers above 999 would still increment normally however
	global $db_cnx;
	if(!$db_cnx){
		global $MASTER_HOSTNAME, $MASTER_USERNAME, $MASTER_PASSWORD, $MASTER_DATABASE;
		$db_cnx=mysqli_connect($MASTER_HOSTNAME, $MASTER_USERNAME, $MASTER_PASSWORD);
		mysqli_select_db($db_cnx, $MASTER_DATABASE);
	}
	//number to increment by
	$increment=1;
	$sql="SELECT $field from $table WHERE $field like '$root%'";
	//where clause allows more targeted selection for the increment
	if(trim($where)) $sql.=" AND ".trim(preg_replace('/^\s*AND\s+/i','',$where));
	$sql.=" ORDER BY $field";
	$result=mysqli_query($db_cnx, $sql) or die($sql . ": " . mysqli_error($db_cnx));
	if(mysqli_num_rows($result)){
		$len=strlen($root);
		while($r=mysqli_fetch_array($result)){
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
		$root.= str_pad($max, $pad, '0', STR_PAD_LEFT);
	}
	return $root;
}
?>