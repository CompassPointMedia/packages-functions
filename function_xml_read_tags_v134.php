<?php
//how to return results - added in v1.34
define('XML_RETURN_ALL',1);
define('XML_RETURN_NEST',2);
define('XML_RETURN_FIRST',3);
//what parameters to return - added in v1.34
define('XML_PARAMS_POSN',1);
define('XML_PARAMS_ADDMID',2);

$xml_read_tags['version']=1.34;
$functionVersions['xml_read_tags']=1.34;
function xml_read_tags($str,$tag, $return=XML_RETURN_ALL, $params=XML_PARAMS_POSN, $case=1){
	//this function returns all instances of a given tag in a string, indexed 1-based, with an array structure for all of the attributes.  Here is the array structure:
	/*----------------------------------------------------------
	2005-10-30: version 1.34 - fixed a bug with closing tag, streamlined coding, added the ability to get the "mid" string of a tag and also to return just the first instance if a tag is known to be unique.  Two changes in output also:
		1. instead of ['_special'] we now return the integer [0]
		2. instead of 'int_strposn' and 'int_length' we now return 'mid_..' which is more understandable
	2004-07-11: added case=1, all attribute names are lcase, 2=all are uppercase
	2004-03-20: added flexiblity in the name call, can search for tags via regex, e.g. passing xml_read_tags($str, '((list)|(focus))*Element') will find: 1) listElement, 2)focusElement, and 3) element, another example would be -- t(r)|(head)  will find either tr or thead in a table structure.
	2004-03-12: added 'int_'strposn'' (intermediate text position) and 'int_length' (intermediate text length) for ease of use
	Array Structure returned:
	1	=>		attribute1		=>		value1
				attribute2		=>		value2
				..
				0			=>		name			=>		tag_name
				0			=>		length		=>		n1
				0			=>		'strposn'		=>		n2
				..
				[no other 0 keys at this time]
	2	=>		same as instance one
	----------------------------------------------------------*/
	
	global $xml_read_tags;
	$regex='/(<('.$tag.')((\s+[^>]+>)|(>)))(.|\s)*$/i';
	$mstLen=strlen($str);
	$mstStr=$str;
	
	while(true){
		$fs++;
		if($fs>200)break;
		
		ob_start();
		$ok=preg_match($regex,$str,$right);
		$x=ob_get_contents();
		ob_end_clean();
		if($x){$xml_read_tags[err]='Parse or other error reading the tag against the string';}
		if(!$ok)break;
		$i++;
		if($return==XML_RETURN_FIRST && $i==2)	return $a[1];
		$offFrRt=strlen($right[0]);
		$offFrLt=$mstLen-$offFrRt;

		//here is the entire tag
		$thisTag=$right[1];
		$tagLen=strlen($thisTag);
		$a[$i][0]['name']=$right[2];
		$p1=($a[$i][0]['strposn']=$offFrLt);
		$p2=($a[$i][0]['length']=$tagLen);
		
		if(preg_match_all('/[a-z0-9_-]+\s*((=\s*\'[^\']*\')|(=\s*"[^"]*")|(=[^ \/>]+))/i',$thisTag,$att)){
			$a[$i][0]['attributes']=count($att[0]);
			//iterate through each attribute = $j
			for($j=0;$j<count($att[0]);$j++){
				#split by =, declare the array
				$thisTagAtt=$att[0][$j];
				$label=trim(substr($thisTagAtt,0,strpos($thisTagAtt,'=')));
				$value=substr($thisTagAtt,strpos($thisTagAtt,'=')+1);
				if(preg_match('/^\'(.|\s)*\'$/',$value)){
					$value=preg_replace('/^\'/','',$value);
					$value=preg_replace('/\'$/','',$value);
				}
				if(preg_match('/^"(.|\s)*"$/',$value)){
					$value=preg_replace('/^"/','',$value);
					$value=preg_replace('/"$/','',$value);
				}
				#set the values in array
				$case==1?$label=strtolower($label):'';
				$case==2?$label=strtoupper($label):'';
				$a[$i][$label]=$value;
			}
		}
		//munch the name value pairs and look for flags like nowrap
		$munch = preg_replace('/[a-z0-9_-]+\s*((=\s*\'[^\']*\')|(=\s*"[^"]*")|(=[^ ]+))(\s|(\/>)|>)/i','',$thisTag);
		$munch= preg_replace('/<'.$tag.'/i','',$munch);
		$munch= preg_replace('/\/*>/','',$munch);
		$munchlist=preg_split('/\s+/',trim($munch));
		if(count($munchlist)){
			foreach($munchlist as $u){
				if($u!==''){
					$case==1?$u=strtolower($u):'';
					$case==2?$u=strtoupper($u):'';
					$a[$i][$u]=1;
					$a[$i][0][flags][]=$u;
				}
			}
		}

		//redeclare the string from the new position
		$str=
		($offFrRt>$tagLen?substr($str,-($offFrRt-strlen($thisTag))):'');
		if(substr($mstStr,$p1+$p2-2,1)!=='/'){
			//this is a nesting tag, three cases:
			#1. we have a nesting tag, with no subnested similar tags
			#2. we have a nesting tag with a subnested similar tag
			#3. we have no closing tag -- bad XML form
			unset($l);
			unset($level);
			unset($nxtStr);
			$l=1;
			$level[$l]=1;
			$nxtStr=$str;

			//========================================
			while(true){
				//fail safe
				$fs2++; if($fs2>200)break;
				//match an open or close tag downstream
				$nxtStr=substr($str,-(strlen($nxtStr)-2));

				preg_match('/<(\/*)'.$tag.'(.|\s)*$/i',$nxtStr,$match2);
				$l++;
				//get the length of the string
		 		$len=strlen($match2[0]);
				if(substr($match2[0],1,1)=='/'){
					$level[$l]=-1;
				}else{
					$level[$l]=1;
				}
				if(array_sum($level)==0){
					// the end tag starts $len from the end of the string
					$p3=$a[$i][0]['end_strposn']=$mstLen-$len;
					#this is risky - though 99.99% of closing tags will be in format </tag>
					$p4=$a[$i][0]['end_length']=strlen($a[$i][0]['name'])+3;
					$p5=$a[$i][0]['total_length']=$a[$i][0]['end_strposn'] - $a[$i][0]['strposn']+$a[$i][0]['end_length'];
					$a[$i][0]['mid_strposn']=$p1+$p2;
					$a[$i][0]['mid_length']=($p3-$p1)-$p2;
					if($params==XML_PARAMS_ADDMID) $a[$i][0]['mid_content']=substr($mstStr,$p1+$p2,$p3-$p1-$p2);
					break;		
				}else{
					$nxtStr=substr($nxtStr, -($len));
				}
			}
			//========================================
		}
	}
	return ($return==XML_RETURN_FIRST ? $a[1] : $a);
}
?>