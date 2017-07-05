<?php
/*
Notes on strtotime: it's not perfect (IMO), here is/are examples of wierd things
	2008-11-13 returns 1226556000, but 2008-11-13x returns 1226574000
*/
//formats
define('F_DT','formatDateTime',true);

define('F_T','formatTime',true); //12:39 PM
define('F_TDB','formatTimeDB',true); //13:39:00

define('F_DSpST','formatDateSpaceShortTime',true); //10/05[/09] 3:35PM

define('F_TS','formatTimeStamp',true);
define('F_UNIX','formatUnix',true);
define('F_QBKS','formatQuickBooks',true);
define('F_SHORT','formatShort',true);
define('F_TRS','formatTimeRemainingStopwatch',true); //e.g. 3:59.4
define('F_TRH','formatTimeRemainingHuman',true); //e.g. 3 minutes 59.4 seconds
define('F_AGE','formatHumanAge',true); //e.g. 15 years old. - this needs to submit to formatting but standard is that anything between 0 and 2 years goes like this 0-18 months as such, 19-23 months 1 year [19-23] months, and integer year after


define('O_DAY','outputDay', true); //for date comparisons as of 2010-06-19

//flags/directives
define('dironal','dironal',true); //"DO IT RIGHT OR NOT AT ALL" - good value returns true, and blank also; just not an error
define('thisyear','thisyear',true); //will remove /09 or /2009
define('noref','flagNoref',true); //2011-02-22
/*
NOT USED YET AS OF 1.10!! Remove these if not needed
define('FL_ZERO','flagOutputZero',true); //if not declared, blank inputs, OR 0000-00-00 or 00:00, or combos, will return blank
define('IFAIL_FALSE','failFalse',true); //on input failure, return false
define('IFAIL_EXIT','failExit',true); //on input failure, return false
define('N_EMAIL','failEmail',true); //email on errors

//2008-11-23; these inputs may not be needed; inputs are read and parsed by the system anyway
define('I_HUMAN','inputHumanEntered',true); //can be anything
define('I_QBKS','inputQuickBooks',true); //QuickBooks format, 12/31/2007
define('I_DT','inputDateTime',true); //2007-12-31 15:00:05
define('I_TS','inputTimestamp',true); //20071231150005 - 14 digits
define('I_UNIX','inputUnix',true); //range from 0 to 1199167200 (1/1/2008) - 10 digits
*/

$tx['defHumanFormat']='quickbooks';
$tx['defDbFormat']='datetime';

//this is a library which can be built
$tx['formats']['quickbooks']='m/d/Y';
$tx['formats']['short']='n/j/y';
$tx['formats']['datetime']='Y-m-d H:i:s';
$tx['formats']['time']='g:iA';
$tx['formats']['timedb']='H:i:s';
$tx['formats']['datespaceshorttime']='m/d/Y g:iA';


