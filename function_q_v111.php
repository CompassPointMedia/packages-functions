<?php
//current definitions for this version
define(C_DEFAULT,1);
define(C_MASTER,2);
define(C_SUPER,3);

//die methods 20-39
define(ERR_DIE,20);
define(ERR_SILENT,21);
define(ERR_ALERT,22);
define(ERR_ECHO,23);

//these are used by the system and are not passed to the function
define(E_NO_SQL_QUERY,40);
define(E_NO_DB_CNX_VARS,41);
define(E_NO_RB_CNX_VARS,42);
define(E_BAD_DB_CNX,43);
define(E_BAD_RB_CNX,44);
define(E_QUERY_FAILED,45);

//output types 100+
define(O_VALUE,100);
define(O_ROW,101);
define(O_EXTRACT_ROW,102);
define(O_COUNT,103);		//var	returns count of select query
define(O_INSERTID,104);
define(O_AFFECTEDROWS,110);
define(O_ARRAY,105);
define(O_ARRAY_ASSOC,106);
define(O_COL,107);
define(O_COL_ASSOC,108);
define(O_NOTHING,109);		//void	returns nothing

define(O_TEST,900);
define(O_TEST_CNX,901);
define(O_DO_NOT_REMEDIATE,902);

//System Definitions; these can be overridden in individual files as needed

//what version of q is this
$qx['version']='1.11';
//default name of global sql variable, default is $sql #not used yet in 1.10
$qx['defSQLVar']='sql';
/*** FOLLOWING TWO VARS APPLY ONLY WHEN CNX CONSTANT IS NOT EXPLICITLY PASSED ***/

//1. insist that default connection method be declared? This is a safer method, and can be overridden in an individual page.  Means you must declare on each page
$qx['defCnxMethodReq']=true;
//2. default connection method.  Setting this to blank will force each page to declare it.  If you want to declare the default connection method here, then the value of the var above doesn't matter
if(!isset($qx['defCnxMethod']))$qx['defCnxMethod']='';

//use error remediation function
$qx['useRemediation']=false;
//initially set this to zero, it will be incremented when we attempt to repair a problem, then set zero when problem is repaired (whether successfully or not)
$qx['remediating']=0;
//remediation function name; if _q[useRemediation] is true, this function will be checked for existence
$qx['remediationFctn']='r';
//number of times we are allowed to attempt to repair the problem; in my r() function, we will eventually allow for  repair of several problems
$qx['remediationIts']=1;


