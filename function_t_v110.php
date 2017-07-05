<?php
/*
Notes on strtotime: it's not perfect (IMO), here is/are examples of wierd things
	2008-11-13 returns 1226556000, but 2008-11-13x returns 1226574000


*/
if(!function_exists('prn')){
function prn($n){
	echo '<pre>';print_r($n);echo '</pre>';
}
}
if(!function_exists('t_in')){
//need to get rid of this
function t_in(&$StartDate, $format=''){
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
	global $tk, $fl, $ln, $t_parse, $t_out;
	if(!strlen($input)){
		return '';
	}else if($input===false){
		return false;
	}else{
		return date($format,$input);
	}
}
}
//formats
define('F_DT','formatDateTime',true);
define('F_TS','formatTimeStamp',true);
define('F_UNIX','formatUnix',true);
define('F_QBKS','formatQuickBooks',true);
define('F_TRS','formatTimeRemainingStopwatch',true); //e.g. 3:59.4
define('F_TRH','formatTimeRemainingHuman',true); //e.g. 3 minutes 59.4 seconds
define('F_AGE','formatHumanAge',true); //e.g. 15 years old. - this needs to submit to formatting but standard is that anything between 0 and 2 years goes like this 0-18 months as such, 19-23 months 1 year [19-23] months, and integer year after


//flags/directives
define('dironal','dironal',true); //"DO IT RIGHT OR NOT AT ALL" - good value returns true, and blank also; just not an error
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

$tx['defHumanFormat']=f_qbks;
$tx['defDbFormat']=f_datetime;

//this is a library which can be built
$tx['formats']['QuickBooks']='m/d/Y';
$tx['formats']['DateTime']='Y-m-d H:i:s';

