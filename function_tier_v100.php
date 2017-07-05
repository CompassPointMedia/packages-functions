<?php
/*
Use
$tier=array(
	0=>'.5',
	5000=>'.6',
	7500=>'.7',
	9000=>'.75'
);
$check=5063;
$what=tier($tier,$balance,$check);
prn($what);*/
function tier($tier,$balance='',$check){
	echo $check.'<br />';
	echo $balance.'<br />';
	foreach($tier as $n=>$v){
		$i++;
		if(!is_array($amounts)) $amounts=array();
		/*skips the first because it assigns it in a more meaningful manner*/
		if($i==1){
			$previousN=$n;
			$previousV=$v;
			$checkSubtotal=$check;
			continue;
		}
		/*all the balance stuff*/
		if($balance){
			if($balance>$n){
				$amountApplicable[$previousV]='0';
				$previousN=$n;
				$previousV=$v;
				if($i==count($tier)){
					$amountApplicable[$v]=$check-array_sum($amountApplicable);
				}
				$balance=$balance-$n;
				continue;
			}
			$previousN=$previousN+$balance;
			$balance=0;
		}
		/*gets the amount of money applicable to that bracket*/
		$amountApplicable[$previousV]=$n-$previousN;
		/*checks if the check is less than the amount applicable, if it is it applies the check amount to the final amount*/
		if($check<$amountApplicable[$previousV] && $check!=0){
			$amount[$previousV]=$check;
			$check=$check-$amount[$preciousV];
		}
		/*checks if the amount applicable is less than the check amount, if it is, it caps the amount applicable*/
		if((($amount[$previousV]=($check-array_sum($amountApplicable)))==0) || ($checkSubtotal>$n)){
			$amount[$previousV]=$amountApplicable[$previousV];
			$checkSubtotal=$check-$amountApplicable[$previousV];
		} else {
			$amount[$previousV]=$checkSubtotal;
			$checkSubtotal=0;
		}
		if($amount[$previousV]<0)$amount[$previousV]=0;
		$previousN=$n;
		$previousV=$v;
		if($i==count($tier)){
			if(($check-array_sum($amountApplicable))<0){ 
					$amount[$v]=0;
				}else{
					$amount[$v]=$check-array_sum($amountApplicable);
				}
		}
		/*
		$check=$check-$previousN;
		if($check<0){
			return($amounts);
			exit;
		}
		$amounts[$v]=$check;
		if($amounts[$previousV]>$n && $i!=count($tier)) $amounts[$previousV]=$n;
		echo $check.'<br />';
		echo $n.'=>'.$v.'<br />';
		$previousN=$n;
		$previousV=$v;
		echo $previousN.'=>'.$previousV.'<br>';*/
	}
	return($amount);
}
?>