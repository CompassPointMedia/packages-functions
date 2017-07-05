<?php
$functionVersions['sql_query_parser']=1.00;
function sql_query_parser($query){
	/* 2011-05-13 only for select statements - will not win me any prizes but works for first project */
	#$query=trim(preg_replace('/^\s*\bSELECT\b/i','',$n));
	$rand=rand(1,1000000);
	$query.=' end_token_'.$rand.' (nothing)';
	$parseWords=array('select','from','where','group by','having','order by','limit','end_token_'.$rand);
	$i=0;
	foreach($parseWords as $word){
		$i++;
		#prn('splitting by '.$word);
		$a=preg_split('/\b'.$word.'\b/i',$query);
		#prn($a);
		//echo '<br /><br />';
		if(count($a)==2){
			//0 idx = previous wordClause
			$j++;
			#echo 'adding j='.$j.'<div style="color:dimgray">'.$a[0].'</div>';
			$clause[$j]=trim($a[0]);
			$wordAt[$j]=$word;
			$query=trim($a[1]);
		}
	}
	if(count($a)==2){
		$j++;
		$clause[$j]=$query;
		$wordAt[$j]=$word;
	}
	/*
	prn('-----');
	prn($clause);
	prn($wordAt);
	prn($goBack);
	prn('-----');
	*/
	foreach($clause as $j=>$v){
		if($j==1)continue;
		$clauses[$wordAt[$j-1]]=$v;
	}
	return $clauses;
}
/* example
prn(sql_query_parser("
SELECT field1, field2

FROM table a, table b, table c
WHERE condition 1 and condition 2
GROUP BY birds and bees
having something
LIMIT 1,5

"));
*/
?>