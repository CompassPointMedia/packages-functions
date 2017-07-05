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
$functionVersions['quickbooks_export_items']=4.00;
function quickbooks_export_items($where='',$options=array()){
	global $quickbooks_export_items, $qr, $qx, $fl, $ln, $fromHdrBugs, $developerEmail;
	/*
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
			'NAME' => 			array('Name', NULL, 	  array(
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
															'ID'

															)),
			'COGSACCNT' => 		array('cogsaccnt.Name', NULL, array(
															LABEL_EXTERNAL_HIERARCHICAL,
															'finan_accounts',
															'cogsaccnt',
															'Name',
															'Accounts_ID',
															'ID'

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









					//store go-shallow directives for looping through the results










					$fields[]=$v[2][2].'.'.$v[2][3]. ' AS `' . $n . '`';
					$goShallow[$n]=$v[2];
				}else{
					//prn('2: '.$n . ':' . $v);
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
					//see if we need to track down parent items
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
					$fields[]=($v[0]==LABEL_EXTERNAL_HIERARCHICAL ? $v[2].'.'.$v[4] : $rootTableAlias.'.'.$v[4]).' AS hierarchy_'.$n;
				}
			}
			$fields[]='1 AS ENDFIELDS';
			//prn($fields,1);
			$selectClause='SELECT '.implode(','."\n",$fields) . "\n";
		}
		if(!$fromClause){
			$fromClause='FROM '.$rootTable . ' ' . $rootTableAlias . "\n";
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

	if($debugQuery)mail($debugQuery,'debug query for file '.__FILE__, $sql, $fromHdrBugs);
	//prn($sql,1);









	if($result=q($sql, O_ARRAY, ($cnx ? $cnx : $qx['defCnxMethod']))){
		if($setAsExported){
			q("UPDATE $rootTable a SET a.ToBeExported=0, a.ExportTime=".($setExportTime ? "'".$setExportTime."'" : 'NOW()').", a.Exporter='".($_SESSION['admin']['userName'] ? $_SESSION['admin']['userName'] : 'system')."' WHERE $where ".str_replace(' AND '.$rootTableAlias.'.ToBexported=1','',$filterWhere));
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






				//get hierarchy prefixes
				if($rd['hierarchy_'.$n]){ // && !isset($hierarchies[$n][$rd['hierarchy_'.$n]])
					if(!isset($hierarchies[$n][$rd['hierarchy_'.$n]])) $hierarchies[$n][$rd['hierarchy_'.$n]]=quickbooks_aux_hierarchy($rd['hierarchy_'.$n], $n, $goShallow[$n]);
					$hierarchy=$hierarchies[$n][$rd['hierarchy_'.$n]];
				}else{
					$hierarchy='';
				}
				if($n=='PREFVEND' && strlen($rd[$n])){
					if(!$quickbooks_export_items['vendors'][$rd['system_PREFVEND']])$quickbooks_export_items['vendors'][$rd['system_PREFVEND']]=$rd['system_PREFVEND'];
				}
				$str=$hierarchy.trim( $convert[$n]? quickbooks_aux_convert($rd[$n], $convert[$n]) : $rd[$n] );
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
if(!function_exists('quickbooks_aux_hierarchy')){
	function quickbooks_aux_hierarchy($value, $outputfield, $args,$options=array()){
		//very simple bubble-up function for hierarchies
		while($value){







			$a=q('SELECT `'.$args[3].'`, `'.$args[4].'` FROM '.$args[1].' WHERE '.$args[5].'=\''.addslashes($value).'\'', O_ROW);
			$str=$a[$args[3]].':' . $str;
			$value=$a[$args[4]];
		}
		return $str;
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