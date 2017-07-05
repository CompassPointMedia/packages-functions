<?php
/*><script>*/
$functionVersions['xml_read_tags']=1.33;
function xml_read_tags($string,$tag, $nameCase=''){
	//this function returns all instances of a given tag in a string, indexed 1-based, with an array structure for all of the attributes.  Here is the array structure:
	/*----------------------------------------------------------
	2004-07-11: added nameCase=1, all attribute names are returned lcase, 2=all are uppercase, 0=all are returned as printed (not a good idea)
	2004-03-20: added flexiblity in the name call, can search for tags via regex, e.g. passing xml_read_tags($string, '((list)|(focus))*Element') will find: 1) listElement, 2)focusElement, and 3) element, another example would be -- t(r)|(head)  will find either tr or thead in a table structure.
	2004-03-12: added int_strposn (intermediate text position) and int_length (intermediate text length) for ease of use
	Array Structure returned:
	1	=>		attribute1		=>		value1
				attribute2		=>		value2
				..
				_special			=>		name			=>		tag_name
				_special			=>		length		=>		n1
				_special			=>		strposn		=>		n2
				..
				[no other _special keys at this time]
	2	=>		same as instance one
	----------------------------------------------------------*/
	
	global $xml_read_tags;
	$regex='/<'.$tag.'((\s+[^>]+>)|(>))(.|\s)*$/i';
	$masterLength=strlen($string);
	$masterString=$string;
	
	while(true){
		$fs++;
		if($fs>200)break;
		
		$ok=preg_match($regex,$string,$right);
		if(!$ok)break;
		$i++;
		$offsetFromRight=strlen($right[0]);
		$offsetFromLeft=$masterLength-$offsetFromRight;

		//here is the string
		preg_match('/^<'.$tag.'((\s+[^>]+>)|(>))/i',$right[0],$exact);
		$this=$exact[0];
		$tagLength=strlen($this);
		preg_match('/^<'.$tag.'/i',$this,$nm);
		$a[$i][_special][name]=str_replace('<','',$nm[0]);
		$p1=($a[$i][_special][strposn]=$offsetFromLeft);
		$p2=($a[$i][_special][length]=$tagLength);
		
		if(preg_match_all('/[a-z0-9_-]+\s*((=\s*\'[^\']*\')|(=\s*"[^"]*")|(=[^ \/>]+))/i',$this,$attributes)){
			$a[$i][_special][attributes]=count($attributes[0]);
			//iterate through each attribute = $j
			for($j=0;$j<count($attributes[0]);$j++){
				#split by =, declare the array
				$thisAttribute=$attributes[0][$j];
				$label=trim(substr($thisAttribute,0,strpos($thisAttribute,'=')));
				$value=substr($thisAttribute,strpos($thisAttribute,'=')+1);
				if(preg_match('/^\'(.|\s)*\'$/',$value)){
					$value=preg_replace('/^\'/','',$value);
					$value=preg_replace('/\'$/','',$value);
				}
				if(preg_match('/^"(.|\s)*"$/',$value)){
					$value=preg_replace('/^"/','',$value);
					$value=preg_replace('/"$/','',$value);
				}
				#set the values in array
				$nameCase==1?$label=strtolower($label):'';
				$nameCase==2?$label=strtoupper($label):'';
				
				$a[$i][$label]=$value;
			}
		}
		//munch the name value pairs and look for flags like nowrap
		$munch = preg_replace('/[a-z0-9_-]+\s*((=\s*\'[^\']*\')|(=\s*"[^"]*")|(=[^ ]+))(\s|(\/>)|>)/i','',$this);
		$munch= preg_replace('/<'.$tag.'/i','',$munch);
		$munch= preg_replace('/\/*>/','',$munch);
		$munchlist=preg_split('/\s+/',trim($munch));
		if(count($munchlist)){
			foreach($munchlist as $u){
				if($u!==''){
					$nameCase==1?$u=strtolower($u):'';
					$nameCase==2?$u=strtoupper($u):'';
					$a[$i][$u]=1;
					$a[$i][_special][flags][]=$u;
				}
			}
		}

		//redeclare the string from the new position
		$string=
		($offsetFromRight>$tagLength?substr($string,-($offsetFromRight-strlen($exact[0]))):'');
		if(substr($masterString,$p1+$p2-2,1)!=='/'){
			//this is a nesting tag, three cases:
			#1. we have a nesting tag, with no subnested similar tags
			#2. we have a nesting tag with a subnested similar tag
			#3. we have no closing tag -- bad XML form
			unset($l);
			unset($level);
			unset($nextString);
			$l=1;
			$level[$l]=1;
			$nextString=$string;

			//========================================
			while(true){
				//fail safe
				$fs2++; if($fs2>200)break;
				//match an open or close tag downstream
				$nextString=substr($string,-(strlen($nextString)-2));

				preg_match('/<(\/*)'.$tag.'(.|\s)*$/i',$nextString,$match2);
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
					$p3=$a[$i][_special][end_strposn]=$masterLength-$len;
					#this is risky
					$p4=$a[$i][_special][end_length]=strlen($tag)+3;
					$p5=$a[$i][_special][total_length]=$a[$i][_special][end_strposn] - $a[$i][_special][strposn]+$a[$i][_special][end_length];
					$a[$i][_special][int_strposn]=$p1+$p2;
					$a[$i][_special][int_length]=($p3-$p1)-$p2;
					break;		
				}else{
					$nextString=substr($nextString, -($len));
				}
			}
			//========================================
		}
	}
	return $a;
}// end xml_read_tags()
?>