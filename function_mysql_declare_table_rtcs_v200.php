<?php
$dbTypeArray[1]='tinyint';
$dbTypeArray[2]='smallint';
$dbTypeArray[3]='mediumint';
$dbTypeArray[4]='int';
$dbTypeArray[5]='bigint';
//decimal fields
$dbTypeArray[6]='float';
$dbTypeArray[7]='double';
$dbTypeArray[8]='decimal';
//time fields
$dbTypeArray[9]='date';
$dbTypeArray[10]='datetime';
$dbTypeArray[11]='timestamp';
$dbTypeArray[12]='time';
$dbTypeArray[13]='year';
//text fields
$dbTypeArray[14]='char';
$dbTypeArray[15]='varchar';
$dbTypeArray[16]='tinyblob';
$dbTypeArray[17]='tinytext';
$dbTypeArray[18]='text';
//long text fields
$dbTypeArray[19]='blob';
$dbTypeArray[20]='mediumblob';
$dbTypeArray[21]='mediumtext';
$dbTypeArray[22]='longblob';
$dbTypeArray[23]='longtext';
$dbTypeArray[24]='enum';
$dbTypeArray[25]='set';

$functionVersions['mysql_declare_table_rtcs']=2.00;
function mysql_declare_table_rtcs($db='', $table, $reload=false, $options=array()){
	//2009-05-31: moved this over to lib functions as own file
	//2006-02-24: converts an entire mysql table into an RTCS array which can be put into a db.  This is my first "lingua franca" use of RTCS without putting it somewhere.
	/***
	2006-02-24 to do: need to get the root information for complete entry - later..
	
	***/
	global $mysql_declare_table_rtcs, $dbTypeArray, $typeFlip, $qr,$qx;
	@extract($options);
	if(!$cnx) $cnx=C_MASTER;
	if(!$db || !$table)return;
	if($mysql_declare_table_rtcs[$db][$table] && !$reload){
		return $mysql_declare_table_rtcs[$db][$table];
	}else{
		//array is .root and .fields
		#we're concerned with root primarily right now
		$idx=get_table_indexes($db,$table,$cnx,true);
		ob_start();
		$fields=q("EXPLAIN ".($db ? "`$db`." : "")."`$table`", O_ARRAY, $cnx, O_DO_NOT_REMEDIATE);
		$err=ob_get_contents();
		ob_end_clean();
		if($err)return false;
		$i=0;
		$typeFlip=array_flip($dbTypeArray);
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
			$RTCS[strtolower($Field)]=$b;
		}
		$mysql_declare_table_rtcs[$db][$table]['fields']=$RTCS;
		return $mysql_declare_table_rtcs[$db][$table];
	}

}
?>