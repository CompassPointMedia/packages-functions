<?php
/* -------- example ---------

	$authorizedCallbackFieldList=array(
		'Parents_ID', 'Other'
	);
	$options=array('useTryCatchErrors'=>true);
	#now call the function
	ob_start();
	callback($options);
	$out=ob_get_contents();
	ob_end_clean();
	echo '<pre>';
	echo htmlentities($out);

   -------------------------- */
$functionVersions['callback']=1.01;
function callback($options=array()){
	/*
	todo:
	1. handle arrays
	2. means to add a value to a dropdown list
	* 2008-09-18 - v1.01
		- fixed try-catch error
		- added coding to allow cbParam to be a single var vs. an array
		- if cbLocation==wpo, then wp will close UNLESS we pass cbRetainParent
	
	2008-01-20 - this is the protocol for better page interaction.  See docs at bottom AND in /home/rbase/public_html/devteam/development/callback.txt
	Options:
	--------
	useTryCatch - default is true
	useTryCatchErrors - default is false
	cbLocation - default is wpo
		if cbLocation==wpo then wp will close UNLESS we pass cbRetainParent
	
	*/
	global $authorizedCallbackFieldList, $developerEmail, $fromHdrBugs;
	extract($options);
	if(!isset($useTryCatch))$useTryCatch=true;
	if($useTryCatch && !isset($useTryCatchErrors))$useTryCatchErrors=false;
	if(!isset($cbLocation))$cbLocation='wpo';
	//-------------- version 1.0 ------------------
	echo "\n";
	?>
<!-- Callback function version 1.00, 2008-01-20, able to 1) set vars 2) set fields 3)call functions in either wp or wpo -->
<script type="text/javascript" language="javascript"><?php echo "\n";
	echo 'var wo =window.opener;'."\n";
	echo 'var wp =window.parent;'."\n";
	echo 'var wpo=window.parent.opener;'."\n";
	echo '//--------------------- begin callback function ---------------------'."\n";
	echo '/* to suppress the tryCatch construct pass array("useTryCatch"=>false) as the first parameter */'."\n";
	//bracket in try-catch statement
	if(!(isset($useTryCatch) && $useTryCatch==false))echo 'try{'."\n";
	foreach($_REQUEST as $n=>$v){
		//directives get declared in the order they appear in the query string
		if($n=='cbVar' || $n=='cbField'){
			if(is_array($v)){
				
			}else{
				//declare location.variable
				$str="\t";
				if(!stristr($v,'.'))$str.=$cbLocation.(strlen($cbLocation)? '.' : '');
				if($n=='cbVar'){
					$str.=$v.'=';
				}else{
					$str.='g(\''.$v.'\').value=';
				}
				//
				//now get the global variable
				if($_REQUEST[($n=='cbVar' ? 'cbVarTo' : 'cbValue')]){
					//normally when differs from the field name
					$o=$_REQUEST[($n=='cbVar' ? 'cbVarTo' : 'cbValue')];
				}else{
					$o=$v;
				}
				if(preg_match('/^fixed:/i',$o)){
					//fixed value, send back as is
					$o=preg_replace('/^fixed:/i','',$o);
					$str.=(is_numeric($o) ? '' : "'");
					$str.=$o;
					$str.=(is_numeric($o) ? '' : "'");
					$str.=";\n";
				}else{
					//security precaution
					if(strlen($o) && !in_array($o, $authorizedCallbackFieldList)){
						mail($developerEmail,'error in file '.__FILE__.', line '.__LINE__, get_globals(), $fromHdrBugs);
						error_alert('function callback() requested a global variable that was not on the auth. callback field list');
					}
					if(is_array($GLOBALS[$o])){
						//not developed
					}else{
						$str.=(is_numeric($GLOBALS[$o]) ? '' : "'");
						$str.=$GLOBALS[$o];
						$str.=(is_numeric($GLOBALS[$o]) ? '' : "'");
						$str.=";\n";
					}
				}
				echo $str;
			}
		}else if($n=='cbFunction'){
			$str="\t";
			if(!stristr($v,'.'))$str.=$cbLocation.(strlen($cbLocation)? '.' : '');
			$str.=$v.'(';
			//immediately get parameters for passage - they must appear in order the function requires them
			$a=$_REQUEST;
			//cbParam is either an array or a string
			foreach($a as $n=>$v){
				if($n!=='cbParam')continue;
				//we will either have cbParam=value
				if(is_array($v)){
					//OK
				}else{
					$v=array($v);
				}
				//we have all the parameters in an array
				foreach($v as $p){
					$param='';
					if(preg_match('/^fixed:/i',$p)){
						//fixed value, send back as is
						$p=preg_replace('/^fixed:/i','',$p);
						$param.=(is_numeric($p) ? '' : "'");
						$param.=$p;
						$param.=(is_numeric($p) ? '' : "'");
					}else{
						//security precaution
						if(strlen($o) && !in_array($o, $authorizedCallbackFieldList)){
							mail($developerEmail,'error in file '.__FILE__.', line '.__LINE__, get_globals(), $fromHdrBugs);
							error_alert('function callback() requested a global variable that was not on the auth. callback field list');
						}
						$param.=(is_numeric($GLOBALS[$p]) ? '' : "'");
						$param.=$GLOBALS[$p];
						$param.=(is_numeric($GLOBALS[$p]) ? '' : "'");
					}
					$parameters[]=$param;
				}
				$str.=implode(',',$parameters);
				//done
				break;
			}
			$str.=');'."\n";
			echo $str;
			if($cbLocation=='wpo' && !$cbRetainParent)echo "\t".'window.parent.close();'."\n";
		}else if($n=='cbSelect'){
			//universal function to add to a dropdown list
			global $cbTable, $cbValue, $cbLabel, $ID;
			if($thisID=$cbValue){
				//OK
			}else if($thisID=$ID){
				//OK
			}else if($thisID=$GLOBALS[$GLOBALS['cbSelect']]){
				//OK
			}
			if($cbLabel && $thisID){
				?>
				window.parent.newOptionSet('<?php echo $thisID?>', '<?php echo addslashes($cbLabel);?>', '<?php echo $v;?>');
				window.parent.close();
				<?php
			}else{
				//nothing to show in the list
				mail($developerEmail,'error in file '.__FILE__.', line '.__LINE__, get_globals(), $fromHdrBugs);
			}
		}
	}
	if(!(isset($useTryCatch) && $useTryCatch==false))echo '}'."\n".'catch(e){ '."\n";
	if(!(isset($useTryCatch) && $useTryCatch==false) && $useTryCatchErrors){
		?>	var descr=false;
	var str='';
	for(j in e){
		if(j=='stack' || j=='number')continue;
		if((j=='message' || j=='description') && descr)continue
		if(j=='description' || j=='message')descr=true;
		str+=(j+': '+e[j])+"\n";
	}		
	if(str) alert(str);
<?php
	}
	if(!(isset($useTryCatch) && $useTryCatch==false))echo '}'."\n";
	echo '//--------------------- end callback function ---------------------'."\n";
	
	?></script><?php echo "\n";
	
}
/*

callback is used for several functions:
setting a var value in the calling page
inserting a value in a field in a calling page
adding dropdown list option(s), viz. "<Add new..>" relationally
calling a function which will then perform other actions
saving a "file" (i.e. a resource with a resource token) as a certain name
opening a file or resource

setting a var value
-------------------
the exe page will set a var value in the parent page (wpo).  There is a huge security issue here however as php is telling js in the wpo a value which we may not want the user to know, and the user could control the query string easily.

cbVar=status[&cbVarTo=status] - sets window.parent.opener.status=global variable $status - 2nd parameter is optional if names are the same
cbVar=selectChild&cbVarTo=Children_ID - sets window.parent.opener.status to global variable $Children_ID
cbVar=status&cbVarTo=fixed:OK - sets var status='OK'
cbVar[status]=Parents_ID - another way of expressing
cbVar[]=firstVar&cbVar[]=secondVar&cbVarTo[]=field1&cbVarTo[]=field2


inserting a value in a field in a calling page
----------------------------------------------
Pass field and value like this
cbField=ConfirmationNumber[&cbValue=ConfirmationNumber] - in this case if the global variable is the same name it is not necessary to pass cbValue
-or-
cbField=fieldName&cbValue=Parents_ID
-or-
cbField[]=field1&cbField[]=field2
-or finally-
cbField[fieldName]=Parents_ID[&cbField[fieldName2]=Children_ID]

This gets parsed into the executing page and passed to the exe file

adding dropdown list options
----------------------------
This is a very standard action.  we need to pass the name of the list, and the callback should add an option with the value and label and by default select this option. an extension for this would be to update multiple dropdown lists (for a VH1 form showing multiple records, for example).
cbSelect=Types

calling a function
------------------
in this case we need to specify what function, and what parameters to pass to the function, as follows:

this will pass one parameter
cbFunction=updateList&cbParam=Parents_ID
e.g. window.parent.opener.updateList(7); //when $Parents_ID=7

this will pass multiple parameters
cbFunction=updateList&cbParam[]=Parents_ID&cbParam[]=Children_ID
e.g. window.parent.opener.updateList(7,25);

cbParam to pass fixed value
cbFunction=updateList&cbParam[]=fixed:3&cbParam[]=Fosterhomes_ID
e.g. window.parent.opener.updateList(3, 28); //when $Fosterhomes_ID=28

Advanced methods for cbParam
----------------------------
METHOD 1: when a global variable is an array such as $childList, the callback function will declare the function in javascript in the exe page, and then pass the array to the wpo function, for example:
<?php
$childList=array(1=>71,2=>13,3=>25);
?>
cbFunction=updateList&cbParam=childList
e.g.:
var childList=new Array();
childList[1]=71;
childList[2]=13;
childList[3]=25;
updateList(childList);

METHOD 2: you may also get something in a php array as follows:
cbFunction=updateList&cbParam=userType[myPermission]
-this will look for <?php $userType[$myPermissions] ?>

this will treat as a string
cbFunction=updateList&cbParam=userType['myPermissions']


Callback parameters can also control how the executing interface behaves, examples are:

cbMultiple=1 //used in file_explorer - multiple files CAN be selected
cbMode=select|saveas //"could" be used in file explorer but not developed



From File Explorer
------------------
var cb=window.open(url with following params)

cb='+cbMode+
cbTarget='+cbTarget+
cbFunction='+cbFunction+
cbTargetNode='+cbTargetNode+
cbMultiple='+(cbMultiple?1:0)+


*/
?>