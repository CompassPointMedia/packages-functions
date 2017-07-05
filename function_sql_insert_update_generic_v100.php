<?php
function noslashes($x){
	return $x;
}
$functionVersions['sql_insert_update_generic']=1.00;
function sql_insert_update_generic($db, $table, $mode, $location='GLOBALS', $options=array(), $setCtrlFields=false, $addslashes=false, $errHandle=1){
	global $sql_insert_update_generic, $dateStamp, $timeStamp, $qr, $qx, $fl, $ln;
	$fl=__FILE__; 
	//2009-05-13: added the ability to pass the specific value PHP:NULL for a field which will set the value as NULL in mySQL
	//2005-12-01: generically inserts or updates a table - intelligent as to fields present
	/***
	options:
	-------------------------------------------------------------
	fields=array(field=>value, field1=>value1, ..)
	existing_primary=array(primary1=>value1, primary2=>value2, ..)
	
	fields node allows for additional 
	The existing_primary array would be used to create something like "UPDATE table SET UserName='newusername' WHERE UserName='oldusername'" - existing_primary would contain the OLD username value.  This is a bit complex but changing a primary is a rare event anyway
	NOTE: this function could also analyze whether the inserted value will go into the field without suffering change and handle this event by exiting or by mailing or by updating the field:
		1. do nothing
		2. email about the problem
		3. [email and] fix the problem
		4. [email and] exit the program
	***/
	@extract($options);
	$mode=strtoupper(trim($mode));
	if($mode=='INSERT' || $mode=='REPLACE')$mode.=' INTO';
	if($mode=='DELETE')$mode.=' FROM';
	$OKmodes=array('DELETE FROM', 'INSERT INTO', 'UPDATE', 'REPLACE INTO');
	if(!in_array($mode,$OKmodes))exit('mode passed must be: INSERT [INTO], UPDATE, DELETE [FROM], or REPLACE [INTO]');
	
	$fctn=($addslashes ? 'addslashes' : 'noslashes');
	$resource=($location ? $location : 'GLOBALS');
	if($resource!=='GLOBALS')eval( 'global $'.$resource.';' );
	$ln=__LINE__+1;
	$qx['asdf']=true;
	if(!$sql_insert_update_generic[$db][$table])
		$sql_insert_update_generic[$db][$table]=q('EXPLAIN `'.$db.'`.`'.$table.'`',O_ARRAY, C_MASTER);
	foreach($sql_insert_update_generic[$db][$table] as $v){
		$f=$v['Field'];
		if($v['Key']=='PRI')$primary[]=$v['Key'];
		// -- main logic structure
		unset($x);
		if(isset($fields[$f])){
			//use the declared value for this field
			$x=$fields[$f];
		}else{
			//use the value if in the collection
			eval( 'isset($'.$resource.'["'.$f.'"]) ? $x=$'.$resource.'["'.$f.'"] : "";' );
		}
		if($setCtrlFields && !isset($x)){
			//allow for any cf field with up to a five-letter prefix
			if(preg_match('/^([a-z0-9]{1,5}_)*(createdate|creator|editdate|editor)$/i',$f)){
				switch(true){
					case preg_match('/INSERT INTO|REPLACE INTO/',$mode):
						stristr($f,'createdate')? $sqls[]=$f.'='."'".$dateStamp."'" : '';
						stristr($f,'creator')? $sqls[]=$f.'='."'".($_SESSION['systemUserName'] ? $_SESSION['systemUserName'] : 'system')."'" : '';
						stristr($f,'editdate')? $sqls[]=$f.'='."'".$timeStamp."'" : '';
					break;
					case preg_match('/UPDATE/',$mode):
						stristr($f,'editdate')? $sqls[]=$f.'='."'".$timeStamp."'" : '';
						stristr($f,'editor')? $sqls[]=$f.'='."'".($_SESSION['systemUserName'] ? $_SESSION['systemUserName'] : 'system')."'" : '';
					break;
				}
			}
			continue;
		}
		
		if( isset($x) ){
			if($v['Key']=='PRI' && ($mode=='UPDATE' || $mode=='DELETE FROM')){
				if(isset($existing_primary[$f])){
					$where[]=$f.'='.(is_null($existing_primary[$f])? 'NULL' : "'".$fctn($existing_primary[$f])."'");
					$sqls[]=$f.'='.(is_null($x) || $x=='PHP:NULL'? 'NULL' : "'".$fctn($x)."'");
				}else{
					$where[]=$f.'='.(is_null($x) ? 'NULL' : "'".$fctn($x)."'");
				}
			}else{
				$sqls[]=$f.'='.(is_null($x) || $x=='PHP:NULL' ? 'NULL' : "'".$fctn($x)."'");
			}
		}
		// -- end main logic structure
	}
	//Handles delete from, replace into, insert and update:
	$sql=$mode.' `'.$db.'`.`'.$table.'` ';
	if($mode!=='DELETE FROM')$sql.='SET'."\n".implode(",\n",$sqls)."\n";
	//primary key or query
	if($mode=='UPDATE' || $mode=='DELETE FROM'){
		if(count($where)!==count($primary))exit('UPDATE and DELETE FROM queries cannot run without primary key passed ('.$fl . ', '.$ln.')');
		$sql.='WHERE ';
		$sql.=implode(' AND ',$where);
	}
	//limit possible damage - this function only handles primary key tables
	if($mode=='UPDATE ' || $mode=='DELETE FROM'){
		$sql.=' LIMIT 1';
	}
	return $sql;
}



?>