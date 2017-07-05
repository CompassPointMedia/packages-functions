<?php
/*><script>*/
$functionVersions['array_file_list_i1']=1.00;
function array_file_list_i1($dir,$queries='',$filters=''){
	/*** this function generates a tabular list of the files present.  You can modify it as you need, save a copy of the original before you make changes though! 
	$queries is an array of criteria (default is OR to join), e.g. array(*.gif,*.jpg,*.png)
	$filters is an array of criteria to screen out
	***/
	global $array_file_list_i1;
	if(!$filters){
		$filters[]='.';
		$filters[]='..';
	}
	if ($fp = opendir($dir)) {
		while (false !== ($file = readdir($fp))){
			foreach($filters as $v){
				$reg='/^'.($v=='..' || $v=='.'?str_replace('.','\\.',$v):$v).'$/';
				if(preg_match($reg,$file))continue(2);
			}
			if($queries){
				$match=0;
				foreach($queries as $v){
					if(preg_match('/^'.$v.'$/i',$file)){$match=1;break;}
				}
				if(!$match)continue;
			}
			//now get file information
			$i++;
			$filePath=preg_replace('/\/$/','',$dir).'/'.$file;
			$files[$i][name]=$file;
			$filesize+=($files[$i][size]=filesize($filePath));
			//this is NOT the correct function but yet yields correct number (last modified) in some cases
			$files[$i][editdate]=fileatime($filePath);
			$pos=strrpos($file,'.');
			if(!($pos===false)){
				$files[$i][extension]=strtolower(substr($file, $y=1-strlen($file)+$pos));
			}
		}
	}
	$array_file_list_i1[createtime]=time();
	$array_file_list_i1[size]=count($files);
	$array_file_list_i1[filters]=$filters;
	$array_file_list_i1[queries]=$queries;
	$array_file_list_i1[root_file_size]=$fileSize;
	$array_file_list_i1[columns]=array("name","size","editdate","extension");
	$array_file_list_i1[version]='1.0';
	$array_file_list_i1[notes]="From array_file_list_i1 last modified 12:19PM 2004/09/17, file TYPE column not developed yet";
	return $files;
}
?>