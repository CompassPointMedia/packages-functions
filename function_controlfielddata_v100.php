<?php
$functionVersions['controlfielddata']=1.00;
function controlfielddata($options=array()){
	if(is_array($options)){
		extract($options);
	}else if(is_string($options)){
		$array=$options;
	}
	/* created 2011-12-07 - order is a, v, globals
	options = array( 'array'=>'rd' ) for example
	 */
	if($array){
		$array=$GLOBALS[$array];
	}else if(!empty($GLOBALS['a'])){
		$array=$GLOBALS['a'];
	}else if(!empty($GLOBALS['v'])){
		$array=$GLOBALS['v'];
	}else $array=$GLOBALS;
	if(!$array)return;
	foreach($array as $n=>$v){
		switch(true){
			case stristr($n,'createdate'):
				$a['created']='created: '.str_replace('12:00AM','',date('Y-m-d \a\t H:i:s',strtotime($v)));
			break;
			case stristr($n,'creator') && trim($v):
				$a['creator']='created by: '.$v;
			break;
			case stristr($n,'editdate'):
				$a['edited']='last edited: '.str_replace('12:00AM','',date('Y-m-d \a\t H:i:s',strtotime($v)));
			break;
			case stristr($n,'editor') && trim($v):
				$a['editor']='edited by: '.$v;
			break;
		}
	}
	if($a['created'] && $a['edited'] && substr($a['created'],-19)==substr($a['edited'],-19))unset($a['edited']);
	if($a)echo "\n".'<!-- Record information by controlfielddata():'."\n".implode("\n",$a)."\n".'-->'."\n";
}

?>