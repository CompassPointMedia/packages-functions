<?php
/***
2006-04-29: got single indexes going to some degree.  Problems are:
	* 1. if child table is not auto_increment for the PRIMARY, it's not getting added
	* 2. not sure if PRIMARY itself would be added
	3. compound indexes are not being taken care of
	4. uniques don't look for content of the table
	5. THIS IS ALL BUILT ON A FLAWED SYSTEM! The get_table_indexes() function has an IL-rife structure to it (only one index represented), and also RTCS is really built on that the index name is the same as the column name - otherwise the mapping would get real complex quickly
	
2006-04-25: see the new function(s) like is_blob() at the bottom of this page
2006-04-20: this is almost ready to notify, testing operation
2006-04-17:	have the table creation mysql-mysql completed - only need to handle type and index override.  Now working on
$notification values:
	0	NO_NOTIFICATION no notification
	1 	ACTION_NOTIFICATION notification for any action taken
	2 	ERR_NOTIFICATION notification only if error
$error_action values:
	0	no action
	1	terminate the function
	2	terminate the script
	
2006-04-16:
Return values of this function as far as table synching:
	0	table was in synch, no synchronization performed
	1	added the table
	2	added a field(s) I
	4	synchronized a fields(s) U
	8	deleted a field(s) D [like old familiar SIUD acronym]
Error codes:
	0	no problems
	1	attempted to synch table with itself!
	100	unspecified problems encountered
2006-04-04:
I believe this can be finished in a week at the outside, at least being functional for certain uses.  The thing that came to my heart while running recently was to work with the fields first, then work on the indexes.  Minimal notes here initially, but this function needs to make a lot of decisions and handle a lot of contingencies, and my goal is to break down the parsing into logical and elegant chunks

Things that need to be done:
1. convert parent mysql into RTCS-type array
2. do all this for the child (mysql and rtcs)
3. crude translation table docs here
4. loop through parent and create or synchronize fields and also set up flag for delete

Journal of Development
----------------------
[Doing things a bit differently here with a journal - and in addition to notes]
2006-04-04: [4:45AM to 7:00AM]
Have the initial code that compares and either synchronizes or adds fields.  What needs to be developed now is translation table rules and flags.
Three levels of synching:
1. CHANGE_FORCEFULLY - change type, don't worry about IL
2. CHANGE_GENTLY - change type gently so that char(15) gets synched with char(25) but not vice versa, and char(25) will not "downgrade" text field to char
3. CHANGE_EXAMININGLY - check db and upgrade if no IL with current data

2006-04-05: [6:10AM to 8:00AM]
Got first synch happening, added fields from parent to child.  Following is a list of minimum features this is going to require:
1. synchronizing two existing fields to a minimal level
2. adding indexes if not present
3. adding the primary key if not present
2006-04-06: [5:00AM to 8:00AM]
Implemented CHANGE_FORCEFULLY  - lots to go but need to have an email sent any time change_forcefully fails so I can take stock of this.  This would be a SERIOUS email error (develop a function for it).
2006-04-07: [5:40AM to 10:40]
error checking, notification and review. Function starts to finally earn a living - it is used in sys_tables.php in the kjv3 module. First start on getting the indexes; the plain English statement is "let's make sure that the primary key is present and matching, and then we'll deal with all other keys" - lack of an index means slow query, but lack of a primary means failure, and lack of unique is dangerous to bad coding.
OK, so I have some significant improvement on the synch routine plus some repairs on the primary key.  when attempting to synch relatebase_tables between cpm006 and cpm00601 (bad, see changes I made in create string at bottom of page on Journal 2006-04-07), the following came up:

1. ALTER TABLE `cpm00601`.`relatebase_tables` CHANGE `id` `ID` int(7) UNSIGNED NOT NULL AUTO_INCREMENT - this is because ID was set to int(5) and UNSIGNED was removed.  However, ID couldn't take the AUTO_INCREMENT flag yet because it was not yet the primary key [and for a way far out stuff, what if it was a text field in child table?] - have got to get key information sooner and there may not be any elegant way on this.
2. Field RecordVersion was added just fine - note there's no way to alter what the default value is right now.
3. The transfer of the primary key [and auto_increment] went off smoothly but ADD PRIMARY KEY implodes and array of lowercase column names; it should be the actual mixed-case field name that is changed
to do now:
1. fix the above
2. make a way to change default values including default action for certain field names
3. handle other indexes
***/