$functionVersions['t']=1.10;
function t(&$param1, $param2=NULL, $param3=NULL, $param4=NULL, $param5=NULL, $param6=NULL, $param7=NULL, $param8=NULL, $param9=NULL){
	/*
	Master date and time management function
	
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
	
	global $tx, $fl, $ln, $t_parse, $t_getformat;

	//configure args
	for($i=1; $i<=9; $i++) eval('if($i==1 || !is_null($param'.$i.'))$args['.$i.']=$param'.$i.';');


	//re-state parameters passed; items not matching constant formats are presumed to be dates/times (even if blank)
	$constFormat='/^(flag|input|output|fail|format)([A-Z][A-Za-z0-9]+)/';
	foreach($args as $n=>$v){
		#echo $n . ':' . $v . '<br>';
		if(strtolower($v)=='dironal'){
			$dironal=true;
		}else if(preg_match($constFormat,$v,$a)){
			//e.g. $outputHuman=true
			$$a[0]=true;
			//alternate way of storing the constants - constants stores only the last of each node, $node stores all - I use $node only as of 2008-11-23
			#echo( '$constants[\''.$a[1].'\']=$'.$a[1].'[]=$a[2];' );
			#echo '<br>';
			eval( '$constants[\''.$a[1].'\']=$'.$a[1].'[]=$a[2];' );
		}else{
			//assume a passed date or time input (including empty value)
			//build list of dates - note the & to pass by reference
			$j++;
			#echo( '$dates['.$j.']=&$param'.$n.';' );
			#echo '<br>';
			eval( '$dates['.$j.']=&$param'.$n.';' );
		}
	}
	#prn($constants);
	#prn($format);
	#prn($output);
	if(count($dates)==0)return false; //no dates passed to evaluate, only constants - might want to notify if this happens
	if(count($dates)==1){
		list($unix,$originalformat)=t_parse($dates[1]);
		#echo "$unix,$originalformat<br>";
		if(stristr($originalformat,'error')){
			//false means error
			return false;
		}else if($originalformat=='null'){
			return ($dironal? true : '');
		}else if($originalformat=='blank'){
			return ($dironal? true : '');
		}else if($originalformat=='human'){
			if($format[0]){
				//return passed format parameter
				$dates[1]=date(t_getformat($format[0]), $unix);
				return $dates[1];
			}else if($tx['defDbFormat']){
				//return as override setting; normally = MySQL datetime e.g. 2008-11-23 11:32:15 - note that a date field will clip the time part, and a time field will clip the date part
				$dates[1]=date(t_getformat($tx['defDbFormat']), $unix);
				return $dates[1];
			}else exit('error - no defDbFormat setting declared');
		}else{
			
			//we will be returning a human output - this var was either datetime, datetime-blank, (MySQL) timestamp, or a unix timestamp
			if($format[0]){
				//return passed format parameter
				$dates[1]=date(t_getformat($format[0]), $unix);
				return $dates[1];
			}else if($tx['defHumanFormat']){
				//return as override setting
				$dates[1]=date(t_getformat($tx['defHumanFormat']), $unix);
				return $dates[1];
			}else exit('error - no defHumanFormat setting declared');
		}
	}else{
		exit('multiple date or time comparisons not yet developed');
	}
}
function t_parse($input){
	/*
	2008-11-22: Parse a date, then return array of a UNIX timestamp and the date's original format
	
	possible format values
	----------------------
	null		- meaning, an actual null value 0x00 - may have been returned on a LEFT JOIN query; usually equivalent to "N/A" or "unknown"
	blank		- meaning, zero string length
	datetime	- mySQL date or time, i.e. 0000-00-00 || 00:00:00
	datetime-blank - mySQL (for 0000-00-00 as normally returned)
	timestamp	- mySQL timestamp; e.g. 20081122143015, 14 digits - UNIX timestamps are currently 10 and won't be 14 for a long time :)
	unix		- integer (including negative if using PHP5)
	human		- anything besides the above
	error		- unable to read; date portion is spit back as FALSE
	*/
	global $tx, $fl, $ln, $t_parse, $t_getformat;
	//pass input directly back to output
	if(is_null($input))return array($input,'null');
	if(!strlen($input))return array($input,'blank');
	
	if(preg_match('/^[0-9]{14}$/',$input)){
		//timestamp (MySQL)
		$yr=substr($input,0,2);
		$YR=substr($input,0,4);
		$readable=$YR.'-'.substr($input,4,2).'-'.substr($input,6,2).' '.substr($input,8,2).':'.substr($input,10,2).':'.substr($input,12,2);
		if(strtotime($readable)==-1 || strtotime($readable)===false){
			//we have to do further operations
			mail($developerEmail,'date read error',get_globals(),$fromHdrBugs);
			return array(false,'error-timestamp');
		}else{
			return array(strtotime($readable),'timestamp');
		}
	}else if(is_int($input)){
		//presume a unix timestamp
		return array($input,'unix');
	}else if(preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}( [0-9]{2}:[0-9]{2}:[0-9]{2})*$/',$input)){
		if(!preg_match('/[1-9]/',$input)){
			//zero datetime - we are going to assume that THIS would never happen in good coding: 0000-00-00 14:30:00 (2:30 elapsed time)
			return array('','blank-datetime');
		}else if(strtotime($input)==-1 || strtotime($input)===false){
			//we have to do further operations
			return array(false,'error-datetime');
		}else{
			return array(strtotime($input),'datetime');
		}
	}else if(preg_match('/^[0-9]{2}:[0-9]{2}:[0-9]{2}$/',$input)){
		if(strtotime($input)==-1 || strtotime($input)===false){
			//we have to do further operations
			return array(false,'error-datetime');
		}else{
			return array(strtotime($input),'datetime');
		}
	}else{
		if(strtotime($input)==-1 || strtotime($input)===false){
			//we have to do further operations
			return array(false,'error-human');
		}else{
			return array(strtotime($input),'human');
		}
	}
}
function t_getformat($format){
	/*
	2008-11-23
	*/
	global $tx, $fl, $ln, $t_parse, $t_getformat;
	if($a=$tx['formats'][$format]){
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


?>