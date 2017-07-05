<?php
//how to return results - added in v1.34
	#multiple array returns
define('XML_RET_ALL',1); //returns an array with the params (see below) for EVERY INSTANCE of this tag
	#single array return
define('XML_RET_FIRST',2); //returns the array for the first instance of this tag
	#string returns
define('XML_RET_INNER',3); //returns the "mid string" i.e. the innerHTML of the *first* tag
define('XML_RET_OUTER',4); //returns the "full string" i.e. the outerHTML of the *first* tag
//what parameters to return - added in v1.34 - these only apply to options 1 and 2 above
define('XML_PARAMS_POSN',1); //position integers only
define('XML_PARAMS_INNER',2); //adds mid string for a nesting tag
define('XML_PARAMS_OUTER',3); //adds full string including opening [and closing] tag
//WARNING: If you hvae a bunch of tags esp. with possible large nesting text inside, using options 2 and 3 can cause a slow return
$functionVersions['xml_read_tags']=1.35;
function xml_read_tags($str, $tag, $attrib='', $return=XML_RET_ALL, $params=XML_PARAMS_POSN, $case=1){
	global $xml_read_tags;
	if($xml_read_tags['debug'])$debug=true;
	/***********  ----------------------------------------------------------
	2005-11-04: there is a SEVERE BUG in this creature, mid_content will not be returned for XML_PARAMS_INNER, and the mid information will not even be seen, when the conent of the tag is less than 2 characters.  This needs solved and quick.
	2005-10-30: version 1.34 - Ability to look only for tags of a specific attribute.  Pass 3rd parameter as array('id'=>'record001') and only this or these tag(s) will returned. Fixed a bug with closing tag, streamlined coding, added the ability to get the "mid" string of a tag and also to return just the first instance if a tag is known to be unique.  Two changes in output also:
		1. instead of ['_special'] we now return the integer [0]
		2. instead of 'int_strposn' and 'int_length' we now return 'mid_..' which is more understandable
	2004-07-11: added case=1, all attribute names are lcase, 2=all are uppercase
	2004-03-20: added flexiblity in the name call, can search for tags via regex, e.g. passing xml_read_tags($str, '((list)|(focus))*Element') will find: 1) listElement, 2)focusElement, and 3) element, another example would be -- t(r)|(head)  will find either tr or thead in a table structure.
	2004-03-12: added 'int_strposn' (intermediate text position) and 'int_length' (intermediate text length) for ease of use
	Array Structure returned:
	1	=>		attribute1		=>		value1
				attribute2		=>		value2
				..
				0			=>		array(
									name,
									strposn,
									length,
									attributes [count],
									end_strposn,
									end_length,
									total_length,
									[mid_strposn, (mid values only if present)
									mid_length,
									[mid_content]] (only if specified)
				..
				[no other 0 keys at this time]
	2	=>		same as instance one
	----------------------------------------------------------*/
	
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
		if($return > XML_RET_ALL && $a[1]){
			break;
		}
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
		//exclude tags without specific attributes if requested
		if(is_array($attrib) && count($attrib)){
			foreach($attrib as $n=>$v){
				//fail for any mismatch
				if($a[$i][$n]!=$v || strlen($a[$i][$n])!==strlen($v)){
					$aprime=array();
					for($j=1;$j<$i;$j++){
						$aprime[$j]=$a[$j];
					}
					$a=$aprime;
					$i--;
					//redeclare the string from the new position
					$str=
					($offFrRt>$tagLen?substr($str,-($offFrRt-strlen($thisTag))):'');
					continue(2);
				}
			}
		}


		//munch the name value pairs and look for standalone flags like nowrap
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
				/** match an open or close tag downstream.
				this has some problems because a <tag> reference in an HTML comment for example will throw things off
				**/
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
					$p5=$a[$i][0]['total_length']=$p3 - $a[$i][0]['strposn']+$p4;
					$a[$i][0]['mid_strposn']=$p1+$p2;
					$a[$i][0]['mid_length']=($p3-$p1)-$p2;
					if($params > XML_PARAMS_POSN || $return > XML_RET_FIRST){
						if($params==XML_PARAMS_INNER || $return==XML_RET_INNER)
							$a[$i][0]['mid_content']=substr($mstStr,$p1+$p2,$p3-$p1-$p2);
						if($params==XML_PARAMS_OUTER || $return==XML_RET_OUTER)
							$a[$i][0]['mid_content']=substr($mstStr,$a[$i][0]['strposn'],$p5);
					}
					break;		
				}else{
					$nxtStr=substr($nxtStr, -($len));
				}
			}
			//========================================
		}
	}
	switch($return){
		case XML_RET_FIRST:
			return $a[1];
		case XML_RET_INNER:
		case XML_RET_OUTER:
			return $a[1][0]['mid_content'];
		default: return $a; /** XML_RET_ALL **/
	}
}
?>