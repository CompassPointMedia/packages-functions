<?php
function logic_algorithm_i1($string){
	global $fieldList, $dauthFieldList, $authFieldList, $recordData, $mm_logic;
	//main string, isolate $ characters, this provides good security but not in promised land
	$string=str_replace('$','&#036;',$string);

	//parse the string
	#must use stripslashes for this to work
	$string=stripslashes($string);
	if(preg_match_all('/\{IF\s(.|\s)*?\sTHEN\s(.|\s)*?(\sELSE\s(.|\s)*?)*\sENDIF\}/i',$string,$match)){
		for($i=0;$i<count($match[0]);$i++){
			
			//======================   isolate the argument =========================
			preg_match('/{IF\s(.|\s)*?\sTHEN\s/i',$match[0][$i],$comp);
			$argument= trim(preg_replace('/(^\{IF\s)|(\sTHEN\s)/i','',$comp[0]));
	
			//isolate any IN() statements, remake them into a php phrase
			#1 database prefix with period
			#2 fieldName
			#3 string in the IN clause
			#
			if(preg_match_all('/(\w+\.)*(\w+)\s+IN\((\s*(([.0-9]+)|(\'.*?\')|(".*?")\s*)((\s*,\s*(([.0-9]+)|(\'.*?\')|(".*?")))\s*)*)\)/i',$argument,$inClause)){
				//generate random string to garble the field entry
				$rand=rand(100000184938,1000000482948014);
				
				for($j=0;$j<count($inClause[0]);$j++){
					$argument = str_replace(
						$inClause[0][$j],
						" sql_in(\$recordData['" . substr($inClause[2][$j],0,1) . $rand . substr($inClause[2][$j],1) . "'],{$inClause[3][$j]}) ",
						$argument
					);
				}
			}
			
			//replace all FieldName values with the recordData in the argument
			foreach($fieldList as $n){
				$argument=str_replace($n, '$recordData["' . $n . '"]',$argument);
			}
	
			//remove all garbled strings
			foreach($fieldList as $n){
				$argument=str_replace(substr($n,0,1).$rand.substr($n,1), $n,$argument);
			}
			
			//convert @= to rand2, == to =, and rand2 back to =
			$rand2=rand(148598455382948,14859845538294842999);
			$argument = str_replace('@=',$rand2,$argument);
			$argument = str_replace('==','=',$argument);
			$argument = str_replace('=','==',$argument);
			$argument = str_replace($rand2,'=',$argument);
			echo $argument;
			
			//we now store the argument in a public array $mm_logic
			$mm_logic[$i][argument]=$argument;
	
			//==============   isolate the iftrue and iffalse statements ========
			preg_match('/\sTHEN\s(.|\s)*?((\sELSE\s)|(\sENDIF\}))/i',$match[0][$i],$ifTrue);
			$mm_logic[$i][ifTrue]=preg_replace('/(\sTHEN\s)|(\sELSE\s)|(\sENDIF\})/','',$ifTrue[0]);
			foreach($fieldList as $n){
				$mm_logic[$i][ifTrue]=str_replace($n, '<?php echo $recordData["' . $n . '"];?>',$mm_logic[$i][ifTrue]);
			}
			
			preg_match('/(\sELSE\s)(.|\s)*?(\sENDIF\})/i',$match[0][$i],$ifFalse);
			$mm_logic[$i][ifFalse]=preg_replace('/(\sELSE\s)|(\sENDIF\})/','',$ifFalse[0]);
			foreach($fieldList as $n){
				$mm_logic[$i][ifFalse]=str_replace($n, '<?php echo $recordData["' . $n . '"];?>',$mm_logic[$i][ifFalse]);
			}
			
			$functionCall= '<?php echo mail_merge_logic_i1($mm_logic[' . $i . ']);?>';
			$string=str_replace($match[0][$i],$functionCall,$string);
		}
	}
	
	//now that we've done the logical statements, gethe rest of the {FieldName} strings
	foreach($fieldList as $n){
		$string=preg_replace('/\{' . $n . '\}/i', '<?php echo $recordData[' . $n . '];?>', $string);
	}
	return $string;	
}
?>
