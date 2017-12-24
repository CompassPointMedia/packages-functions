<?php
$functionVersions['sql_autoinc_text']=2.32;
function sql_autoinc_text($table, $field, $root, $options=array()){
	/*
	2013-02-25:
	* now if $table=array(array('table'=>'addr_contacts','field'=>'UserName'), array('table'=>'bais_universal','field'=>'un_username')) and $field=NULL, the query will aggregate from multiple tables
	2009-06-13: 
	* now allowing this function to find user text autoincs such as AAA Bastrop Storage(2) for company name 
	* !!!!!NOTE!!!!!! - make sure slashes are present for user strings; if the name is O'Malley's Welding, what needs to be passed is root=O\'Malley\'s .. - what will be returned will also have slashes
	* redid params to pass options:
		where - for filtering a subgroup within a dataset
		pad - by default blank, number of places to pad with zero, e.g. jbond007 vs. jbond7
		cnx - when needed else default cnx
		leftSep - e.g. [ or (
		rightSep - e.g. ] or )
		trimRightNumbers - default=true
		returnLowerCase - default=true
	
	
	*/
	//2009-03-06: returned as lower case
	//2008-12-02: allowed $root to be a 2-key array where 0=firstname and 1=lastname - will turn john smith -> jsmith
	//2007-10-05: changed code handling slightly to use leftsep again with better logic - NOTE leftSep and rightSep are made to fit in a regular expression statement
	//2007-01-23: switched over to q() for the query and can now pass the connection
	//note the pad value, if 3, would produce 001, 002, 003, 004, 005, etc.  Numbers above 999 would still increment normally however
	global $qx, $developerEmail, $fromHdrBugs;


	extract($options);
	if(!isset($trimRightNumbers))$trimRightNumbers='/[ 0-9]*$/';
	if(!isset($returnLowerCase))$returnLowerCase=true;
	
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
	if($trimRightNumbers)$root=preg_replace($trimRightNumbers,'',$root);
	$root=rtrim($root);
	//number to increment by
	$increment=1;
	
	if(is_array($table) && is_null($field)){
		//2013-02-25; multiple table queries
		$a=array();
		foreach($table as $n=>$v){
			$sql="SELECT ".$v['field']." from ".$v['table']." where ".$v['field']." like '$root%'";
			if(!empty($where[$n])) $sql.=" AND ".trim(preg_replace('/^\s*AND\s+/i','',$where[$n]));
			$sql.=" ORDER BY ".$v['field'];
			$b=q($sql, $cnx, O_COL, O_DO_NOT_REMEDIATE);
			$a=array_merge($a,$b);
		}
		sort($a);
	}else{
		$sql="SELECT $field from $table WHERE $field like '$root%'";
	
		//where clause allows more targeted selection for the increment
		if(!empty($where)) $sql.=" AND ".trim(preg_replace('/^\s*AND\s+/i','',$where));
		$sql.=" ORDER BY $field";
		$a=q($sql, $cnx, O_COL, O_DO_NOT_REMEDIATE);
	}

	if(count($a)){
		$len=strlen($root);
		foreach($a as $present){
			if(strtolower($present)==strtolower(stripslashes($root))){
				if(!isset($max))$max=2; //so jsmith autoincrement would be jsmith 2
				continue;
			}
			//this is the remainder
			$str= substr($present,strlen(stripslashes($root)),1000);
			
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
			$root.= $leftSep . ($pad && strlen($max)<$pad ? str_pad($max, $pad, '0', STR_PAD_LEFT) : $max) . $rightSep;
		}
	}
	if($returnLowerCase)$root=strtolower($root);
	return $root;
}
