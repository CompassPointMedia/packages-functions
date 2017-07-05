<?php
$functionVersions['get_picture_assets']=1.00;
function get_picture_assets($folder, $mode){
	/***
	2006-05-13: this was taken from the slide show feature - many of the things in here don't apply to jst getting folder assets generally.
	this function looks in a folder and gets the following file structure:
	
	alphabet soup.jpg => Array(
		[name] => Alphabet Soup.jpg
		[width] => 300
		[height] => 175
		[size] => 57.3 //measured in KB 
		[atime] => [mysql DateStamp]
		[ctime] => [mysql DateStamp]
		[mode] => original | large | thumb
		[original] => array( width, height, size ),
		[large] => array( width, height, size ),
		[thumb] => array( width, height, size )
	)
	
	***/

	//this function looks in the thumbnail directory and assumes that the contents represent ALL of the slideshow
	global $config, $status;
	if(!$folder)exit('Must pass name of folder containing pictures (1st parameter)');
	if(!$mode)exit('Must pass original|large|thumb as mode (2nd parameter)');
	if($fp = opendir($folder)) {
		if(!$filter)$filter = '/\.(jpg|jpeg|gif|png)$/i';
		if(!$includes)$includes=array();
		if(!$excludes)$excludes=array();
		while(list($out)=each($excludes)) $_excludes[strtolower($out)]=1;
		while(list($in)=each($includes)) $_includes[strtolower($in)]=1;
		while(false !== ($file = readdir($fp))){
			//improve selection criteria
			if(!preg_match($filter,$file) && !$_includes[strtolower($file)]) continue;
			if($_excludes[strtolower($file)]) continue;
			$i++;
			$files[$i]=$file;
			$lfiles[$i]=strtolower($file);
		}
		closedir($fp);
	}else{
		//can't read directory
		exit('Can\'t read '.$folder);
	}
	//sort lfiles
	if($lfiles){
		asort($lfiles);
		$i=0;
		foreach($lfiles as $n=>$v){
			$i++;
			$pictures[$v]['name']=$files[$n];
			$fp=fopen($folder.'/'.$files[$n],'r');
			$stat=fstat($fp);
			fclose($fp);
			$pictures[$v]['atime']=date('Y-m-d H:i:s',$stat['atime']);
			$pictures[$v]['mtime']=date('Y-m-d H:i:s',$stat['mtime']);
			$pictures[$v]['ctime']=date('Y-m-d H:i:s',$stat['ctime']);
			$pictures[$v]['size']=$stat['size']/1024;
			list($pictures[$v]['width'], $pictures[$v]['height'])=getimagesize($folder.'/'.$files[$n]);
			$pictures[$v]['mode']=$mode;
		}
	}
	return $pictures;
}
?>