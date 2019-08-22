<?php
$functionVersions['q_tools']=1.00;
function q_tools($options = []){
	/**

    example: [
        'mode' => 'field_exists',
        'table' => 'addr_access',
        'field' => 'Category',
        'return' => 'change',
        // figure out from template db, or optionally
        'command' => 'ALTER TABLE `addr_access` ADD `Category` CHAR( 30 ) NOT NULL COMMENT \''.date('Y-m-d').'\' AFTER `ID`, ADD UNIQUE `CategoryName`(`Category`,`Name`)',
    ]

	created 2013-06-18 as a does-it-all management feature
	table_exists
	field_exists
	index_exists
	records_exist [array]
		expected_count
	todo:
\	* once this can perform operations solidly, it needs a recursive feature for multiple databases
	*/
	extract($options);
	global $q_tools, $qr, $qx, $acct;

	if(!isset($create)) $create=true;
	if(!isset($cnx)) $cnx=$qx['defCnxMethod'];
	if(empty($mode)) return false;
	if(empty($submode)) $submode = '';
	if(!isset($return)) $return = '';
	if(!isset($refresh)) $refresh = false;
	if(empty($table)) return false;
	if(empty($command)) $command = '';
	if(empty($field)) $field = '';
	if(empty($records)) $records = [];
	if(empty($check)) $check = [];

	if($mode=='table_exists'){
		//previously processed
		if($a=$q_tools['data'][$acct][$table] && !$refresh) return ($return=='data' ? $a : true);

        ob_start();
		$qx['useRemediation']=$create;
		$a=q("EXPLAIN $table", O_ARRAY, ERR_ECHO, $cnx);
		$err=ob_get_contents();
		ob_end_clean();


		if($err && q("SHOW TABLES LIKE '$table'", O_ARRAY, $cnx)){ /*wasn't there but now is*/
			$change=true;
			$a=q("EXPLAIN $table", $cnx, O_ARRAY, O_DO_NOT_REMEDIATE);
		}else if(empty($a)){
		    return false;
        }

		foreach($a as $n => $v){
		    $q_tools['data'][$acct][$table][strtolower($v['Field'])]=$v;
        }
		return ($return == 'change' ? $change : ($return == 'data' ? $q_tools['data'][$acct][$table] : true));
	}else if($mode=='field_exists'){
		if($node = q_tools([
            'mode' => 'table_exists',
            'table' => $table,
            'return' => 'data',
            'create' => (isset($options['create']) ? $options['create'] : true),
        ]
        )){
		    if(!empty($node[strtolower($field)])){
			    $a = $node[strtolower($field)];
				return ($return == 'change' ? false : ($return == 'data' ? $a : true));
			}else{
				//add the field by command or by q remediation
				if($command){

				    ob_start();
					q($command, $cnx, O_DO_NOT_REMEDIATE, ERR_ECHO);
					$err=ob_get_contents();
                    ob_end_clean();

					if($err){
						return false;
					}else{
						//go through again
						$a=q_tools(array(
							'mode' => 'table_exists',
							'table' => $table,
							'return' => 'data',
						));
						return ($return=='change'?true:($return=='data'?$a[strtolower($field)]:true));
					}
				}else{
					$qx['useRemediation']=true;
					q("SELECT $field FROM $table", $cnx, ERR_ECHO);
					return true;
				}
			}
		}else{
			return false;
		}
	}else if($mode == 'records'){
		if($submode == 'insert'){
		    $change = 0;
			foreach($records as $n => $v){
				if($check){
					$sql="SELECT COUNT(*) FROM $table WHERE ";
					foreach($check as $w)$sql.=$w.'=\''.addslashes($v[$w]).'\' AND ';
					//if record is present do not insert
					if(q(preg_replace('/ AND $/','',$sql), $cnx, O_VALUE))continue;
				}
				$sql='INSERT INTO '.$table.' SET ';
				foreach($v as $o => $w)$sql.=$o.'=\''.$w.'\', ';
				$ID=q(preg_replace('/, $/','',$sql), $cnx, O_INSERTID);
				if($qr['affected_rows'])$change++;
				if($return=='data')$ids[]=$ID;
			}
			return ($return == 'change' ? $change : ($return == 'data' ? $ids : true));
		}
	}else{
		return false;
	}
}

