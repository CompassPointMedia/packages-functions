<?php
	$tz=array(
		/** US Time Zones **/
		'PDT'=>-7, 'MDT'=>-6, 'CDT'=>-5, 'EDT'=>-4, 
		'PST'=>-8, 'MST'=>-7, 'CST'=>-6, 'EST'=>-5);
//change if desired
$parse_javascript_gmt_date['debug']=2; //1=send only unrecognized time zone, 2=send unknown date format
$functionVersions['parse_javascript_gmt_date']=1.20;
function parse_javascript_gmt_date($x, $browser){
	/**
	2008-02-01: cleaned entire coding up
	2006-05-04 by Sam: returns GMT (absolute) time as a Linux timestamp in seconds
	**/
	global $parse_javascript_gmt_date,$tz, $developerEmail, $fromHdrBugs;
	if(!$x)return false;
	// this is the string you get with a javascript date - isn't javascript just wonderful..
	/**
	IE:  //Tue Apr 11 05:35:03 CDT 2006
	MOZ: //Tue Apr 11 2006 05:33:28 GMT-0500 (Central Daylight Time)
	Also: //Mon Aug 1 21:45:48 CDT 2011
	notice that we subtract the offset to get UTC - so if we're CST (-5) we subtract to add 5 hours
	**/

	$m=array('Jan'=>'01','Feb'=>'02','Mar'=>'03','Apr'=>'04','May'=>'05','Jun'=>'06','Jul'=>'07','Aug'=>'08','Sep'=>'09','Oct'=>'10','Nov'=>'11','Dec'=>'12');
	$w=array('Sun'=>'01','Mon'=>'02','Tue'=>'03','Wed'=>'04','Thu'=>'05','Fri'=>'06','Sat'=>'07');

	if(preg_match('/([a-z]+) ([a-z]+) ([0-9]+) ([0-9]+):([0-9]+):([0-9]+) ([a-z]+) ([0-9]+)/i',trim($x),$d)){
		$parse_javascript_gmt_date['TZ']=$tz[$d[7]];
		$parse_javascript_gmt_date['TZString']=$d[7];
		
		//return the date
		$baseTime=strtotime($d[8].'-'.str_pad($m[$d[2]],2,'0',STR_PAD_LEFT).'-'.str_pad($d[3],2,'0',STR_PAD_LEFT).' '.$d[4].':'.$d[5].':'.$d[6]);
		if($baseTime===false && $parse_javascript_gmt_date['debug']>1){
			mail($developerEmail,'unrecognized date format',get_globals(),$fromHdrBugs);
		}else{
			return $baseTime;
		}
		
	}else if($browser=='IE'){
		//e.g. Tue Apr 11 05:35:03 CDT 2006
		$x=str_replace(':',' ',$x);
		$d=explode(' ',$x);
		$d[0]=$w[$d[0]];
		$d[1]=$m[$d[1]];
		$d[2]=str_pad($d[2],2,'0',STR_PAD_LEFT);
		//final date format
		if(!preg_match('/^([0-9]{2} ){6}[A-Z]{3} [0-9]{4}/i',implode(' ',$d))){
			//different format, want to see it and the version it came from
			if($parse_javascript_gmt_date['debug']>1)
			mail($developerEmail,'IE unrecognized date format',get_globals(),$fromHdrBugs);
		}

		//set the time zone
		$thistz=$d[6];
		if(!$thistz || !isset($tz[$thistz])){
			if($parse_javascript_gmt_date['debug']>0)
			mail($developerEmail,'Moz unrecognized time zone string (d[6])',get_globals(),$fromHdrBugs);
		}
		$parse_javascript_gmt_date['TZ']=$tz[$thistz];
		$parse_javascript_gmt_date['TZString']=$thistz;
		
		//return the date
		$baseTime=strtotime($d[7].'-'.$d[1].'-'.$d[2].' '.$d[3].':'.$d[4].':'.$d[5]);
		return $baseTime;
	}else{ /*Moz*/
		//e.g. Tue Apr 11 2006 05:33:28 GMT-0500 (Central Daylight Time)
		
		$x=str_replace(':',' ',$x);
		$a=explode(' (',trim($x));
		$d=explode(' ',$a[0]);
		$d[0]=$w[$d[0]];
		$d[1]=$m[$d[1]];
		$d[2]=str_pad($d[2],2,'0',STR_PAD_LEFT);
		$d[]=str_replace(')','',$a[1]);
		//final date format
		if(!preg_match('/^([0-9]{2} ){3}[0-9]{4} ([0-9]{2} ){3}(GMT|UTC)[+-][0-9]{4} [a-z ]+/i',implode(' ',$d))){
			//different format, want to see it and the version it came from
			if($parse_javascript_gmt_date['debug']>1)
			mail($developerEmail,'Moz unrecognized date format',get_globals(),$fromHdrBugs);
		}

		//set the time zone
		$thistz=preg_replace('/[^A-Z]*/','',$d[8]);
		if(!$thistz || !isset($tz[$thistz])){
			if($parse_javascript_gmt_date['debug']>0)
			mail($developerEmail,'Moz unrecognized time zone string (d[8])',get_globals(),$fromHdrBugs);
		}
		$parse_javascript_gmt_date['TZ']=$tz[$thistz];
		$parse_javascript_gmt_date['TZString']=$thistz;
		
		//return the date
		$baseTime=strtotime($d[3].'-'.$d[1].'-'.$d[2].' '.$d[4].':'.$d[5].':'.$d[6]);
		return $baseTime;
	}
}
?>