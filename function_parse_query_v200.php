<?php

$parse_query['map']=array(
	'are'=>'like',
	'is'=>'like',
	'exactly'=>'=',
	'before'=>'<',
	'starts like'=>'starts like',
	'ends like'=>'ends like',
	'after'=>'>',
	'less than'=>'<',
	'more than'=>'>',
	'greater than'=>'>',
	'from'=>'>=',
	'to'=>'<=',
	'ge'=>'>=',
	'le'=>'<=',
	'in'=>'IN',
	'regex'=>'REGEXP',
	'regexp'=>'REGEXP'
);
$functionVersions['parse_query']=2.00;
function parse_query($query, $table=''){
	/*
	2013-05-08
	* added IN() function, improved coding, and added parenthesis around REGEXP()
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
		if(!preg_match('/^[a-z0-9_]+$/i',$table)){
			//it is a query
			$clauses=sql_query_parser($table);
			if(!preg_match('/^[a-z0-9_]+$/i',$clauses['from']))error_alert('unable to get table for query line '.__LINE__);
			$table=$clauses['from'];
		}
	}else if($table=$parse_query['defaultTable']){
		//OK
	}else{
		error_alert('Unable to get table for query ('.$table.')');
	}
	$query=trim($query);
	$reg='(\b(are|is|exactly|before|after|like|starts like|ends like|less than|more than|greater than|from|to|regex|regexp|ge|le|in)\b)|(!*=|<=|>=|<>*|>)';
	
	/* ----------- this is probably all flawed --------------- */
	$a=preg_split('/'.$reg.'/i',$query);
	if(count($a)<2)return false;
	$subject=preg_replace('/\s+/','',trim($a[0]));
	//get relationship and predicate
	$rest=trim(substr($query,strlen($a[0]), strlen($query)));
	preg_match('/^'.$reg.'/i',$rest,$a);
	if($op=trim($parse_query['map'][strtolower(trim($a[0]))])){
		//OK
	}else{
		$op=trim(strtolower(trim($a[0])));
	}
	//-----------------------------------------------------------
	$comp=substr($rest,strlen($a[0]),strlen($rest));
	$comp=trim($comp);

	//get fields in the table
	if(!($fields=$parse_query['fields'][strtolower($table)])){
		if(!($f=q("EXPLAIN $table", O_ARRAY))){
			if($parse_query['debug']){
				$pln=__LINE__;
				parse_query_notify($pln,$message='unable to get table info for '.$table);
			}
			error_alert($message);
		}
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
	
	//2012-07-16 - attempt to recognize a field in an expression
	$reg='/[^_a-z0-9]('.implode('|',array_keys($parse_query['fields'][strtolower($table)])).')[^_a-z0-9]/i';
	if(preg_match('/^[_a-z0-9]+$/i',trim($subject))){
		//OK
		$subjectField=$subject;
	}else if(preg_match($reg,$subject,$m)){
		$expression=true;
		$subjectField=$m[1];
	}else{
		$parse_query['error']='Unable to determine database field in subject "'.$subject.'"';
		return false;
	}
	
	//-- from aborted 2.0 attempt, may be needed - 

	if($a=$fields[strtolower(preg_replace('/[^a-z0-9_]*/i','',$subjectField))] or $a=$fields[$aliases[strtolower(preg_replace('/[^a-z0-9_]*/i','',$subjectField))]]){
	//if($a=$fields[$aliases[strtolower(preg_replace('/[^a-z0-9_\/ ]*/i','',$subject))]] or $a=$fields[strtolower(preg_replace('/[^a-z0-9_]*/i','',$subject))]){
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
	$str=($expression ? $subject : $field).' '.preg_replace('/starts|ends/i','',$op).' ';
	if(preg_match('/^\'|"/',$comp,$a) && preg_match('/\'|"$/',$comp,$b) && $a[0]!==$b[0]){
		error_alert('quotation mark mismatch');
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
		//clear out user-specified quotes on the ends, and rebuild
		$comp=stripslashes($comp);
		$c1=preg_replace('/^\'/','',preg_replace('/(\'$)/','',$comp));
		if(strlen($c1)<strlen($comp)){
			$comp=addslashes($c1);
		}else{
			$c2=preg_replace('/^"/','',preg_replace('/("$)/','',$comp));
			if(strlen($c2)<strlen($comp)){
				$comp=addslashes($c2);
			}else{
				$comp=addslashes($comp);
			}
		}
		if($op=='IN'){
			//remove leading and trailing ()
			$comp=stripslashes($comp);
			$comp=preg_replace('/(^\()|(\)$)/','',$comp);
			$comp=explode(',',$comp);
			foreach($comp as $n=>$v)
			if(!trim($v)){unset($comp[$n]);}else{$comp[$n]=trim($v);}
			//now handle quotes - they may or may not have used them
			foreach($comp as $n=>$v){
				if((substr($v,0,1)=="'" && substr($v,-1)=="'") || (substr($v,0,1)=='"' && substr($v,-1)=='"'))continue;
				$comp[$n]="'".str_replace('\'','\\\'',$v)."'";
			}
			$comp='('.implode(', ',$comp).')';
		}else{
			$comp="'".($op=='like' || $op=='ends like'?'%':'').$comp.($op=='like' || $op=='starts like'?'%':'')."'";
			$comp=preg_replace('/%+/','%',$comp);
			if($op=='REGEXP')$comp='('.$comp.')';
		}
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