<?php
$functionVersions['js_declare_array']=1.00;
function js_declare_array($array,$name,$allKeysAsString=0, $allValuesAsString=0){
	global $js_declare_array;
	/*** USE OF THE FUNCTION ***
	$members['chicago'][0]=35;
	$members['chicago'][1]=253;
	$members['chicago'][2]=15;
	$members['des moines'][jaycees]=189;
	$members['des moines'][kiwanis]=50;
	$members['des moines'][rotary]=3;
	
	echo "<pre>";
	echo htmlentities(js_declare_array($members,'members',1,0));
	***/
	
	//declare array initially
	if($ct=count($array)){
		$str.="var $name= new Array();\n";
	}else{
		//no values in the array
		$js_declare_array['status']='No values in array';
		$str="$name = new Array();\n";
		return $str;
	}
	foreach($array as $n=>$v){
		$i++;
		if(is_array($v)){
			if($allKeysAsString){
				$q="'";					
			}else{
				$q= (is_numeric($n)?'':"'");
			}
			$str.=$name.'['.$q.$n.$q.'] = new Array();'."\n";
			foreach($v as $o=>$w){
				if(is_array($w)){
					//exit('js_declare_array not developed to 3rd dimension');
				}else{
					if($allKeysAsString){
						$q=$q2="'";					
					}else{
						$q= (is_numeric($n)?'':"'");
						$q2=(is_numeric($o)?'':"'");
					}
					if($allValuesAsString){
						$qv="'";					
					}else{
						$qv= (is_numeric($w)?'':"'");
					}
					$str.='  '.$name.'['.$q.$n.$q.']['.$q2.$o.$q2.']='.$qv.$w.$qv.';'."\n";
				}
			}
				
		
		}else{
			//handle string value for first dimension element
			if($allKeysAsString){
				$q="'";					
			}else{
				$q=(is_numeric($n)?'':"'");
			}
			if($allValuesAsString){
				$qv="'";					
			}else{
				$qv= (is_numeric($v)?'':"'");
			}
			$str.='  '.$name.'['.$q.$n.$q.']='.$qv.$v.$qv.'; ';
		}
	}
	return $str;
}
?>