<?php

/*
2012-05-06:
I want to pass a NAME of the query now with as little overhead as possible
also want to pass the line the query was on as for example __LINE__/10000 - any decimal=a line number
but the key thing I want to do is somehow register a query for a wrapper function, specifically a translation function, e.g.
    translations=array(
        query1=>array(
            col1=>firstname(this=1&that=2&protocol=as_with_dataset_objects)
        )
    )
    then when q() recognizes this is query1, it effects the translations specified if present

*/

//current definitions for this version
define('C_DEFAULT',1);
define('C_MASTER',2);
define('C_SUPER',3);
define('C_DEFAULT_GENERIC',4); #ADDED 2010-01-06

//die methods 20-39
define('ERR_DIE',20);
define('ERR_SILENT',21);
define('ERR_ALERT',22);
define('ERR_ECHO',23);

//these are used by the system and are not passed to the function
define('E_NO_SQL_QUERY',40);
define('E_NO_DB_CNX_VARS',41);
define('E_NO_RB_CNX_VARS',42);
define('E_BAD_DB_CNX',43);
define('E_BAD_RB_CNX',44);
define('E_QUERY_FAILED',45);
define('E_NO_SUP_CNX_VARS',46);

//output types 100+
define('O_VALUE',100);
define('O_ROW',101);
define('O_EXTRACT_ROW',102);
define('O_COUNT',103);		//var	returns count of select query
define('O_INSERTID',104);
define('O_AFFECTEDROWS',110);
define('O_ARRAY',105);
define('O_ARRAY_ASSOC',106);
define('O_COL',107);
define('O_COL_ASSOC',108);
define('O_NOTHING',109);		//void	returns nothing
define('O_ARRAY_APPEND',111);		//added 2010-07-28
define('O_ARRAY_ASSOC_MULTI',115);
define('O_ARRAY_ASSOC_2D',116);


define('O_TEST',900);
define('O_TEST_CNX',901);
define('O_DO_NOT_REMEDIATE',902);

//System Definitions; these can be overridden in individual files as needed

//what version of q is this
$qx['version']='1.20';
//default name of global sql variable, default is $sql #not used yet in 1.10
$qx['defSQLVar']='sql';
/*** FOLLOWING TWO VARS APPLY ONLY WHEN CNX CONSTANT IS NOT EXPLICITLY PASSED ***/

//1. insist that default connection method be declared? This is a safer method, and can be overridden in an individual page.  Means you must declare on each page
$qx['defCnxMethodReq']=true;
//2. default connection method.  Setting this to blank will force each page to declare it.  If you want to declare the default connection method here, then the value of the var above doesn't matter
if(!isset($qx['defCnxMethod']))$qx['defCnxMethod']='';

//use error remediation function
if(!isset($qx['useRemediation']))$qx['useRemediation']=false;
//initially set this to zero, it will be incremented when we attempt to repair a problem, then set zero when problem is repaired (whether successfully or not)
$qx['remediationStep']=0;
//remediation function name; if _q[useRemediation] is true, this function will be checked for existence
$qx['remediationFctn']='r';
//number of times we are allowed to attempt to repair the problem; in my r() function, we will eventually allow for  repair of several problems
$qx['remediationIts']=10;


//will default to ERR_DIE, which echoes the file and line number and errornumber and error text
$qx['defaultQDieMethod']=ERR_DIE;

if(!isset($qx['slowQueryThreshold']))$qx['slowQueryThreshold']=0; //0 means no threshold
if(!@$qx['slowQueryFunction'])$qx['slowQueryFunction']='q_notify';

