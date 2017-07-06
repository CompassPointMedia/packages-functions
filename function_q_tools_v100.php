<?php
$functionVersions['q_tools']=1.00;
function q_tools($options=array()){
	/*
	created 2013-06-18 as a does-it-all management feature 
	table_exists
	field_exists
	index_exists
	records_exist [array]
		expected_count
	todo:
	* flag to store results in session vs. q_tools
	* once this can perform operations solidly, it needs a recursive feature for multiple databases
	*/
	extract($options);
	global $q_tools;
	global $fl,$ln,$qr,$qx,$developerEmail,$fromHdrBugs,$acct;
	if(!isset($create))$create=true;
	if(!isset($cnx))$cnx=$qx['defCnxMethod'];
	if(!$db)$db=$acct;
	ob_start();
	if($mode=='table_exists'){
		//previously processed
		if($a=$q_tools['data'][$acct][$table] && !$refresh)return ($return=='data'?$a:true);
		
		$qx['useRemediation']=$create;
		$a=q("EXPLAIN $table", O_ARRAY, ERR_ECHO, $cnx);
		$err=ob_get_contents();
		ob_end_clean();
		if($err && q("SHOW TABLES LIKE '$table'", O_ARRAY, $cnx)){ /*wasn't there but now is*/
			$change=true;
			$a=q("EXPLAIN $table", $cnx, O_ARRAY, O_DO_NOT_REMEDIATE);
		}else if($a){
			//OK			
		}else return false;
		foreach($a as $n=>$v)	$q_tools['data'][$acct][$table][strtolower($v['Field'])]=$v;
		return ($return=='change'?$change:($return=='data'?$q_tools['data'][$acct][$table]:true));
	}else if($mode=='field_exists'){
		if($node=q_tools(array(
			'mode'=>'table_exists', 
			'table'=>$table, 
			'return'=>'data',
			'create'=>false,
			))){
			if($a=$node[strtolower($field)]){
				ob_end_clean();
				return ($return=='change'?false:($return=='data'?$a:true));
			}else{
				//add the field by command or by q remediation
				if($command){
					q($command, $cnx, O_DO_NOT_REMEDIATE, ERR_ECHO);
					$err=ob_get_contents();
					if($err){
						ob_end_clean();
						return false;
					}else{
						//go through again
						$a=q_tools(array(
							'mode'=>'table_exists',
							'table'=>$table,
							'return'=>'data',
						));
						ob_end_clean();
						return ($return=='change'?true:($return=='data'?$a[strtolower($field)]:true));
					}
				}else{
					$qx['useRemediation']=true;
					q("SELECT $field FROM $table", $cnx, ERR_ECHO);
					return true;
				}
			}
		}else{
			ob_end_clean();
			return false;
		}
	}else if($mode=='records'){
		if($submode=='insert'){
			foreach($records as $n=>$v){
				if($check){
					$sql="SELECT COUNT(*) FROM $table WHERE ";
					foreach($check as $w)$sql.=$w.'=\''.addslashes($v[$w]).'\' AND ';
					//if record is present do not insert
					if(q(preg_replace('/ AND $/','',$sql), $cnx, O_VALUE))continue;
				}
				$sql='INSERT INTO '.$table.' SET ';
				foreach($v as $o=>$w)$sql.=$o.'=\''.$w.'\', ';
				$ID=q(preg_replace('/, $/','',$sql), $cnx);
				if($qr['affected_rows'])$change++;
				if($return=='data')$ids[]=$ID;
			}
			ob_end_clean();
			return ($return=='change'?$change:($return=='data'?$ids:true));
		}
	}else{
		ob_end_clean();
		return false;
	}
}

?>