<?php
//these functions started 2009-08-15 to deal with new relatebase_tree table for each account
/*
relatebase_tree is a hierarchical table for describing a file system

*/

$functionVersions['tree_functions']=1.00;
function tree_functions(){
	//placeholder
	return true;
}
$functionVersions['tree_image']=1.00;
function tree_image($options=array()){
	/*
	width
	height
	box
	id
	class
	style
	alt	
	*/
	global $MASTER_PASSWORD;
	is_array($options) ? extract($options) : $src=$options;
	if(is_numeric($src)){
		$Tree_ID=$src;
	}else{
		$Tree_ID=tree_build_path(ltrim($src,'/'));
	}
	if(!$mode)$mode='output';
	if($mode=='output'){
		if(!$disposition)global $disposition;
		if(!$boxMethod)global $boxMethod;
		if($disposition)$d=explode('x',$disposition);
		$src='/images/reader.php?Tree_ID='.$Tree_ID.'&Key='.md5($Tree_ID.$MASTER_PASSWORD).($disposition?'&disposition='.$disposition:'').($boxMethod?'&boxMethod='.$boxMethod:'');
		$str='<img src="'.$src.'"';
		foreach(array('id','class','style','onclick','onmouseover','onmouseout','ondblclick','ondragdrop','onhover') as $v){
			if($$v)$str.=' '.$v.'="'.str_replace('"','&quot;',$$v).'"';
		}
		if($d[0])$str.=' width="'.$d[0].'"';
		if($d[1])$str.=' height="'.$d[1].'"';
		$str.=' alt="'.($alt ? $alt : ($src ? h($src) : 'img')).'"';
		$str.=' />';
		echo $str;
	}
	return array('Tree_ID'=>$Tree_ID, 'disposition'=>$disposition,'src'=>$src);
}
$functionVersions['tree_id_to_path']=1.00;
function tree_id_to_path($n,$options=array()){
    /*
     * 2017-12-04 - q() call contains a nasty hack in the 4th parameter, solves a problem encountered in GL Franchise where the db it's looking for is cpm151 and not cpm151_sanmarcos.  This is going to take visualization across all the coding to solve and not break elsewhere.
     */
    global $tree_id_to_path,$qx;
    extract($options);

    if(empty($cnx)) $cnx=$qx['defCnxMethod'];
    $row=q(
        "SELECT Tree_ID, Name, Type FROM relatebase_tree WHERE ID='$n'",
        O_ROW,
        $cnx,
        (!empty($_SESSION['currentConnection']) ? $_SESSION['currentConnection'] : '')
    );
    if($row['Type']=='file') $tree_id_to_path=$row;
    return ($row ? tree_id_to_path($row['Tree_ID'],$options).'/'.$row['Name'] : '');
}
function tree_path_to_id($n){
	//normal string passage would be images/assets/spacer.gif OR /images/assets/spacer.gif - the left / is removed
	global $tree_path_to_id, $qr;
	$tree_path_to_id=array();
	$a=explode('/',ltrim($n,'/'));
	$Tree_ID='NULL';
	foreach($a as $idx=>$node){
		$idx++;
		if($result=q("SELECT ID, Type, Name FROM relatebase_tree WHERE Name='".addslashes($node)."' AND Tree_ID".($Tree_ID=='NULL'?' IS ':'=').$Tree_ID, O_ROW)){
			$Tree_ID=$result['ID'];
			$tree_path_to_id['build_to'].=$result['Name'] . ($idx<count($a)?'/':'');
		}else{
			return false;
		}
	}
	return $result['ID'];
}
function tree_build_path($n,$options=array()){
	/*
	2011-3-4 added option defaultTable
	2009-10-19 - added option lastNodeType='file'; this will set type=that value for the last node; so you can pass:
		
		tree_build_path('/images/assets/slides/slide_01.jpg', $options=array('lastNodeType'=>'file'));
	
	and the ID returned need not be used for a 2ndarey operation
	2009-09-06 - go through a path and begin adding when we need to, return the id of the new node (folder)
	*/
	$a=explode('/',trim($n,'/'));
	extract($options);
	if(!$defaultTable)$defaultTable='relatebase_tree';
	if(!isset($Tree_ID))$Tree_ID=NULL;
	if(count($lastNodeValues)){
		foreach($lastNodeValues as $n=>$v){
			$str.=$n."='".addslashes($v)."',\n";
		}
	}
	foreach($a as $n=>$v){
		$i++;
		if($o=q("SELECT ID FROM ".$defaultTable." WHERE Tree_ID".(is_null($Tree_ID)?' IS NULL':'='.$Tree_ID)." AND Name='".addslashes($v)."' AND Type='".($lastNodeType && $i==count($a) ? $lastNodeType : 'folder')."'", O_VALUE)){
			//OK
			$Tree_ID=$o;
		}else{
			$Tree_ID=q("INSERT INTO ".$defaultTable." SET 
			".($Tree_ID ? 'Tree_ID='.$Tree_ID.',' : '')."
			".($lastNodeValues && $i==count($a) ? $str : '')."
			Type='".($lastNodeType && $i==count($a) ? $lastNodeType : 'folder')."', 
			Name='".addslashes($v)."', 
			CreateDate=NOW(), 
			Creator='".($Creator ? $Creator : ($_SESSION['systemUserName'] ? $_SESSION['systemUserName'] : 'system'))."'", O_INSERTID);
		}
	}
	return $Tree_ID;
}
function tree_delete_children($n,$options=array()){
	//Weird Variable Name because I don't want to step on anything else because it is just a counter
	//Couldn't find another way around needing the initial ID, there is no other way to re-call the function and still have it delete everything
	/*
	$options array
	'customFileRoot'=>'/home/cpm051/public_html/images/documentation/lutheran/documents/ *File Name Here*
	*/
	global $satetagsdga, $tree_delete_children, $init_ID;
	extract($options);
	$satetagsdga++;
	if($satetagsdga==1) $init_ID=$n;
	if($mainFolder=q("SELECT ID,Type FROM relatebase_tree WHERE Tree_ID='$n'",O_COL_ASSOC)){
		foreach($mainFolder as $ID=>$Type){
			if(strtolower($Type)=='folder'){
				if($finalFolder=q("SELECT * FROM relatebase_tree WHERE Tree_ID='$ID'",O_ARRAY)){
					tree_delete_children($ID,$options);
				} else {
					if($folderName=q("SELECT Name FROM relatebase_tree WHERE ID='$ID'",O_VALUE)){
						if($link=file_exists(tree_id_to_path($ID)) && !$customFileRoot){
							rmdir($folderName);	
						} else if($customFileRoot && file_exists($customFileRoot.$folderName)){
							rmdir($customFileRoot.$folderName);	
						} else if((!file_exists($customFileRoot.$folderName)) && $customFileRoot){
						
						} else if((!file_exists(tree_id_to_path($ID)) && !$customFileRoot)){
						}
					}
					q("DELETE FROM relatebase_tree WHERE ID='$ID'");
					tree_delete_children($init_ID,$options);
				}
			}else if(strtolower($Type)=='file'){
				if($fileName=q("SELECT Name FROM relatebase_tree WHERE ID='$ID'",O_VALUE)){
					if($link=file_exists(tree_id_to_path($ID)) && !$customFileRoot){
						unlink($link);	
					} else if($customFileRoot && file_exists($customFileRoot.$fileName)){
						unlink($customFileRoot.$fileName);	
					} else if((!file_exists($customFileRoot.$fileName)) && $customFileRoot){
					
					} else if((!file_exists(tree_id_to_path($ID)) && !$customFileRoot)){
					}
				}
				q("DELETE FROM relatebase_tree WHERE ID='$ID'");
				tree_delete_children($init_ID,$options);
			}
		}
	} else {
	}
	q("DELETE FROM relatebase_tree WHERE ID='$init_ID'");
	return(true);
}

?>