<?php
$functionVersions['gl_link_manager']=1.01;
function gl_link_manager($vars=''){
	/* Created 2010-08-07: after a brainstorm where I figured that the page opener from email is kind of like a download manager, and we want to pass multiple rows to a GUI similar to this
	
	*/
	global $GCUserName,$gl_link_manager;
	unset($gl_link_manager['error']);
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
	$legacy=array(
		'LOC'=>'loc_assign',
		'staff'=>'timesheets',
		'viewBulletin'=>'read_bulletins',
		'selfstudy'=>'',
		'children'=>'children',
		'parents'=>'parents',
		'fosterhomes'=>'homes',
		'childrenlocs'=>'loc_assign',
		'selfstudy'=>'selfstudy',
		'attendances'=>'training_event',
		'progress notes'=>'progress_reports',
		'child status reports'=>'progress_reports',
		'incident report'=>'focus_incident_reports',
		'restraint report'=>'focus_restraint_reports',
		'cm logs'=>'casemanager',
		'therapy notes'=>'clinical'
	);
	if($legacy[$node])$node=$legacy[$node];
	//more stuff you have to do to accommodate for idiots
	$node=strtolower($node);
	$ResourceToken=preg_replace('/^20[0-9]/','',date('YmdHis').substr(rand(10000,100000),0,5));
	/* --------------------------------------------------- */
	if($node){
		//another lame thing.. don't NEED node value, just ID and TABLE
		if($pageRegistry[$node]){
			extract($pageRegistry[$node]);
			//More Legacy stuff
			$button=$label;
		}else{
			mail($developerEmail,'Error file'.__FILE__.', line'.__LINE__.' : Node (\''.$node.'\') is not registered',get_globals(),$fromHdrBugs);
			return false;
		}
	}
	switch(true){
		case $node=='loc_assign':
			$url.='?Children_ID='.$Children_ID.'&UpdateLocs_ID='.$ID.'&action=update&ResourceToken='.$ResourceToken;
		break;
		case $node=='timesheets':
			if($a=q("SELECT * FROM gf_timesheets WHERE ID=$ID", O_ROW)){
				$url.='?Timesheets_ID='.$ID;
			}else{
				$gl_link_manager['error']=('There is not timesheet available by that ID; either it has been deleted or the URL is incorrect');
				return false;
			}
		break;
		case $node=='read_bulletins':
			if($a=q("SELECT * FROM gf_bulletins WHERE ID=$Bulletins_ID", O_ROW)){
				//prn($a);
				$url.='?Bulletins_ID='.$Bulletins_ID;
			}else{
				$gl_link_manager['error']=('There is no bulletin available by that ID; either it has been deleted or the URL is incorrect');
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
			$gl_link_manager['error']=('There is not a record present for the ID provided.');
			return false;
		}
	}else if($l=q("SELECT * FROM gf_logs WHERE ResourceType IS NOT NULL AND Statuses_ID>0 AND ID='$ID'", O_ROW)){
		/* 2011-10-13: last resort and I'm going to rework this entire function: ID only is passed.  what was I thinking? $url should be fully defined HERE not somewhere else!!!! */
		switch(true){
			/* this is the only sane case in the bunch... */
			case strtolower($l['Type'])=='child monthly contact':
				$urlBase='document_childcontact.php';
				$url=$urlBase.'?ID='.$ID;
				$button=$a['Type'];
				$size='860,674';
				$target='l1_cmc';
			break;
			case strtolower($l['Type'])=='family monthly contact':
				$urlBase='document_familycontact.php';
				$url=$urlBase.'?ID='.$ID;
				$button=$a['Type'];
				$size='861,675';
				$target='l1_fmc';
			break;
			case strtolower($l['Type'])=='therapy notes':
				$urlBase='clinical.php';
				$url=$urlBase.'?Logs_ID='.$ID;
				$button=$a['Type'];
				$size='755,655';
				$target='l1_therapynotes';
			break;
			/*transitional*/
			case count($a)==1 && strtolower($l['Type'])=='progress notes':
				$urlBase='progress_reports.php';
				$url=$urlBase.'?ID='.$ID;
				$button='Child Status Report';
				$size='900,676';
				$target='l1_progress';
			break;
			case count($a)==1 && strtolower($l['Type'])=='incident report':
				$urlBase='focus_incident_reports.php';
				$url=$urlBase.'?ID='.$ID;
				$button='Serious Incident Report';
				$size='800,674';
				$target='l1_incident';
			break;
			case count($a)==1 && strtolower($l['Type'])=='restraint report':
				$urlBase='focus_restraint_reports.php';
				$url=$urlBase.'?ID='.$ID;
				$button='Restraint Serious Incident Report';
				$size='800,674';
				$target='l1_restraint';
			break;
			case count($a)==1 && strtolower($l['Type'])=='diagnostic assessment':
			case count($a)==1 && strtolower($l['Type'])=='admission assessment':
				$urlBase='diagnostics.php';
				$url=$urlBase.'?Logs_ID='.$ID;
				$button=$l['Type'];
				$size='801,673';
				$target='l1_'.str_replace(' ','',strtolower($l['Type']));
			break;
			
			

			
			
			
			
			
			
			case $node=='training_event':
				$url.='?Events_ID='.q("SELECT Events_ID FROM gf_attendances WHERE ID=$ID", O_VALUE);
				global $attending;
				if(isset($attending))$url.='&attending='.$attending;
			break;
			case $node=='progress_reports':
				$url.='?ID='.$ID;
			break;
			case $node=='focus_incident_reports':
				$url.='?ID='.$ID;
			break;
			case $node=='focus_restraint_reports':
				$url.='?ID='.$ID;
			break;
			case $node=='document_familycontacts':
				$url.='?ID='.$ID;
			case $node=='casemanager':
				$url.='?Logs_ID='.$ID;
			break;
			case $node=='clinical':
				$url.='?Logs_ID='.$ID;
			break;
			default:
				mail($developerEmail,'error file function_gl_link_manager_v101.php line '.__LINE__,get_globals(),$fromHdrBugs);
				?>
				Unable to process your request - staff have been notified<br />
				<input type="button" name="Submit" value="Home Page" onclick="window.location='home.php';" />
				<?php
				exit;
		}
	}else{
		$gl_link_manager['error']=('There is no progress note, report, or page available by that ID; either it has been deleted or the URL is incorrect');
		return false;
	}
	return true;
}

?>