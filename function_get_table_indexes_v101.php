<?php
$functionVersions['get_table_indexes']=1.01;
function get_table_indexes($db, $table, $cnx='', $reload=false, $mode='mysql'){
	/** created 2006-02-09 - SEE BELOW FOR KNOWN BUGS
	2009-05-31: moved over from production fro mysql_declare_table_rtcs
	2006-04-07: eventually this function will get the same array parts for an RTCS call
	2006-02-25: only change from 1.00 is that the array is now lowercase assoc. with [Column_name] as a parameter, and the returned array is extractable to $singleIdx, $multiIdx, and $compoundXML

	here is a sample array returned.  Note that for singleIdx, the key name is the name of the index, and the Column_name param is the name of the field - you can have multiple indexes on a column.  RelateBase, on or about 2006-04-07, only looks at one of the keys named the same as the column - and that's all RB needs
	[singleIdx] => Array
    (
      [aa_tyid] => Array
        (
          [Table] => bif_AttachAttach
          [Non_unique] => 1
          [Key_name] => aa_tyid
          [Seq_in_index] => 1
          [Column_name] => aa_tyid
          [Collation] => A
          [Cardinality] => 2
          [Sub_part] => 
          [Packed] => 
          [Null] => 
          [Index_type] => BTREE
          [Comment] => 
        )
      [aa_title] => Array
        (
          [Table] => bif_AttachAttach
          [Non_unique] => 1
          [Key_name] => aa_title
          [Seq_in_index] => 1
          [Column_name] => aa_title
          [Collation] => A
          [Cardinality] => 2
          [Sub_part] => 
          [Packed] => 
          [Null] => 
          [Index_type] => BTREE
          [Comment] => 
        )
      [aa_child_atid] => Array
        (
          [Table] => bif_AttachAttach
          [Non_unique] => 1
          [Key_name] => aa_Child_atid
          [Seq_in_index] => 1
          [Column_name] => aa_Child_atid
          [Collation] => A
          [Cardinality] => 29
          [Sub_part] => 
          [Packed] => 
          [Null] => 
          [Index_type] => BTREE
          [Comment] => 
        )
    )
  [multiIdx] => Array
    (
      [PRIMARY] => Array
        (
          [0] => Array
            (
              [Table] => bif_AttachAttach
              [Non_unique] => 0
              [Key_name] => PRIMARY
              [Seq_in_index] => 1
              [Column_name] => aa_Parent_atid
              [Collation] => A
              [Cardinality] => 29
              [Sub_part] => 
              [Packed] => 
              [Null] => 
              [Index_type] => BTREE
              [Comment] => 
            )
          [1] => Array
            (
              [Table] => bif_AttachAttach
              [Non_unique] => 0
              [Key_name] => PRIMARY
              [Seq_in_index] => 2
              [Column_name] => aa_Child_atid
              [Collation] => A
              [Cardinality] => 29
              [Sub_part] => 
              [Packed] => 
              [Null] => 
              [Index_type] => BTREE
              [Comment] => 
            )
        )
    )
  [compoundXML] => <compoundKey Key_name="PRIMARY" Column_count="2" Non_unique="0">
<keyColumn Seq_in_index="1" Column_name="aa_Parent_atid" Sub_part="" Comment="">
<keyColumn Seq_in_index="2" Column_name="aa_Child_atid" Sub_part="" Comment="">
</compoundKey>	

	KNOWN BUGS:
	--------------
	2006-02-25: If there is a PRIMARY key for a field and it's also declared unique, this function misses both.  Granted this situation is rare (and not needed) but needs to be addressed since primary is that important
	
	**/
	if($mode!=='mysql') exit('only mysql index mode developed');
	global $get_table_indexes, $dbTypeArray;
	if(!$cnx)$cnx=C_MASTER;
	if($get_table_indexes[$db][$table] && !$reload){
		return $get_table_indexes[$db][$table];
	}else{
		$fl=__FILE__;
		$ln=__LINE__+1;
		ob_start();
		$result=q("SHOW INDEXES FROM `$db`.`$table`", $cnx, ERR_ECHO, O_DO_NOT_REMEDIATE);
		$err=ob_get_contents();
		ob_end_clean();
		if($err)return false;
		
		$typeFlip = array_flip($dbTypeArray);
		$inCompound=false;
		while($v=mysqli_fetch_array($result,MYSQLI_ASSOC)){
			$w++;
			@extract($v);
			if($buffer==$Key_name){
				//duplicate part of a key
				if(!$inCompound){
					$multiIdx[$Key_name][]=$singleIdx[count($singleIdx)-1];
					unset($singleIdx[count($singleIdx)-1]);
					//next two lines overcome "bug" in php: just cause I unset the highest element, this will not reset the next index assigned when I say $singleIdx[]=.. later on.
					$clr=$singleIdx;
					$singleIdx=$clr;
				}
				$multiIdx[$Key_name][]=$v;
				$inCompound=true;
			}else{
				$singleIdx[]=$v;
				$buffer=$Key_name;
				$inCompound=false;
			}
		}
		//set $singleIdx as assoc for reference
		if(count($singleIdx)){
			foreach($singleIdx as $v) $a[strtolower($v['Column_name'])]=$v;
			$singleIdx=$a;
		}
		//store compound keys as XML
		if($multiIdx){
			$compoundKey='';
			foreach($multiIdx as $n=>$v){
				$ci.='<compoundKey Key_name="'.$n.'" Column_count="'.count($v).'"';
				$i=0;
				foreach($v as $w){
					$i++;
					if($i==1)$ci.=' Non_unique="'.$w['Non_unique'].'">'."\n";
					$ci.='<keyColumn Seq_in_index="'.$w['Seq_in_index'].'" Column_name="'.$w['Column_name'].'" Sub_part="'.$w['Sub_part'].'" Comment="'.htmlentities($w['Comment']).'">'."\n";
				}
					
				$ci.='</compoundKey>';
			}
		}
		$get_table_indexes[$db][$table]=array('singleIdx'=>$singleIdx, 'multiIdx'=>$multiIdx, 'compoundXML'=>$ci);
		return $get_table_indexes[$db][$table];
	}
}
?>