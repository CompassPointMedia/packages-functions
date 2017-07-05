<?php
$functionVersions['sql_autoinc_text']=2.31;
function sql_autoinc_text($table, $field, $root, $where='', $pad=0, $cnx='', $leftSep='',$rightSep=''){
	//2009-03-06: returned as lower case
	//2008-12-02: allowed $root to be a 2-key array where 0=firstname and 1=lastname - will turn john smith -> jsmith
	//2007-10-05: changed code handling slightly to use leftsep again with better logic - NOTE leftSep and rightSep are made to fit in a regular expression statement
	//2007-01-23: switched over to q() for the query and can now pass the connection
	//note the pad value, if 3, would produce 001, 002, 003, 004, 005, etc.  Numbers above 999 would still increment normally however
	global $qr,$qx,$fl,$ln,$developerEmail, $fromHdrBugs;
	if($cnx){
		//OK
	}else if($cnx=$qx['defCnxMethod']){
		//OK
	}else{
		mail($developerEmail,'autoinc failure file: '.__FILE__.', line: '.__LINE__,'fix it',$fromHdrBugs);
		global $db_cnx;
		if($db_cnx){
			$cnx=$db_cnx;
		}else{
			global $MASTER_HOSTNAME, $MASTER_USERNAME, $MASTER_PASSWORD, $MASTER_DATABASE;
			$cnx=array($MASTER_HOSTNAME, $MASTER_USERNAME, $MASTER_PASSWORD, $MASTER_DATABASE);
		}
	}
	//Normal convention I've used is first letter of first name, up to 16 letters of last, this follows that convention
	if(is_array($root)){
		$root=substr(preg_replace('/[^a-z]*/i','',$root[0]),0,1).substr(preg_replace('/[^a-z]*/i','',$root[1]),0,16);
	}
	//number to increment by
	$increment=1;
	$sql="SELECT $field from $table WHERE $field like '$root%'";

	
	//where clause allows more targeted selection for the increment
	if(trim($where)) $sql.=" AND ".trim(preg_replace('/^\s*AND\s+/i','',$where));
	$sql.=" ORDER BY $field";
	if($a=q($sql, $cnx, O_COL)){
		$len=strlen($root);
		foreach($a as $present){
			if(strtolower($present)==strtolower($root)){
				if(!isset($max))$max=2; //so jsmith autoincrement would be jsmith 2
				continue;
			}
			//this is the remainder
			$str= substr($present,strlen($root),1000);
			
			//alpha strings are ignored (e.g. jsmithe not a threat to jsmith)
			$left=str_replace('(','\(',str_replace('[','\[',$leftSep));
			$right=str_replace(')','\)',str_replace(']','\]',$rightSep));
			if(!preg_match('/^'.$left.'[0-9]+'.$right.'$/',$str))continue;

			//convert to an integer
			$str= intval(ltrim($str,'0'));
			if($str>=$max) $max=$str+$increment;
		}
		if($max){
			//alter jamesbond to jamesbond007 (padding 7 to three digits if pad=3)
			$root.= $leftSep . str_pad($max, $pad, '0', STR_PAD_LEFT) . $rightSep;
		}
	}
	return strtolower($root);
}
?>