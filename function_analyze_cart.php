<?php
function analyze_cart(){
	/*
	reviewed this 2006-01-25 - these principles aren't implemented yet
	The only time the cart analysis will be performed is on page_1 where we're still relying on session variables 
	the criteria for a wholesale login is $_SESSION['wholesale']=1 (afterwards carried in a hidden form field.  If that's true, we look in a field called 'WholeSalePrice' else we look first in Price, then in RetailPrice.
	*/
	global $_settings;
	if(sizeof($_SESSION['shopCart'])){
		//STEP ONE: FIND THE PRICE FIELD (WHOLESALE, RETAIL, OR JUST PRICE)
		if($_SESSION['wholesale']){
			//look for WholesalePrice
			$priceField = 'WholesalePrice';
		}else{
			//look for 'RetailPrice'
			foreach($_SESSION['shopCart'] as $n=>$v){
				if(isset($v['RetailPrice'])){$priceField='RetailPrice';
				}else{ exit('analyze_cart(): the cart session is not set up properly');}
			}	
		}
		//STEP TWO: RECURSE THE CART AND GET THE TOTAL
		foreach($_SESSION['shopCart'] as $n=>$v){
			//this presumes control over fractional units from elsewhere
			if($v[1]>0){$subTotal += $v[1] * $v[$priceField];}
		}
		//STEP THREE: RETURN CART STATUS
		if($subTotal<=0){
			return 0;
		}elseif($_SESSION['wholesale'] && $subTotal < $_settings['minimumWholesaleOrder']){
			return 2;
		}elseif(isset($_settings['minimumOrder']) && $subTotal < $_settings['minimumOrder']){
			return 1;
		}else{
			return 3; //3=good order
		}
	}else{
		return 0; //cart is completely empty
	}
}
?>