<?php
function analyze_cart(){
	/*
	2010-10-10: reviewed and updated for 4.0 style shopping cart
	reviewed this 2006-01-25 - these principles aren't implemented yet
	The only time the cart analysis will be performed is on page_1 where we're still relying on session variables 
	the criteria for a wholesale login is $_SESSION['wholesale']=1 (afterwards carried in a hidden form field.  If that's true, we look in a field called 'WholeSalePrice' else we look first in Price, then in RetailPrice.
	*/
	global $_settings, $developerEmail, $fromHdrBugs, $acct;
	if(count($_SESSION['shopCart'][$_SESSION['selectedCart']])){
		//STEP ONE: FIND THE PRICE FIELD (WHOLESALE, RETAIL, OR JUST PRICE)
		if($_SESSION['cnx'][$acct]['wholesaleAccess']>=8 || $_SESSION['wholesale']){
			//look for WholesalePrice
			$priceField = 'WholesalePrice';
		}else{
			//look for 'RetailPrice'
			foreach($_SESSION['shopCart'][$_SESSION['selectedCart']] as $v){
				if(isset($v['RetailPrice'])){
					$priceField='RetailPrice';
				}else{
					mail($developerEmail, 'Error file '.__FILE__.', line '.__LINE__,get_globals(),$fromHdrBugs);
					exit('analyze_cart(): the cart session is not set up properly');
				}
			}	
		}
		//STEP TWO: RECURSE THE CART AND GET THE TOTAL
		foreach($_SESSION['shopCart'][$_SESSION['selectedCart']] as $n=>$v){
			//this presumes control over fractional units from elsewhere
			if($v['Quantity']>0){$subTotal += $v['Quantity'] * $v[$priceField];}
		}
		//STEP THREE: RETURN CART STATUS
		if($subTotal<=0){
			return 0;
		}elseif(($_SESSION['cnx'][$acct]['wholesaleAccess']>=8 || $_SESSION['wholesale']) && $subTotal < $_settings['minimumWholesaleOrder']){
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