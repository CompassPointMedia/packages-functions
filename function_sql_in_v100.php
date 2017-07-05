<?php
function sql_in(){
	if(func_num_args()<2){
		return 0;
	}else{
		$argList=func_get_args();
		for($i=1;$i<count($argList);$i++){
			if( $argList[0] == $argList[$i]){
				return 1;
			}
		}
		return 0;
	}
}
?>