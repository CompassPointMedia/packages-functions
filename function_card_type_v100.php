<?php
function card_type($ccNo){
	/***
	returns the type of a card based on number, or blank if not recognized
	***/
	$ccNo=trim($ccNo);
	$ccNo=str_replace(' ','',$ccNo);
	$ccNo=str_replace('-','',$ccNo);
	if(!preg_match('/^[0-9]{13,16}$/',$ccNo)){
		return '';
	}
	$rv=strrev($ccNo);
	$ccln=strlen($ccNo);
	for($i = 0; $i < $ccln; $i++) {
		$x=substr($rv, $i, 1);

		if(fmod($i,2)){ #i mod 2 = 0
			$x*=2; $x=($x<10?$x: (substr($x,0,1) + substr($x,1,1)) );
		}
		$sum+=$x;
	}
	if(($sum > 0) && (fmod($sum,10)==0)) {
		$f2=substr($ccNo,0,2);
		$f3=substr($ccNo,0,3);
		$f4=substr($ccNo,0,4);
		switch(true){
			case ($ccln==13):
				if(substr($ccNo,0,1)==4) return 'Visa';
				break;
			case ($ccln==14):
				if( ($f3>=300 && $f3<=305) || $f2==36 || $f2==38 ) return 'Diners';
				break;
			case ($ccln==15):
				if($f2==34 || $f2==37) return 'Amex';
				break;
			case ($ccln==16):
				if(substr($ccNo,0,1)==4) return 'Visa';
				if($f4==6011) return 'Discover';
				if($f2>=51 && $f2<=55) return 'Master Card';
				break;
			default:
				return "";
		}
	}
	return "";
}
?>