$functionVersions['t']=1.12;
function t(&$param1, $param2=NULL, $param3=NULL, $param4=NULL, $param5=NULL, $param6=NULL, $param7=NULL, $param8=NULL, $param9=NULL){
	/*
	Master date and time management function
	v1.12 2010-06-23
	---------------------------------------------------
	* spawned this new version for testing a problem
	* previous version started a clumsy use of date differences
	v1.11 2009-08-15
	---------------------------------------------------
	* cleaned up and reviewed coding some
	
	v1.10 2008-02-01 
	---------------------------------------------------
	This function operates on a simple concept, that usually we want to convert human dates to machine, or vice versa.  Additionally, we want to read if the date is valid.  This function passes the date by reference, and returns false if the date is not valid, but otherwise also converts the date.  Here is the basic concept:
	When passing one date or time:
		if input=human, output = database [can do global setting to change this]
		if input=non-human (i.e. unix timestamp or MySQL YYYY-MM-DD format), output = human, default format [can do global setting to change this]
	When passing two dates or times:
		(not developed yet, but..) find difference between them, an array of information
	When want to add time to a date:
		 not developed yet

	NOTES:
	------
	1. if I call t($date) and $date is not declared (null), then t will capture date anyway as a potential SQL output with a null value.  dironal will be in effect for null as with blank (empty)
	2. DIRONAL means "do it right or not at all" - good input or no input is OK, only BAD input is not accepted (returns false)

	#------------------------------------------------------------
	If the way I pass parameters to the function looks clumsy, blame PHP :) - I found that it was not possible to do this:
	
			function t(){
				$args=func_get_args();
				.. etc. ..
			}
			//call function with variable number of params like this
			t(&$date1, I_DATETIME, &$date2, O_UNIX);
	
	..and have $date1 and $date2 be preserved by reference; so we declare 9 parameters, enough for any future growth; only one parameter is required.  Any date after the first parameter must be passed by reference on the FUNCTION CALL, i.e.:
	t($date1, CONSTANT_A, .. CONSTANT_N, &$date2); //note how date2 is passed
	#------------------------------------------------------------
	

	todo:
	-----
	on creating a db date such as 2008-11-23 00:00:00, see if the (human) date passed INTENDED a time part, or be able to flag that this is not allowed

	already used as 2nd parameter
		o_datetime
		o_qbks
	*/
	global $tx,$developerEmail,$fromHdrBugs;

	//configure args
	for($i=1; $i<=9; $i++) eval('if($i==1 || !is_null($param'.$i.'))$args['.$i.']=$param'.$i.';');

	//re-state parameters passed; items not matching constant formats are presumed to be dates/times (even if blank)
	$constFormat='/^(flag|input|output|fail|format)([A-Z][A-Za-z0-9]+)/';
	//pre-declare args for flagNoref
	foreach($args as $v)if(strtolower($v)=='flagnoref')$noref=true;
	
	foreach($args as $n=>$v){
		if(strtolower($v)=='thisyear'){
			$thisyear=true;
		}else if(strtolower($v)=='dironal'){
			$dironal=true;
		}else if(preg_match($constFormat,$v,$a)){
			//e.g. $outputHuman=true
			$$a[0]=true;
			//alternate way of storing the constants - constants stores only the last of each node, $node stores all - I use $node only as of 2008-11-23
			eval( '$constants[\''.$a[1].'\']=$'.$a[1].'[]=$a[2];' );
		}else{
			//assume a passed date or time input (including empty value)
			//build list of dates - note the & to pass by reference
			$j++;
			eval( '$dates['.$j.']='.($noref?'':'&').'$param'.$n.';' );
		}
	}
	if(count($dates)==0)return false; //no dates passed to evaluate, only constants - might want to notify if this happens
	if(count($dates)==1){
		list($unix,$originalformat)=t_parse($dates[1]);
		#echo "$unix,$originalformat<br>";
		if(stristr($originalformat,'error')){
			//false means error
			return false;
		}else if(stristr($originalformat,'null')){
			return ($dironal? true : '');
		}else if(stristr($originalformat,'blank')){
			return ($dironal? true : '');
		
		/* ----------------------- logical treatment (hard-coded for now ---------------------
		the following two codeblocks simply state that if the format is human, we want to convert it to db and vice versa.  However, there are times when a human is going to enter e.g. 2009-08-15 or even .. 13:49:05 so the assumed conversion would be wrong - we need a way to override the logic */
		
		}else if(strstr($originalformat,'human')){
			//note defDbFormat means we think it's being converted to a db time
			if(!$format[0] && !$tx['defDbFormat'])error_alert('error - no defDbFormat setting declared');
			//return as override setting; normally = MySQL datetime e.g. 2008-11-23 11:32:15 - note that a date field will clip the time part, and a time field will clip the date part
			$dates[1]=date(t_getformat($format[0] ? $format[0] : $tx['defDbFormat']), $unix);
			return $dates[1];
			
		}else{
			#covered cases:
			$originalformat=='datetime';
			//we will be returning a human output - this var was either datetime, datetime-blank, (MySQL) timestamp, or a unix timestamp
			if(!$format[0] && !$tx['defHumanFormat'])error_alert('error - no defHumanFormat setting declared');
			$dates[1]=date(t_getformat($format[0] ? $format[0] : $tx['defHumanFormat']), $unix);
			if($thisyear)$dates[1]= preg_replace('#\/('.date('Y|y').')( |$)#','$2',$dates[1]);
			return $dates[1];

		}
		/* ------------------------ end logical treatment hard-coded -------------------------- */
		
		
	}else if(count($dates)==2){
		//transitional handling
		$a=t_parse($dates[1]);
		$b=t_parse($dates[2]);
		if($output[0]){
			$calc=array(strtolower($output[0]));
		}else if(substr($a[1],0,8)=='datetime' && substr($b[1],0,8)=='datetime' && (fmod($a[0],24*3600) || fmod($b[0],24*3600))){
			$calc=array('year','month','week','day','hour','minute','second');
		}else if(substr($a[1],0,4)=='date' && substr($b[1],0,4)=='date'){
			$calc=array('year','month','week','day');
		}else if(substr($a[1],0,4)=='time' && substr($b[1],0,4)=='time'){
			$calc=array('hour','minute','second');
		}
		foreach($calc as $v){
			if($v=='year'){
				//how do I do this
				//subtract out the years
			}else if($v=='month'){
				//ditto - 3/15 to 7/22 is 4 solid months, big question is how many months between 11/30 and 2/28? you could say 2 or 3 but if you say 2, on 3/31 you jump to 4
			}else if($v=='week'){
			
			}else if($v=='day'){
				if(count($calc)==1){
					$out=floor( ($b[0]-$a[0])/(24*3600) );
					return $out;
				}
			}else if($v=='hour'){
			
			}else if($v=='minute'){

			}else if($v=='second'){

			}
		}
		/*
		passing these OK:
			date - date
			datetime - datetime
			time* - time* (see function t_parse)
		if date-date we get differences of days, months and years
		if datetime-datetime we get (months days) hours minutes seconds
		if time-time we get hours minutes seconds
		
		*/
		exit('multiple date or time comparisons not yet developed');
	}
}
function t_parse($input){
	/*
	2009-08-27: new return for format = (unrecognized|datepart)(:human)*(-blank|-error|-null) .. NOTE that :human is a "pseudo class"
	2008-11-22: Parse a date, then return array of a UNIX timestamp and the date's original format
	
	possible dateparts
	----------------------
	null		- meaning, an actual null value 0x00 - may have been returned on a LEFT JOIN query; usually equivalent to "N/A" or "unknown"
	blank		- meaning, zero string length
	unrecognized- 
	datetime	- mySQL date or time, i.e. 2008-11-22 || 14:30:15
	timestamp	- mySQL timestamp; e.g. 20081122143015, 14 digits - UNIX timestamps are currently 10 and won't be 14 for a long time :)
	unix		- integer (including negative if using PHP5)
	error		- unable to read; date portion is spit back as FALSE
	*/
	global $tx,$developerEmail,$fromHdrBugs;
	//pass input directly back to output
	if(is_null($input))return array($input,'null');
	if(!strlen($input))return array($input,'blank');
	
	if(preg_match('/^0*[1-9]-0*[1-9]-[12][0-9]{3}$/',$input)){
		$a=explode('-',$input);
		$a[0]=str_pad($a[0],2,'0',STR_PAD_LEFT);
		$a[1]=str_pad($a[1],2,'0',STR_PAD_LEFT);
		if(strlen($a[2])==2){
			$a[2]='20'.$a[2];
		}else{
		
		}
		$input=implode('-',$a);
	}
	
	if(preg_match('/^[0-9]{14}$/',$input)){
		//timestamp (MySQL)
		$yr=substr($input,0,2);
		$YR=substr($input,0,4);
		$readable=$YR.'-'.substr($input,4,2).'-'.substr($input,6,2).' '.substr($input,8,2).':'.substr($input,10,2).':'.substr($input,12,2);
		if(strtotime($readable)==-1 || strtotime($readable)===false){
			//we have to do further operations
			mail($developerEmail,'date read error',get_globals(),$fromHdrBugs);
			return array(false,'timestamp-error');
		}else{
			return array(strtotime($readable),'timestamp');
		}
	}else if(preg_match('/^-*[0-9]+$/',$input)){
		//presume a unix timestamp
		return array($input,'unix');
	}else if(preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}( [0-9]{2}:[0-9]{2}:[0-9]{2})*$/',$input)){
		if(!preg_match('/[1-9]/',$input)){
			//zero datetime - we are going to assume that THIS would never happen in good coding: 0000-00-00 14:30:00 (2:30 elapsed time)
			return array('','blank-error');
		}else if(strtotime($input)==-1 || strtotime($input)===false){
			//we have to do further operations
			return array(false,'datetime-error');
		}else{
			return array(strtotime($input), strlen($input)==10?'date':'datetime' );
		}
	}else if(preg_match('/^[0-9]{2}:[0-9]{2}:[0-9]{2}$/',$input)){
		if(!preg_match('/[1-9]/',$input)){
			return array('','time-blank');
		}else if(strtotime($input)==-1 || strtotime($input)===false){
			//we have to do further operations
			return array(false,'time-error');
		}else{
			return array(strtotime($input),'time');
		}
	}else{
		if(strtotime($input)==-1 || strtotime($input)===false || preg_match('/^(sat(ur)*|sun|mon|tue(s)*|wed(nes)*|thu(r)*(s)*|fri)(day)*$/i',$input)){
			//we have to do further operations
			return array(false,'unrecognized:human-error');
		}else{
			//now we see if the string is date-only, time-only, or both
			/*
			times would be: 1:45pm, 12pm, 12:00:39, noon, 6pm, 12:34 P.M. - max length = 10 digits
			dates would be: today, tomorrow, yesterday, 08/13/09, 08/13/2009 - max length= 10 digits or more
			date times would be LIKE a GMT, but the longer strong 
			
			*/
			if(
				preg_match('#/|Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec|th|rd|nd|st|now|day#i',$input) || 
				strlen($input)>10 || preg_match('/[0-9]+(\/|-)[0-9]+(\/|-)[0-9]+/',$input))$components='date';
			if(
				preg_match('/[a|p]\.*\s*[m]\.*/i',$input) || 
				preg_match('/minute|hour|second/i',$input) ||
				strtolower($input)=='now' || 
				strstr($input,':'))$components.='time';
			if(!$components){
				//this would be wierd - php says it's valid but it contains none of these things
				mail($developerEmail,'error file '.__FILE__.', line '.__LINE__,get_globals('input='.$input),$fromHdrBugs);
			}
			return array(strtotime($input),$components.':human');
		}
	}
}
function t_getformat($format){
	/*
	2008-11-23 - simple method to return the layout of the date based on an abbreviation
	*/
	global $tx,$developerEmail,$fromHdrBugs;
	if($a=$tx['formats'][strtolower($format)]){
		return $a;
	}else{
		return 'Y-m-d H:i:s';
	}
}

