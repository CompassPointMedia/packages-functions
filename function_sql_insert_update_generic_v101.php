<?php
if($_SERVER['HTTP_HOST']=='www.ldatx.com'){
	//prn(get_included_files());
}
define('_FIELD_TYPE_',1);
define('_FIELD_ATTRIB_',2);
if(!function_exists('no_slashes')){
	function no_slashes($x){
		return $x;
	}
}
$functionVersions['sql_insert_update_generic']=1.01;
if(!function_exists('sql_insert_update_generic')){
function sql_insert_update_generic($db, $table, $mode, $options=array()){
	global $sql_insert_update_generic, $dateStamp, $timeStamp, $qr, $qx, $fl, $ln, $developerEmail, $fromHdrBugs;

	unset($sql_insert_update_generic['failList'],$sql_insert_update_generic['dataintegrity'],$sql_insert_update_generic['fields'],$sql_insert_update_generic['where']);
	$fl=__FILE__;
	/***




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




	if(!$location)$location='GLOBALS';
	global $globalSetCtrlFields;
	if(!isset($setCtrlFields))$setCtrlFields=(isset($globalSetCtrlFields)?$globalSetCtrlFields:false);
	if(!isset($addslashes))$addslashes=false;
	if(!isset($errHandle))$errHandle=1;
	if(!isset($cnx))$cnx=C_MASTER;
	
	$mode=strtoupper(trim($mode));
	if(!preg_match('/^(INSERT|UPDATE|DELETE|REPLACE)/',$mode,$a))error_alert('mode passed must be: INSERT [INTO], UPDATE, DELETE [FROM], or REPLACE [INTO]');
	$mode=$a[1];
	if($mode=='INSERT' || $mode=='REPLACE')$mode.=' INTO';
	if($mode=='DELETE')$mode.=' FROM';
	
	$fctn=($addslashes ? 'addslashes' : 'no_slashes');
	$resource=($location ? $location : 'GLOBALS');
	if($resource!=='GLOBALS')eval( 'global $'.$resource.';' );

	$ln=__LINE__+1;
	if(!$sql_insert_update_generic['prop'][$db][$table])
		$sql_insert_update_generic['prop'][$db][$table]=q('EXPLAIN `'.$db.'`.`'.$table.'`',O_ARRAY, $cnx);
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
			}








			//-------------------------------------------------------------------------------------------
	
			unset($yw);
			if($v['Key']=='PRI' && ($mode=='UPDATE' || $mode=='DELETE FROM')){
				if(isset($existing_primary[$f])){
					$yw=(is_null($existing_primary[$f])? 'NULL' : "'".$fctn($existing_primary[$f])."'");
					$y=(is_null($x) || strtoupper($x)=='PHP:NULL'? 'NULL' : "'".$fctn($x)."'");
					$where[]=$f.'='.$yw;
					$sqls[]=$f.'='.$y;
				}else{
					$yw=(is_null($x) ? 'NULL' : "'".$fctn($x)."'");
					$where[]=$f.'='.$yw;
				}
			}else{
				$y=(is_null($x) || strtoupper($x)=='PHP:NULL' ? 'NULL' : "'".$fctn($x)."'");
				$sqls[]=$f.'='.$y;
			}
			if(isset($yw))$sql_insert_update_generic['where'][$f]=$yw;
			$sql_insert_update_generic['fields'][$f]=$y;


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

}


?>