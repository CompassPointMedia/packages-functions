<?php
$functionVersions['set_priority']=1.00;
function set_priority($ID, $dir, /*optional from here */ $whereFilter='', $absolute=0,$options=array()){
	/*
	Created 2008-09-13 by Samuel
	$dir = 1 || -1 : 1 means move up but actually means make Priority=Priority-1 : -1 means move down (actually INCREASE Priority by 1)
	
	options:
	$cnx=>defCnxMethod if not specified
	*/
	global $set_priority, $qr, $qx, $fl, $ln;
	//reset
	$set_priority=array();
	extract($options);
	if(!$priorityTable)$priorityTable='finan_items';
	if(!$priorityField)$priorityField='Priority';
	if(!$whereFilter)$whereFilter=1;
	if(!$cnx)$cnx=$qx['defCnxMethod'];
	//better query
	$data=q("SELECT COUNT(DISTINCT Priority) AS 'Distinct', COUNT(*) AS Count, MIN($priorityField) AS min, MAX($priorityField) AS max FROM $priorityTable WHERE $whereFilter", O_ROW);
	if($debug)prn($qr);
	extract($data);
	if($Distinct==$max && $Count==$max && $min==1){
		//sequence is clean
		//echo 'ok';
	}else{
		//clean sequence
		$ids=q("SELECT ID FROM $priorityTable WHERE $whereFilter ORDER BY $priorityField", O_COL);
		if($debug)prn($qr);
		foreach($ids as $v){
			$e++;
			q("UPDATE $priorityTable SET $priorityField=$e WHERE ID='$v'");
			if($debug)prn($qr);
		}
		$max=$e;
	}
	if($thispriority=q("SELECT $priorityField FROM $priorityTable WHERE ID='$ID'", O_VALUE)){
		if($debug)prn($qr);
		if($dir==1 && $thispriority>1){
			//i.e. move product up
			if($absolute){
				q("UPDATE $priorityTable SET $priorityField=$priorityField+1 WHERE $whereFilter AND $priorityField<$thispriority", $cnx);
				if($debug)prn($qr);
				q("UPDATE $priorityTable SET $priorityField=1 WHERE ID=$ID", $cnx);
				if($debug)prn($qr);
			}else{
				//swap
				q("UPDATE $priorityTable SET $priorityField=$priorityField+1 WHERE $whereFilter AND $priorityField+1=$thispriority", $cnx);
				if($debug)prn($qr);
				q("UPDATE $priorityTable SET $priorityField=$thispriority-1 WHERE ID=$ID", $cnx);
				if($debug)prn($qr);
			}
		}else if($dir==-1 && $thispriority<$max){
			//move product down
			if($absolute){
				q("UPDATE $priorityTable SET $priorityField=$priorityField-1 WHERE $whereFilter AND $priorityField>$thispriority", $cnx);
				if($debug)prn($qr);
				q("UPDATE $priorityTable SET $priorityField=$max WHERE ID=$ID", $cnx);
				if($debug)prn($qr);
			}else{
				//swap
				q("UPDATE $priorityTable SET $priorityField=$priorityField-1 WHERE $whereFilter AND $priorityField-1=$thispriority", $cnx);
				if($debug)prn($qr);
				q("UPDATE $priorityTable SET $priorityField=$priorityField+1 WHERE ID=$ID", $cnx);
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