define('CHANGE_FORCEFULLY',1);
define('CHANGE_GENTLY',2);
define('CHANGE_EXAMININGLY',3);



$functionVersions['rtcs_mysql_synchronize']=1.04;
function rtcs_mysql_synchronize($parentDb, $parentTable, $parentType, $childDb, $childTable, $childType, $options=array()){
	/*** Started about 4-1-2006; this function still has a LOT to do, and 
	Options needed are:
	1. notification - 0,1,2, i.e. none, any action, error
	2. error_action - 0,1,2, i.e. none, terminate function, terminate script
	
	***/
	$fga=func_get_args();
	@extract($options);
	$operationLevel=0;
	//trivial case
	if(strtolower($parentDb.$parentTable.$parentType)==strtolower($childDb.$childTable.$childType)) return false;
	global $rtcs_mysql_synchronize, $rtcs_mysql_synchronize_its, $operationLevel;
	global $dbTypeArray, $adminEmail, $fromHdrBugs, $fl, $ln, $qr, $dateStamp, $timeStamp, $rmsTestMode;
	$rtcs_mysql_synchronize_its++; //master count of calls to this function
	$rtcs_mysql_synchronize[$rtcs_mysql_synchronize_its]['args']=func_get_args();
	$types=array('mysql','rtcs');
	$parentType=strtolower($parentType);
	$childType=strtolower($childType);
	#-------- node 1 -----------
	if(!in_array($parentType,$types) || !in_array($parentType,$types)){
		exit('no valid parent or child type passed');
	}
	//get parent
	if($parentType=='mysql'){
		ob_start();
		$fl=__FILE__; $ln=__LINE__+1;
		$fields=q("EXPLAIN `".$parentDb."`.`".$parentTable."`",O_ARRAY, ERR_ECHO);
		$err=ob_get_contents();
		ob_end_clean();
		if($err){
			#-------- node 2 -----------
			//analyze the error and return status
			prn($err);
			exit('unspecified error getting parent mysql table');
		}
		//get parent indexes
		$parentIndexes=get_table_indexes($parentDb, $parentTable, false /** use C_MASTER **/, true /** reload **/);
		$typeFlip = array_flip($dbTypeArray);
		foreach($fields as $v){
			extract($v);
			$f=strtolower($Field);
			preg_match('/^([a-z]+)(\(([0-9, ]+)\))*/',$Type,$a);
			$parent[$f]['DNAME']=$Field;
			$parent[$f]['DTYPE']=$typeFlip[$a[1]];
			$parent[$f]['DCOMMENT']="Field $Field, $Type, *RTCS version 2.0, by rtcs_mysql_synchronize v1.03, line ".__LINE__;
			$parent[$f]['DATTRIB']=$a[3];
			$parent[$f]['DDEFAULT']=(strlen($Default) ? $Default : ($Null=='YES'? NULL : ''));
			$parent[$f]['DNULL']=($Null=='YES' ? 1 : 0);
			$parent[$f]['DAUTOINC']=($Extra=='auto_increment'? 1 : 0);
			$parent[$f]['DUNSIGNED']=(preg_match('/unsigned/i',$Type)? 1 : 0);
			$parent[$f]['DZEROFILL']=(preg_match('/zerofill/i',$Type)? 1: 0);
			$parent[$f]['DBINARY']=(preg_match('/binary/i',$Type)?1:0);
			//we don't really need this for first pass
			$parent[$f]['DPRIMARY']=($Key=='PRI' ? 1 : 0);
		}
	}else if($parentType=='rtcs'){
		//fix the connection issue on this
		if($parent=q("SELECT LCASE(DNAME), `$parentDb`.relatebase_tables_schema.* FROM `$parentDb`.relatebase_tables_schema, `$parentDb`.relatebase_tables WHERE ID=Tables_ID AND TableName='$parentTable' ORDER BY Idx ASC", O_ARRAY)){
			//OK, ready for next round below
		}else{
			#-------- node 3 -----------
			exit('unable to get parent RTCS');
		}
	}

	//get child
	if($childType=='mysql'){
		ob_start();
		$fl=__FILE__; $ln=__LINE__+1;
		$fields=q("EXPLAIN `".$childDb."`.`".$childTable."`",O_ARRAY, ERR_ECHO);
		$err=ob_get_contents();
		ob_end_clean();
		if($err){
			//create the table - it doesn't exist.  Note that on the connection we're giving the keys to the shop by using C_MASTER to connect; need some type of authorization flag in place, or the currenct conn needs read-access to the template table.  On this creation, we need to be able to usurp indexes and types as well
			$createMissingTable=true;
			if($createMissingTable){
				#-------- node 4 -----------
				$a=q("SHOW CREATE TABLE `$parentDb`.`$parentTable`",O_ROW);
				$ct=preg_replace('/^CREATE TABLE /i','CREATE TABLE `'.$childDb.'`.', $a['Create Table']);
				//run through rb_vars - expected values are {RB_CURRENTACCTNAME} AND {RB_NOW01}
				$ct=rb_vars($ct);
				if(!rtcs_mysql_synchronize_execute($ct, $fga, 1)) return false;
				if($rmsTestMode)prn($qr);
				//if OK passage to this point notify admin
				ob_start();
				print_r($fga);
				$str1=ob_get_contents();
				ob_end_clean();
				mail($adminEmail, 'No child mysql table present, successfully created as flagged createMissingTable (hard-coded) on line '.__LINE__, "Arguments:\n".$str1."\nSQL:\n".$ct, $fromHdrBugs);
				return true;
			}else{
				#-------- node 5 -----------
				exit('No child mysql table present, not flagged for createMissingTable (hard-coded)');
			}
		}
		$typeFlip = array_flip($dbTypeArray);
		$hasBlob['child']=false;
		foreach($fields as $v){
			extract($v);
			$f=strtolower($Field);
			preg_match('/^([a-z]+)(\(([0-9, ]+)\))*/',$Type,$a);
			if(is_blob($typeFlip[$a[1]]))$hasBlob['child']=true;
			$child[$f]['DNAME']=$Field;
			$child[$f]['DTYPE']=$typeFlip[$a[1]];
			$child[$f]['DCOMMENT']="Field $Field, $Type, *RTCS version 2.0, by rtcs_mysql_synchronize v1.04, line ".__LINE__;
			$child[$f]['DATTRIB']=$a[3];
			$child[$f]['DDEFAULT']=(strlen($Default) ? $Default : ($Null=='YES'? NULL : ''));
			$child[$f]['DNULL']=($Null=='YES' ? 1 : 0);
			$child[$f]['DAUTOINC']=($Extra=='auto_increment'? 1 : 0);
			$child[$f]['DUNSIGNED']=(preg_match('/unsigned/i',$Type)? 1 : 0);
			$child[$f]['DZEROFILL']=(preg_match('/zerofill/i',$Type)? 1: 0);
			$child[$f]['DBINARY']=(preg_match('/binary/i',$Type)?1:0);
			$child[$f]['DPRIMARY']=($Key=='PRI' ? 1 : 0); //we don't really need this for first pass
		}
	}else if($childType=='rtcs'){
		//fix the connection issue on this
		if($child=q("SELECT LCASE(DNAME), `$childDb`.relatebase_tables_schema.* FROM `$childDb`.relatebase_tables_schema, `$childDb`.relatebase_tables WHERE ID=Tables_ID AND TableName='$childTable' ORDER BY Idx ASC", O_ARRAY)){
			//OK, ready for next round below
		}else{
			#-------- node 6 -----------
			//here is where we would create the child rtcs
			exit('unable to get child RTCS - create if flagged OK');
		}
	}
	//this indexes the position of the parent fields
	foreach($parent as $field=>$v){
		$i++;
		$indexes[$i]=$field;
	}
	//now begin synchronization
	foreach($parent as $field=>$v){
		extract($v);
		$fieldToLookFor=(strtolower($nameLink[$field]) ? strtolower($nameLink[$field]) : $field);
		if($child[$fieldToLookFor]){
			if($fieldToLookFor <> $field && $child[$field] && $ChangeFieldNames==true){
				//how to handle this: we could 1) not add the field, 2) change the name of the existing field, 3) change the name of the new field - not dealt with right now
				#-------- node 7 -----------
				exit('there is a translation in place, but no flag for what to do');
			}
			/***
			Synchronize: no indexes considered at this point, only name, type, length, and attributes
			Name may be changed or remain through translation
			If we ARE changing the name and there's interference, handle this
			Default is to look at data to prevent IL, but more needed here
			
			***/
			if(!$changeType)$changeType=CHANGE_FORCIBLY;
			/** notice we do the following only for mysql tables.  This might leave an RTCS out of synch with its physical table, but only the two objects compared are considered **/
			$canChangeForcibly = ($childType=='mysql' && !q("SELECT COUNT(*) FROM `$childDb`.`$childTable`",O_VALUE) ? true : false);
			if($changeType==CHANGE_FORCIBLY || $canChangeForcibly){
				if($childType=='mysql'){
					if(
					/** 2006-04-25: NOTE the ternary in the first place of each array below.  We evaluate whether the difference is only a char vs a varchar difference.  If the child table has a text field (and this could easily be the case) it is pointless to alter field to field char(n) since it won't stick **/
					/** parent key params to look at - these are extracted..**/
					array(
						($DTYPE==DCHAR && $child[$fieldToLookFor]['DTYPE']==DVARCHAR
						 && $hasBlob['child']? 1 : $DTYPE),
						$DATTRIB,
						$DUNSIGNED,
						$DNULL,
						$DZEROFILL,
						$DBINARY,
						($compareDefaults ? $DDEFAULT : '')
					)!==
					/** child key params to look at **/
					array(
						($DTYPE==DCHAR && $child[$fieldToLookFor]['DTYPE']==DVARCHAR
						 && $hasBlob['child']? 1 : $child[$fieldToLookFor]['DTYPE']),
						$child[$fieldToLookFor]['DATTRIB'],
						$child[$fieldToLookFor]['DUNSIGNED'],
						$child[$fieldToLookFor]['DNULL'],
						$child[$fieldToLookFor]['DZEROFILL'],
						$child[$fieldToLookFor]['DBINARY'],
						($compareDefaults ? $child[$fieldToLookFor]['DDEFAULT'] : '')
					)){
						$sql = "ALTER TABLE `$childDb`.`$childTable` CHANGE `".$child[$fieldToLookFor]['DNAME']."` ";
						$sql .= '`'.($ChangeFieldNames ? $v['DNAME'] : $child[$fieldToLookFor]['DNAME']).'` ';
						$sql .= rtcs_declare_field_attributes_mysql($v, false);
						/** if the modification includes 'auto_increment' we can only add this if the child field is already a primary key.  ^> we really need to know this before we start **/
						
						if(!rtcs_mysql_synchronize_execute($sql,$fga,1)) return false;
						if($rmsTestMode)prn($qr);
					}
				}else{
					#-------- node 10 -----------
					//rtcs
					exit('line '.__LINE__.' not developed');
				}
			}else if($changeType==CHANGE_GENTLY){
				//update value respecting longer length, null, signs, or broader type	
				exit('line '.__LINE__.' not developed');
			}else{
				// CHANGE_EXAMININGLY - change if no information loss would happen
				exit('line '.__LINE__.' not developed');
			}
		}else{
			/***
			Add field: In this case it will be possible to add the field exactly as it has been given in parent
			Field may be added through translation or added as is
			We need to work out where to add the field (after which existing field).  If the child type is RTCS we need to especially work on this, going back to the DB and re-indexing AS WELL AS re-indexing the array we have
			It may not be possible [later] to set unique with rows in the table - so we'll need to fill with data
			Add any single index if possible here, then we only need to deal with multis and changes of primary - index name also needs translation
			
			***/
			if($childType=='mysql'){
				//attempt to get the position of the new field
				$afterField='';
				for($i=array_search($field,$indexes)-1; $i>=1; $i--){
					if($afterField=$child[$indexes[$i]]['DNAME'])break;
				}
				
				//alter table command
				$sql="ALTER TABLE `$childDb`.`$childTable` ADD ";
				$sql .= $v['DNAME'].' ';
				$sql .= rtcs_declare_field_attributes_mysql($v, false);
				$sql .= ($afterField ? ' AFTER '.$afterField : (array_search($field,$indexes)==1 ? ' FIRST' : ''));
				if(!rtcs_mysql_synchronize_execute($sql, $fga, 1)) return false;
				if($rmsTestMode)prn($qr);
				
				//add the node into the child array
				$child[$fieldToLookFor]=$v;
				
			}else if($childType=='rtcs'){
				#-------- node 13 -----------
				exit('update of RTCS not developed, line '.__LINE__);
			}
		}
		
	}
	exit;
	//handle deletions of fields if called for - not developed
	
	//handle primary key and auto_increment attribute
	if($parentType=='mysql' && $childType=='mysql'){
		//===================== Handle primary key synch ===========================
		#parent single primary
		if($parentIndexes['singleIdx'])
		foreach($parentIndexes['singleIdx'] as $n=>$v){
			if(strtolower($v['Key_name'])=='primary'){
				//implicitly on one column
				$primary[strtolower($n)]=($parent[strtolower($v['Column_name'])]['DAUTOINC'] ? 1 : 0);
				break;
			}
		}
		#parent compound primary
		if(!$primary && $parentIndexes['multiIdx'])
		foreach($parentIndexes['multiIdx'] as $n=>$v){
			if(strtolower($n)=='primary'){
				foreach($v as $o=>$w) $primary[strtolower($w['Column_name'])]= 0 /** Not autoinc if compound? **/;
				break;
			}
		}
		if(!$primary){
			//bad situation mail administrator and exit
			ob_start();
			print_r($GLOBALS);
			$env=ob_get_contents();
			ob_end_clean();
			mail($adminEmail, 'Error in synch', "The parent mysql table $parentDb.$parentTable does not have a primary key\n".$env,$fromHdrBugs);
			exit('No primary key in parent table');
		}
		//get child indexes
		$childIndexes=get_table_indexes($childDb, $childTable, false /** use C_MASTER **/, true /** reload **/);
		#child single primary
		if($childIndexes['singleIdx'])
		foreach($childIndexes['singleIdx'] as $n=>$v){
			if(strtolower($v['Key_name'])=='primary'){
				//implicitly on one column
				$childPrimary[strtolower($n)]=($child[strtolower($v['Column_name'])]['DAUTOINC'] ? 1 : 0);
				break;
			}
		}
		#child compound primary
		if(!$childPrimary && $childIndexes['multiIdx'])
		foreach($childIndexes['multiIdx'] as $n=>$v){
			if(strtolower($n)=='primary'){
				foreach($v as $o=>$w) $childPrimary[strtolower($w['Column_name'])]=0;
				break;
			}
		}
		sort($primary);
		sort($childPrimary);
		
		//------------------------- modify the primary key ---------------------------
		/***
		NOTE: this doesn't take into account field name translation
		***/
		if($primary!==$childPrimary){
			//flip each field to the value side
			unset($a,$b);
			foreach($primary as $n=>$v)$a[]=$n;
			$primary=$a;
			foreach($childPrimary as $n=>$v)$b[]=$n;
			$childPrimary=$b;
			foreach($child as $field=>$v){
				if($v['DAUTOINC']==1){
					$sql="ALTER TABLE `$childDb`.`$childTable` CHANGE `".$v['DNAME']."` `".$v['DNAME']."` ";
					$v['DAUTOINC']=0;
					$sql .= rtcs_declare_field_attributes_mysql($v, false);
					if(!rtcs_mysql_synchronize_execute($sql, $fga, 2)) return false;
					$reacquireChildIndexes=true;
					break;
				}
			}

			$sql="ALTER TABLE `$childDb`.`$childTable` DROP PRIMARY KEY";
			if(!rtcs_mysql_synchronize_execute($sql, $fga, 2)) return false;
			$reacquireChildIndexes=true;
			if($rmsTestMode)prn($qr);
			$sql="ALTER TABLE `$childDb`.`$childTable` ADD PRIMARY KEY (`".
				implode('`, `',$primary)."`)";
			if(!rtcs_mysql_synchronize_execute($sql, $fga, 2)) return false;
			$reacquireChildIndexes=true;
			if($rmsTestMode)prn($qr);
			//this routine looks for autoinc in parent, but adds it to child leaving everything else as it is - may result in errors
			foreach($parent as $field=>$v){
				if($v['DAUTOINC']==1){
					foreach($child as $childField=>$w){
						if($childField==$field){
							$sql="ALTER TABLE `$childDb`.`$childTable` CHANGE `".$v['DNAME']."` `".$v['DNAME']."` ";
							$sql .= rtcs_declare_field_attributes_mysql($v, false);
							if(!rtcs_mysql_synchronize_execute($sql, $fga, 2)) return false;
							$reacquireChildIndexes=true;
							if($rmsTestMode)prn($qr);
							break;
						}
					}
				}
			}
		}
		//-----------------------------------------------------------------------------
	}
	// ---------------------- Handle Indexes ------------------
	if($parentType=='mysql' && $childType=='mysql'){
		if($reacquireChildIndexes){
			$childIndexes=get_table_indexes($childDb, $childTable, false /** use C_MASTER **/, true /** reload **/);
		}
		foreach($parentIndexes['singleIdx'] as $n=>$v){
			if(strtolower($n)!==strtolower($v['Key_name'])) continue; // only consider field-named keys
			#$desiredField; //must get this
			$indexKey='';
			foreach($childIndexes['singleIdx'] as $o=>$w){
				if(strtolower($o)==strtolower($n /**$desiredField**/ )){
					$indexKey=$o;
					$matchedChildSingleIndexes[]=strtolower($o);
					break;
				}
			}
			if(!$indexKey){
				//we can create the index
				$sql="ALTER TABLE `$childDb`.`$childTable` ADD ";
				$sql.=($v['Non_unique']=='1'?'INDEX':'UNIQUE');
				$sql.=" `$n`(`$n`";
				$sql.=(!is_null($v['Sub_part']) && trim($v['Sub_part']) ? '('.$v['Sub_part'].')' : '');
				$sql.=")";
				if(!rtcs_mysql_synchronize_execute($sql, $fga, 2)) return false;
			}else{
				$parentSubPart=(is_null($v['Sub_part']) && !trim($v['Sub_part']) ? 1000000 : $v['Sub_part']);
				$childSubPart=(is_null($childIndexes['singleIdx'][$indexKey]['Sub_part']) && !trim($childIndexes['singleIdx'][$indexKey]['Sub_part']) ? 1000000 : $childIndexes['singleIdx'][$indexKey]['Sub_part']);
				echo "for $n and $o, parent is $parentSubPart, child is $childSubPart<br>";
				if($parentSubPart>$childSubPart){
					//we must drop the index and re-create it - it may fail if rlx is in place
					/**
					NOTE: We'll get an error if the parent field length is longer and has a index subpart, and the child field length hasn't beeen changed
					**/
					$sql="ALTER TABLE `$childDb`.`$childTable` DROP INDEX `".$n."`, ADD ";
					$sql.=($v['Non_unique']=='1'?'INDEX':'UNIQUE');
					$sql.=" `$n`(`$n`";
					$sql.=(!is_null($v['Sub_part']) ? '('.$v['Sub_part'].')' : '');
					$sql.=")";
					if(!rtcs_mysql_synchronize_execute($sql, $fga, 2)) return false;
				}else if($childSubPart>$parentSubPart){
					//send me an email - we don't touch this yet
					ob_start();
					print_r($GLOBALS);
					$env=ob_get_contents();
					ob_end_clean();
					mail($adminEmail, 'An index was not touched', "The child mysql table $childDb.$childTable  has a subpart key longer than the parent mysql table $parentDb.$parentTable\n".$env,$fromHdrBugs);
				}
			}
		}
		$deleteUnmatchedSingleIndexes=true;
		if($deleteUnmatchedSingleIndexes && count($childIndexes)){
			foreach($childIndexes['singleIdx'] as $n=>$v){
				if(!in_array(strtolower($n), $matchedChildSingleIndexes) && strtolower($n)==strtolower($v['Key_name'])){
					//drop the index - called "index" whether it's index or unique
					$sql="ALTER TABLE `$childDb`.`$childTable` DROP INDEX `".$n."`";
					if(!rtcs_mysql_synchronize_execute($sql, $fga, 2)) return false;
				}
			}
		}
		
	
		if($rtcs_mysql_synchronize[$rtcs_mysql_synchronize_its]['queries']){
			ob_start();
			print_r($fga);
			$str1=ob_get_contents();
			ob_end_clean();
			ob_start();
			print_r($rtcs_mysql_synchronize[$rtcs_mysql_synchronize_its]['queries']);
			$str2=ob_get_contents();
			ob_end_clean();
			ob_start();
			print_r($rtcs_mysql_synchronize[$rtcs_mysql_synchronize_its]['errors']);
			$str3=ob_get_contents();
			ob_end_clean();
			mail($adminEmail,'Report for completed synchronization of table on line '.__LINE__, "Arguments:\n".$str1."\nQueries:\n".$str2."\nErrors:\n".$str3, $fromHdrBugs);
		}
	}
}

