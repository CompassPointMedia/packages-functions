<?php
$functionVersions['quickbooks_export_invoice']=4.00;
function quickbooks_export_invoice($where='', $options=array()){
	global $quickbooks_export_invoice, $qr, $qx, $fl, $ln, $fromHdrBugs, $developerEmail;
	/*
	version 4.1 2013-05-15
	* redo coding to handle Finan2.0 structure
	
	version 4.0 2008-07-24
	2008-12-04: this function is now very clumsy compared to .._export_items - I added $structureOverrides to allow for mods but $structure is a 1d array vs. $qbksFields which is 2d
	
	uses finan_invoices structure PRE-VERSION 2.00 (as for MGA Planning)
	options:
		cnx=array() - for custom connection string
		filterExported [false] (if set to true will filter records that already have been exported)
		setAsExported [true] (set ToBeExported=0 for each record)
		setExportTime - [NOW()] otherwise pass a string literal e.g. '2008-07-24 12:15:03'
		print [true] - set the invoice as ToBePrinted on the EXPORT (not the database record)
		structureOverrides[QBKSFIELDNAME]=SQLEXPRESSION..
	TODO:
	account is hard-coded as accounts rec. - make what the real system name is
	TRNSTYPE = "INVOICE" - same as above
	invoice number - special "w" @front
	
	*/
	extract($options);
	if(!strlen($where)){
		$where=1;
	}else if(is_array($where)){
		$where = 'a.ID IN('.implode(',',$where).')';
	}else{
		//literal
	}
	if($filterExported)$filterWhere=' AND a.ToBeExported=1';
	if(!isset($setAsExported))$setAsExported=true;
	if(!isset($print))$print=true;
	$quickbooks_export_invoice=array();

	//current header I have
	$quickbooks_export_invoice['header']= <<<aleftarightarockstep
!HDR	PROD	VER	REL	IIFVER	DATE	TIME	ACCNTNT	ACCNTNTSPLITTIME
HDR	QuickBooks Pro	Version 17.0D	Release R1P	1	{_ExportCreateDate_}	{_ExportTimeStamp_}	N	0

aleftarightarockstep;
	
	//declaration line for an invoice
	$trns=array('TRNSID', 'TRNSTYPE', 'TIMESTAMP', 'DATE', 'ACCNT', 'NAME',
	'CLASS', 'AMOUNT', 'DOCNUM', 'MEMO', 'CLEAR', 'TOPRINT', 'ADDR', 'DUEDATE', 'TERMS', 'PAID', 'SHIPDATE', 'INVMEMO');
	//for the split
	$spl=array('SPLID', 'TRNSTYPE', 'TIMESTAMP', 'DATE', 'ACCNT', 'NAME',
	'CLASS', 'AMOUNT', 'DOCNUM', 'MEMO', 'CLEAR', 'QNTY', 'PRICE', 'INVITEM',
	'PAYMETH', 'TAXABLE', 'REIMBEX', 'TOTAL');
	$structure=array(
		'REFNUM'=>'a.ID',
		'NAME'=>"CONCAT(IF(bb.ID>0, CONCAT(bb.ClientName,':'), ''), b.ClientName)",
		'ACCNT'=>"'Accounts Receivable'",
		'TEMPLATE'=>"'DEFAULT'",
		'DATE'=>"CONCAT(IF(DATE_FORMAT(a.CreateDate,'%c')<10,'0',''),DATE_FORMAT(a.CreateDate,'%c'),'/',IF(DATE_FORMAT(a.CreateDate,'%e')<10,'0',''),DATE_FORMAT(a.CreateDate,'%e/%y'))",
		'DOCNUM'=>"IF(a.HeaderNumber, a.HeaderNumber, a.ID)",
		'ADDR'=>"b.ClientName\nCONCAT(b.PrimaryFirstName,' ',b.PrimaryLastName)\nb.Address1\nCONCAT(b.City,', ',b.State,' ',b.Zip)",
		'PONUM'=>"",
		'TERMS'=>"'PAID'",
		'CLASS'=>"CONCAT(IF(clclRoot.ID>0,CONCAT(clclRoot.Name,':'),''), clRoot.Name)",
		'SPL_INVITEM'=>"CONCAT(IF(ee.ID>0,CONCAT(ee.Name,':'),''), e.Name)",
		'SPL_ACCNT'=>"CONCAT(IF(ff.ID>0,CONCAT(ff.Name,':'),''), f.Name)",
		'SPL_QNTY'=>"g.Quantity",
		'SPL_MEMO'=>"e.Description",
		'SPL_AMOUNT'=>"g.Extension",
		'SPL_TOTAL'=>"g.Extension",
		'SPL_CLASS'=>"CONCAT(IF(clcl.ID>0,CONCAT(clcl.Name,':'),''), cl.Name)",
		'INVMEMO'=>"'Thank You'",
		'TOPRINT'=>($print ? "'Y'" : "'N'"),
		'MEMO'=>"a.Notes",
		'TRNSTYPE'=>'"INVOICE"',
		'TIMESTAMP'=>time(),
		'SPL_TRNSTYPE'=>'"INVOICE"',
		'SPL_TIMESTAMP'=>time()
	);
	//this will override members of the above array
	if($structureOverrides){
		foreach($structureOverrides as $n=>$v){
			$structure[$n]=$v;
		}
	}
	
	foreach($structure as $n=>$v){
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
								$fields[]=trim(stripslashes($w)) . " AS ADDR".($a);
							}
						}
					}
				}
			}else{
				if(trim($v)){
					$fields[]=stripslashes($v) . " AS " . $n;
				}
			}
		}
	}
	$fields[]="a.ID AS TRNSID";
	$fields[]="g.ID AS SPLID";
	$fields[]='
	c.ID AS system1_Accounts_ID,
	cc.ID AS system2_Accounts_ID,
	f.ID AS system3_Accounts_ID,
	ff.ID AS system4_Accounts_ID,
	e.ID AS system1_Items_ID,
	ee.ID AS system2_Items_ID,
	a.Clients_ID AS system_Clients_ID
	';
	//build the query		
	$sql="SELECT\n" . implode(",\n",$fields) . "\n";
	
	if(!$finanStructure)$finanStructure=2;
	if($finanStructure>=2){
		$sql.="
		FROM finan_headers a
		LEFT JOIN finan_clients b ON a.Clients_ID = b.ID
		LEFT JOIN finan_clients bb ON b.Clients_ID = bb.ID  /* for client heirarchy */
		LEFT JOIN finan_accounts c ON a.Accounts_ID=c.ID
		LEFT JOIN finan_accounts cc on c.Accounts_ID=cc.ID
		LEFT JOIN finan_classes clRoot ON a.Classes_ID=clRoot.ID
		LEFT JOIN finan_classes clclRoot ON clRoot.Classes_ID=clclRoot.ID,
		finan_transactions g
		LEFT JOIN finan_items e ON g.Items_ID = e.ID
		LEFT JOIN finan_items ee ON e.Items_ID = ee.ID /*parent item*/
		LEFT JOIN finan_accounts f ON g.Accounts_ID=f.ID
		LEFT JOIN finan_accounts ff ON f.Accounts_ID=ff.ID
		LEFT JOIN finan_classes cl ON g.Classes_ID=cl.ID
		LEFT JOIN finan_classes clcl ON cl.Classes_ID=clcl.ID
		WHERE a.ID=g.Headers_ID  AND $where $filterWhere ORDER BY a.ID, g.ID";
	}else{
		$sql.="
		FROM finan_invoices a
		LEFT JOIN finan_clients b ON a.Clients_ID = b.ID
		LEFT JOIN finan_clients bb ON b.Clients_ID = bb.ID  /* for client heirarchy */
		LEFT JOIN finan_accounts c ON a.Accounts_ID=c.ID
		LEFT JOIN finan_accounts cc on c.Accounts_ID=cc.ID
		LEFT JOIN finan_classes clRoot ON a.Classes_ID=clRoot.ID
		LEFT JOIN finan_classes clclRoot ON clRoot.Classes_ID=clclRoot.ID,
		finan_transactions g
		LEFT JOIN finan_items e ON g.Items_ID = e.ID
		LEFT JOIN finan_items ee ON e.Items_ID = ee.ID /*parent item*/
		LEFT JOIN finan_accounts f ON g.Accounts_ID=f.ID
		LEFT JOIN finan_accounts ff ON f.Accounts_ID=ff.ID
		LEFT JOIN finan_classes cl ON g.Classes_ID=cl.ID
		LEFT JOIN finan_classes clcl ON cl.Classes_ID=clcl.ID
		WHERE a.ID=g.Invoices_ID  AND $where $filterWhere ORDER BY a.ID, g.ID";
	}
	

	if(!isset($qtyMultiplier))$qtyMultiplier=-1;//conform to quickbooks debits and credits


	if(!isset($invMultiplier))$invMultiplier=1; //conform to quickbooks debits and credits
	$splMultiplier = $invMultiplier * -1; // inverts the transactions on my system

	if($setAsExported){
		if($finanStructure>=2){
			q("UPDATE finan_headers a SET a.ToBeExported=0, a.ExportTime=".($setExportTime ? "'".$setExportTime."'" : 'NOW()').", a.Exporter='".($_SESSION['admin']['userName'] ? $_SESSION['admin']['userName'] : 'system')."' WHERE $where $filterWhere");
		}else{
			q("UPDATE finan_invoices a SET a.ToBeExported=0, a.ExportTime=".($setExportTime ? "'".$setExportTime."'" : 'NOW()').", a.Exporter='".($_SESSION['admin']['userName'] ? $_SESSION['admin']['userName'] : 'system')."' WHERE $where $filterWhere");
		}
	}
	
	if($result=q($sql,O_ARRAY, ($cnx?$cnx:$qx['defCnxMethod']))){
		$c=<<<aleftarightarockstep
!TRNS	TRNSID	TRNSTYPE	TIMESTAMP	DATE	ACCNT	NAME	CLASS	AMOUNT	DOCNUM	MEMO	CLEAR	TOPRINT	ADDR1	ADDR2	ADDR3	ADDR4	ADDR5	DUEDATE	TERMS	PAID	SHIPDATE	INVMEMO
!SPL	SPLID	TRNSTYPE	TIMESTAMP	DATE	ACCNT	NAME	CLASS	AMOUNT	DOCNUM	MEMO	CLEAR	QNTY	PRICE	INVITEM	PAYMETH	TAXABLE	REIMBEX	TOTAL
!ENDTRNS

aleftarightarockstep;
		$i=0;
		foreach($result as $rd){
			if(is_null($rd['NAME'])){
				//build a reporting array for the user
			}else{
				foreach($rd as $n=>$v){
					$rd[$n]=trim($v);
					//handle windows combinations or \r and \n
					$rd[$n]=str_replace("\r\n","\n",$rd[$n]);
					$rd[$n]=str_replace("\n\r","\n",$rd[$n]);
					$rd[$n]=str_replace("\r",' ',$rd[$n]);
					//allow for newline
					$rd[$n]=str_replace("\n",'\\'.'n',$rd[$n]);
				}
				//build used items and accounts IDs
				for($j=1;$j<=4;$j++) ($rd['system'.$j.'_Accounts_ID'] && !$accounts[$rd['system'.$j.'_Accounts_ID']] ? $accounts[$rd['system'.$j.'_Accounts_ID']]=$rd['system'.$j.'_Accounts_ID'] : '');
				for($j=1;$j<=2;$j++) ($rd['system'.$j.'_Items_ID'] && !$items[$rd['system'.$j.'_Items_ID']] ? $items[$rd['system'.$j.'_Items_ID']]=$rd['system'.$j.'_Items_ID'] : '');
				$quickbooks_export_invoice['invoices'][]=$rd['REFNUM'];
				$rd['system_Clients_ID'] && !$customers[$rd['system_Clients_ID']] ? $customers[$rd['system_Clients_ID']]=$rd['system_Clients_ID'] : '';

				$i++;
				if($i==1 || $rd['DOCNUM']!==$buffer){
					//this is the beginning of a new invoice
					$buffer=$rd['DOCNUM'];
					$r++;
					$invAmtKey[$r]=base_convert(rand(1000000,100000000)*100000000,10,16).'_total';
					if($i==1){
						$rbSubtotal=round($rd['SPL_AMOUNT'],2);
					}else{
						$invAmt[$r-1]=round($rbSubtotal*$invMultiplier,2);
						$rbSubtotal=0.00;
						$rbSubtotal=round($rd['SPL_AMOUNT'],2);
					}
					//close out the last record
					if($i>1)$records.="ENDTRNS\n";
					
					//print the invoice wrapper -------------------------------------------------------
					$records.="TRNS";
					foreach($trns as $v){
						if(strstr($rd[$v],"\n") || strstr($rd[$v],"\t") || strstr($rd[$v],'"')){
							$rd[$v]=str_replace('"',"'",$rd[$v]);
							$rd[$v]='"'.$rd[$v].'"';
						}
						if($v=='AMOUNT' && !$structure['AMOUNT']){
							$records.="\t" . $invAmtKey[$r];
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
					$rbSubtotal+=round($rd['SPL_AMOUNT'],2);
				}
				
				//now enter the split -------------------------------------------------------
				$records.="SPL";
				foreach($spl as $v){
					if('SPL_'.$v=='SPL_AMOUNT'){
						$records.="\t".round($rd['SPL_'.$v]*$splMultiplier,2);
					}else if('SPL_'.$v=='SPL_MEMO'){
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
						$records.="\t".trim($rd['SPL_'.$v]);
					}
				}
				$records.="\n";
			}
		}
		$invAmt[$r]=round($rbSubtotal,2);
		$rbSubtotal=0.00;
		$records.="ENDTRNS\n";
		
		foreach($invAmtKey as $n=>$v){
			$records=str_replace($v,$invAmt[$n],$records);
		}
		
		//globalize used items and accounts
		$quickbooks_export_invoice['records']=$c.$records;
		$quickbooks_export_invoice['customers']=$customers;
		$quickbooks_export_invoice['accounts']=$accounts;
		$quickbooks_export_invoice['items']=$items;
		return true;
	}
}
?>