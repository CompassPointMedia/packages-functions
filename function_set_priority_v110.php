<?php
$functionVersions['set_priority']=1.10;
function set_priority($ID, $dir, $abs, $options=array()){
	global $idxdataset;
	/*
  [0] => 16
  [1] => 1
  [2] => 0
  [3] => Array
    (
      [whereFilter] => ResourceType IS NOT NULL
      [priorityTable] => ss_albums
      [priorityField] => Priority
    )


	updated 2010-05-20 by Samuel
	* TODO: allow $ID to = array and then interpret as being a compound primary and auto-configuring this
	* reduced/changed order of parameters; table is now defined by default as cms1_articles
	Created 2008-09-13 by Samuel
	$dir = 1 || -1 : 1 means move up but actually means make Priority=Priority-1 : -1 means move down (actually INCREASE Priority by 1)
	
	options:
	$cnx=>defCnxMethod if not specified
	*/
	global $set_priority, $qr, $qx, $fl, $ln, $developerEmail, $MASTER_USERNAME,$fromHdrBugs;
	//reset
	$set_priority=array();
	@extract($options);
	if(!$priorityTable)$priorityTable='cms1_articles';
	if(!$priorityField)$priorityField='Priority';
	if(!$IDField)$IDField='ID';
	if(!$whereFilter)$whereFilter=1;
	if(!$cnx)$cnx=$qx['defCnxMethod'];
	//better query
	ob_start();
	$data=q("SELECT COUNT(DISTINCT Priority) AS 'Distinct', COUNT(*) AS Count, MIN($priorityField) AS min, MAX($priorityField) AS max FROM $priorityTable WHERE $whereFilter", O_ROW, ERR_ECHO);
	$err=ob_get_contents();
	ob_end_clean();
	if($err){
		mail($developerEmail, 'Error in '.$MASTER_USERNAME.':'.end(explode('/',__FILE__)).', line '.__LINE__,get_globals($err='You do not have access to this page'),$fromHdrBugs);
		return false;
	}

	
	if($idxdataset=='albumList')$debug=true;
	if($debug)prn($qr);
	extract($data);
	if($Distinct==$max && $Count==$max && $min==1){
		//sequence is clean
		//echo 'ok';
	}else{
		//clean sequence
		$ids=q("SELECT $IDField FROM $priorityTable WHERE $whereFilter ORDER BY $priorityField", O_COL);
		if($debug)prn($qr);
		foreach($ids as $v){
			$e++;
			q("UPDATE $priorityTable SET $priorityField=$e WHERE $IDField='$v'");
			if($debug)prn($qr);
		}
		$max=$e;
	}
	if($thispriority=q("SELECT $priorityField FROM $priorityTable WHERE $IDField='$ID'", O_VALUE)){
		if($debug)prn($qr);
		if($dir==1 && $thispriority>1){
			//i.e. move product up
			if($abs){
				q("UPDATE $priorityTable SET $priorityField=$priorityField+1 WHERE $whereFilter AND $priorityField<$thispriority", $cnx);
				if($debug)prn($qr);
				q("UPDATE $priorityTable SET $priorityField=1 WHERE $IDField=$ID", $cnx);
				if($debug)prn($qr);
			}else{
				//swap
				q("UPDATE $priorityTable SET $priorityField=$priorityField+1 WHERE $whereFilter AND $priorityField+1=$thispriority", $cnx);
				if($debug)prn($qr);
				q("UPDATE $priorityTable SET $priorityField=$thispriority-1 WHERE $IDField=$ID", $cnx);
				if($debug)prn($qr);
			}
		}else if($dir==-1 && $thispriority<$max){
			//move product down
			if($abs){
				q("UPDATE $priorityTable SET $priorityField=$priorityField-1 WHERE $whereFilter AND $priorityField>$thispriority", $cnx);
				if($debug)prn($qr);
				q("UPDATE $priorityTable SET $priorityField=$max WHERE $IDField=$ID", $cnx);
				if($debug)prn($qr);
			}else{
				//swap
				q("UPDATE $priorityTable SET $priorityField=$priorityField-1 WHERE $whereFilter AND $priorityField-1=$thispriority", $cnx);
				if($debug)prn($qr);
				q("UPDATE $priorityTable SET $priorityField=$priorityField+1 WHERE $IDField=$ID", $cnx);
				if($debug)prn($qr);
			}
		}else{
			$set_priority['notice']='no move needed';
		}
	}else{
		$set_priority['error']='id of target record not found in group';
	}
	if($debug)prn($set_priority);
}
?>