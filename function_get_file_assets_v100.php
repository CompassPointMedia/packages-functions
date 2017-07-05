<?php
$functionVersions['get_file_assets']=1.00;
function get_file_assets($folder, $options=array()){
	/***
	2009-09-14: added 'area' = w*h for images
	2009-04-05: added options, starting with positive and negative filters
	2006-06-16: now general to getting files and folders
	2006-05-13: this was taken from the slide show feature - many of the things in here don't apply to jst getting folder assets generally.
	this function looks in a folder and gets the following file structure:
	
	alphabet soup.jpg => Array(
		[name] => Alphabet Soup.jpg
		[width] => 300
		[height] => 175
		[size] => 57.3 //measured in KB 
		[atime] => [mysql DateStamp]
		[ctime] => [mysql DateStamp]
	)
	
	***/
	global $get_file_assets;
	@extract($options);
	if(strlen($positiveFilters) && !is_array($positiveFilters))$positiveFilters=array($positiveFilters);
	if(strlen($negativeFilters) && !is_array($negativeFilters))$negativeFilters=array($negativeFilters);
	
	if(!is_dir($folder)){
		$get_file_assets['err']='No such folder';
		return;
	}
	//from php.net
	$getimagesizeFiles=array('gif', 'jpg', 'png', 'swf', 'swc', 'psd', 'tiff', 'bmp', 'iff', 'jp2', 'jpx', 'jb2', 'jpc', 'xbm', 'wbmp');
	$gisFilesReg='/\\.('.implode('|',$getimagesizeFiles).')$/i';
	if(!$folder)exit('Must pass name of folder containing pictures (1st parameter)');
	if($fp = opendir($folder)) {
		while(false !== ($file = readdir($fp))){
			if($file=='.' || $file=='..')continue;

			//exclusions (negative filters)
			if(count($negativeFilters)){
				foreach($negativeFilters as $v)if(preg_match('/'.$v.'/i',$file))continue;
			}
			
			//specific match (positive filters)
			if(count($positiveFilters)){
				
				$match=false;
				foreach($positiveFilters as $v){
					if(preg_match('/'.$v.'/i',$file)){
						$match=true;
						break;
					}
				}
				if(!$match)continue;
			}
			$i=strtolower($file);
			$files[$i]['name']=$file;
			//todo: recurse and get attributes for thumbs.dbr
			$files[$i]['folder']=(is_dir($folder.'/'.$file) ? 1 : 0);
			@$fp2=fopen($folder.'/'.$file,'r');
			@$stat=fstat($fp2);
			@fclose($fp2);
			$files[$i]['atime']=date('Y-m-d H:i:s',$stat['atime']);
			$files[$i]['mtime']=date('Y-m-d H:i:s',$stat['mtime']);
			$files[$i]['ctime']=date('Y-m-d H:i:s',$stat['ctime']);
			$files[$i]['size']=$stat['size']/1024;
			$a=explode('.',$file);
			if(count($a)>1){
				$files[$i]['actual_ext']=$a[count($a)-1];
				$files[$i]['ext']=strtolower($files[$i]['actual_ext']);
			}
			if(preg_match($gisFilesReg,$file)){
				list($files[$i]['width'], $files[$i]['height'])=@getimagesize($folder.'/'.$file);
				$files[$i]['area']=$files[$i]['width'] * $files[$i]['height'];
			}
		}
		closedir($fp);
	}else{
		//can't read directory
		exit('Can\'t read folder');
	}
	return $files;
}
?>