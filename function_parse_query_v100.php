<?php

$parse_query['map']=array(
	'are'=>'like',
	'is'=>'like',
	'exactly'=>'=',
	'before'=>'<',
	'after'=>'>',
	'less than'=>'=',
	'more than'=>'=',
	'greater than'=>'=',
	'from'=>'>=',
	'to'=>'<=',
	'ge'=>'>=',
	'le'=>'<=',
	'regex'=>'REGEXP',
	'regexp'=>'REGEXP'
);
$functionVersions['parse_query']=1.00;
function parse_query($query, $table=''){
	/*
	2009-02-04
	----------
	* added ge,le, and regex|regexp for regular expressions
	* refined coding some to pick up aliases like p/n with the slash in it
	* send developer an email if $parse_query['debug'] set true
	
	created 2008-07-15
	*/
	global $parse_query, $qr, $fl, $ln, $qx;
	//initially OK
	unset($parse_query['error']);
	if($table){
		//OK
	}else if($table=$parse_query['defaultTable']){
		//OK
	}else{
		error_alert('unable to get table for query');
	}
	$query=trim($query);
	$reg='(\b(are|is|exactly|before|after|like|less than|more than|greater than|from|to|regex|regexp|ge|le)\b)|(=|<=|>=|<|>|<>)';
	
	/* ----------- this is probably all flawed --------------- */
	$a=preg_split('/'.$reg.'/i',$query);
	if(count($a)<2)return false;
	$subject=trim($a[0]);
	//get relationship and predicate
	$rest=trim(substr($query,strlen($a[0]), strlen($query)));
	preg_match('/^'.$reg.'/i',$rest,$a);
	#print_r($a);
	if($op=$parse_query['map'][strtolower(trim($a[0]))]){
		//OK
	}else{
		$op=strtolower(trim($a[0]));
	}
	//-----------------------------------------------------------


	$comp=substr($rest,strlen($a[0]),strlen($rest));
	$comp=trim($comp);
	if(!($f=q("EXPLAIN $table", O_ARRAY))){
		if($parse_query['debug']){
			$pln=__LINE__;
			parse_query_notify($pln,$message='unable to get table infor for '.$table);
		}
		error_alert($message);
	}
	//get fields in the table
	if(!($fields=$parse_query['fields'][strtolower($table)])){
		foreach($f as $v){
			if(preg_match('/int/',$v['Type'])){
				$type='int';
			}else if(preg_match('/float/',$v['Type'])){
				$type='float';
			}else if(preg_match('/char|text/',$v['Type'])){
				$type='char';
			}else if(preg_match('/date|time/',$v['Type'])){
				$type='time';
			}
			$fields[strtolower($v['Field'])]=array(
				'name'=>$v['Field'],
				'type'=>$type
			);
		}
		$parse_query['fields'][strtolower($table)]=$fields;
	}
	$aliases=$parse_query['aliases'][strtolower($table)];
	if($a=$fields[$aliases[strtolower(preg_replace('/[^a-z0-9_\/ ]*/i','',$subject))]] or $a=$fields[strtolower(preg_replace('/[^a-z0-9_]*/i','',$subject))]){
		$field=$a['name'];
		$type=$a['type'];
	}
	if(!$field || !$type){
		if($parse_query['debug']){
			$pln=__LINE__;
			parse_query_notify($pln,$message='unrecognized field name in query '.$query);
		}
		$parse_query['error']=$message;
		return false;
	}
	$str=$field.' '.$op.' ';
	if(preg_match('/^\'|"/',$comp,$a) && preg_match('/\'|"$/',$comp,$b)){
		//leave as is - but make sure slashes added
		if($a[0]!==$b[0])error_alert('quotation mark mismatch');
		
	}else{
		if($type=='time'){
			if(($converted=strtotime($comp))==-1){
				if($parse_query['debug']){
					$pln=__LINE__;
					parse_query_notify($pln,$message='invalid date format in query '.$query);
				}
				$parse_query['error']=$message;
				return false;
			}
			$comp=date('Y-m-d H:i:s',$converted);
		}
		$comp="'".($op=='like'?'%':'').$comp.($op=='like'?'%':'')."'";
	}
	
	$str.=$comp;
	return $str;

}
function parse_query_notify($ln,$message){
	global $developerEmail, $fromHdrBugs, $parse_query, $dataobject;
	ob_start();
	print_r(array($parse_query,$dataobject));
	$out=ob_get_contents();
	ob_end_clean();
	mail($developerEmail,'Parse query error line '.$ln,$message . "\n" . $out, $fromHdrBugs);
}
/*

---------- example --------------
$parse_query['aliases']['finan_clients']=array(
	'name'=>'clientname',
	'status'=>'statuses_id'
);
$trans=parse_query($n,'finan_clients');
echo '<pre>';
echo $trans;
?>
<form name="form1" method="get" action="">
	<input name="n" type="text" id="n" value="<?php echo htmlentities(stripslashes($n))?>" size="55">
	<input type="submit" name="Submit" value="Submit">
</form>
<?php

*/
?>
