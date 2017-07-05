<?php
$functionVersions['get_recipient_data_row']='1.00';
function get_recipient_data_row($n){
	/* moved to a_f folder 2011-05-21 */
	global $importHeadersDeclared, $fp, $maxLine, $separator, $headers, $groupQueryResult, $groupQueryArray, $sqlResultRecipientDataRow, $get_recipient_data_row, $Groups_ID;
	if(!$maxLine)$maxLine=15000;
	if(!$separator)$separator=',';

	switch($n){
		case 'group':
			if(list(,$a)=each($groupQueryArray)){
				$i=0;
				foreach($a as $v){
					$a[$i]=$v;
					$i++;
				}
				//get the groups the individual is in - note this doesn't add the groups that those GROUPS are in
				unset($_GroupsList, $GroupsIDs);
				//2006-07-25: note we have a lot of field rlx positions that might be in addr_ContactsGroups beyond title and position, should get field names ideally
				if($_GroupsList=q("SELECT b.Name AS GroupName, a.Groups_ID, a.GroupPrimary, a.Title, a.Position, b.AllowUserToUnsubscribe, c.Name AS CategoryName FROM addr_ContactsGroups a, addr_groups b LEFT JOIN addr_groups_categories c ON c.ID=b.Categories_ID WHERE a.Groups_ID=b.ID AND a.Contacts_ID='".$a['ID']."'", O_ARRAY_ASSOC, C_DEFAULT)){
					foreach($_GroupsList as $v) $_GroupsIDs[]=$v['Groups_ID'];
					$a['_GroupsList']=$_GroupsList;
					$a['_GroupsIDs']=$_GroupsIDs;
					$a['_GroupsCount']=count($_GroupsList);
				}
				return $a;
			}else{
				return false;
			}
		break;
		case 'import':
			switch($_POST[ImportType]){
				case 'tab':
					$separator="\t";

				case 'csv':
					$_POST[ImportType]=='csv'?$separator=',':'';
					if($_POST[ImportHeaders]){
						if(!$importHeadersDeclared){
							//first catch will be headers only
							$a=fgetcsv($fp,$maxLine,$separator);
							foreach($a as $n=>$v){
								$headers[$n]=$v;
							}
							$importHeadersDeclared=true;
						}
					}
					if($a=fgetcsv($fp,$maxLine,$separator)){
						if($_POST[ImportHeaders]){
							foreach($a as $n=>$v){
								$hdr=( trim($headers[$n]) ? preg_replace('/\s*/','',$headers[$n]) : $n );
								$b[$hdr]=$v;
							}
							return $b;
						}else{
							return $a;
						}
					}
				break;
				case 'xls':
				
				
				break;
				default:
					exit('No proper import type declared');
				break;
			
			}
		break;
		case 'complex':
			if($get_recipient_data_row[query_err])return false;
			
			if(!$sqlResultRecipientDataRow){
				ob_start();
				$sqlResultRecipientDataRow=q(stripslashes($_POST['ComplexQuery']),O_ARRAY);
				$err=ob_get_contents();
				ob_end_clean();
				if($err){
					$get_recipient_data_row['query_err']= $err;
					return false;
				}
			}
			if($a=each($sqlResultRecipientDataRow)){
				return $a[1];
			}
			return false;
		case 'manual':
			global $ManualList, $fs;
			if($rd=each($ManualList)){
				$a[0]=$rd['value'];
				return $a;
			}
			return false;
		break;
	}
}
?>