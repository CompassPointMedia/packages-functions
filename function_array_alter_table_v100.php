<?php
$functionVersions['array_alter_table']=1.0;
function array_alter_table(){
	global $table, $schemaArray, $dbTypeArray;
	/*
	2009-12-03 - blast from the past moved to the library today
	*/

	for($i=1;$i<=sizeof($schemaArray);$i++){
		//see $bufferField at end of document
		if($i==1){$useField=$_POST['addPosition'];}else{$useField=$bufferField;}
		
		$createString .= "ADD ";
	
		//until I get the include checkbox in there.
		$includeField=1;
		
		//I am depending on several factors, including a valid DNAME
		if($includeField &&$schemaArray[$i][DNAME]){
			$createString .= $schemaArray[$i][DNAME];
			$createString .= ' ' . $dbTypeArray[$schemaArray[$i][DTYPE]];
			if($schemaArray[$i][DATTRIB] <> '' && $schemaArray[$i][DTYPE] <> DDATETIME){
				$createString .= '(' . $schemaArray[$i][DATTRIB] . ') ';
			}else{$createString .= ' ';}

			//handle sign, 10
			if(!$schemaArray[$i][DSIGN] &&($schemaArray[$i][DTYPE]==DSMALLINT || $schemaArray[$i][DTYPE]==DINT || $schemaArray[$i][DTYPE]==DBIGINT /* 2002-12-09: you can't do this! -- || $schemaArray[$i][DTYPE]==DFLOAT */ || $schemaArray[$i][DTYPE]==DDOUBLE || $schemaArray[$i][DTYPE]==DDECIMAL )){$createString .= ' unsigned';}

			//handle null, 7
			#this probably doesn't take into account all reasons to say NOT NULL
			if(!($schemaArray[$i][DTYPE] == DTEXT) &&
			!($schemaArray[$i][DTYPE] == DFLOAT) &&
			(!$schemaArray[$i][DNULL] || $schemaArray[$i][DPRIMARY])
			){
				$createString .= ' not null';
			#this doesn't take into account all reasons to withhold saying null
			}elseif(!($schemaArray[$i][DTYPE] == DTEXT) && !($schemaArray[$i][DTYPE] == DFLOAT)){
				$createString .= ' null';
			}

			//handle default, 5
			if($schemaArray[$i][DDEFAULT]){
				$createString .= ' default ' . "'" . str_replace("'", "\'", $schemaArray[$i][DDEFAULT]) . "'";}

			//handle auto increment, 8 (build a string)
			/* I have disabled this feature since for relatebase structure so far,
			if they need an auto-incrementing field it can be handled in the ID */
			#if($schemaArray[$i][DAUTOINC]){$createString .= ' auto_increment';}

			//handle primary key, 9, build string
			if($schemaArray[$i][DPRIMARY]){$primaryKeyString .= $schemaArray[$i][DNAME] . ', ';}

			//handle zero fill, 11
			if($schemaArray[$i][DZEROFILL]){$createString .= ' zerofill';}

			//handle indexed, 12
			if($schemaArray[$i][DINDEX]){$indexString .= $schemaArray[$i][DNAME] . ', ';}

			//handle unique, 13
			if($schemaArray[$i][DUNIQUE]){$createString .= ' unique';}

			//handle binary, 14
			if($schemaArray[$i][DBINARY]){$createString .= ' binary';}

			if($i==1 && $_POST['addPosition']==-1){
				$afterClause='FIRST';
			}elseif($i==1 && $_POST['addPosition']){
				$afterClause='AFTER ' . $useField;
			}elseif($_POST['addPosition']){
				$afterClause='AFTER ' . $useField;
			}

			$createString .= " $afterClause,\n";
			
		}		
		$bufferField=$schemaArray[$i][DNAME];
	}
	#comments at the end of the string
	
	
	//table name
	($tableName =$_POST['table']['name']?'':$tableName = $_POST['tableName']);
	
	$createString = 'ALTER table '  . $tableName . "\n" .
	substr($createString,0,strlen($createString)-2) . "\n";
	
	if($primaryKeyString){
		$createString = str_replace($rootFieldStructure['ID'] . ",\n",'',$createString);
	}
	return $createString;
}//end array_alter_table()

?>