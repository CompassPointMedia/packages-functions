<?php

//if you are going to modify qbksFields, you must have these present or use the integer equivalents
if(!defined('LABEL_EXTERNAL_NONHIERARCHICAL'))define('LABEL_EXTERNAL_NONHIERARCHICAL',1); //label found through foreign key but no recurse needed
if(!defined('LABEL_SELF_HIERARCHICAL'))define('LABEL_SELF_HIERARCHICAL',2); //this table itself contains a hierarchy
if(!defined('LABEL_EXTERNAL_HIERARCHICAL'))define('LABEL_EXTERNAL_HIERARCHICAL',3); //foreign key, and recurse of foreign table needed

//this is an example array which can be flipped with no IL
$qbksItemTypes=array(
	'Service'=>'SERV',
	'Inventory part'=>'INVENTORY',
	'Non-inventory part'=>'PART',
	'Other charge'=>'OTHC'
);
$logicalEquiv=array(
	'Y'=>1,
	'N'=>0,
	'YES'=>1,
	'NO'=>0
);
$functionVersions['quickbooks_export_items']=4.10;
function quickbooks_export_items($where='',$options=array()){
	global $quickbooks_export_items, $qr, $qx, $fl, $ln, $fromHdrBugs, $developerEmail;
	/*
	2012-04-09: option blankAccounts_ID - used in case where Accounts_ID is blank
	2008-12-04: debugQuery = email of where you want the sql statement generated to be sent
	2008-12-03: option to override array qkbksFields using qbksFieldsOverrides as follows:
		$qbksFieldsOverrides['Items']['PREFVEND'] = array(
			'fh.HomeName', /* field name * /
			NULL, /* conversion specs * /
			array(  /* foreign key/hier specs * /
				LABEL_EXTERNAL_NONHIERARCHICAL,
				'gf_fosterhomes',
				'fh',
				'HomeName',
				'Vendors_ID',
				'ID'
			)
		);
		$options=array('qbksFieldsOverrides'=>$qbksFieldsOverrides);
	
	10:10AM - too flexible - too hard! :) - though I'm able to modify the qbksFields array with success for an unusual structure
	2008-12-02: come at Quickbooks imports from a more generic and flexible approach; allow for complex queries, actions performed, filters, modifications to data, etc.
	
	qbksFields - value=default field in a default query

	sample go-shallow directives
	----------------------------------------
	array(
		LABEL_EXTERNAL_HIERARCHICAL,
		$alias='cogsaccnt',
		$table='finan_accounts',
		$label='Name',
		$foreignKey='Accounts_ID',
		$primaryKey='ID'		
	);
	
	*/

	$object='Items';
	$rootTable='finan_items';
	$rootTableAlias='i';
	$exportObjects=array(
		'Items'=>'INVITEM',
		'Invoices',
		'Chart of Accounts',
		'Vendors',
		'Customers',
		'Employees',
		'Classes');
	$exportObjectPrefix['items']='INVITEM';


	extract($options);
	/*
	here's the power: I can get ANY root object eventually from this function including
		items
		classes
		customer list
		vendor list
		
		any field list,
		any object
	
	*/

	//default query
	if($sql=$completeQuery){
		//OK
	}else{
		//build query
		//recognized quickbook fields for invoice items and their default values in the typical SQL Query - from QuickBooks Premier Version 16.0D Release R12P, IIF Version 1.0
		//NOTE the value can be a string or an array, if an array the value is key 0, key 1 is a list of acceptable values. Key 2 is instruction for "go-shallow" recursive logic for hierarchy
		$qbksFields['Items']=array(
			'INVITEMTYPE' =>	array('Type', 'qbksItemTypes'),
	
			/* -------- these all carry "go-shallow" directives ------------
			*/
			'NAME' => 			array($rootTableAlias.'.Name', NULL, 	  array( /* rootTableAlias = new */
															LABEL_SELF_HIERARCHICAL,
															'finan_items',
															$rootTableAlias,
															'Name',
															'Items_ID',
															'ID'
															)),
			'ACCNT' => 			array('accnt.Name', NULL, array(
															LABEL_EXTERNAL_HIERARCHICAL,
															'finan_accounts',
															'accnt',
															'Name',
															'Accounts_ID',
															'ID'
															)),
			'ASSETACCNT' => 	array('assetaccnt.Name', NULL, array(
															LABEL_EXTERNAL_HIERARCHICAL,
															'finan_accounts',
															'assetaccnt',
															'Name',
															'Accounts_ID',
															'ID',
															'AssetAccounts_ID' /* new */
															)),
			'COGSACCNT' => 		array('cogsaccnt.Name', NULL, array(
															LABEL_EXTERNAL_HIERARCHICAL,
															'finan_accounts',
															'cogsaccnt',
															'Name',
															'Accounts_ID',
															'ID',
															'COGSAccounts_ID' /* new */
															)),
			'PREFVEND' => 		array('v.ClientName', NULL, array(
															LABEL_EXTERNAL_NONHIERARCHICAL,
															'finan_vendors',
															'v',
															'ClientName',
															'Vendors_ID',
															'ID'
															)),
			
			'REFNUM' => 		'ID',
			'TIMESTAMP' => 		'UNIX_TIMESTAMP('.$rootTableAlias.'.EditDate)',
			'TAXABLE' => 		'IF('.$rootTableAlias.'.Taxable>0,"Y","N")',
			'ISPASSEDTHRU' => 	'IF('.$rootTableAlias.'.IsPassedThrough>0,"Y","N")',
			'HIDDEN' =>			'IF('.$rootTableAlias.'.Active,"N","Y")',
			'DESC' => 			'Description',
			'PURCHASEDESC' => 	'Description',
			'REORDERPOINT' => 	'ReorderPt',
			'NOTES' => 			'Notes',
			'PRICE' => 			'UnitPrice',
			'COST' => 			'PurchasePrice',
			'CUSTFLD1' => 		'CUSTFLD1',
			'CUSTFLD2' => 		'CUSTFLD2',
			'CUSTFLD3' => 		'CUSTFLD3',
			'CUSTFLD4' => 		'CUSTFLD4',
			'CUSTFLD5' => 		'CUSTFLD5',
		/*	'QNTY' => 			NULL, quickbooks iif file had this twice, not sure why 	*/
			'QNTY' => 			NULL,
			'SALESTAXCODE' => 	NULL,
			'PAYMETH' => 		NULL,
			'TAXVEND' => 		NULL,
			'EXTRA' => 			NULL,
			'DEP_TYPE' => 		NULL,
			'DELCOUNT' => 		NULL,
			'USEID' => 			NULL,
			'ISNEW' => 			NULL,
			'PO_NUM' => 		NULL,
			'SERIALNUM' => 		NULL,
			'WARRANTY' => 		NULL,
			'LOCATION' => 		NULL,
			'VENDOR' => 		NULL,
			'ASSETDESC' => 		NULL, /* not sure */
			'SALEDATE' => 		NULL,
			'SALEEXPENSE' => 	NULL,
			'ASSETNUM' => 		NULL,
			'COSTBASIS' => 		NULL,
			'ACCUMDEPR' => 		NULL,
			'UNRECBASIS' => 	NULL,
			'PURCHASEDATE' => 	NULL
		);
		//this will override members of the above array
		if($qbksFieldsOverrides){
			foreach($qbksFieldsOverrides as $n=>$v){
				foreach($v as $o=>$w){
					$qbksFields[$n][$o]=$w;
				}
			}
		}
		if(!$selectClause){
			foreach($qbksFields[$object] as $n=>$v){
				if(is_null($v))continue;
				if(is_array($v) && $v[2]){

/*
															1 'finan_accounts',
															2 'assetaccnt',
															3 'Name',
															4 'Accounts_ID',
															5 'ID',
															6 'AssetAccounts_ID'
*/
					//store go-shallow directives for looping through the results

					//orig.: $fields[]=$v[2][2].'.'.$v[2][3]. ' AS `' . $n . '`';
					if($v[2][0]==LABEL_EXTERNAL_NONHIERARCHICAL){
						//no need to include a hierarchy_ cognate field
						$fields[]=$v[2][2].'.'.$v[2][3]. ' AS `' . $n . '`';
					}else if($v[2][0]==LABEL_SELF_HIERARCHICAL){
						//we get the first label and the hierarchy
						$fields[]=$v[0].' AS `'.$n.'`';
					}else{ //LABEL_EXTERNAL_HIERARCHICAL
						$fields[]=$rootTableAlias.'.'.($v[2][6] ? $v[2][6] : $v[2][4]) . ' AS `' . $n . '`';
					}
					$goShallow[$n]=$v[2];
				}else{

					if(substr($v,0,7)=='CUSTFLD')continue;
					$str=(is_array($v) ? $v[0] : $v);
					$fields[]=(preg_match('/^[a-z0-9_]+$/i',$str) ? $rootTableAlias.'.' : '') . $str . ' AS `'.$n . '`';
				}
				if(is_array($v) && $a=$v[1]){
					$convert[$n]=$a;
				}
				if($n=='PREFVEND'){
					$fields[]=$rootTableAlias.'.Vendors_ID AS system_PREFVEND';
				}
			}
			if(count($goShallow)){
				foreach($goShallow as $n=>$v){
					if($v[0]==LABEL_EXTERNAL_NONHIERARCHICAL)continue;
					if($v[0]==LABEL_EXTERNAL_HIERARCHICAL){
						//cognate means simply a flag to get the lineage - we will use the entire go-shallow string vs. the actual field value
						$fields[]='1 AS hierarchy_'.$n;
					}else if($v[0]==LABEL_SELF_HIERARCHICAL){
						//cognate means get shallower lineage if present
						$fields[]=$rootTableAlias.'.'.$v[4].' AS hierarchy_'.$n;
					}












				}
			}
			$fields[]='1 AS ENDFIELDS';
			//prn($fields,1);
			$selectClause='SELECT '.implode(','."\n",$fields) . "\n";
		}
		if(!$fromClause){
			$fromClause='FROM '.$rootTable . ' ' . $rootTableAlias . "\n";			if(count($goShallow))
			foreach($goShallow as $v){
				if($v[0]==LABEL_SELF_HIERARCHICAL) continue;
				$fromClause.='LEFT JOIN '.$v[1].' '.$v[2]. ' ON '.$rootTableAlias . '.'.$v[4].'='.$v[2].'.'.$v[5]."\n";
			}
		}
		if(!strlen($where)){
			$where=1;
		}else if(is_array($where)){
			$where = $rootTableAlias.'.ID IN('.implode(',',$where).')';
		}else{
			//literal
		}
		if($filterExported)$filterWhere=' AND '.$rootTableAlias.'.ToBeExported=1';
		$whereClause='WHERE '.$where . ' ' .$filterWhere;
		$sql = $selectClause . $fromClause . $whereClause;
	}

	if(!isset($setAsExported))$setAsExported=true;
	if(!isset($print))$print=true;
	$quickbooks_export_items=array();
	if($debugQuery){
		ob_start();
		$result=q($sql, O_ARRAY, ($cnx ? $cnx : $qx['defCnxMethod']),ERR_ECHO);
		print_r($result);
		$out=ob_get_contents();
		ob_end_clean();
		mail($developerEmail,'debug output for file '.__FILE__, $out, $fromHdrBugs);
	}
	if($result=q($sql, O_ARRAY, ($cnx ? $cnx : $qx['defCnxMethod']))){
		$quickbooks_export_items['count']=count($result);
		if($setAsExported){
			q("UPDATE $rootTable $rootTableAlias SET $rootTableAlias.ToBeExported=0, $rootTableAlias.ExportTime=".($setExportTime ? "'".$setExportTime."'" : 'NOW()').", $rootTableAlias.Exporter='".($_SESSION['admin']['userName'] ? $_SESSION['admin']['userName'] : 'system')."' WHERE $where ".str_replace(' AND '.$rootTableAlias.'.ToBexported=1','',$filterWhere));
		}
		foreach($result as $rd){
			$i++;
			//-------------------- header -----------------------
			if($i==1){
				$header='!INVITEM';
				foreach($rd as $n=>$v){
					if($n=='ENDFIELDS' || preg_match('/^hierarchy_/',$n) || preg_match('/^system_/',$n))continue;
					$n=str_replace("'",'',$n);
					$header.="\t$n";
				}
				$header.= "\n";
			}
			//-------------------- records ----------------------
			$body.='INVITEM';
			foreach($rd as $n=>$v){
				if($n=='ENDFIELDS' || preg_match('/^hierarchy_/',$n) || preg_match('/^system_/',$n))continue;

				if($n=='PREFVEND' && strlen($rd[$n])){
					if(!$quickbooks_export_items['vendors'][$rd['system_PREFVEND']])$quickbooks_export_items['vendors'][$rd['system_PREFVEND']]=$rd['system_PREFVEND'];
				}
				if($n=='ACCNT' && !$v && $blankAccounts_ID){
					//2012-04-09 set Accounts_ID to a default value if not present in items table
					$rd[$n]['ACCNT']=$v['ACCNT']=$blankAccounts_ID;
				}

				//get hierarchy prefixes
				if($rd['hierarchy_'.$n]){
					$key=strtolower($rd['hierarchy_'.$n] . '-'.$rd[$n]);
					if(!isset($hierarchies[$n][$key])){
						$hierarchies[$n][$key]=quickbooks_aux_hierarchy_i2($n, $rd, $goShallow[$n]);
					}
					$str=$hierarchies[$n][$key];
				}else{
					$str=( $convert[$n]? quickbooks_aux_convert($rd[$n], $convert[$n]) : $rd[$n] );
				}



				if(strstr($str,"\n") || strstr($str,"\t") || strstr($str,'"')){
					$str=str_replace("\n",'\\n',$str);
					$str=str_replace('"',"'",$str);
					$str='"'. $str.'"';
				}
				$body.="\t".$str;
			}
			$body.="\n";
		}
		$quickbooks_export_items['customnamedictionary']=$c;
		$quickbooks_export_items['records']=$header.$body;
		return true;
	}
}
if(!function_exists('quickbooks_aux_hierarchy_i2')){
	function quickbooks_aux_hierarchy_i2($field, $record, $args, $options=array()){
		//very simple bubble-up function for hierarchies
		//only called for LABEL_EXTERNAL_HIERARCHICAL and LABEL_SELF_HIERARCHICAL
		/*
			example of LABEL_EXTERNAL_HIERARCHICAL
			--------------------------------------
			1 'finan_accounts',
			2 'assetaccnt',
			3 'Name',
			4 'Accounts_ID',
			5 'ID',
			6 'AssetAccounts_ID'
			
			example of LABEL_SELF_HIERARCHICAL
			----------------------------------
			1 finan_items
			2 i
			3 Name
			4 Items_ID
			5 ID
			
		*/
		global $qr, $developerEmail, $fromHdrBugs;
		extract($options);
		//2009-03-09: trucate item and account names at 31 characters (quickbooks)
		if(!isset($truncateBuild))$truncateBuild=31;
		
		$value=$record[$args[0]==LABEL_SELF_HIERARCHICAL ? 'hierarchy_'.$field : $field];
		while($value){
			$a=q('SELECT `'.$args[3].'`, `'.$args[4].'` FROM '.$args[1].' WHERE '.$args[5].'=\''.addslashes($value).'\'', O_ROW);
			$str=substr($a[$args[3]],0,($truncateBuild>0 ? $truncateBuild : 175)) . (strlen($str)?':':'') . $str;
			$value=$a[$args[4]];
		}
		if($args[0]==LABEL_EXTERNAL_HIERARCHICAL){
			return $str;
		}else if($args[0]==LABEL_SELF_HIERARCHICAL){
			return (strlen($str) ? $str . ':' : '') . substr($record[$field],0,($truncateBuild>0 ? $truncateBuild : 175));
		}
	}
}
if(!function_exists('quickbooks_aux_convert')){
	function quickbooks_aux_convert($value,$array){
		global $developerEmail,$fromHdrBugs;
		if(preg_match('/^[a-z0-9_]+$/i',$array)){
			global $$array;
			foreach($$array as $n=>$v){
				if(strtolower($n)==strtolower($value))return $v;
			}
			mail($developerEmail,'unable to convert passed value '.$value.' using array '. $array,get_globals(),$fromHdrBugs);
			return $value;
		}
	}
}
?>