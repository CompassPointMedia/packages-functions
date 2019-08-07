<?php
$functionVersions['CSS_parser']='1.0';
function CSS_parser($str){
    /**
     * If we were to parse a media query also, the heirarchy on a "proper" CSS page with no //comments like this, would be:
     *  - inside single or double quotes, with quotes themselves escaped by ??
     *  - inside /* comments * /
     *  - inside media query declarations
     *  - inside declaration blocks
     *
     *
	2011-08-16 - first used on cpm024 Juliet to parse css decs and separate out.
	does not handle @media=screen{ .. } overwrap and would fail in this..
	*/
	global $CSS_parser;
	if(empty($CSS_parser['retain_global']))$CSS_parser=array();

	$buffer = '';
	$escLevel = 0;
	$char = '';
	$start = 0;

	for($i=0; $i<strlen($str); $i++){
		$cp=$str{$i-1};
		$c=$str{$i};
		// $cn=$str{$i+1};

		if($escLevel==2){
			if($cp=='*' && $c=='/'){
				//buffer
				$buffer=preg_replace('/\*$/','',$buffer);
				$rand=str_repeat( 
					md5(rand(1,1000000)), 
					ceil(strlen($buffer)/32)
				);
				$rand=substr($rand,0,strlen($buffer));
				$escapes[]=array($start,$i,$buffer,$rand);
				//substr_replace($str,....);
				$buffer='';
				$escLevel = 0;
			}else{
				$buffer.=$c;
			}
		}else if($escLevel==1){
			if($c==$char){
				$rand=str_repeat( 
					md5(rand(1,1000000)), 
					ceil(strlen($buffer)/32)
				);
				$rand=substr($rand,0,strlen($buffer));
				$escapes[]=array($start,$i,$buffer,$rand);
				$buffer='';
				$escLevel='';
			}else{
				$buffer.=$c;
			}
		}else{
			$buffer='';
			if($cp=='/' && $c=='*'){
				$escLevel=2;
				$start=$i;
			}else if($c=='\'' || $c=='"'){
				$escLevel=1;
				$char=$c;
				$start=$i;
			}
		}
	}
	$clean=$str;
	if($escapes) foreach($escapes as $v) $clean=str_replace($v[2],$v[3],$clean);

	//now I've enumerated comments and strings; replace for later surgery
	//print_r($str);

	//cubic but necessary
	if(preg_match_all('/([^\r\n{]+?)\{[^}]*?\}/',$clean,$declarations)){
		if($escapes)
		foreach($declarations[0] as $n=>$v){
			foreach($escapes as $o=>$w){
				if(strstr($v,$w[3])){
					$declarations[0][$n]=str_replace($w[3],$w[2],$v);
				}
			}
		}
	}
	
	$CSS_parser['clean']=$clean;
	$CSS_parser['escapes']=$escapes;
	$CSS_parser['declarations']=$declarations;
}
/*
CSS_parser($str);

exit;
*/

