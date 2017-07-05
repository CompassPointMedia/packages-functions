<?php
function gf_link_manager($vars=''){
	/* Created 2010-08-07: after a brainstorm where I figured that the page opener from email is kind of like a download manager, and we want to pass multiple rows to a GUI similar to this
	
	*/
	global $GCUserName;
	if(!$vars)$vars=$_SERVER['QUERY_STRING'];
	parse_str($vars,$a);
	extract($a);
	//inputs covered by string passed
	
	//outputs as follows
	global $url, $window, $button, $size,$qr,$qx;
	/*
	Master Array 
	array(
		'LOC'=>array(
			'url'=>'loc_assign.php?Children_ID='.$Children_ID.'&UpdateLocs_ID='.$ID.'&action=update&ResourceToken='.$ResourceToken,
			'window'=>'l1_locassign',
			'size'=>'600,650',
			'button'=>'LOC assignment'
		),
		'staff'=>array(
			'url'=>'timesheets.php?Timesheets_ID='.$ID,
			'window'=>'l1_timesheets',
			'size'=>'950,450',
			'button'=>'timesheet',
		),
		'viewBulletin'=>array(
			'url'=>'read_bulletins.php?Bulletins_ID='.$Bulletins_ID,
			'window'=>'l1_reader',
			'size'=>'',
			'button'=>'Bulletin',
		),
		'children'=>array(
				'url'=>'children.php?Children_ID='.$ID,
				'window'=>'l1_children',
				'size'=>'700,700',
				'button'=>'Child Record',
		),
		'parents'=>array(
			'url'=>'parents.php?Parents_ID='.$ID,
			'window'=>'l1_parents',
			'button'=>'Parent Record',
			'size'=>'700,700'
		),
		'fosterhomes'=>array(
			'url'=>'homes.php?Fosterhomes_ID='.$ID,
			'window'=>'l1_fosterhomes',
			'button'=>'Fosterhome Record',
			'size'=>'700,700',
		),
		'childrenlocs'=>array(
			'url'=>'loc_assign.php?Children_ID='.$Children_ID.'&Locs_ID='.$ID.'&action=update&ResourceToken='.$ResourceToken,
			'window'=>'l1_locassign',
			'size'=>'550,650',
			'button'=>'LOC assignment',	
		),			
		'selfstudy'=>array(
			'url'=>'selfstudy.php?Tests_ID='.$ID,
			'window'=>'l1_selfstudy',
			'size'=>'700,700',
			'button'=>'Self-study Test'
		),				
		'attendances'=>array(
			'url'=>'training_event.php?Events_ID='.q("SELECT Events_ID FROM gf_attendances WHERE ID=$ID", O_VALUE),
			global $attending,
			if(isset($attending))$url.='&attending='.$attending,
			'window'=>'l1_trainingevent',
			'size'=>'700,700',
			'button'=>'Training Event'
		),
		'progress notes' || strtolower($Type)=='child status reports'=>array(
			'url'='progress_reports.php?ID='.$ID,
			'window'=>'l1_progress',
			'button'=>preg_replace('/s$/','',$Type),
			'size'=>'850,650',
		),
		'incident report'=>array(
			'url'='focus_incident_reports.php?ID='.$ID,
			'window'=>'l1_incident',
			'button'=>'Incident Report',
			'size'=>'700,700',
		),
		'restraint report'=>array(
			'url'='focus_restraint_reports.php?ID='.$ID,
			'window'=>'l1_restraint',
			'button'=>'Restraint Report',
			'size'=>'700,700',
		),
		'cm logs'=>array(
			'url'='casemanager.php?Logs_ID='.$ID,
			'window'=>'l1_casemanager',
			'button'=>'Case Management Log',
			'size'=>'700,700',
		),
		'therapy notes'=>array(
			'url'='clinical.php?Logs_ID='.$ID,
			'window'=>'l1_therapynotes',
			'button'=>'Therapy Note',
			'size'=>'700,700',
		),
	);*/
	
	if($node=='LOC'){
		$ResourceToken=preg_replace('/^20[0-9]/','',date('YmdHis').substr(rand(10000,100000),0,5));
		$url='loc_assign.php?Children_ID='.$Children_ID.'&UpdateLocs_ID='.$ID.'&action=update&ResourceToken='.$ResourceToken;
		$window='l1_locassign';
		$size='600,650';
		$button='LOC assignment';
	}else if($node=='staff'){
		if($a=q("SELECT * FROM gf_timesheets WHERE ID=$ID", O_ROW)){
			$url='timesheets.php?Timesheets_ID='.$ID;
			$window='l1_timesheets';
			$size='950,450';
			$button='timesheet';
		}else{
			$gf_link_manager['notice']=('There is not timesheet available by that ID; either it has been deleted or the URL is incorrect');
			return false;
		}
	}else if($pnrmode=='viewBulletin'){
		if($a=q("SELECT * FROM gf_bulletins WHERE ID=$Bulletins_ID", O_ROW)){
			//prn($a);
			$url='read_bulletins.php?Bulletins_ID='.$Bulletins_ID;
			$window='l1_reader';
			$button='Bulletin';
		}else{
			$gf_link_manager['notice']=('There is no bulletin available by that ID; either it has been deleted or the URL is incorrect');
			return false;
		}
	}else{
		if($object){
			if($object=='selfstudy'){
				$table='t_tests';
			}else{
				$table='gf_'.$object;
			}
			if($record=q("SELECT * FROM $table WHERE ID='$ID'", O_ROW)){
				//great..
				switch(true){
					case strtolower($object)=='children':
						$url='children.php?Children_ID='.$ID;
						$window='l1_children';
						$button='Child Record';
						$size='700,700';
					break;
					case strtolower($object)=='parents':
						$url='parents.php?Parents_ID='.$ID;
						$window='l1_parents';
						$button='Parent Record';
						$size='700,700';
					break;
					case strtolower($object)=='fosterhomes':
						$url='homes.php?Fosterhomes_ID='.$ID;
						$window='l1_fosterhomes';
						$button='Fosterhome Record';
						$size='700,700';
					break;
					case strtolower($object)=='childrenlocs':
						$url='loc_assign.php?Children_ID='.$Children_ID.'&Locs_ID='.$ID.'&action=update&ResourceToken='.$ResourceToken;
						$window='l1_locassign';
						$size='550,650';
						$button='LOC assignment';				
					break;
					case strtolower($object)=='selfstudy':
						$url='selfstudy.php?Tests_ID='.$ID;
						$window='l1_selfstudy';
						$size='700,700';
						$button='Self-study Test';				
					break;
					case strtolower($object)=='attendances':
						$url='training_event.php?Events_ID='.q("SELECT Events_ID FROM gf_attendances WHERE ID=$ID", O_VALUE);
						global $attending;
						if(isset($attending))$url.='&attending='.$attending;
						$window='l1_trainingevent';
						$size='700,700';
						$button='Training Event';				
					break;
				}
			}else{
				$gf_link_manager['notice']=('There is not a record present for the ID provided.');
				return false;
			}
		}else if($a=q("SELECT * FROM gf_logs WHERE ResourceType IS NOT NULL AND Statuses_ID>0 AND ID=$ID", O_ROW)){
			//object from gf_logs
			extract($a);
			if($a['FosterhomesParents_ID'])
				$Parents_ID=q("SELECT Parents_ID FROM gf_FosterhomesParents WHERE ID=".$a['FosterhomesParents_ID'], O_VALUE);
			switch(true){
				case strtolower($Type)=='progress notes' || strtolower($Type)=='child status reports':
					$url='progress_reports.php?ID='.$ID;
					$window='l1_progress';
					$button=preg_replace('/s$/','',$Type);
					$size='850,650';
				break;
				case strtolower($Type)=='incident report':
					$url='focus_incident_reports.php?ID='.$ID;
					$window='l1_incident';
					$button='Incident Report';
					$size='700,700';
				break;
				case strtolower($Type)=='restraint report':
					$url='focus_restraint_reports.php?ID='.$ID;
					$window='l1_restraint';
					$button='Restraint Report';
					$size='700,700';
				break;
				case strtolower($Type)=='cm logs':
					$url='casemanager.php?Logs_ID='.$ID;
					$window='l1_casemanager';
					$button='Case Management Log';
					$size='700,700';
				break;
				case strtolower($Type)=='therapy notes':
					$url='clinical.php?Logs_ID='.$ID;
					$window='l1_therapynotes';
					$button='Therapy Note';
					$size='700,700';
				break;
				default:
					mail($developerEmail,'error file home_pnr.php line '.__LINE__,get_globals(),$fromHdrBugs);
					?>
					Unable to process your request - staff have been notified<br />
					<input type="button" name="Submit" value="Home Page" onclick="window.location='home.php';" />
					<?php
					exit;
			}
		}else{
			$gf_link_manager['notice']=('There is no progress note, report, or page available by that ID; either it has been deleted or the URL is incorrect');
			return false;
		}
	}
	return true;
}

?>