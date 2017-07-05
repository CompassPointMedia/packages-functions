<?php

$mysql_dfar_refresh=false;
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

$functionVersions['mysql_declare_field_attributes_rtcs']=1.00;
function mysql_declare_field_attributes_rtcs($db,$table,$field='', $options=array()){
	/*2006-07-20: if not present, stores field attributes in 0(server).db.table as an array, and returns that field if present, else the whole array.
	**/
	global $mysql_dfar_store, $dbTypeArray, $mysql_declare_field_attributes_rtcs;
	@extract($options);
	if(!$cnx)$cnx=C_MASTER;

	$d=strtolower($db);
	$t=strtolower($table);
	$field=strtolower($field);

	if(!$mysql_declare_field_attributes_rtcs[0][$d][$t] || !$mysql_dfar_refresh){
		//this is dangerous because the field may change - but this array only lasts as long as the script
		//get the information
		if(!($a=q("EXPLAIN `$db`.`$table`",$cnx,O_ARRAY,ERR_SILENT))) return; /** no such db.table **/
		//put it in array
		foreach($a as $v){
			$f=strtolower($v['Field']);
			preg_match('/^([a-z]+)(\([0-9 ,]+\))*/i',$v['Type'],$attrib);
			$mysql_declare_field_attributes_rtcs[0][$d][$t][$f]['DNAME']=$v['Field'];
			$mysql_declare_field_attributes_rtcs[0][$d][$t][$f]['DTYPE']=
				/**$dbTypeArray[$attrib[1]];**/ array_search($attrib[1], $dbTypeArray);
			$mysql_declare_field_attributes_rtcs[0][$d][$t][$f]['DATTRIB']=
				preg_replace('/\(|\)/','',$attrib[2]);
			$mysql_declare_field_attributes_rtcs[0][$d][$t][$f]['DNULL']=($v['Null']? 1 : 0);
			$mysql_declare_field_attributes_rtcs[0][$d][$t][$f]['DDEFAULT']=$v['Default'];
			$mysql_declare_field_attributes_rtcs[0][$d][$t][$f]['DAUTOINC']=
				(strstr($v['Extra'],'auto_inc') ? 1 : 0);
			$mysql_declare_field_attributes_rtcs[0][$d][$t][$f]['DBINARY']=
				(strstr($v['Type'],'binary') ? 1 : 0);
			$mysql_declare_field_attributes_rtcs[0][$d][$t][$f]['DUNSIGNED']=
				(strstr($v['Type'],'unsigned') ? 1 : 0);
			$mysql_declare_field_attributes_rtcs[0][$d][$t][$f]['DZEROFILL']=
				(strstr($v['Type'],'zerofill') ? 1 : 0);
		}
	}
	//return this specific value set, blank if field not in table
	if($field){
		return $mysql_declare_field_attributes_rtcs[0][$d][$t][$field];
	}else{
		return $mysql_declare_field_attributes_rtcs[0][$d][$t];
	}
}

?>