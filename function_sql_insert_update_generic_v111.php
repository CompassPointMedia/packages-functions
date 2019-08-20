<?php

define('_FIELD_TYPE_',1);
define('_FIELD_ATTRIB_',2);
function noslashes($x){
	return $x;
}
$functionVersions['sql_insert_update_generic']=1.11;
function sql_insert_update_generic($db, $table, $mode, $options=array()){
	global
    $sql_insert_update_generic,
    $dateStamp,
    $timeStamp,
    $fl,
    $ln;
	unset(
	    $sql_insert_update_generic['failList'],
        $sql_insert_update_generic['dataintegrity'],
        $sql_insert_update_generic['fields'],
        $sql_insert_update_generic['where']
    );
	if(empty($fl)) $fl=__FILE__;
	/*
	2012-06-08: added for mysql:function() not being quoted 
	2012-01-28: changed setCtrlFields as default=true
	2011-01-06: added logical field analysis; function will determine if the data will make it into the table and normalize it
		date values going into date fields were already being converted whether date or datetime
		tinyint(1) fields will now use is_logical() analysis to convert to 1 or 0 if allowLogicalFieldConversion=true 
		(and it is the default)
	2010-04-17: changed logic for mode; now you can just pass $mode from navbuttons as long as first chars = insert|update|replace|delete
	2009-08-26: moved setCtrlFields and other params into options array
	2009-05-13: added the ability to pass the specific value PHP:NULL for a field which will set the value as NULL in mySQL
	2005-12-01: generically inserts or updates a table - intelligent as to fields present
	
	options:
	-------------------------------------------------------------
	fields=array(field=>value, field1=>value1, ..)
	existing_primary=array(primary1=>value1, primary2=>value2, ..)
	setCtrlFields - default false
	addslashes - default false (assume slashes present)
	errHandle - default 1
	
	fields node allows for additional 
	The existing_primary array would be used to create something like "UPDATE table SET UserName='newusername' WHERE UserName='oldusername'" - existing_primary would contain the OLD username value.  This is a bit complex but changing a primary is a rare event anyway
	NOTE: this function could also analyze whether the inserted value will go into the field without suffering change and handle this event by exiting or by mailing or by updating the field:
		1. do nothing
		2. email about the problem
		3. [email and] fix the problem
		4. [email and] exit the program
	***/

	$bin=array(
		'datetime'=>3,
		'timestamp'=>3,
		'date'=>2,
		'time'=>1
	);


	@extract($options);
	//2011-01-06 new defaults
	if(!isset($allowLogicalFieldConversion))$allowLogicalFieldConversion=true;
	if(!isset($allowHumanDatetimeConversion))$allowHumanDatetimeConversion=true;
	
	if(empty($location)) $location='GLOBALS';
	global $globalSetCtrlFields;
	if(!isset($setCtrlFields))$setCtrlFields=(isset($globalSetCtrlFields)?$globalSetCtrlFields:true);
	if(!isset($addslashes))$addslashes=false;
	if(!isset($errHandle))$errHandle=1;
	if(!isset($cnx))$cnx=C_MASTER;
	
	$mode=strtoupper(trim($mode));
	if(!preg_match('/^(INSERT|UPDATE|DELETE|REPLACE)/',$mode,$a))error_alert('mode passed must be: INSERT [INTO], UPDATE, DELETE [FROM], or REPLACE [INTO]');
	$mode=$a[1];
	if($mode=='INSERT' || $mode=='REPLACE')$mode.=' INTO';
	if($mode=='DELETE')$mode.=' FROM';
	
	$fctn=($addslashes ? 'addslashes' : 'noslashes');
	$resource=($location ? $location : 'GLOBALS');
	if($resource!=='GLOBALS')eval( 'global $'.$resource.';' );

	$ln=__LINE__+1;
	if(!$sql_insert_update_generic['prop'][$db][$table]) {
	    $sql = 'EXPLAIN `'.$db.'`.`'.$table.'`';
        $sql_insert_update_generic['prop'][$db][$table] = q($sql, O_ARRAY, $cnx);
    }

	foreach($sql_insert_update_generic['prop'][$db][$table] as $v){
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
						stristr($f,'creator')? $sqls[]=$f.'='."'".($_SESSION['admin']['userName'] ? $_SESSION['admin']['userName'] : ($_SESSION['systemUserName'] ? $_SESSION['systemUserName'] : ($_SERVER['PHP_AUTH_USER'] ? $_SERVER['PHP_AUTH_USER'] : 'system')))."'" : '';
						stristr($f,'editdate')? $sqls[]=$f.'='."'".$timeStamp."'" : '';
					break;
					case preg_match('/UPDATE/',$mode):
						stristr($f,'editdate')? $sqls[]=$f.'='."'".$timeStamp."'" : '';
						stristr($f,'editor')? $sqls[]=$f.'='."'".($_SESSION['admin']['userName'] ? $_SESSION['admin']['userName'] : ($_SESSION['systemUserName'] ? $_SESSION['systemUserName'] : ($_SERVER['PHP_AUTH_USER'] ? $_SERVER['PHP_AUTH_USER'] : 'system')))."'" : '';
					break;
				}
			}
			continue;
		}
		if( isset($x) ){
			//--------------- 2009-08-26 - check DATETIME data integrity of insert/update ----------------------
			preg_match('/^([a-z]+)(.*)/',$v['Type'],$receiver);
			$attrib=$receiver[_FIELD_ATTRIB_];
			$di=2; //i.e. data integrity
			if($bin[$receiver[_FIELD_TYPE_]]){
				//we have a datetime element
				list($unix,$originalformat)=t_parse($x);
				if(strstr($originalformat,':human')){
					//this is a human date
					if($allowHumanDatetimeConversion && !strstr($originalformat,'error')){
						$x=date('Y-m-d H:i:s',$unix);
					}else{
						$sql_insert_update_generic['failList'][$f]=$x;
					}
				}else if(strstr($originalformat,'error')){
					$sql_insert_update_generic['failList'][$f]=$x;
				}else{
					//computer datetime, should go
				}
				preg_match('/(date)*(time)*/',$originalformat,$a);
				$originalformat=$a[0];
				if($bin[$originalformat]){
					if(!($bin[$originalformat] ^ $bin[$receiver[_FIELD_TYPE_]])){
						//OK - ullr
						$di=2;
					}elseif(($bin[$originalformat] ^ $bin[$receiver[_FIELD_TYPE_]])==3){
						//complete data loss
						$sql_insert_update_generic['dataintegrity'][$f]=$di=0;
					}elseif($bin[$receiver[_FIELD_TYPE_]]<$bin[$originalformat]){
						//partial data loss
						$sql_insert_update_generic['dataintegrity'][$f]=$di=1;
					}
				}
			}else if($allowLogicalFieldConversion && strtolower($receiver[_FIELD_TYPE_])=='tinyint' && strstr($receiver[_FIELD_ATTRIB_],'(1)')){
				//tinyint(1) fields interpret as logical
				$logical=output_logical($x);
				if(is_null($logical)){
					$sql_insert_update_generic['dataintegrity'][$f]=$di=0;
				}else{
					$x=$logical;
				}
			}
			//-------------------------------------------------------------------------------------------
	
			unset($yw);
			if($v['Key']=='PRI' && ($mode=='UPDATE' || $mode=='DELETE FROM')){
				if(isset($existing_primary[$f])){
					$yw=(is_null($existing_primary[$f])? 'NULL' : "'".$fctn($existing_primary[$f])."'");
					if(is_null($x) || strtoupper($x)=='PHP:NULL'){
						$y='NULL';
					}else if(strtolower(substr($x,0,6))=='mysql:'){
						$y=preg_replace('/^mysql:/i','',$x);
					}else{
						$y="'".$fctn($x)."'";
					}
					$where[]=$f.'='.$yw;
					$sqls[]=$f.'='.$y;
				}else{
					$yw=(is_null($x) ? 'NULL' : "'".$fctn($x)."'");
					$where[]=$f.'='.$yw;
				}
			}else{
				if(is_null($x) || strtoupper($x)=='PHP:NULL'){
					$y='NULL';
				}else if(strtolower(substr($x,0,6))=='mysql:'){
					$y=preg_replace('/^mysql:/i','',$x);
				}else{
					$y="'".$fctn($x)."'";
				}
				$sqls[]=$f.'='.$y;
			}
			if(isset($yw))$sql_insert_update_generic['where'][$f]=$yw;
			$sql_insert_update_generic['fields'][$f]=$y;
		}else{
			//we do not declare fields for which no values have been set
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

