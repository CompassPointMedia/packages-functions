<?php
$functionVersions['array_merge_accurate']=1.0;

//these 2 vars are just for testing
$passcounter1=$passcounter2=0;

function array_merge_accurate(){
	/* 
	2010-06-13: modified array_merge() for php5
	created 2009-11-26 by Samuel 
	
	*/
	$arrays=func_get_args();
	if(count($arrays)==0)return '';
	if(count($arrays)==1)return $arrays[0];
	//store the first array passed
	$firstArrayIdentifier='a'.md5(time().rand(1,100000));
	global $$firstArrayIdentifier;
	$$firstArrayIdentifier=$arrays[0];
	for($i=1; $i<=count($arrays); $i++){
		@array_merge_accurate_subroutine($firstArrayIdentifier, $arrays[$i]);
	}
	return @$$firstArrayIdentifier;
}
function array_merge_accurate_subroutine($firstArrayIdentifier,$comparisonArray, $keys=array()){
	global $passcounter1, $passcounter2, $$firstArrayIdentifier;
	//where are we
	if($keys){
		$str='';
		foreach($keys as $build)$str.='['.(is_numeric($build)?'':'\'').$build.(is_numeric($build)?'':'\'').']';
	}
	#echo( '<span style=color:darkred>$mylocation=$comparisonArray'.$str.';</span><br>' );
	eval( '@$mylocation=$comparisonArray'.$str.';' );
	if(is_array($mylocation)){
		$passcounter1++;
		foreach($mylocation as $n=>$v){
			#echo 'calling again '.$passcounter1.':' . $passcounter2.' with key <strong>'.$n.'</strong> and keys <strong>'.(implode('.',$keys) ? implode('.',$keys) : '-').'</strong><br />';
			array_merge_accurate_subroutine($firstArrayIdentifier, $comparisonArray, array_merge($keys ? $keys : array(), array($n)));
		}	
	}else{
		$passcounter2++;
		#echo('pass '.$passcounter1.':'.$passcounter2.' $'.$firstArrayIdentifier.$str.'='.(is_numeric($mylocation)?'':'"').str_replace('"','\"',$mylocation).(is_numeric($mylocation)?'':'"').';<br />');
		if($str)
		eval('@$'.$firstArrayIdentifier.$str.'='.(is_numeric($mylocation)?'':"'").str_replace("'","\'",$mylocation).(is_numeric($mylocation)?'':"'").';');
	}
}
?>