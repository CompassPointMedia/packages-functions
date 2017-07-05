<?php
//if the form has been submitted we work the changes
if($HTTP_POST_VARS){
	foreach($commentArray as $n => $v){
		//build the comments string
		foreach($v as $o => $w){
			if($o == 'initialString'){$initialString = $w;}
			if($o == 'GENERAL_FUNCTION_NAME'){$functionName = $w;}
			if($o !== 'initialString' and $o !== 'initialComments'){
				$newComments .= $o . ":" . str_replace(';',';;',str_replace(':','::',stripslashes($w))) . ';' . "\n";
			}
		}
		//finish building comments string
		$newComments = "\n" . '/' . '*' . "\n" . $newComments .  '*' . '/';
		$newString = $initialString . "\n" . $newComments;
		
		//put the comment in
			#get the current file into a string
				$workingFileString = implode('',file($fileToModify));
			#replace the string
				$workingFileString = preg_replace("/function " . $functionName . "\([^)]*\){\s*\/\*(.|\s)*?\*\//i",$newString,$workingFileString);
			
			#rewrite the file
		     echo $fp = fopen($fileToModify,'w'); 
			 echo 'here';
			 fwrite($fp, $workingFileString); 
			 fclose($fp);
			
		
		
		//clear things out
			unset($newComments);
			unset($initialString);
			unset($newString);
			unset($workingFileString);
			unset($functionName);
				
	}		
}

//here's the default function if we don't have one:
if(!$ref){$ref='function_array_create_table_v100.php';}

//first we get the contents of the directory, we'll need them no matter what
if ($dir = @opendir('.')) {
	while (($file = readdir($dir)) !== false ) {
		//we include only the files starting with function_ and ending with .php
		$showFile = false;
		$group = explode('.',$file);
		if($group[sizeof($group)-1]=='php' and substr($group[0],0,8)=='function'){
			$functionLibrary=true;}else{$functionLibrary=false;}
		unset($group);	
		unset($fileExt);
		if($functionLibrary){
			$functionArray[$i]=$file;
			$i++;
		}
	}  
	closedir($dir);
}
//sort the array alphabetically
	asort($functionArray);
	reset($functionArray);
	$q=1;
	foreach($functionArray as $n=>$v){
		$sortedArray[$q]=$v;
		$q++;
	}

//build the string for the dropdown
$functionArray=$sortedArray; unset($sortedArray);
foreach($functionArray as $n => $v){
	if($ref==$v){
		$selected = 'selected';	
	}else{
		$selected = '';	
	}
	$goList .= '<option value="' . $v . '" ' . $selected . '>' . strtoupper(str_replace('_',' ',str_replace('.php','',preg_replace('/^function_/','',$v)))) . "</option>\n";
}


//here's the next ref for the form action
$getKey = array_flip($functionArray);
if(!$nextRef=$functionArray[$getKey[$ref]+1]){$nextRef='function_array_create_table_v100.php';};


?>
<html>
<head>
<title>Edit Functions</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<SCRIPT LANGUAGE="JavaScript"> <!-- Begin
closetime = 0; // Close window after __ number of seconds? // 0 = do not close, anything else = number of seconds

function Start(URL, WIDTH, HEIGHT) {
windowprops = "left=50,top=50,width=" + WIDTH + ",height=" + HEIGHT + ",scrollbars=yes,resizable=yes";
preview = window.open(URL, "preview", windowprops);
if (closetime) setTimeout("preview.close();", closetime*1000);
}

function doPopup() {
url = "viewcode.php?ref=<?php echo $ref;?>";
width = 650; // width of window in pixels
height = 650; // height of window in pixels
delay = 1; // time in seconds before popup opens
timer = setTimeout("Start(url, width, height)", delay*1000);
}
// End -->
</script>
<script>
function variable_submit(theValue){
	if (theValue=="Submit and Review"){
		   document.form1.action="function_editor.php?ref=<?php echo $ref;?>"
		   document.form1.submit();
	}else{
		   document.form1.action="function_editor.php?ref=<?php echo $nextRef;?>"
		   document.form1.submit();
	}
}
</script>
</head>
<body bgcolor="#FFFFFF" text="#000000" OnLoad="doPopup();">
<font face="Verdana, Arial, Helvetica, sans-serif" size="-1"> </font> 
<form name="form1" method="post" action="<?php echo $_SERVER['PHP_SELF'] . "?ref=$nextRef";?>">
  <h4><font face="Verdana, Arial, Helvetica, sans-serif" color="#009999">Current 
    Function: 
    <?php echo strtoupper(str_replace('_',' ',str_replace('.php','',preg_replace('/^function_/','',$ref))));?>
    </font><font face="Verdana, Arial, Helvetica, sans-serif"> </font></h4>
  <p> <font face="Verdana, Arial, Helvetica, sans-serif" size="-1">Change to other 
    function: 
    <select name="golist" onChange='window.location="function_editor.php?ref="+this.value;'>
      <?php echo $goList;?>
    </select>
    <br>
    <input type="submit" name="Submitit" value="Submit Changes" onClick="variable_submit(this.value);">
    <br>
    <input type="checkbox" name="comeBack" value=1 onClick="if(document.form1.Submitit.value=='Submit Changes'){document.form1.Submitit.value='Submit and Review';}else{document.form1.Submitit.value='Submit Changes';}" <?php if($comeBack){echo 'checked';}?>>
    (Come back to this function)<br>
    <br>
    <font face="Courier New, Courier, mono">Descriptions Parameters:<br>
    <?php
