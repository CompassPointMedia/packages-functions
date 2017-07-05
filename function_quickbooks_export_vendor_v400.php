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
$functionVersions['quickbooks_export_vendor']=4.00;
function quickbooks_export_vendor($where='',$options=array()){
	global $quickbooks_export_vendor, $qr, $qx, $fl, $ln, $fromHdrBugs, $developerEmail;
	/*
	see docs for quickbooks_export_items
	*/

	$object='Vendors';
	$rootTable='finan_vendors';
	$rootTableAlias='v';
	$exportObjects=array(
		'Items'=>'INVITEM',
		'Invoices',
		'Chart of Accounts',
		'Vendors'=>'VEND',
		'Customers',
		'Employees',
		'Classes');
	$exportObjectPrefix['vendors']='VEND';

	extract($options);


	//default query
	if($sql=$completeQuery){
		//OK
	}else{
		//build query
		//recognized quickbook fields for vendors
		//NOTE the value can be a string or an array, if an array the value is key 0, key 1 is a list of acceptable values. Key 2 is instruction for "go-shallow" recursive logic for hierarchy
		$qbksFields['Vendors']=array(
			'NAME' =>			'ClientName',
			'TIMESTAMP' =>		'UNIX_TIMESTAMP('.$rootTableAlias.'.EditDate)',
			'REFNUM' =>			'ID',
			'ADDR1' =>			'ClientName',
			'ADDR2' =>			'Address1',
			'ADDR3' =>			'IF('.$rootTableAlias.'.Address2!="", '.$rootTableAlias.'.Address2, CONCAT('.$rootTableAlias.'.City,", ",'.$rootTableAlias.'.State,"  ",'.$rootTableAlias.'.Zip))',
			'ADDR4' =>			'IF('.$rootTableAlias.'.Address2!="", CONCAT('.$rootTableAlias.'.City,", ",'.$rootTableAlias.'.State,"  ",'.$rootTableAlias.'.Zip),"")',
			'VTYPE' =>			'Type',
			'TERMS' =>			array('Terms_ID', NULL, array(
															LABEL_EXTERNAL_NONHIERARCHICAL,
															'finan_terms',
															't',
															'Name',
															'Terms_ID',
															'ID'
															)),
			'CONT1' =>			'CONCAT('.$rootTableAlias.'.PrimaryFirstName, IF('.$rootTableAlias.'.PrimaryMiddleName," ",""), '.$rootTableAlias.'.PrimaryMiddleName, " ", '.$rootTableAlias.'.PrimaryLastName)',
			'HIDDEN' =>			'IF('.$rootTableAlias.'.Active,"N","Y")',
			'PHONE1' =>			'Phone',
			'PHONE2' =>			'Phone2',
			'FAXNUM' =>			'Fax',
			'NOTE' =>			'Notes',
			'LIMIT' =>			'CreditLimit',
			'SALUTATION' =>		'PrimarySalutation',
			'COMPANYNAME' =>	'CompanyName',
			'FIRSTNAME' =>		'PrimaryFirstName',
			'MIDINIT' =>		'PrimaryMiddleName',
			'LASTNAME' =>		'PrimaryLastName',
			'CUSTFLD1' =>		$CUSTFLD1,
			'CUSTFLD2' =>		$CUSTFLD2,
			'CUSTFLD3' =>		$CUSTFLD3,
			'CUSTFLD4' =>		$CUSTFLD4,
			'CUSTFLD5' =>		$CUSTFLD5,
			'CUSTFLD6' =>		$CUSTFLD6,
			'CUSTFLD7' =>		$CUSTFLD7,
			'TAXID' =>			NULL,
			'ADDR5' =>			NULL,
			'\'1099\'' =>		NULL,
			'PRINTAS' =>		NULL			
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
			}
			if(count($goShallow))
			foreach($goShallow as $n=>$v){
				if($v[0]==LABEL_EXTERNAL_NONHIERARCHICAL)continue;
				//see if we need to track down parent items
				$fields[]=($v[0]==LABEL_EXTERNAL_HIERARCHICAL ? $v[2].'.'.$v[4] : $rootTableAlias.'.'.$v[4]).' AS hierarchy_'.$n;
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
	$quickbooks_export_vendor=array();

	if($debugQuery)mail($debugQuery,'debug query for file '.__FILE__, $sql, $fromHdrBugs);
	//prn($sql);
	if($result=q($sql, O_ARRAY, ($cnx ? $cnx : $qx['defCnxMethod']))){
		if($setAsExported){
			q("UPDATE $rootTable a SET a.ToBeExported=0, a.ExportTime=".($setExportTime ? "'".$setExportTime."'" : 'NOW()').", a.Exporter='".($_SESSION['admin']['userName'] ? $_SESSION['admin']['userName'] : 'system')."' WHERE $where ".str_replace(' AND '.$rootTableAlias.'.ToBexported=1','',$filterWhere));
		}
		foreach($result as $rd){
			$i++;
			//-------------------- header -----------------------
			if($i==1){
				$header='!'.$exportObjects[$object];
				foreach($rd as $n=>$v){
					if($n=='ENDFIELDS' || preg_match('/^hierarchy_/',$n))continue;
					$n=str_replace("'",'',$n);
					$header.="\t$n";
				}
				$header.= "\n";
			}
			//-------------------- records ----------------------
			$body.=$exportObjects[$object];
			foreach($rd as $n=>$v){
				if($n=='ENDFIELDS' || preg_match('/^hierarchy_/',$n))continue;
				//get hierarchy prefixes
				if($rd['hierarchy_'.$n]){ // && !isset($hierarchies[$n][$rd['hierarchy_'.$n]])
					if(!isset($hierarchies[$n][$rd['hierarchy_'.$n]])) $hierarchies[$n][$rd['hierarchy_'.$n]]=quickbooks_aux_hierarchy($rd['hierarchy_'.$n], $n, $goShallow[$n]);
					$hierarchy=$hierarchies[$n][$rd['hierarchy_'.$n]];
				}else{
					$hierarchy='';
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
		$quickbooks_export_vendor['customnamedictionary']=$c;
		$quickbooks_export_vendor['records']=$header.$body;
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