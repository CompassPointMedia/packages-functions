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
$functionVersions['coa']=1.00;
function coa($id='', $level=1, $returnType='html', $subGroup=''){
	/* 
	2009-02-26
	broght back from categories to accounts, mods are:
		* modifed the query - dirt simple
		* modified the tr id's - also simple
		* changed attribute cattype to accttype
	2008-01-19
	this was pared down nicely from function coa()
	2007-06-28
	html|array for 3rd param
	this will return a heirarchical set of rows, OR an assoc array incorrect order for compiling a sort index (hard with parent nodes)
	*/
	global $fl, $ln, $qr, $COAArray /*, $COAbsAccts, $Conference_ID, $COAArray, $COARegMaps*/;
	if($subGroup){
		$subGroup=' AND '.preg_replace('/\s*AND\s+/i','',$subGroup);
	}else{
		$subGroup=' AND 1';
	}
	!$id ? $where="(a.Accounts_ID='' OR a.Accounts_ID IS NULL) $subGroup" : $where="a.Accounts_ID='$id' $subGroup";
	$a=q("SELECT a.*, b.Name AS Type FROM finan_accounts a LEFT JOIN finan_accounts_types b ON a.Types_ID=b.ID WHERE $where ORDER BY b.ID, a.Name", O_ARRAY);
	if(count($a)){
		foreach($a as $v){
			@extract($v);
			if(strtolower($returnType)=='html'){
				?><tr id="a_<?php echo $ID?>" accttype="<?php echo $Type?>" onclick="h(this,'coa',1,event)" onContextMenu="h(this,'coa',1,event);" onDblClick="h(this,'coa',1,event);coa_edit();" class="coa" style="<?php echo !$Active?'visibility:none;':''?>">
				<td style="padding-left:<?php echo 4+($level-1)*17?>px;">
				<?php echo htmlentities($Name)?>
				</td>
				<td><?php echo $Type?></td>
				</tr><?php
			}else{
				$COAArray[count($COAArray)+1]=array(
					'ID'=>$ID,
					'Name'=>$Name,
					'Level'=>$level,
					'Type'=>$Type
				);
			}
			coa($ID, $level+1, $returnType, $subGroup);
		}
	}
}





?>