//$error_action (s)
define('NO_ACTION',0);
define('TERMINATE_FUNCTION',1);
define('TERMINATE_SCRIPT',2);
//$notification (s)
define('NO_NOTIFICATION',0);
define('ACTION_NOTIFICATION',1);
define('ERR_NOTIFICATION',2);
//$operationLevel (s)
/**
0 NO action performed | 1 field actions performed | 2 index actions performed | 4 field errors encountered | 8 index errors encountered [therefore operationLevel >= 4 means error state]
**/

function rtcs_mysql_synchronize_execute($sql, $args, $op=1 /** 1='fieldChange' vs 2='indexChange' **/){
	global $rtcs_mysql_synchronize, $rtcs_mysql_synchronize_its, $fromHdrBugs, $adminEmail, $operationLevel, $rmsTestMode;
	//make flags available
	@extract($args['options']);
	if(!$error_action)$error_action=NO_ACTION;
	if(!$notification)$notification=ACTION_NOTIFICATION;
	$executeQuery=true;			
	if($executeQuery){
		//do it
		$rtcs_mysql_synchronize[$rtcs_mysql_synchronize_its]['queries'][]=$sql;
		ob_start();
		q($sql, ERR_ECHO, C_MASTER);	
		$error=ob_get_contents();
		ob_end_clean();
		if($error){
			$count=count($rtcs_mysql_synchronize[$rtcs_mysql_synchronize_its]['queries'])-1;
			$rtcs_mysql_synchronize[$rtcs_mysql_synchronize_its]['errors'][$count]=$error;
			$operationLevel=bitwise_op($operationLevel, 4 /** field errors **/);
			//action not performed, error on query
			if($error_action==NO_ACTION){
				//no action for now - notify will be at the end of parent function
				return true;
			}else if($error_action==TERMINATE_FUNCTION){
				//gather queries and notify now if flagged
				if($notification==NO_NOTIFICATION){
					//do nothing
				}else if($operationLevel/** 1 = action, 4 = error **/ >= $notification){
					//notify
					ob_start();
					echo "There was an error synchronizing tables in function rtcs_mysql_synchronize_execute() and the function was flagged to terminate THE SCRIPT on error.  This note is on line ".__LINE__.".  Here are the function arguments:\n";
					print_r($args);
					echo "\nHere are the queries executed in this function call.  The last one may be considered failed:\n";
					print_r($rtcs_mysql_synchronize[$rtcs_mysql_synchronize_its]['queries']);
					echo "\nHere are the errors:\n";
					print_r($rtcs_mysql_synchronize[$rtcs_mysql_synchronize_its]['errors']);
					$str=ob_get_contents();
					ob_end_clean();
					mail($adminEmail, 'Error on table synchronization on line '.__LINE__, $str, $fromHdrBugs);
				}
				return false; //signal to terminate function
			}else if($error_action==TERMINATE_PROGRAM){
				//only need to notify
				if($notification==NO_NOTIFICATION){
					//do nothing
				}else if($operationLevel/** 1 = action, 4 = error **/ >= $notification){
					//notify
					ob_start();
					echo "There was an error synchronizing tables in function rtcs_mysql_synchronize_execute() and the function was flagged to terminate on error.  This note is on line ".__LINE__.".  Here are the function arguments:\n";
					print_r($args);
					echo "\nHere are the queries executed in this function call.  The last one may be considered failed:\n";
					print_r($rtcs_mysql_synchronize[$rtcs_mysql_synchronize_its]['queries']);
					echo "\nHere are the errors:\n";
					print_r($rtcs_mysql_synchronize[$rtcs_mysql_synchronize_its]['errors']);
					$str=ob_get_contents();
					ob_end_clean();
					mail($adminEmail, 'Error on table synchronization on line '.__LINE__, $str, $fromHdrBugs);
				}
				exit('Function rtcs_mysql_synchronize_execute() flagged to be terminated on line '.__LINE__);
			}
		}else{
			//action performed, no error on query
			$operationLevel=bitwise_op($operationLevel, $op /** field operations **/);
			return true;
		}
	}				
}



function is_blob($x){
	global $ty_longfixedlengthcharacter, $ty_longcharacter, $ty_blobfields;
	if(in_array($x,$ty_longfixedlengthcharacter) || in_array($x,$ty_longcharacter) || in_array($x,$ty_blobfields) || $x==17 /** tinytext **/) return true;
	return false;
}
?>