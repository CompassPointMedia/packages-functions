<?php
/*
$COAbsAccts=array('Asset','Liability');
$COARegMaps = array(
	'Member/Full'=> array(1,'lr','Designated Member/Full account - for all positions'),
	'Member/Day'=> array(2,'ur','Designated Member/Day account - for all positions'),
	'Non-member/Full'=> array(3,'ll','Designated Non-member/Full account - for all positions'),
	'Non-member/Day'=> array(4,'ul','Designated Non-member/Day account - for all positions')
);
*/
$functionVersions['coc']=1.00;
function coc($id='', $level=1, $returnType='html', $subGroup=''){
	/* 
	2008-01-19
	this was pared down nicely from function coa()
	2007-06-28
	html|array for 3rd param
	this will return a heirarchical set of rows, OR an assoc array incorrect order for compiling a sort index (hard with parent nodes)
	*/
	global $fl, $ln, $qr, $COCArray /*, $COAbsAccts, $Conference_ID, $COCArray, $COARegMaps*/;
	!$id ? $where="(a.Categories_ID='' OR a.Categories_ID IS NULL) /* AND a.Conference_ID='$Conference_ID' */ $subGroup" : $where="a.Categories_ID='$id' /* AND a.Conference_ID='$Conference_ID' */ $subGroup";
	$a=q("SELECT a.*, b.Name AS Type FROM sma_categories a LEFT JOIN sma_categories_types b ON a.Types_ID=b.ID WHERE $where ORDER BY b.ID, a.Name", O_ARRAY);
	if(count($a)){
		foreach($a as $v){
			@extract($v);
			if(strtolower($returnType)=='html'){
				?><tr id="c<?php echo (false && in_array($Category,$COAbsAccts)?'b':'').'_'.$ID?>" cattype="<?php echo $Type?>" onClick="h(this,'coc',1,event)" onContextMenu="h(this,'coc',1,event);" onDblClick="h(this,'coc',1,event);coc_edit();" class="coc" style="<?php echo !$Active?'visibility:none;':''?>">
				<td style="padding-left:<?php echo 4+($level-1)*17?>px;">
				<?php echo htmlentities($Name)?>
				</td>
				<td><?php echo $Type?></td>
				</tr><?php
			}else{
				$COCArray[count($COCArray)+1]=array(
					'ID'=>$ID,
					'Name'=>$Name,
					'Level'=>$level,
					'Type'=>$Type
				);
			}
			coc($ID, $level+1, $returnType, $subGroup);
		}
	}
}
function coc_get_sqlx($seed, $Conference_ID, $type, $Categories_ID='',$keys=array()){
	/*
	global $$seed;
	if($a=q("SELECT ID, Name FROM epld_finan_accounts WHERE Conference_ID='$Conference_ID' AND Types_ID='$type' AND Categories_ID='$Categories_ID'", O_COL_ASSOC)){
		foreach($a as $id=>$name){
			$ktemp=$keys;
			$ktemp[]=$id;
			if(q("SELECT COUNT(*) FROM epld_finan_accounts WHERE Conference_ID='$Conference_ID' AND Types_ID='$type' AND Categories_ID='$id'",O_VALUE)){
				//go down from here
				$str='$'.$seed;
				if(
				coc_get_sql($seed, $Conference_ID, $type, $id, $ktemp);
			}else{
				//declare the value as 1
				$str='$'.$seed;
				if(count($keys)>0)$str.="[".implode("][",$keys)."]";
				$str.="[$id]";
				$str.='='.$name.';';
				eval($str);
			}
		}
	}
	*/
}
function coc_get_sql($seed, $Conference_ID, $type, $Categories_ID='',$keys=array()){
	/*
	global $$seed;
	if($a=q("SELECT ID, Name FROM epld_finan_accounts WHERE Conference_ID='$Conference_ID' AND Types_ID='$type' AND Categories_ID='$Categories_ID'", O_COL_ASSOC)){
		foreach($a as $id=>$name){
			$ktemp=$keys;
			$ktemp[]=$id;
			if(q("SELECT COUNT(*) FROM epld_finan_accounts WHERE Conference_ID='$Conference_ID' AND Types_ID='$type' AND Categories_ID='$id'",O_VALUE)){
				//go down from here
				coc_get_sql($seed, $Conference_ID, $type, $id, $ktemp);
			}else{
				//declare the value as 1
				$str='$'.$seed;
				if(count($keys)>0)$str.="['".implode("']['",$keys)."']";
				$str.="['$id']";
				$str.='='.$name.';';
				eval($str);
			}
		}
	}
	*/
}


#call function like this: coc_get_sql('bankAccounts', $Conference_ID, 1); //where 1=Bank Account
/* ---------------------------------
return array will look like this:
Array
(
  [Frost Bank] => Array
    (
      [Washing Machine (2)] => 1
      [Washing Machine (3)] => Array
        (
          [Washing Machine (4)] => Array
            (
              [Blender 1] => 1
            )
        )
    )
)
---------------------------------- */



function coc_merge($coc, $type, $Categories_ID, $options=array()){
	/*
	Created 2007-07-07 - allows merging of heirarchical table records as in the chart of accounts, based on the array produced by coc_get_sql() above.  options as follows:
	updateAccountData = 1|0 - will update all the data from the from the database merge record - set to zero if using array
	
	* /
	global $from_confid, $to_confid, $qr, $fl, $ln, $qx;
	foreach($coc as $name=>$subs){
		//pull all the data related to 
		//either insert this or not
		if($aid=q("SELECT ID FROM epld_finan_accounts WHERE Conference_ID='$Conference_ID' AND Name='$name' AND Types_ID='$type' AND Categories_ID='$Categories_ID'", O_VALUE)){
			//we are going to update the account number and notes by default
			if($updateAccountData){
				q("UPDATE epld_finan_accounts SET 
				AcctNumber='
				Notes=
				WHERE");
			}
		}else{
			$aid=q("INSERT INTO epld_finan_accounts SET Categories_ID='get this', Conference_ID='$Conference_ID', Name='$name', Types_ID='get this', AcctNumber='asdf asdf', Description='asdf asdf', Notes='asdf asdf'", O_INSERTID);
			
		}

		if(is_array($subs)){
			coc_merge($subs, $type, $aid);
		}
	
	}
	*/
}


?>