<?php
$functionVersions['valid_phone']=1.0;
function valid_phone($initial, $options=array()){ //validUSPhoneOnly
	/*
	2013-08-01: created somewhere in like 2009 - this was not in the a_f folder though, just put it in today
	
	International phone numbers need developed:
	sample british: +44 171 000 0000
	011 + country code + city code + number is the way US locations would dial the number
	+41 61
	for each country:
	1. what is the city code format and range
	2. what is the number format and range
	
	
	
	options
		allowAlpha
	*/
	extract($options);
	$sp=' '; //defines spacer
	global $valid_phone;
	global $localAreaCode;
	global $localCountryCode;
	
	$initial=trim($initial);
	$buffer = $initial;
	
	//allows alpha translation, this should be hard coded to be faster
	if($allowAlpha){
		$keypad=array(
			'A'=>2,
			'B'=>2,
			'C'=>2,
			'D'=>3,
			'E'=>3,
			'F'=>3,
			'G'=>4,
			'H'=>4,
			'I'=>4,
			'J'=>5,
			'K'=>5,
			'L'=>5,
			'M'=>6,
			'N'=>6,
			'O'=>6,
			'P'=>7,
			'Q'=>7,
			'R'=>7,
			'S'=>7,
			'T'=>8,
			'U'=>8,
			'V'=>8,
			'W'=>9,
			'X'=>9,
			'Y'=>9,
			'Z'=>9
		);
		for($i=65;$i<=90;$i++)$buffer=preg_replace('/'.chr($i).'+/i',$keypad[chr($i)],$buffer);
	}
	
	$replace=array(':',' ',',',')','(',"'",'"','-','.','/','~','//','+');
	$buffer = str_replace($replace,'',$buffer);
	
	//isolate the numeric string
	#1 + 7 digits is OK
	#7 digits are OK as long as localAreaCode is set
	#10 digits are OK as long as first three between 200 and 999
	#longer strings are OK as long as first three are 011 or ....
	
	if(preg_match('/^011[0-9]{6,}/',$buffer,$match)){
		//international number -- i'm requiring at least 6 digits afterward
		$valid_phone['err']='International number parsing not developed yet';
		return false;
	}else if(preg_match('/^1?[2-9]{1}[0-9]{2}[2-9][0-9]{6}/',$buffer,$match)){
		$valid_phone['status']='US or Canada phone syntax';
		// 1 + 10 digits
		echo $match[0] . '--';
		$kill=strlen($match[0]);
		$onePlus = preg_replace('/^1/','',$match[0]);
		
		//basic components of phone number
		$b['raw']=$initial;
		$b['country']=1;
		$b['area']=substr($onePlus,0,3);
		$b['prefix']=substr($onePlus,3,3);
		$b['body']=substr($onePlus,6,4);
		$b['phone']=$b['prefix'].$sp.$b['body'];
		$b['raw_phone']=$b['prefix'].$b['body'];
		$b['full_number']=$b['area'].$b['prefix'].$b['body'];
		
		//find remainder of string if present
		for($i=1;$i<=strlen($initial);$i++){
			$alphaAlso=($allowAlpha?"a-z":"");
			$ct++; //initially 1
			$char=substr($initial,$ct-1,1);
			if(preg_match("/[0-9$alphaAlso]/",$char)){
				$digit++;
			}
			if($digit==$kill){
				$mainStringCt=$ct;
				break;
			}
		}
		if($x=trim(substr($initial,$mainStringCt,strlen($initial)-$mainStringCt))){
			$b[remainder]=$x;
			//get the extension from this string
			if(preg_match('/(e)*x((\.)|(t\.)|(tension))*\s*[0-9]+/i',$x,$a)){
				$b['extension']=preg_replace('/(e)*x((\.)|(t\.)|(tension))*\s*/i','',$a[0]);
			}
			if(preg_match('/[0-9]+/',$x,$a)){
				$b['possible_extension']=$a[0];
			}
		}
		return $b;
	}else{
		return false;
	}
}
?>