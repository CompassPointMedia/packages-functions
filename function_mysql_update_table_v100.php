<?php
$functionVersions['mysql_update_table']=1.00;
function mysql_update_table($dbTableFrom, $dbTableTo, $options=array()){
	global $mysql_update_table, $qr, $qx, $fl, $ln, $developerEmail, $fromHdrBugs;
	$mysql_update_table=array();
	extract($options);
	if(!$cnx || !$toCnx){
		global $SUPER_MASTER_HOSTNAME,$SUPER_MASTER_USERNAME,$SUPER_MASTER_PASSWORD;
		$cnx=$cnxTo=array($SUPER_MASTER_HOSTNAME,$SUPER_MASTER_USERNAME,$SUPER_MASTER_PASSWORD);
	}
	/* 
	Created 2010-03-31
	settings needed
	importTemplateTableData - merge over data from the template table
	excludeColsOnInsert=array() - for example, exclude the ID
	
	need to notify if field attributes are different or if any data would have been lost
	need some field order organization
	
	*/
	$tableFrom=q("EXPLAIN $dbTableFrom", O_ARRAY, $cnx);
	foreach($tableFrom as $n=>$v){
		$aTableFields[strtolower($v['Field'])]=$n;
	}
	$tableTo=q("EXPLAIN $dbTableTo", O_ARRAY, $cnxTo);
	foreach($tableTo as $n=>$v){
		$bTableFields[strtolower($v['Field'])]=$n;
	}
	//see extra fields needed
	foreach($bTableFields as $n=>$v){
		if(!$aTableFields[$n])$mysql_update_table['addTo'][$n]=$tableTo[$v];
	}
	//create the new table
	$temp=substr(md5(rand(1,1000)),0,5);
	$targetDb=current(explode('.',$dbTableTo));
	$targetTable=end(explode('.',$dbTableTo));
	$createTable=q("SHOW CREATE TABLE $dbTableFrom", O_ARRAY, $cnx);
	$createTable=trim($createTable[1]['Create Table']);
	$createTable=preg_replace('/^CREATE TABLE\s+/','CREATE TABLE '.$targetDb.'.',$createTable);
	$createTable=preg_replace('/^CREATE TABLE '.$targetDb.'.`'.$targetTable.'`/i', 'CREATE TABLE '.$targetDb.'.`'.$targetTable.'_'.$temp.'`', $createTable);
	$createTable=explode("\n",$createTable);
	$endLine=array_pop($createTable);
	$createTable[count($createTable)-1].=',';
	//add the fields from the target table
	if($mysql_update_table['addTo']){
		$getFields=q("SHOW CREATE TABLE $dbTableTo", O_ARRAY, $cnxTo);
		$getFields=$getFields[1]['Create Table'];
		$getFields=explode("\n",$getFields);
	
		foreach($mysql_update_table['addTo'] as $n=>$v){
			foreach($getFields as $o=>$w){
				if(preg_match('/^\s*`'.$n.'`/i', $w)){
					$createTable[]=$w;
				}
			}
		}
	}
	$mysql_update_table['create_table']=$createTable=rtrim(implode("\n",$createTable),',')."\n".$endLine;
	
	//create the table now
	ob_start();
	q($createTable, $cnx, ERR_ECHO);
	$err=ob_get_contents();
	ob_end_clean();
	if($err){
		mail($developerEmail, 'Error file '.__FILE__.', line '.__LINE__,get_globals(),$fromHdrBugs);
		//failed
		return '';
	}else{
		//copy the data over
		if($startCount=q("SELECT COUNT(*) FROM $dbTableTo", O_VALUE, $cnxTo)){
			$mysql_update_table['data_count']=$startCount;
			$a=q("SELECT * FROM $dbTableTo", O_ARRAY, $cnxTo);
			foreach($a as $n=>$v){
				$str="INSERT INTO $dbTableTo".'_'.$temp.' SET ';
				foreach($v as $o=>$w){
					$str.=$o.'=\''.str_replace("'","\'",$w).'\', ';
				}
				$str=rtrim( trim($str), ',');
				q($str,$cnxTo);
			}
		}
		if($startCount==q("SELECT COUNT(*) FROM $dbTableTo".'_'.$temp, O_VALUE, $cnx)){
			//rename original table
			q("ALTER TABLE $dbTableTo RENAME $dbTableTo".'_updating', $cnxTo);
			//rename newly created table
			q("ALTER TABLE $dbTableTo".'_'.$temp.' RENAME '.$dbTableTo, $cnxTo);
			//destroy backup
			q("DROP TABLE $dbTableTo", $cnxTo);
				
			return $mysql_update_table;
		}else{
			//failed
			return '';
		}
	}
}
?>