$functionVersions['q']=1.20;
function q(){
    /**
     * version 1.30 --
    2016-12-12 -- changed from mysql to mysqli with some cleanup of undefined

     * version 1.20 --
    2012-05-09: implemented passage of a decimal __LINE__/1000 - this will be recognized as the line the query is on as opposed to the old $ln method
    2009-09-03 -- started using and developing remediation
    version 1.11 -- 2009-05-31 -- fixed stupid error where mysqli_select_db was not connecting to explicitDB when present
    version 1.10 -- 2006-01-14 -- started remediation in earnest - simple problems like missing fields can be looked up.  Idea is that I can figure out where the table came from by a libary of defs and do the repair.
    version 1.06 -- 2005-10-12 -- reviewed and verified passage of a cnx via an array
    also unset qr.output and qr.cols so that 
    version 1.05 -- 2004-12-19 -- Remediation added and constant names have been shortened.
    version 1.04 -- 2004-12-06 -- O_EXTRACT_SINGLE now returns true if recordset, false if not (and does not extract).  First used on WIDI console, more to follow on this version number.
    version 1.03 -- 2004-11-27 -- added O_RETURN_ASSOC, so if I say SELECT Code, Label FROM values, I'll get back an array of $a[Code1]=Label1, $a[Code2]=Label2, etc.; Also fixed error: if the cnx was passed as an array without a db, the err message wasn't specific
     **/
    global $qx,$qr,$fl,$ln,$cnxString,$qQueryCount,$qtest,$developerEmail,$fromHdrBugs;
    //qr is specific to each query; it's cleared out each time
    unset($qr);
    global $qr;

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
    $arg_list=func_get_args();
    if($qx['useRemediation'] && $qx['remediationStep'] && !in_array(O_DO_NOT_REMEDIATE,$arg_list)){
        //this is a requery of the original after one or more remediation attempts has been made
        mail($developerEmail, 'Error, calling q in remediation mode, '.__FILE__.', line '.__LINE__,get_globals(),$fromHdrBugs);
        echo '<strong>Calling q() in remediation mode line '.__LINE__.', here are the args:</strong><br />';
        prn($arg_list);
    }else{
        //OK
    }

    $knownConst=array(1,2,3,4,20,21,22,23,40,41,42,43,44,45,100,101,103,104,105,106,107,108,109,110,111,115,116,900,901,902); //this prevents passing constants from an older version to the function
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
                        $arg_list[$i]==902 ? $qDoNotRemediate=true : '';
                        break(2);
                    case ($arg_list[$i]>99): $out=$arg_list[$i];
                        break(2);
                    case ($arg_list[$i]>19 && $arg_list[$i]<40): $errDieMethod=$arg_list[$i];
                        break(2);
                    case ($arg_list[$i]>0):	$cnx=$arg_list[$i];
                        break(2);
                }
            case is_float($arg_list[$i]) && $arg_list[$i]<1:
                $_ln_=$arg_list[$i];
                unset($arg_list[$i]);
                break;
            default:
                //strings are queries
                //for pre 1.02 version constructs, ignore blank values
                $x=$arg_list[$i];
                if(!trim($x))continue;
                //presumes db names follow this regex, might pull this out for version 1.2
                preg_match('/^[_a-z]+[a-z0-9_]*$/',$x)?$explicitDB=$x:$sql=$x;
        }
    }

    $qQueryCount++;
    $qr['idx']=$qQueryCount;
    $qr['file']=(!empty($fl) ? $fl : 'UNKNOWN');
    $qr['line']=(!empty($_ln_) ? $_ln_*10000 : (!empty($ln) ? $ln : 'UNKNOWN'));

    if(!empty($qTesting)) prn($arg_list);
    //get the query and default values
    $cc = !empty($_SESSION['currentConnection']) ? $_SESSION['currentConnection'] : '';
    $cu = !empty($_SESSION['cnx'][$cc]['userName']) ? $_SESSION['cnx'][$cc]['userName'] : '';
    $queryPassType = (func_num_args()>0?'passed':'globally available');
    if(empty($errDieMethod)){
        if($errDieMethod=$qx['defaultQDieMethod']){
        }else $errDieMethod=ERR_DIE;
    }
    if(!$sql)global $sql;
    unset($qr['err']);
    if(!trim($sql)){
        //this will not be remediated
        if($qTesting)prn('error line '.__LINE__);
        return sub_e($errDieMethod, E_NO_SQL_QUERY, $arg_list, '', $qDoNotRemediate, $queryPassType);
    }
    $sql=trim($sql);
    if(!empty($qTesting)) prn($sql);
    //evaluate the sql query for type, not developed
    if(empty($cnx)){
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
    if(!empty($qTestingCnx)){
        echo 'cnx: ';
        prn($cnx);
    }
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
            empty($db) ? $db = $SUPER_MASTER_DATABASE:'';
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
            empty($db) ? $db = $MASTER_DATABASE : '';
            if(!empty($qTesting)){
                prn("$host:$user:$pass:$db");
            }
            $cnxString='rb_cnx';
            $problem=E_NO_RB_CNX_VARS;
            $problem2=E_BAD_DB_CNX;
            break;
        case $cnx==C_DEFAULT_GENERIC:
        case $cnx==C_DEFAULT:
            //default RelateBase connection
            global $db_cnx;
            $host=$_SESSION['cnx'][$cc]['hostName'];
            $user=$_SESSION['cnx'][$cc]['userName'];
            $pass=$_SESSION['cnx'][$cc]['password'];
            if($cnx==C_DEFAULT_GENERIC)$pass=generic5t($pass,'decode');
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
    if(!empty($qTestingCnx)) echo '<br />connection: ('.$cnxString.') '.($$cnxString ? $$cnxString : 'not established');
    if(!$$cnxString){
        if(!$host || !$user){
            //no vars passed to connect
            if($qTesting)prn('error line '.__LINE__);
            return sub_e($errDieMethod, $problem, $arg_list, '', $qDoNotRemediate, $queryPassType);
        }
        if(!empty($qTestingCnx)) echo "<br />connecting with $host, $user, ***";
        ob_start();
        $$cnxString=mysqli_connect($host, $user, $pass);
        $x=ob_get_contents();
        ob_end_clean();
        if(!empty($qTestingCnx))echo '<br />mysqli_connect result: '. $$cnxString;
        if(!$$cnxString || strlen($x)){
            if($qTesting)prn('error line '.__LINE__.': ('.$x.')');
            return sub_e($errDieMethod, E_BAD_DB_CNX, $arg_list, $x, $qDoNotRemediate, $queryPassType);
        }
        if($db){
            $x=mysqli_select_db($$cnxString, $db);
            if(!empty($qTestingCnx)) echo '<br />db='.$db.', returned '.$x;
            if(!$x){
                if($qTesting)prn('error line '.__LINE__);
                return sub_e($errDieMethod, $problem2, $arg_list, array(mysqli_errno($$cnxString),mysqli_error($$cnxString)), $qDoNotRemediate, $queryPassType);
            }
        }
    }
    //explicit connect to passed database parameter
    if(!empty($explicitDB)){
        if($qTesting)prn('(explicit db='.$db.', line '.__LINE__.')');
        $x=mysqli_select_db($$cnxString, $explicitDB);
        if(!$x){
            if($qTesting)prn('error line '.__LINE__);
            return sub_e($errDieMethod, $problem2, $arg_list, array(mysqli_errno($$cnxString),mysqli_error($$cnxString)), $qDoNotRemediate, $queryPassType);
        }
    }
    //run query, including timing
    $qr['query']=$sql;
    list($usec0, $sec0) = explode(' ',microtime());
    $result=mysqli_query($$cnxString, $sql);
    list($usec1, $sec1) = explode(' ',microtime());
    $qr['result']=$result;
    $qr['time']=round($sec1+$usec1-$sec0-$usec0,6);

    //added 2012-05-06
    if($qx['slowQueryThreshold'] && $qr['time']>$qx['slowQueryThreshold']){
        $f=$qx['slowQueryFunction'];
        $f($arg_list);
    }
    if(!empty($qTesting)) prn($result);
    if(mysqli_error($$cnxString)){
        if($qTesting)prn(mysqli_errno($$cnxString) . ' : '.mysqli_error($$cnxString));
        //here is the actual failed query section
        if($qTesting)prn('error line '.__LINE__);
        return sub_e($errDieMethod, E_QUERY_FAILED, $arg_list, array(mysqli_errno($$cnxString),mysqli_error($$cnxString)), $qDoNotRemediate, $queryPassType);
    }
    //will be re-declared later for some queries
    unset($qr['output'],$qr['cols'],$qr['warning']);
    //get query stats
    if(preg_match('/^(INSERT INTO)/i',$sql)){
        $qr['insert_id']=mysqli_insert_id($$cnxString);
    }else unset($qr['insert_id']);
    if(preg_match('/^(INSERT INTO)|(DELETE\b)|(UPDATE)|(REPLACE INTO)|(TRUNCATE)/i',$sql)){
        $qr['affected_rows']=mysqli_affected_rows($$cnxString);
    }else unset($qr['affected_rows']);
    if(preg_match('/^SELECT/i',$sql)){
        $qr['count']=mysqli_num_rows($result);
    }else unset($qr['count']);
    //handle output parameters
    if($out==O_INSERTID){
        return $qr['insert_id'];
    }
    //the following operations only apply to a SELECT query
    unset($r);
    switch($switch = true){
        case $out == O_AFFECTEDROWS:
            if(!preg_match('/^(INSERT|UPDATE|DELETE|REPLACE)/i',$sql))$qr['warning']='Query inconsistent with output constant';
            $r= $qr['affected_rows'];
            break;
        case preg_match('/DESCRIBE/i',$sql):
            $r= $result;
            break;
        case $out == O_NOTHING:
            break;
            break;
        case $out == O_COUNT:
            $r= $qr['count'];
            break;
        case $out == O_VALUE:
            if(!($rd=mysqli_fetch_array($result,MYSQLI_NUM)))return false;
            $qr['output']=$rd;
            $r= $rd[0];
            break;
        case $out == O_ROW:
            $rd=mysqli_fetch_array($result,MYSQLI_ASSOC);
            $qr['output']=$rd;
            $r= $rd;
            break;
        case $out == O_EXTRACT_ROW:
            //this overwrites any existing global variables with same name
            if(!($rd=mysqli_fetch_array($result,MYSQLI_ASSOC)))return false;
            $qr['output']=$rd;
            ob_start();
            foreach($rd as $n=>$v){
                eval('global $'.$n.';');
                $$n=$v;
            }
            ob_end_clean();
            mysqli_free_result($result);
            $r= true;
            break;
        case $out == O_ARRAY:
        case $out == O_ARRAY_ASSOC:
        case $out == O_ARRAY_APPEND:
            if($out==O_ARRAY_APPEND){
                eval('global $'.$qx['merge_array'].'; $x=count($'.$qx['merge_array'].';');
            }else{
                $x=0;
            }
            while($rd=mysqli_fetch_array($result,MYSQLI_ASSOC)){
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
            if($out!==O_ARRAY_APPEND)$r= $a;
            break;
        case $out == O_ARRAY_ASSOC_MULTI:
        case $out == O_ARRAY_ASSOC_2D:
            $x=0;
            while($rd=mysqli_fetch_array($result,MYSQLI_ASSOC)){
                $x++;
                if($x==1){
                    $y=0;
                    foreach($rd as $n=>$v){
                        $y++;
                        if($y==1)$firstCol=$n;
                        if($y==2)$secondCol=$n;
                        $qr['cols'][$y]=$n;
                    }
                }
                $a[$rd[$firstCol]][($out==O_ARRAY_ASSOC_MULTI ? count($a[$rd[$firstCol]])+1 : $rd[$secondCol])]=$rd;
            }
            $r= $a;
            break;
        case $out == O_COL:
        case $out == O_COL_ASSOC:
            $x=0;
            $idx=($out==O_COL_ASSOC?1:0);
            while($rd=mysqli_fetch_array($result,MYSQLI_NUM)){
                $x++;
                $out==O_COL_ASSOC?$x=$rd[0]:'';
                $a[$x]=$rd[$idx];
            }
            $r= $a;
            break;
        default:
            //this is redundant but OK for now
            $r= $result;
    }
    if(isset($r))return $r;
}

function sub_e($errDieMethod, $type, $arg_list, $system_err, $qDoNotRemediate, $queryPassType){
    /**
    Error handling sub-routine
     **/
    global $fl,$ln,$qx,$qr,$cnxString;
    global $$cnxString;

    if($qx['useRemediation']) $qx['remediationStep']++;
    /*
    2009-06-16
    ----------
    NOTE: if error is E_NO_SQL_QUERY, we do not use remediation.  Also many functions such as get_table_indexes() are presumed sound and will be needed DURING remediation, so we will pass O_DO_NOT_REMEDIATE in the queries in these functions.
    
    
    */
    if($qx['useRemediation'] && !$qDoNotRemediate && $qx['remediationStep']>0 && $type!==E_NO_SQL_QUERY){
        if($qx['remediationStep']>$qx['remediationIts']){
            $qx['remediationStep']=0;
            //problem was not remediated, unset, send me an email about the problem
            echo 'problem was not remediated<br />';
            r_notify($qr,$qx);
            //this will now continue through to the default error method
        }else{
            //run remediation
            if(r($errDieMethod, $type, $arg_list, $system_err, $qDoNotRemediate, $queryPassType)){
                echo 'success calling q in remediation mode<br />';
                echo 'useremed = '.$qx['useRemediation'] . '<br />';
                echo 'remediation step = '.$qx['remediationStep'] . '<br />';
                $str='q(';
                foreach($arg_list as $n=>$v){
                    if(is_array($v)){
                        $str.='$arg_list['.$n.'],';
                    }else{
                        $str.=(is_int($v) ? $v : "'".str_replace("'","\'",$v)."'").',';
                    }
                }
                $str=rtrim($str,',').');';
                echo 'calling query again:<br />';
                prn($str);

                eval($str);
                return;
            }else{
                $qx['remediationStep']=0;
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
    $qr['err']='SQL Error<br />';
    if($fl)$qr['err'].='In file: '.$fl.'<br />';
    if($ln)$qr['err'].='On line: '.$ln.'<br />';
    $qr['err'].=':<br />';
    $qr['err'].=$msg;
    $qr['system_err']=$system_err[1];
    $qr['system_errno']=$system_err[0];
    //get query
    foreach($arg_list as $v){
        if(strstr($v,' '))$query=$v;
    }
    if($errDieMethod==ERR_DIE){
        die(
            '<div class="sqlException" style="background-color:ALICEBLUE;border:1px dashed #CCC;padding 5 10;">'.
            $qr['err']. '<br />'.
            '(Query: '.($query ? $query : '[-by qr array]'.$qr['query']).')<br />'.
            '</div>'
        );
    }else if($errDieMethod==ERR_ECHO){
        echo $qr['err']=
            '<div class="sqlException" style="background-color:ALICEBLUE;border:1px dashed #CCC;padding 5 10;">'.
            $qr['err']. '<br />'.
            '(Query:<br />'.($query ? $query : '[+by qr array]'.$qr['query']).')<br />'.
            '</div>';
    }else if($errDieMethod==ERR_SILENT){
        return false;
    }else if($errDieMethod==ERR_ALERT){
        error_alert("ERR_ALERT method not developed");
    }
}
function r($errDieMethod, $type, $arg_list, $system_err, $qDoNotRemediate, $queryPassType){
    global $tiredOfThis;
    $tiredOfThis++;
    $temp=func_get_args();
    prn('--- calling r(), args ---');
    prn($temp);

    if($tiredOfThis>30)exit('game over');
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
    global $fl,$ln,$qx,$qr,$remedTable,$FUNCTION_ROOT, $MASTER_DATABASE, $dbTypeArray, $developerEmail, $fromHdrBugs, $rCalled;
    $query=$qr['query'];

    //handle connection and database
    foreach($arg_list as $w) if(($w>0 && $w <20) || is_array($w))$cnx=$w;
    if(!$cnx)$cnx=$qx['defCnxMethod'];

    if($oldCoding){
        error_alert(x);
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
                    $result=mysqli_query($$cnxString, $rsql);
                    $rd=mysqli_fetch_array($result,MYSQLI_ASSOC);
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

    if(!$rCalled && false){
        $rCalled=true;
        ?><div style="border:1px solid #000;padding:20px; background-color:#FFAAAA;">
            <div class="fr" style="padding:0px 15px 15px 0px;"><img src="/images/i/alert01.gif" alt="Notice" /></div>
            <h2 style="color:darkred;">What is this code below here?</h2>
            <p>
                if you notice unusual coding located below this message, it means that the system tried to repair a missing field or table, or other database problem.  If you refresh the page and this message goes away, more than likely the problem was able to be resolved.  If you refresh this page and get the same message, please contact RelateBase or Compass Point Media staff for assistance.
            </p>
            <div style="clear:both;">&nbsp;</div>
        </div><?php
    }
    if($system_err[0]==1146){
        //----- table doesn't exist --------
        $str=$system_err[1];
        if(!preg_match("#table '([^.]+)\.([^.]+)' doesn\'t exist#i",$str,$dbTable)){
            mail($developerEmail, 'error file '.__FILE__.', line '.__LINE__,get_globals(),$fromHdrBugs);
            return false;
        }
        $createtable=q("SHOW CREATE TABLE relatebase_template.".$dbTable[2], O_ROW, C_SUPER, O_DO_NOT_REMEDIATE);
        if($createtable['Create View']){
            $creating='Create View';
            if(!preg_match('/VIEW `([-_a-z0-9]+)`\.`([-_a-z0-9]+)` AS/i',$createtable['Create View'],$a)){
                mail($developerEmail, 'error file '.__FILE__.', line '.__LINE__,get_globals(),$fromHdrBugs);
                return false;
            }
            $createtable=str_replace($a[0],'VIEW `'.$MASTER_DATABASE.'`.`'.$a[2].'` AS',$createtable['Create View']);
            $createtable=str_replace('`relatebase_template`.','`'.$MASTER_DATABASE.'`.',$createtable);
            $createtable=preg_replace('/DEFINER=`[-_a-z0-9]+`@`[-_a-z0-9]+`/i','DEFINER=`'.$MASTER_DATABASE.'`@`localhost`',$createtable);
        }else{
            $creating='Create Table';
            $createtable=str_replace("CREATE TABLE `", "CREATE TABLE $MASTER_DATABASE.`",$createtable['Create Table']);
        }
        ob_start();
        q($createtable, O_DO_NOT_REMEDIATE, /*ERR_ECHO, */C_SUPER);
        $err=ob_get_contents();
        ob_end_clean();
        if($err){
            mail($developerEmail, 'error file '.__FILE__.', line '.__LINE__,get_globals(),$fromHdrBugs);
            return false;
        }
        return true;
    }else if($system_err[0]==1054){
        //----- unknown column - we handle the field list currently, not the where clause etc. (2009-06-16) ------
        $str=$system_err[1];
        if(!preg_match("#unknown column '([^']+)' in '(field list|where clause|on clause|order clause)'#i",$str,$unknownColumn)){
            mail($developerEmail, 'error file '.__FILE__.', line '.__LINE__,get_globals(),$fromHdrBugs);
            return false;
        }
        $unknownColumn=$unknownColumn[1];
        $a=explode('.',$unknownColumn);
        $unknownColumn=end($a);
        #prn("looking for $unknownColumn");
        if(count($a)>1)$tableAlias=$a[count($a)-2];
        //get tables
        if($qx['tableList']){
            //this is a very clumsy method and hand-coded in light of function sql_query_parser()
            foreach($qx['tableList'] as $table=>$db){

                $db='relatebase_template';
                $fieldList=mysql_declare_table_rtcs($db,$table,false,$options=array('cnx'=>C_SUPER));
                $targetFieldList=mysql_declare_table_rtcs('',$table,false,$options=array('cnx'=>$cnx));


                prn("------------- table $table ---------");
                //get field list - NOTE this will add the field from the first table present
                if(is_int($db))$db='relatebase_template';
                $fieldList=mysql_declare_table_rtcs($db,$table,false,$options=array('cnx'=>C_SUPER));
                $targetFieldList=mysql_declare_table_rtcs('',$table,false,$options=array('cnx'=>$cnx));
                #foreach($fieldList['fields'] as $field=>$v)echo $field . ', ';
                prn(" ----- end table stats -------");

                foreach($fieldList['fields'] as $field=>$v){
                    //prn($field . ':' . $unknownColumn);
                    if($field==strtolower($unknownColumn)){
                        //insert the field - the original 
                        //get a function
                        if(!function_exists('rtcs_declare_field_attributes_mysql'))
                            require($FUNCTION_ROOT.'/function_rtcs_declare_field_attributes_mysql_v200.php');
                        $str="ALTER TABLE $MASTER_DATABASE.$table ADD ";
                        $str.=rtcs_declare_field_attributes_mysql($v);
                        $str.=($buffer && $targetFieldList['fields'][strtolower($buffer)] ? ' AFTER '.$buffer : '');
                        prn($str);
                        q($str, $cnx, O_DO_NOT_REMEDIATE);
                        return true;
                    }
                    $buffer=$v['DNAME'];
                }
            }
            return false;
        }else{
            if(!function_exists('sql_query_parser'))require($FUNCTION_ROOT.'/function_sql_query_parser_v100.php');
            if($a=sql_query_parser($query)){
                $db=($qx['rTemplateDatabase'] ? $qx['rTemplateDatabase'] : 'relatebase_template');
                foreach(q("SHOW TABLES IN $db", O_ARRAY, O_DO_NOT_REMEDIATE, C_SUPER) as $v)$tables[]=$v['Tables_in_'.$db];
                $possibleTables=preg_split('/\s+/',trim($a['from']));
                foreach($possibleTables as $table){
                    if(!preg_match('/\b('.implode('|',$tables).')\b/',$table))continue;
                    $fieldList=mysql_declare_table_rtcs($db,$table,false,$options=array('cnx'=>C_SUPER));
                    $targetFieldList=mysql_declare_table_rtcs('',$table,false,$options=array('cnx'=>$cnx));
                    foreach($fieldList['fields'] as $field=>$v){
                        if($field==strtolower($unknownColumn)){
                            //insert the field - the original 
                            require_once($FUNCTION_ROOT.'/function_rtcs_declare_field_attributes_mysql_v200.php');
                            $str="ALTER TABLE $MASTER_DATABASE.$table ADD ";
                            $str.=rtcs_declare_field_attributes_mysql($v);
                            $str.=($buffer && $targetFieldList['fields'][strtolower($buffer)] ? ' AFTER '.$buffer : '');
                            prn('--- remediation query: ---');
                            prn($str);
                            prn("\n");
                            q($str, $cnx, O_DO_NOT_REMEDIATE, ERR_ECHO);
                            prn('--- after the rem. query ---');
                        }
                        $buffer=$v['DNAME'];
                    }
                }
            }else{
                mail($developerEmail, 'error file '.__FILE__.', line '.__LINE__,get_globals('sql_query_parser() failure'),$fromHdrBugs);
            }
        }
    }else if($system_err[0]==1048){
        //----- unknown column - we handle the field list currently, not the where clause etc. (2009-06-16) ------
        $str=$system_err[1];
        if(!preg_match("#Column '([^']+)' cannot be null#i",$str,$nonNullColumn)){
            mail($developerEmail, 'r() failure '.__FILE__.', line '.__LINE__,get_globals(),$fromHdrBugs);
            return false;
        }
        $nonNullColumn=$nonNullColumn[1];
        if(!preg_match('#^\s*(INSERT INTO|REPLACE INTO|UPDATE)\s+([^ ]+)#i',$arg_list[0],$table)){
            mail($developerEmail, 'r() failure '.__FILE__.', line '.__LINE__,get_globals(),$fromHdrBugs);
            return false;
        }
        $table=explode('.',$table[2]);
        if(count($table)>1){
            $db=str_replace('`','',$table[count($table)-2]);
        }else{
            mail($developerEmail, 'unable to get db for error 1048, '.__FILE__.', line '.__LINE__,get_globals(),$fromHdrBugs);
            return false;
        }
        $table=end($table);
        $table=str_replace('`','',$table);
        $fieldList=mysql_declare_table_rtcs($db,$table,false,$options=array('cnx'=>C_SUPER));
        $a=$fieldList[strtolower($nonNullColumn)];
        mail($developerEmail, 'alter table change non null column not finished, '.__FILE__.', line '.__LINE__,get_globals(),$fromHdrBugs);
        error_alert('alter table change non null column not finished');
    }
}
function q_notify($a){
    global $qr, $qx, $developerEmail, $fromHdrBugs;
    ob_start();
    print_r($a);
    echo "\n";
    print_r($qr);
    echo "\n\n";
    print_r($GLOBALS);
    $err=ob_get_contents();
    ob_end_clean();
    mail($developerEmail, 'Slow query in '.$MASTER_USERNAME.':'.end(explode('/',__FILE__)).', line '.__LINE__,$err,$fromHdrBugs);
}
function r_notify(){
    $msg=('a query problem was not able to be remediated');
    mail($developerEmail, 'error file '.__FILE__.', line '.__LINE__,get_globals($msg),$fromHdrBugs);
}
?>