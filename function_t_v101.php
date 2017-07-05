<?php
/*
NOTE: the Dallas Hostgator server supports negative timestamps.  This value is "time inception" on my computer: strtotime('12/31/1969 18:00:01'); #ouputs 1

2008-04-07:
t($date,O_QBKS) -> produces 12/31/2007


*/
//inputs
define('I_HUMAN','inHumanEntered'); //can be anything
define('I_QBKS','inQuickbooks'); //QuickBooks format, 12/31/2007
define('I_DT','inDateTime'); //2007-12-31 15:00:05
define('I_TS','inTimestamp'); //20071231150005 - 14 digits
define('I_UNIX','inUnix'); //range from 0 to 1199167200 (1/1/2008) - 10 digits

//outputs
define('O_DT','outDateTime');
define('O_DATETIME','outDateTime');
define('O_TS','outTimeStamp');
define('O_UNIX','outUnix');
define('O_QBKS','outQuickbooks');
define('O_TRS','outTimeRemainingStopwatch'); //e.g. 3:59.4
define('O_TR','outTimeRemainingHuman'); //e.g. 3 minutes 59.4 seconds
define('O_AGE','outHumanAge'); //e.g. 15 years old. - this needs to submit to formatting but standard is that anything between 0 and 2 years goes like this 0-18 months as such, 19-23 months 1 year [19-23] months, and integer year after

//flags/directives
define('FL_ZERO','flagOutputZero'); //if not declared, blank inputs, OR 0000-00-00 or 00:00, or combos, will return blank

define('IFAIL_FALSE','failFalse'); //on input failure, return false
define('IFAIL_EXIT','failExit'); //on input failure, return false

define('N_EMAIL','failEmail'); //email on errors

$tk['version']='1.00';

$functionVersions['t']=1.01;
function t(){
	global $tk, $fl, $ln, $t_parse, $t_out;
	/*
	2008-02-01 Master time management function
	------------------------------------------
	Ideally I want to pass to this function
	input, [input format,] [operation desired,] [output,] [output format]
	most of these will be implicit - this function thinks like "I" think in handling the output
	
	t() only returns false on a failure to accomplish due to defective input.  Otherwise it will return blank or the ouput
	
	Working with time
	-----------------
	Things I want to do with time
	1. output javascript code which will return a clock value or the like
	2. convert a human time to a computer time
		- form entry to mysql datetime
		- enhanced beyond strtotime such as December 8, 1066
	3. convert a computer time to human time
		- date
		- date and time
		- time
		- estimated time to completion e.g. 3 hours, 15 minutes, or 35:15
	4. compare dates
	5. add time to a date or time
		- we have Feb 15th (assume this year), return the object for the 15th 2 months away, i.e. April 15th
		- add 60 days to a time
	6. get the floor of a time e.g. zero hour, zero day or zero months
	7. get time information on a record in a database
	8. get time information on a filesystem file
	
	there is an issue of precision, for example I don't need seconds when I need 60 days from "now"
	
	examples of calling
	t(239.4, o_tr); //presumes input=239.4 seconds, and outputs 3 minutes, 59.4 seconds
	t(239.4, o_tr); //presumes input=239.4 seconds, and outputs 03:59.4

	NOTE:
	(as of 1.00) there is not a way to specify output FORMAT - the two preceeding could be written several ways:
	3 minutes 59.4 seconds
	3 m, 59.4 s
	3 min, 59.4 sec
	3:59.4
	03:59.400
	etc.

	
	
	*/
	$args=$tk['arg_list']=func_get_args();
	/*
	the actual integer or date format input(s) should be declared by the first three arguments.
	
	*/
	if(count($args)){
		//get assets
		$constFormat='/^(flag|in|out|fail|format)[A-Z]/';
		foreach($args as $n=>$v){
			if(!preg_match($constFormat,$v,$a)){
				$input[count($input)+1]=$v;
				unset($args[$n]);
				continue;
			}else{
				if($a[1]=='in')$in=$a[1];
				if($a[1]=='out')$outs[]=$a[1];
				if($a[1]=='flag')$flags[]=$a[1];
			}
		}
		//shorthand
		if(count($outs)==1)$out=$outs[0];
		if(count($args)){
			//extract constants as variables
			$a=array_flip($args);
			foreach($a as $n=>$v)$a[$n]=true;
			extract($a);
		}
	}else{
		//unlike my function q(), no global variables here
		return false;
	}
	//input considered now if not provided
	if(!count($input)){
		global $dateStamp;
		if($dateStamp){
			$input[1]=strtotime($dateStamp);
		}else{
			$input[1]=time();
		}
	}
	//make sure we have the right format for the input
	switch(true){
		//case
	}
	
	if(count($input)==1){
		//preprocess input for validity
		$input=$input[1];
		if((!$input || preg_match('/^[-0: ]+$/',$input)) && !$flagOutputZero)return '';
		
		switch(true){
			case $outQuickbooks:
				//mm/dd/yyyy
				return t_out('m/d/Y',t_parse($input));
			break;
			case $outDateTime:
				return t_out('Y-m-d H:i:s',t_parse($input));
			break;


		}
	}else{
		//multiple inputs
	}
	
	//dev stage - do a few useful things
	switch(true){
		case $outHumanAge:
			/*
			this may be inprecise dealing with the eom date difference
			*/
			$from=t_parse($input[1]);
			$to=(t_parse($input[2]) ? t_parse($input[2]) : time());
			//calculate
			$from=explode(' ',date('Y m d',$from));
			$to=explode(' ',date('Y m d',$to));
			$years=$to[0] - $from[0] - ($to[1].$to[2] < $from[1].$from[2] ? 1 : 0);
			
			//get months
			$months=$years * 12;
			$months += ($from[1]-$to[1] - ($to[2] < $from[2] ? 1 : 0));
			
			//get days
			
			//output - not in keeping with what I want to get to
			$tk['out']=array('years'=>$years,'months'=>$months);
			return $years;
		break;
	}
}
function t_parse($input){
	global $tk, $fl, $ln, $t_parse, $t_out;
	/*
	uses logic to figure out what a string is and converts it to UNIX timestamp (unless it already is = it is an integer).  Here is the table
	Input				Output
	------------------------------
	[empty]				empty
	20080331121507		unix timestamp for 3/31/2008 12:15:07
	unix timestamp		unix timestamp
	readable date		unix timestamp
	[error]				boolean: false
	*/
	//pass blank directly back to output
	if(!strlen($input))return '';
	
	if(preg_match('/^[0-9]{14}$/',$input)){
		//dateStamp
		$yr=substr($input,0,2);
		$YR=substr($input,0,4);
		$readable=$YR.'-'.substr($input,4,2).'-'.substr($input,6,2).' '.substr($input,8,2).':'.substr($input,10,2).':'.substr($input,12,2);
		if(strtotime($readable)==-1 || strtotime($readable)===false){
			//we have to do further operations
			return false;
		}else{
			return strtotime($readable);
		}
	}else if(is_int($input)){
		//presume a unix timestamp
		return $input;
	}else{
		if(strtotime($input)==-1 || strtotime($input)===false){
			//we have to do further operations
			return false;
		}else{
			return strtotime($input);
		}
	}
}
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

//t(3522, O_TR);
/*
echo t('7/23/1964', O_AGE);
echo '<pre>';
print_r($tk['out']);
*/
?>