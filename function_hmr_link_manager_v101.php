<?php
function hmr_link_manager($vars=''){
	/* Created 2010-08-07: after a brainstorm where I figured that the page opener from email is kind of like a download manager, and we want to pass multiple rows to a GUI similar to this
	
	*/
	global $GCUserName,$hmr_link_manager;
	unset($hmr_link_manager['error']);
	if(is_numeric($vars)){
		$vars='ID='.$vars;
	}else if(!$vars){
		$vars=$_SERVER['QUERY_STRING'];
	}
	
	parse_str($vars,$a);
	extract($a);
	//inputs covered by string passed
	//outputs as follows
	global $url,$urlBase, $target, $button, $size,$qr,$qx,$label,$developerEmail,$fromHdrBugs;
	/* ----------- this is the new coding -------------- */
	global $pageRegistry;
	if((!$node && $object) && $object!='t_certificates' /* added 2011-11-25 as new implemented */){
		$node=$object;
	}else if(!$node && $Type){
		$node=$Type;
	}else if(!$node && $pnrnode){
		$node=$pnrnode;
	}

	//more stuff you have to do to accommodate for idiots
	$node=strtolower($node);
	$ResourceToken=preg_replace('/^20[0-9]/','',date('YmdHis').substr(rand(10000,100000),0,5));
	/* --------------------------------------------------- */
	switch(true){
		case $node=='loc_assign':
			$url.='?Children_ID='.$Children_ID.'&UpdateLocs_ID='.$ID.'&action=update&ResourceToken='.$ResourceToken;
		break;
		case $node=='timesheets':
			if($a=q("SELECT * FROM gf_timesheets WHERE ID=$ID", O_ROW)){
				$url.='?Timesheets_ID='.$ID;
			}else{
				$hmr_link_manager['error']=('There is not timesheet available by that ID; either it has been deleted or the URL is incorrect');
				return false;
			}
		break;
		case $node=='read_bulletins':
			if($a=q("SELECT * FROM gf_bulletins WHERE ID=$Bulletins_ID", O_ROW)){
				//prn($a);
				$url.='?Bulletins_ID='.$Bulletins_ID;
			}else{
				$hmr_link_manager['error']=('There is no bulletin available by that ID; either it has been deleted or the URL is incorrect');
				return false;
			}
		break;
	}
	if($object){
		if($object=='t_certificates'){
			$table=$object;
			$keyString='Certificates_ID';
		}else if($object=='selfstudy'){
			$table='t_tests';
		}else{
			$table='gf_'.$object;
		}
		$record=q("SELECT * FROM $table WHERE ID='".($$keyString?$$keyString:$ID)."'", O_ROW);
		if($record){
			switch(true){
				//only one(s) good are at top
				case $object=='t_certificates':
					$urlBase='certificate.php';
					$url=$urlBase.'?Certificates_ID='.$$keyString.($AuthKey?'&AuthKey='.$AuthKey:'').($SuperAuthKey?'&SuperAuthKey='.$SuperAuthKey:'');
					$button='Training Certificate';
					$size='800,700';
					$target='l1_certificate';
				break;


				case $node=='children':
					$url.='?Children_ID='.$ID;
				break;
				case $node=='parents':
					$url.='?Parents_ID='.$ID;
				break;
				case $node=='homes':
					$url.='?Fosterhomes_ID='.$ID;
				break;
				case $node=='loc_assign':
					$url.='?Children_ID='.$Children_ID.'&Locs_ID='.$ID.'&action=update&ResourceToken='.$ResourceToken;
				break;
				case $node=='selfstudy':
					$url.='?Tests_ID='.$ID;
				break;
			}
		}else{
			$hmr_link_manager['error']=('There is not a record present for the ID provided.');
			return false;
		}
	}else if($l=q("SELECT * FROM finan_items WHERE /* ResourceType IS NOT NULL AND */ ID='$ID'", O_ROW)){
		/* 2011-10-13: last resort and I'm going to rework this entire function: ID only is passed.  what was I thinking? $url should be fully defined HERE not somewhere else!!!! */
		switch(true){
			/* this is the only sane case in the bunch... */
			case true:
				$urlBase='products.php';
				$url=$urlBase.'?Items_ID='.$ID;
				$button='Product Record';
				$size='860,674';
				$target='l1_items';
			break;
			default:
				mail($developerEmail,'error file function_hmr_link_manager_v101.php line '.__LINE__,get_globals(),$fromHdrBugs);
				?>
				Unable to process your request - staff have been notified<br />
				<input type="button" name="Submit" value="Home Page" onclick="window.location='home.php';" />
				<?php
				exit;
		}
	}else{
		$hmr_link_manager['error']=('There is no progress note, report, or page available by that ID; either it has been deleted or the URL is incorrect');
		return false;
	}
	return true;
}

?>