#get the file string
$rawFile = implode('',file($ref));

if(preg_match_all("/function [a-z_]+[0-9a-z]*\([^)]*\){/i",	$rawFile,	$functionNameOnlyMatches)){ //JUST the function part
	for($i=0;$i<sizeof($functionNameOnlyMatches[0]);$i++){ //i from 0 to whatever
		//get the name of the function
		$functionNameIsolated = preg_replace("/^function\s*/i",'',$functionNameOnlyMatches[0][$i]);
		$functionNameIsolated = preg_replace("/\([^)]*\){/i",'',$functionNameIsolated);
		$finished[$i]['initialString'] = $functionNameOnlyMatches[0][$i];

		//see if that function has comments
		if(preg_match("/function " . $functionNameIsolated . "\([^)]*\){\s*\/\*(.|\s)*?\*\//i",$rawFile,$actualFunctionRawComments)){
			//use it, using the actual function name in the preg
			$rawComments = $actualFunctionRawComments[0];
			$rawComments = preg_replace("/function [a-z_]+[0-9a-z]*\([^)]*\){/i","",$rawComments);
			$finished[$i]['initialComments'] = $rawComments;
			
			
		}else{
			//get the new structure
			//here's where we get all the parameters in correctly.
			preg_match("/function " . $functionNameIsolated . "\([^)]*\){\s*/i", $rawFile, $stringLead);
			
			$finished[$i]['initialComments'] =	"/" . "*" . 
			"general_version:Function Description Parameters Version 1.0 8-5-2002;
			general_function_name:unspecified;
			general_function_title:unspecified;
			general_brief_description:unspecified;
			general_function_author:unspecified;
			general_serial_number:unspecified;
			general_create_date:unspecified;
			general_instance:unspecified;
			general_protocol_location:unspecified;
			
			link_returns_possible_values:unspecified;
			link_modifies_passed_values:unspecified;
			link_modifies_external_variables:unspecified;
			link_modifies_external_arrays:unspecified;
			link_required_external_variables:unspecified;
			link_required_external_arrays:unspecified;
			link_references_external_functions:unspecified;
			link_references_external_files:unspecified;
			link_dependent_external_functions:unspecified;
			
			mode1_comments:unspecified;
			mode2_comments:unspecified;
			mode3_comments:unspecified;
			mode4_comments:unspecified;
			mode5_comments:unspecified;
			mode6_comments:unspecified;
			mode7_comments:unspecified;
			mode8_comments:unspecified;
			comments:unspecified;
			to_do:unspecified;
			gotchas:unspecified;" . "*" . "/";

			
		}
		
		$finished[$i]['initialComments'] = str_replace('::','&colon&', $finished[$i]['initialComments']);
		$finished[$i]['initialComments'] = str_replace(';;','&semicolon&',$finished[$i]['initialComments']);
		$rawPairs = explode(";",$finished[$i]['initialComments']);
		if(sizeof($rawPairs)){
			foreach($rawPairs as $v){
				$finalSplit = explode(':',$v);
					if(trim($finalSplit[1])<>'blank' and trim($finalSplit[1])<>''){
						$finished[$i][strtoupper(str_replace('&semicolon&',';',(str_replace('&colon&',':',str_replace('/'.'*','',$finalSplit[0])))))] = str_replace('&semicolon&',';',(str_replace('&colon&',':',str_replace('/'.'*','',$finalSplit[1]))));
					
					} 
					
					unset($finalSplit);
			
			
			}
		}
		unset($rawPairs);
		//gather the information ($rawComments)into array[i]
		
		//var the string to be replaced in array[i][STRING TO REPLACE]
		
	}

}else{
	//no function on this page!
}


echo "<br><br>";
?>
    <input type="hidden" name="fileToModify" value="<?php echo $ref;?>">
    <br>
    <?php if(sizeof($finished)){foreach($finished as $n => $v){ ?>
    </font></font> 
  <table border="0" cellspacing="0" cellpadding="2">
    <?php foreach($v as $o => $w){$a++;?>
    <tr <?php if(floor($a/2)==($a/2)){echo "bgcolor=#dddddd";}?>> 
      <td> 
        <div align="right"> <font size="-1" face="Verdana, Arial, Helvetica, sans-serif"> 
          <?php 
		  $output=str_replace('GENERAL_','',$o);
		  $output=str_replace('LINK_','',$output);
		  if($o <> 'initialString' and $o <> 'initialComments'){echo str_replace('_',' ',$output) . ":";}?>
          </font></div>
      </td>
      <td> <font size="-1" face="Verdana, Arial, Helvetica, sans-serif"> 
        <?php if($o <> 'initialString' and $o <> 'initialComments'){?>
        <input type="text" name="commentArray[<?php echo $n;?>][<?php echo trim($o);?>]" 
			value="<?php echo htmlentities($w);?>" size="75">
        <?php }elseif($o == 'initialString'){?>
        <input type="hidden" name="commentArray[<?php echo $n;?>][<?php echo $o;?>]" 
			value="<?php echo htmlentities($w);?>">
        <?php }elseif($o == 'initialComments'){?>
        <input type="hidden" name="commentArray[<?php echo $n;?>][<?php echo $o;?>]" 
			value="<?php echo htmlentities($w);?>">
        <?php }?>
        </font></td>
    </tr>
    <?php }?>
  </table>
  <?php }}?>
</form>
</body>
</html>


