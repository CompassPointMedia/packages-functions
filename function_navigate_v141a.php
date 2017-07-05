<?php
$functionVersions['navigate']=1.41;
function navigate($newCount, $options=array(), $exit=true){
	global $navigateExit;
	if(isset($navigateExit))$exit=$navigateExit;
	//for navButtons v1.4
	/*
	2009-08-19
	* added navMode=delete
	
	added 2008-02-26 for navMode=insert - this function must be in place in the parent page
	wp.resetRecord();

	2008-01-22
	added options
		upFolder - presumes that the calling page is one folder up - true by default
	2007-03-14
	elements needed for this sytem
	navButtons 1.4 incl.
		php coding at top
		js functions focus_nav() and focus_nav_cxl() and clear_form() and dChge()
		all fields must have onchange="dChge(this)" - to be developed more
	call to this function
	if applicable, array in parent called sets which will set initial values
	if applicable, array in parent called labels which will set ghosted values
	NOTE: you must do a replace on ghosted variables in the exe file or they'll go into the db
	
	*/
	//newCount is the count of items in the navigation set after an insert or delete
	global $navVer,$nav,$navMode,$navObject,$navQueryFunction;
	global $mode,$insertMode,$updateMode,$deleteMode,$count,$abs;
	global $developerEmail,$fromHdrBugs;
	global $assumeErrorState;

	@extract($options);
	if(!isset($upFolder))$upFolder=true;

	if($navMode=='navig'){
		/* from pressing previous or next button */
		?><script language="javascript" type="text/javascript">
		var wp=window.parent;
		wp.location=<?php echo $upFolder ? '\'../\'+' : ''?>wp.thispage+'?navMode=navig&nav=<?php echo $nav?>&count=<?php echo $newCount;?>&abs=<?php echo $abs;?>'<?php echo $navQueryFunction ? ' + wp.'.stripslashes($navQueryFunction): ''?>;
		</script><?php
	}else if($navMode=='insert'){
		/* from adding a new record - we clear the form and reset to add another */
		?><script language="javascript" type="text/javascript">
		//clear form
		// - declared in parent: var sets=new Array();
		var wp=window.parent;
		wp.sets['mode']='<?php echo $insertMode?>';
		wp.sets['count']='<?php echo $newCount?>';
		wp.sets['abs']='<?php echo $newCount+1?>';
		wp.clear_form(wp.ignores, wp.sets, true);
		wp.g('Previous').disabled=false;
		wp.detectChange=0;
		try{
			//added 2008-02-26 - this function must be in place
			wp.resetRecord();
		}catch(e){ }
		</script><?php
	}else if($navMode=='kill'){
		/* pressing OK or Cancel or Save & Close */
		?><script language="javascript" type="text/javascript">
		var wp=window.parent;
		wp.close();
		</script><?php
	}else if($navMode=='remain'){
		//developed 2008-11-11
		?><script language="javascript" type="text/javascript">
		window.parent.detectChange=0;
		window.parent.status='Record updated OK';
		//do this if we allow save and remain for new records - though as of 2008-11-11 this is not available for navbuttons
		if(window.parent.g('mode').value=='<?php echo $insertMode?>'){
			//update the mode
			window.parent.g('mode').value='<?php echo $updateMode?>';
		}
		</script><?php
	}else if($navMode=='delete'){
		if(true){
			if(rand(1,10)==5)mail($developerEmail, 'Notice in '.$MASTER_USERNAME.':'.end(explode('/',__FILE__)).', line '.__LINE__,get_globals($err='navmode=delete; for now we just close parent window'),$fromHdrBugs);
			?><script language="javascript" type="text/javascript">
			window.parent.close();
			</script><?php
		}else{
			if($abs>$count-1){
				//new record
				$asdf='new';
			}else{
				//next record
				$asdf='next';
			}
			error_alert($asdf,1);
			?><script language="javascript" type="text/javascript">
			window.parent.detectChange=0;
			window.parent.locationReplace=/\/resources/;
			window.parent.focus_nav_load();
			</script><?php
		}
	}
	if($exit){
		$assumeErrorState=false;
		exit;
	}
}
?>