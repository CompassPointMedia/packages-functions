<?php
$functionVersions['get_contents']=1.00;
function get_contents(){
	/* 2008-06-30 - for handling output buffering 
	2009-11-29 - made an "official" function in a_f; it was in 5 files.  Only in comp_tabs v2.00 (+?) the end logic is NOT if(beginnextbuffer) then ob_start() ELSE return gcontents.out - instead the logic is if(beginnextbuffer) then ob_start(); return gcontents.out PERIOD
	HOWEVER, beginnextbuffer is never flagged in comp_tabs so I have no fear of back-compat problems
	this function will return output and can optionally start the next buffer.
	GOTCHA! since this is a function, we must ob_start() before we return the contents.  Therefore, if you store the value returned as a variable, thats great, but if you wish to echo it, you are already in the next buffer.  So you cannot do a rewrite as done in cal widget and etc.
	*/
	$cmds=array('striptabs','beginnextbuffer','trim');
	global $gcontents;
	unset($gcontents);
	if($a=func_get_args()){
		foreach($a as $v){
			if(in_array($v, $cmds)){
				$$v=true;
			}
		}
	}
	$gcontents['out']=ob_get_contents();
	if(!empty($trim)) $gcontents['out']=trim($gcontents['out'])."\n";
	ob_end_clean();
	if(!empty($striptabs)) $gcontents['out']=str_replace("\t",'',$gcontents['out']);
	if(!empty($beginnextbuffer)){
		ob_start();
	}else{
		return $gcontents['out'];
	}
}
$functionVersions['get_contents_tabsection']=1.00;
function get_contents_tabsection($node,$options=array()){
	/*
	2012-06-07: added a new mode to this for tabs version 3
	*/
	global $tabVersion;
	extract($options);
	if($tabVersion==3){
		global $tabOutput;
		$tabGroup='generic';
		$tabOutput[$tabGroup]['tabSet'][$node]=ob_get_contents();
		ob_end_clean();	
	}else{
		global $tabOutput,$tabGroup,$__tabs__;
		if(!$tabGroup) foreach($__tabs__ as $tabGroup=>$v)break;
		//much simpler system
		$tabOutput[$tabGroup]['tabSet'][$node]=ob_get_contents();
		ob_end_clean();
	}
	//we presume that we will be adding another tab after this.  Comp_tabs_v220+ when called will cancel with flush();
	ob_start();
}

$functionVersions['get_contents_enhanced']=1.00;
function get_contents_enhanced_start($line=''){ ob_start(); }
function get_contents_enhanced(){
	/*
	2012-06-10: this is now re-done where I UNDERSTAND the function
	the main difference in this function is that it will begin the next buffer by default
	*/
	//translate
	$a=func_get_args();
	if(count($a)==1 && is_array($a[0])){
		//options
		extract($a[0]);
	}else if(!empty($a)){
		if(count($a)==1)$a=explode(',',$a[0]);
		//old method
		$cmds=array('start','stop','beginnextbuffer'/*legacy*/,'cxlnextbuffer','echo','noecho','striptabs','trim');
		foreach($a as $v){
			if(is_int($v)){
				$line=$v;
				continue;
			}
			if(in_array(strtolower($v),$cmds)){
				$v=strtolower($v);
				$$v=true;
			}
		}
	}
	//defaults
	if(!isset($beginnextbuffer))$beginnextbuffer=true;
	if(!isset($return))$return=true;
	if(!isset($echo) && !$noecho)$echo=true;

	//[start if specified]
	if($start)return get_contents_enhanced_start($line);

	//aggregate preceding contents
	global $get_contents_enhanced;
	$get_contents_enhanced['out']=ob_get_contents();
	ob_end_clean();
	
	//operate on content
	if($trim)$get_contents_enhanced['out']=trim($get_contents_enhanced['out'])."\n";
	if($striptabs)$get_contents_enhanced['out']=str_replace("\t",'',$get_contents_enhanced['out']);
	
	//echo
	if($echo)echo $get_contents_enhanced['out'];

	//begin next	
	if($beginnextbuffer && !$cxlnextbuffer){
		get_contents_enhanced_start($line);
	}	
	
	//return
	if($return)return $get_contents_enhanced['out'];
}

/* --------- example of use -------
get_contents_enhanced('start');
?>
this is the first section
<?php

$a['section1']=get_contents_enhanced();

?>
this is the second section
<?php
$a['section2']=get_contents_enhanced(array('echo'=>false));
?>
this is the third section
<?php
$a['section3']=get_contents_enhanced('stop');
print_r($a);
  --------------------------------- */

?>