//will default to ERR_DIE, which echoes the file and line number and errornumber and error text
$qx['defaultQDieMethod']=ERR_DIE;
$functionVersions['q']=1.11;
function q(){
	/**
	//version 1.11 -- 2009-05-31 -- fixed stupid error where mysql_select_db was not connecting to explicitDB when present
	//version 1.10 -- 2006-01-14 -- started remediation in earnest - simple problems like missing fields can be looked up.  Idea is that I can figure out where the table came from by a libary of defs and do the repair.
	//version 1.06 -- 2005-10-12 -- reviewed and verified passage of a cnx via an array
	also unset qr.output and qr.cols so that 
	//version 1.05 -- 2004-12-19 -- Remediation added and constant names have been shortened.
	//version 1.04 -- 2004-12-06 -- O_EXTRACT_SINGLE now returns true if recordset, false if not (and does not extract).  First used on WIDI console, more to follow on this version number.
	//version 1.03 -- 2004-11-27 -- added O_RETURN_ASSOC, so if I say SELECT Code, Label FROM values, I'll get back an array of $a[Code1]=Label1, $a[Code2]=Label2, etc.; Also fixed error: if the cnx was passed as an array without a db, the err message wasn't specific
	**/
	global $qx,$qr,$fl,$ln,$cnxString,$qQueryCount,$qtest;
	//qr is specific to each query; it's cleared out each time
	unset($qr);
	global $qr;
	
	$qQueryCount++;
	$qr['idx']=$qQueryCount;
	$qr['file']=($fl?$fl:'UNKNOWN');
	$qr['line']=($ln?$ln:'UNKNOWN');
	/**
	developed by Sam Fullman starting 2004-11-17
	2009-03-27	the error "no standard rb connection vars" was caused b/c session.currentConnection did not match up with session.defaultConnection (in fact it was another database).  If you get that error, it means exactly what it says so - check your session.cnx and session.currentConnection and session.defaultConnection values
	2004-12-19	Started function remediation and some system variables for this function.  You can set the number of remediation attempts for the function (some queries may be missing two+ tables for example), and declare the name of the remediation function on a case by case basis by declaring $qx[remediationFctn]='fctn_name'
	
	2004-11-22	really got powerful, ability to pass parameters in any order and to set the connection and die method globally.  Very simple to use now, really not much more to do except outputting in an array and sorting if desired, then there are lots of things to do from there such as grouping and reporting; SQL-R and SQL-S could be implemented here.
	
	todo:
	------------------
	need to store all activity on this function globally, with options to save, print, email etc. when called...
	way to die gracefully
	way to alert on die
	way to redirect
	way to repair the problem on die or on no results or on a definiable result
	
	//WARNING: I've had problems with globals when q() is called inside a function; make sure inside a function that all vars are passed explicitly
	//function depends on any global query being stored in a variable named $sql
	//IMPORTANT: the standard relatebase connection is going to pass away because it stores the password on the system
	//there should be a master summary of all queries and their stats that can be stored in an array and printed
	**/
	//we globalize arg list in case we need to use remediation
	if($qx['useRemediation'] && $qx['remediating']){
		echo '<strong>Calling q() in remediation mode!</strong><br>';
		$remedTest=true;
		$arg_list=$qx['remediate_arg_list'];
		prn($arg_list);
	}else{
		$arg_list=$qx['arg_list']=func_get_args();
	}
	$knownConst=array(1,2,3,20,21,22,23,40,41,42,43,44,45,100,101,103,104,105,106,107,108,109,110,900,901); //this prevents passing constants from an older version to the function
	for($i=0; $i < count($arg_list); $i++){
		//if($qtest) echo $arg_list[$i] . ':' . preg_match('/^(O_|ERR_|C_|E_)[A-Z_]+$/',$arg_list[$i]) . ':' . is_int($arg_list[$i]) . ':' . in_array($arg_list[$i],$knownConst) . '<br />';
		if(@preg_match('/^(O_|ERR_|C_|E_)[A-Z_]+$/',$arg_list[$i]) || (is_int($arg_list[$i]) && !@in_array($arg_list[$i],$knownConst))){
			exit('Attempting to call function q() with an unrecognized or outdated constant or integer: '.$arg_list[$i]);
		}
		//rule is, connections are arrays, queries are strings, and flags are constants
		switch(true){
			case is_array($arg_list[$i]):
				//connection string
				$cnx=$arg_list[$i];
			break;
			case is_int($arg_list[$i]):
				//constants
				switch(true){
					case ($arg_list[$i]>899):
						$arg_list[$i]==900 ? $qTesting=true : '';
						$arg_list[$i]==901 ? $qTestingCnx=true : '';
					break(2);
					case ($arg_list[$i]>99): $out=$arg_list[$i];
					break(2);
					case ($arg_list[$i]>19 && $arg_list[$i]<40): $err=$arg_list[$i];
					break(2);
					case ($arg_list[$i]>0):	$cnx=$arg_list[$i];
					break(2);
				}
			default:
				//strings are queries
				//for pre 1.02 version constructs, ignore blank values
				$x=$arg_list[$i];
				if(!trim($x))continue;
				//presumes db names follow this regex, might pull this out for version 1.2
				preg_match('/^[_a-z]+[a-z0-9_]*$/',$x)?$explicitDB=$x:$sql=$x;
		}
	}
	if($qTesting)prn($arg_list);
	//get the query and default values
	$cc=$_SESSION['currentConnection'];
	$cu=$_SESSION['cnx'][$cc]['userName'];
	$queryPassType=(func_num_args()>0?'passed':'globally available');
	if(!$err){
		if($err=$qx['defaultQDieMethod']){
		}else $err=ERR_DIE;
	}
	if(!$sql)global $sql;
	unset($qr['err']);
	if(!trim($sql)){
		//this will not be remediated
		return sub_e($err,E_NO_SQL_QUERY,$queryPassType);
	}
	$sql=trim($sql);
	if($qTesting)prn($sql);
	//evaluate the sql query for type, not developed
	if(!$cnx){
		#1. default connection method declared
		if($cnx=$qx['defCnxMethod']){
			//OK
		#2. required but not present, fail
		}else if($qx['defCnxMethodReq']){
			exit('In function q(), a default connection method is required and has not been passed');
		#3. this is a RelateBase convention and can be removed.  Won't get to here as long as defCnxMethodReq=true
		}else{
			$cnx=C_DEFAULT;
		}
	}
	if($qTestingCnx)echo 'cnx: '.$cnx;
	//set up parameters for connection
	switch(true){
		case is_array($cnx):
			//array MUST be in this order
			$host=$cnx[0];
			$user=$cnx[1];
			$pass=$cnx[2];
			isset($cnx[3])?$db=$cnx[3]:'';
			if($qTesting)prn('(db='.$db.', line '.__LINE__.')');
			$cnxString='cnx_'.substr(md5($host.$user.$pass),0,8);
		break;
		case $cnx==C_SUPER:
			//this cnx is always $rb_cnx if present
			global $rb_cnx,$SUPER_MASTER_HOSTNAME, $SUPER_MASTER_USERNAME, $SUPER_MASTER_PASSWORD,$SUPER_MASTER_DATABASE;
			$host=$SUPER_MASTER_HOSTNAME;
			$user=$SUPER_MASTER_USERNAME;
			$pass=$SUPER_MASTER_PASSWORD;
			!strlen($db)?$db=$SUPER_MASTER_DATABASE:'';
			$cnxString='sup_cnx';
			$problem=E_NO_SUP_CNX_VARS;
			$problem2=E_BAD_DB_CNX;
		break;
		case $cnx==C_MASTER:
			//this cnx is always $rb_cnx if present
			global $rb_cnx,$MASTER_HOSTNAME, $MASTER_USERNAME, $MASTER_PASSWORD,$MASTER_DATABASE;
			$host=$MASTER_HOSTNAME;
			$user=$MASTER_USERNAME;
			$pass=$MASTER_PASSWORD;
			!strlen($db)?$db=$MASTER_DATABASE:'';
			$cnxString='rb_cnx';
			$problem=E_NO_RB_CNX_VARS;
			$problem2=E_BAD_DB_CNX;
		break;
		case $cnx==C_DEFAULT:
			//default RelateBase connection
			global $db_cnx;
			$host=$_SESSION['cnx'][$cc]['hostName'];
			$user=$_SESSION['cnx'][$cc]['userName'];
			$pass=$_SESSION['cnx'][$cc]['password'];
			!strlen($db)?$db=$cc:'';
			$cnxString='db_cnx';
			$problem=E_NO_DB_CNX_VARS;
			$problem2=E_BAD_RB_CNX;
		break;
		default:
			exit('Function q() cannot determine a connection method');
	} //-- end connection handling
	//connect and select database
	global $$cnxString;
	if($qTestingCnx)echo '<br />connection: '.($$cnxString ? $$cnxString : 'not established');
	if(!$$cnxString){
		if(!$host || !$user){
			//no vars passed to connect
			return sub_e($err, $problem,$queryPassType);
		}
		ob_start();
		$$cnxString=mysql_connect($host, $user, $pass);
		$x=ob_get_contents();
		ob_end_clean();
		if(!$$cnxString || strlen($x))return sub_e($err,E_BAD_DB_CNX,'',$x);
		if($db){
			if($qTesting)prn('(db='.$db.', line '.__LINE__.')');
			$x=mysql_select_db($db,$$cnxString);
			if(!$x){
				return sub_e($err,$problem2,$queryPassType,array(mysql_errno($$cnxString),mysql_error($$cnxString)));
			}
		}
	}
	//explicit connect to passed database parameter
	if($explicitDB){
		if($qTesting)prn('(explicit db='.$db.', line '.__LINE__.')');
		$x=mysql_select_db($explicitDB,$$cnxString);
		if(!$x){
			return sub_e($err,$problem2,$queryPassType,array(mysql_errno($$cnxString),mysql_error($$cnxString)));
		}
	}
	//run query, including timing
	$qr['query']=$sql;
	list($usec0, $sec0) = explode(' ',microtime());
	$result=mysql_query($sql,$$cnxString);
	list($usec1, $sec1) = explode(' ',microtime());
	$qr['result']=$result;
	$qr['time']=round($sec1+$usec1-$sec0-$usec0,6);
	if(mysql_error($$cnxString)){
		//here is the actual failed query section
		return sub_e($err,E_QUERY_FAILED,$queryPassType, array(mysql_errno($$cnxString),mysql_error($$cnxString)));
	}
	//will be re-declared later for some queries
	unset($qr['output'],$qr['cols'],$qr['warning']);
	//get query stats
	if(preg_match('/^(INSERT INTO)/i',$sql)){
		$qr['insert_id']=mysql_insert_id($$cnxString);
	}else unset($qr['insert_id']);
	if(preg_match('/^(INSERT INTO)|(DELETE FROM)|(UPDATE)|(REPLACE INTO)/i',$sql)){
		$qr['affected_rows']=mysql_affected_rows($$cnxString);
	}else unset($qr[affected_rows]);
	if(preg_match('/^SELECT/i',$sql)){
		$qr['count']=mysql_num_rows($result);
	}else unset($qr['count']);
	//handle output parameters
	if($out==O_INSERTID){
		return $qr['insert_id'];
	}
	//the following operations only apply to a SELECT query
	switch(true){
		case $out == O_AFFECTEDROWS:
			if(!preg_match('/^(INSERT|UPDATE|DELETE|REPLACE)/i',$sql))$qr['warning']='Query inconsistent with output constant';
			return $qr['affected_rows'];
		case preg_match('/DESCRIBE/i',$sql):
			return $result;
		case $out == O_NOTHING:
			return;
		case $out == O_COUNT:
			return $qr['count'];
		case $out == O_VALUE:
			if(!($rd=mysql_fetch_array($result,MYSQL_NUM)))return false;
			$qr['output']=$rd;
			return $rd[0];
		case $out == O_ROW:
			$rd=mysql_fetch_array($result,MYSQL_ASSOC);
			$qr[output]=$rd;
			return $rd;
		case $out == O_EXTRACT_ROW:
			//this overwrites any existing global variables with same name
			if(!($rd=mysql_fetch_array($result,MYSQL_ASSOC)))return false;
			$qr['output']=$rd;
			ob_start();
			foreach($rd as $n=>$v){
				eval('global $'.$n.';');
				$$n=$v;
			}
			ob_end_clean();
			mysql_free_result($result);
			return true;
		case $out == O_ARRAY:
		case $out == O_ARRAY_ASSOC:
			$x=0;
			while($rd=mysql_fetch_array($result,MYSQL_ASSOC)){
				$x++;
				if($x==1){
					$y=0;
					foreach($rd as $n=>$v){
						$y++;
						if($y==1)$firstCol=$n;
						$qr['cols'][$y]=$n;
					}
				}
				$out==O_ARRAY_ASSOC?$x=$rd[$firstCol]:'';
				$a[$x]=$rd;
			}
			return $a;
		case $out == O_COL:
		case $out == O_COL_ASSOC:
			$x=0;
			$idx=($out==O_COL_ASSOC?1:0);
			while($rd=mysql_fetch_array($result,MYSQL_NUM)){
				$x++;
				$out==O_COL_ASSOC?$x=$rd[0]:'';
				$a[$x]=$rd[$idx];
			}
			return $a;
		default:
			//this is redundant but OK for now
			return $result;
	}
}

