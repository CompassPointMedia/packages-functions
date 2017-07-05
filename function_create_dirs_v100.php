<?php
function create_dirs($path, $root=''){
	global $create_dirs;
	if($root){
		$root=rtrim($root,'/').'/';
	}else{
		//file we are in right now
		$root=ltrim($_SERVER['SCRIPT_FILENAME'],'/');
		$root=explode('/',$root);
		array_pop($root);
		$root='/'.implode('/',$root);
		$root=rtrim($root,'/').'/';
	}
	if(preg_match('/^\.\.\//',$path)){
		//knock off root
		$j= ( strlen($path) - strlen(str_replace('../','',$path)) )/3;
		$root=explode('/',rtrim(ltrim($root,'/'),'/'));
		for($i=1;$i<=$j;$i++){
			array_pop($root);
		}
		$root='/'.implode('/',$root).'/';
		$path=str_replace('../','',$path);
	}else if(preg_match('/^\//',$path)){
		//path is absolute
		$root='/';
	}
	$path=trim($path,'/');
	if(!$path)return;
	$path=explode('/',$path);
	$string=$root;
	foreach($path as $v){
		if(!file_exists($string.$v)){
			//create the folder
			//echo 'creating '.$string.$v . '<br />';
			if(!mkdir($string.$v)){
				$create_dirs['error']='unable to create directory '.$string.$v;
				return false;
			}
		}
		$string.=$v.'/';
	}
	return true;
}
?>