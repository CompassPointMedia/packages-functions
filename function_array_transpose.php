<?php
$functionVersions['array_transpose']=1.00;
function array_transpose($tArray='', $idx1='', $idx2=''){
	if(is_array($tArray)){
		//transpose the values
		$f_index=array_keys($tArray);
		for ($firstCounter=0;$firstCounter<count($f_index);$firstCounter++) {
			$s_index=array_keys($tArray[$f_index[$firstCounter]]);
			for ($scounter=0;$scounter < count($s_index);$scounter++) {
				$tArray_transpose[$s_index[$scounter]][$f_index[$firstCounter]] =
				$tArray[$f_index[$firstCounter]][$s_index[$scounter]];
			}
		}
		//finally, overwrite the value of tArray so that passing by reference changes
		$tArray = $tArray_transpose;

		//determine the return type
		if(func_num_args()==3){
			#$value = array_transpose($passedArray, DNAME, 7) returns the value $passedArray[7][DNAME]
			return $tArray_transpose[$idx1][$idx2];
		}elseif(func_num_args()==2){
			#$subArray = array_transpose($passedArray, DNAME) will return the subArray
			return $tArray_transpose[$idx1];
		}else{ 	#just return the array
			#array_transpose(&$passedArray)  will transpose the array
			#$new = array_transpose($passedArray) will save the transpose as new
			return $tArray_transpose;
		}
	}else{
		return;
	}
}
?>
