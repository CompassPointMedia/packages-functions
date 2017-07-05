<?php
$functionVersions['quickbooks_export_invoice']=1.00;
function quickbooks_export_invoice($where='', $options=array()){
	global $headerExported;
	/***************************
	so I modularized this from UrsPress starting 2004-10-31, pulling all variables to the top and goal is eventually to translate the table structure and the field structure to the essential and non-essential fields
	
	
	
	
	
	****************************/
	//declaration line for an invoice
	$trns=array('TRNSID', 'TRNSTYPE', 'TIMESTAMP', 'DATE', 'ACCNT', 'NAME',
	'CLASS', 'AMOUNT', 'DOCNUM', 'MEMO', 'CLEAR', 'TOPRINT', 'ADDR', 'DUEDATE', 'TERMS', 'PAID', 'SHIPDATE', 'INVMEMO');
	//for the split
	$spl=array('SPLID', 'TRNSTYPE', 'TIMESTAMP', 'DATE', 'ACCNT', 'NAME',
	'CLASS', 'AMOUNT', 'DOCNUM', 'MEMO', 'CLEAR', 'QNTY', 'PRICE', 'INVITEM',
	'PAYMETH', 'TAXABLE', 'REIMBEX', 'TOTAL');
	
	$_STRUCTURE['NAME']="CONCAT( IF(bb.ID>0, CONCAT(bb.ID,' - ',bb.PrimaryFirstName,' ',bb.PrimaryLastName,':'), ''), b.ID,' - ', b.PrimaryFirstName,' ', b.PrimaryLastName )";
	$_STRUCTURE['ACCNT']="'Accounts Receivable'";
	$_STRUCTURE['TEMPLATE']="'DEFAULT'";
	$_STRUCTURE['DATE']="CONCAT(IF(DATE_FORMAT(a.CreateDate,'%c')<10,'0',''),DATE_FORMAT(a.CreateDate,'%c'),'/',IF(DATE_FORMAT(a.CreateDate,'%e')<10,'0',''),DATE_FORMAT(a.CreateDate,'%e/%y'))";
	$_STRUCTURE['DOCNUM']="a.ID";
	$_STRUCTURE['ADDR']="b.ClientName\nCONCAT(b.PrimaryFirstName,' ',b.PrimaryLastName)\nb.Address1\nCONCAT(b.City,', ',b.State,' ',b.Zip)";
	$_STRUCTURE['PONUM']="";
	$_STRUCTURE['TERMS']="'PAID'";
	$_STRUCTURE['SPL_INVITEM']="CONCAT(IF(ee.ID>0,CONCAT(ee.Name,':'),''), e.Name)";
	$_STRUCTURE['SPL_ACCNT']="CONCAT(IF(ff.ID>0,CONCAT(ff.Name,':'),''), f.Name)";
	$_STRUCTURE['SPL_QNTY']="g.Quantity";
	$_STRUCTURE['SPL_MEMO']="e.Description";
	$_STRUCTURE['SPL_AMOUNT']="g.Extension";
	$_STRUCTURE['SPL_TOTAL']="g.Extension";
	$_STRUCTURE['INVMEMO']="'Thank You'";
	$_STRUCTURE['TOPRINT']="'Y'";
	$_STRUCTURE['MEMO']="a.Notes";
	$_STRUCTURE['TRNSTYPE']='"INVOICE"';
	$_STRUCTURE['TIMESTAMP']=time();
	$_STRUCTURE['SPL_TRNSTYPE']='"INVOICE"';
	$_STRUCTURE['SPL_TIMESTAMP']=time();
	
	foreach($_STRUCTURE as $n=>$v){
		if(in_array($n,$trns) || in_array(preg_replace('/^SPL_/i','',$n),$spl) || preg_match('/^ADDR/i',$n)){
			if(substr($n,0,4)=='ADDR'){
				//split up the fields
				if(trim($v)){
					$addr=explode("\n",$v);
					if($addr){
						$a=0;
						foreach($addr as $o=>$w){
							if(preg_replace('/^[ ,-]*$/i','',trim($w))){
								$a++;
								$select[]=trim(stripslashes($w)) . " AS ADDR".($a);
							}
						}
					}
				}
			}else{
				if(trim($v)){
					$select[]=stripslashes($v) . " AS " . $n;
				}
			}
		}
	}
	$select[]="a.ID AS TRNSID";
	$select[]="g.ID AS SPLID";
	
	//build the query		
	$sql="SELECT\n" . implode(",\n",$select) . "\n";
	$sql.="
	FROM finan_invoices a, finan_transactions g
	LEFT JOIN finan_clients b ON a.Clients_ID = b.ID
	LEFT JOIN finan_clients bb ON b.Clients_ID = bb.ID  /* for client heirarchy */
	LEFT JOIN finan_accounts c ON a.Accounts_ID=c.ID
	LEFT JOIN finan_accounts cc on c.Accounts_ID=cc.ID
	LEFT JOIN finan_items e ON g.Items_ID = e.ID
	LEFT JOIN finan_items ee ON e.Items_ID = ee.ID /*parent item*/
	LEFT JOIN finan_accounts f ON g.Accounts_ID=f.ID
	LEFT JOIN finan_accounts ff ON f.Accounts_ID=ff.ID
	WHERE a.ID=g.Invoices_ID " . $where;
	$sql.=" ORDER BY a.ID, g.ID";
	$c=<<<aleftarightarockstep
!TRNS	TRNSID	TRNSTYPE	TIMESTAMP	DATE	ACCNT	NAME	CLASS	AMOUNT	DOCNUM	MEMO	CLEAR	TOPRINT	ADDR1	ADDR2	ADDR3	ADDR4	ADDR5	DUEDATE	TERMS	PAID	SHIPDATE	INVMEMO
!SPL	SPLID	TRNSTYPE	TIMESTAMP	DATE	ACCNT	NAME	CLASS	AMOUNT	DOCNUM	MEMO	CLEAR	QNTY	PRICE	INVITEM	PAYMETH	TAXABLE	REIMBEX	EXTRA
!ENDTRNS

aleftarightarockstep;

	$qtyMultiplier=-1;//conform to quickbooks debits and credits
	$invMultiplier=1; //conform to quickbooks debits and credits
	$splMultiplier = $invMultiplier * -1; // inverts the transactions on my system
	$result=mysql_query($sql) or die(mysqli_error(). "<br><br>" . $sql);
	$i=0;
	if(!mysqli_num_rows($result))return false;
	
	while($rd=mysqli_fetch_array($result,MYSQLI_ASSOC)){
		
		if(is_null($rd['NAME'])){
			//build a reporting array for the user
		}else{
			$i++;
			if($i==1 || $rd['DOCNUM']!==$buffer){
				//this is the beginning of a new invoice
				$buffer=$rd['DOCNUM'];
				$r++;
				$invAmtKey[$r]=base_convert(rand(1000000,100000000)*100000000,10,16).'_total';
				if($i==1){
					$rbSubtotal=$rd['SPL_AMOUNT'];
				}else{
					$invAmt[$r-1]=$rbSubtotal*$invMultiplier;
					$rbSubtotal=0;
					$rbSubtotal=$rd['SPL_AMOUNT'];
				}
				//close out the last record
				if($i>1)$records.="ENDTRNS\n";
				
				//print the invoice wrapper -------------------------------------------------------
				$records.="TRNS";
				foreach($trns as $v){
					if($v=='AMOUNT' && !$_STRUCTURE['AMOUNT']){
						$records.="\t" . $invAmtKey[$r];
					}else if($v=='MEMO' || $v=='INVMEMO'){
						$records.="\t".
						str_replace("\n",'[newline_char]',
						str_replace("\t",'[tab_char]',
						str_replace("\r",'[carraigereturn_char]',
						$rd[$v])));
					}else if($v=='ACCNT'){
						if(!trim($rd[$v])){
							$records.="\t[unspecified_AR_or_UF]";
						}else{
							$records.="\t".$rd[$v];
						}
					}else if($v=='ADDR'){
						unset($x);
						$x=array();
						for($ad=1;$ad<=5;$ad++){
							if($y=preg_replace('/^[ ,-]*$/','',trim($rd['ADDR'.$ad]))){
								$x[]=$y;
							}
						}
						$records.="\t".implode("\t",$x). str_repeat("\t",5-count($x));
					}else{
						$records.="\t" . $rd[$v];
						if($v=='NAME'){$clientBuffer=$rd[$v];}
					}
				}
				$records.="\n";
			}else{
				$rbSubtotal+=$rd['SPL_AMOUNT'];
			}
			
			//now enter the split -------------------------------------------------------
			$records.="SPL";
			foreach($spl as $v){
				if('SPL_'.$v=='SPL_AMOUNT'){
					$records.="\t".($rd['SPL_'.$v]*$splMultiplier);
				}else if('SPL_'.$v=='SPL_MEMO'){
					//$records.="\t".
					//str_replace("\n",'[newlinechar]',str_replace("\t",'[tabchar]',str_replace("\r",'[carraigereturnchar]','',$rd['SPL_'.$v])));
					$records.="\t".$rd['SPL_'.$v];
				}else if('SPL_'.$v=='SPL_QNTY'){
						$records.="\t".($rd['SPL_'.$v]*$qtyMultiplier);
				}else if('SPL_'.$v=='SPL_NAME'){
					$records.="\t".$clientBuffer;
				}else if('SPL_'.$v=='SPL_ACCNT'){
					if(!trim($rd['SPL_'.$v])){
						$records.="\t[unspecified_INC_or_EXP]";
					}else{
						$records.="\t".$rd['SPL_'.$v];
					}
				}else if($v=='SPLID'){
					$records.="\t".$rd[$v];
				}else{
					$records.="\t".$rd['SPL_'.$v];
				}
			}
			$records.="\n";
		}
	}
	$invAmt[$r]=$rbSubtotal;
	$rbSubtotal=0;
	$records.="ENDTRNS";

	foreach($invAmtKey as $n=>$v){
		$records=str_replace($v,$invAmt[$n],$records);
	}
	
	$string=$c.$records;
	return $string;
}
?>