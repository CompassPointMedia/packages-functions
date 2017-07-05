<?php
$functionVersions['csv_to_array']=1.00;
function csv_to_array($file, $options=array()){
	/* Created 2009-03-10 by Samuel
	
	*/
	global $developerEmail,$fromHdrBugs;
	@extract($options);
	if(!isset($headerFirstRow))$headerFirstRow=true;  //assume the first row is headers
	$fp=fopen($file, 'r');
	$i=0;
	while($r=fgetcsv($fp,'4096')){
		$i++;
		//get the header if present
		if($i==1 && $headerFirstRow){
			foreach($r as $n=>$v){
				$headers[$n]=$v;
			}
			continue;
		}
		//build the array
		if($headerFirstRow){
			foreach($r as $n=>$v){
				$a[$headers[$n]]=$v;
			}
		}else{
			$a=$r;
		}
		$output[count($output)+1]=$a;
	}
	//return any results
	if(count($output))return $output;
}
?>