function sub_e($err,$type,$queryPassType='',$system_err='',$q=''){
	/**
	Error handling sub-routine
	**/
	global $fl,$ln,$qx,$qr,$cnxString;
	global $$cnxString;
	
	if($qx['useRemediation']) $qx['remediating']++;
	if($qx['useRemediation'] && $qx['remediating']>0){
		if($qx['remediating']>$qx['remediationIts']){
			$qx['remediating']=0;
			//problem was not remediated, unset, send me an email about the problem
			unset($qx['arg_list']);
			if(!function_exists('r_notify')){
				mail($qAdminEmail,'r_notify function not present',implode("\n",array($GLOBALS['PHP_SELF'], $fl, $ln)),'From: bugreports@relatebase.com');
			}else{
				r_notify($qr,$qx);
			}
			//this will now continue through to the default error method
		}else{
			//run remediation
			if(r($err,$type,$queryPassType,$system_err)){
				echo 'success calling q in remediation mode<br>';
				echo 'useremed = '.$qx['useRemediation'] . '<br>';
				echo 'remediating step = '.$qx['remediating'] . '<br>';
				q();
			}else{
				$qx['remediating']=0;
				#return false; //(? not sure if this should be here - can delete if it works) we're done with error system
			}
		}
	}	
	//create the error message
	switch($type){
		case E_NO_DB_CNX_VARS:
			$msg='No Standard RelateBase connection vars (host and username, password) available';
		break;
		case E_NO_SUP_CNX_VARS:
			$msg='No SUPER_CNX RelateBase connection vars available';
		case E_NO_RB_CNX_VARS:
			$msg='No Master RelateBase connection vars available';
		break;
		case E_NO_SQL_QUERY:
			$msg='No SQL query '.$queryPassType;
		break;
		case E_BAD_DB_CNX:
			prn(func_get_args());
			//we should state if the db doesn't exist, or there were not OK permissions
			$x=str_replace('<b>','',$system_err);
			$x=str_replace('</b>','',$x);
			$x=str_replace('<br />',"\n",$x);
			$x=trim($x);
			$a=explode("\n",$x);
			if(is_array($a)){
				foreach($a as $v){
					if(!trim($v))continue;
					$b[]=trim($v);
				}
				$msg=implode("<br />",$a);
			}else{
				$msg='UNKNOWN ERROR';
			}
			$msg='Unable to establish connection: <br />'.$msg;
		break;
		case E_BAD_RB_CNX:
			$msg="(Error #{$system_err[0]}) {$system_err[1]}";
		break;
		case E_QUERY_FAILED;
			$msg="Query failed with the following: (Error #{$system_err[0]}) {$system_err[1]}";
		break;
		default:
			$msg="Query failed with the following: (Error #{$system_err[0]}) {$system_err[1]}";
		break;
	}
	$qr['err']='SQL Error<br>';
	if($fl)$qr['err'].='In file: '.$fl.'<br>';
	if($ln)$qr['err'].='On line: '.$ln.'<br>';
	$qr['err'].=':<br>';
	$qr['err'].=$msg;
	if($err==ERR_DIE){
		die('<div class="sqlException" style="background-color:ALICEBLUE;border:1px dashed #CCC;padding 5 10;">'.$qr['err']. ($qr['query']?'(Query:<br>'.$qr['query'].')':'') . '</div>');
	}else if($err==ERR_ECHO){
		echo $qr['err']='<div class="sqlException" style="background-color:ALICEBLUE;border:1px dashed #CCC;padding 5 10;">'.$qr['err']. ($qr['query']?'(Query:<br>'.$qr['query'].')':'') . '</div>';
	}else if($err==ERR_SILENT){
		return false;
	}else if($err==ERR_ALERT){
		echo '<script defer>alert("ERR_ALERT method not developed")</script>';
		exit;
	}
}
function r(){
	/** Remediation function :-) first started in earnest 2005-01-14: this function will analyze the error and see of the problem can be addressed.  It will also eventually log the errors, whether they were fixed or not, etc.. R() is going to return true if it thinks it's solved the problem - q() will be called again - or false if it thinks the problem cannot be solved
	NOTE: make sure the interface has all the functions necessary to do updating including:
		function_rtcs_update_table_mysql_v101.php
		function_rtcs_declare_field_attributes_mysql_v200.php
		function_rtcs_update_field_mysql_v100.php
	
	the basic question what is the problem, and where is the problem occuring.  Problems fall into several types:
	1. access denied for that user
	2. query itself poorly structure
	3. field not present, table not present
	4. field(s) cannot support entry of given data
		a. violates an index
		b. violates a key
		c. field will not accept a NULL for example because it was declared NOT NULL
	
	Our first job is to determine what db and table(s) are being called. r() will use some new functions to be developed for parsing SQL queries which I don't have yet.
	**/
	global $fl,$ln,$qx,$qr,$cnxString, $remedTable;
	global $$cnxString;
	$args=func_get_args();
	$query=$qr['query'];
	preg_match('/^(INSERT INTO|REPLACE INTO|UPDATE|DELETE FROM)((.|\s)*)/i',$query,$a);
	$action=strtoupper($a[1]);
	if($action =='DELETE FROM') return false;
	prn($action);
	$a=preg_split('/\s+SET\s+/i',$a[2]);
	$tableList=explode(',',trim($a[0]));
	foreach($tableList as $v){
		$i++;
		$t=explode('.',$v);
		if(count($t)==2){
			$tables[$i]['db']=$t[0];
			$tables[$i]['table']=$t[1];
		}else{
			if(!$db){
				$rsql="SHOW TABLES";
				$result=mysql_query($rsql,$$cnxString);
				$rd=mysql_fetch_array($result,MYSQL_ASSOC);
				foreach($rd as $o=>$w){
					$db=$tables[$i]['db']=preg_replace('/^Tables_in_/','',$o);
					break;
				}
			}else{
				$tables[$i]['db']=$db;
			}
			$tables[$i]['table']=$t[0];
		}
	}
	foreach($tables as $v){
		//in this case we are indefinite, can't remediate - should notify admin
		if(!$v['db'] || !$v['table']) return false;
		extract($remedTable[$v['table']]['rootRemed']);
		if(in_array($args[3][0], $triggerErrors) /** and we need to ask if the error came from this table **/){
			switch(true){
				case $stockAction==1000:
					//synchronize the table
					#turn off useRemediation bit - allows functions(s) to work normally
					$qx['useRemediation']=false;
					$qx['remediate_arg_list']=$qx['arg_list'];
					$a=explode('.',$remediateWith);
					$template=$a[0];
					$table=$a[1];
					rtcs_update_table_mysql($template, $table, $version='', $v['db'], $v['table'], $cnx='', $targetCnx='');

					#turn on useRemediation bit
					$qx['useRemediation']=true;
					return true;
				break;
				case $stockAction==1001:
					//create the table
					
				break;
				default:
					//should notify, can't recognize stock action
					return false;
			}
		}
	}
}
function r_notify(){
	echo('a query problem was not able to be remediated');
	return false;
}
?>