/* -------------- EXAMPLES ----------------
#1
//t() can be called with parameters in any order
echo $in='2008-11-23';
t(f_qbks, $in); //returns 11/23/2008 but also changes $in by reference; format QuickBooks
echo $in; //$in is now 11/23/2008


#2
$date='some value';
#or alternately
unset($date); //date is now NULL

if(t($date, dironal)){
	//good or blank - date was also converted to db
	echo $date;
}else{
	exit('error reading date');
}


#3
$date='2008-11-23'; //considered a non-human date but some might type it like that (me for example :-|)
t($date, f_dt); //add f_dt to force a datetime being output vs. defHumanFormat
echo $date; //2008-11-23 00:00:00

*/

if(!function_exists('t_in')){
//need to get rid of this
function t_in(&$StartDate, $format=''){
	global $tx,$developerEmail,$fromHdrBugs;
	if($out=t_parse($StartDate)){
		//convert the var by reference
		switch(true){
			case $format==O_DATE:
				$format='Y-m-d';
				break;
			case $format==O_TIME:
				$format='H:i:s';
				break;
			default: //O_DT, O_DATETIME
				$format='Y-m-d H:i:s';
		}
		$StartDate=t_out($format,$out);
		//also return it - vs just true	
		return $StartDate;
	}else{
		return false;
	}
}
}
if(!function_exists('t_out')){
function t_out($format,$input){
	global $tx,$developerEmail,$fromHdrBugs;
	if(!strlen($input)){
		return '';
	}else if($input===false){
		return false;
	}else{
		return date($format,$input);
	}
}
}

?>