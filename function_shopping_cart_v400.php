<?php
/*
2012-06-02
	* starting to come back to life here
	* allow comma-separated lists of items for multi-adding
	
2008-10-01
----------
* integrated shopping_cart_calculate() function using the "Tasmanian Devil" code used in ecom_flex1_003.php - we can now modify the cart array by reference, and only need call the function one time normally
* improved side-array session.shopCartSubItems to be easier to traverse

2008-09-24
----------
this page will eventually need a way to sum shopping carts and present shopping cart data, and calculate shipping
*/
$functionVersions['shopping_cart']=4.00;
function shopping_cart($ID, $qty=1, $options=array()){
	//2012-06-02: allow for passage of multiple products simultaneously
	if(strstr($ID,',')){
		$a=explode(',',$ID);
		foreach($a as $ID){
			shopping_cart($ID,$qty,$options);
		}
		return true;
	}
	/***
	2008-09-24: v4.00
	---------------------------
	MAJOR REVISIONS AND FEATURES:
	1. sub-carts (multiple shopping carts)
	2. retained and slightly improved GPP data
	3. added P3 (Packaged Product Protocol)
	4. added Quasi SKU's (e.g. not existing in the database, for example AS3052-N (-N is the quasi part))
	
	
	Specific Changes
	---------------------------
	* moved ID=>244 into the array; shopping cart item key is now set to an Idx-like value, not the product ID
	* added $_SESSION['shopCart'][$cart] where $cart=>'default' to the session array nodes - preparation for multiple shopping carts
		var cart in options will override 'default' value
	* added option combineItems=(default true).  Adding a 2nd instance of the same product will simply increase the quantity of that line item
	* added options for Quasi SKU's:
		SKUSuffix && SKUPrefix
		NameSuffix && NamePrefix
		DescriptionSuffix && DescriptionPrefix
		- these must contain their own dashes and parentheses
		- the shopping cart is responsible for parsing these in presentation and invoicing
		- no way to add these to subitems
	* WHEN ITEM *IS* A SUBSCRIPTION
	To implement subscriptions, the fields SubscriptionInterval and SubscriptionQuantity must be included in the productTable array; if they are present the item or subitem will be considered a subscription.  By default a subscription charges the cost of the item or subitem (subitems can be subscriptions); if the option SubscriptionDeferCharge=1 is passed, the cost will be set to zero for this shopping cart.  This is useful in the case of subscriptions where the charge is incurred when the item(s) are delivered through the subscription period, not at the time of subscription.
	* Also for subscriptions, you can pass the overrride options SubscriptionInterval and SubscriptionQuantity to either make something a subscription or make it a fixed item purchase
	* WHEN SALE ITEM IS *FROM* A SUBSCRIPTION
	The variable Subscriptions_ID=>7 can be added to options if the item refers back to a previous subscription purchase
		
	* added a side array _SESSION[shopCartSubItems][$cart] to indicate that a subitem is part of its parent package
	* added a GPPSchema=>5 to indicate the product is a GPP item and get the schema
	* added IsPackage=1 to indicate the product is a package
	* added SubItem=1 to indicate the product is a subitem
	* added Shipmethods_ID field to the default productsTable array
	
	To do:
	--------
	P3 packages are NOT combined - each shows up separately - I figure this is what would normally be desired - this also means that for packages you can't change quantity but only remove the package
	NO INVENTORY LOGIC IN PLACE: we really don't have the ability to precisely sell, say, 100 monitors
	
	
	#############################################################


	2008-06-15: v3.01
	--------------------------
	added options:
		OverridePrice=>45.00 will overrride the database unit (retail) Price
		OverridePriceWholesale=>22.50 will overrride the database wholesale Price
		OverridePriceSale=>35.00 will overrride the sale price
	
	2007-01-10: added addCartMode=grouped for GPP 0.1.  For docs on this see notes in admin-local.  Basically this glues together parameters passed and finds the selected product, and sets that ID, then continues as before.
	2006-03-02: cut out the ugly fat of previous shopping carts, starting to gel on the protocol and rules.  
	***/

	global $shopping_cart, $fl, $ln, $qr, $productTable, $_settings, $addCartMode, $GPPSchema, $developerEmail, $fromHdrBugs;
	$shopping_cart['error']='';

	if(count($options))extract($options);
	if(!$cart)$cart='default';
	if(!isset($combineItems))$combineItems=true; //same item added twice will be one line item, qty=2
	if(!isset($term))$term=$_COOKIE['term']; //these two assoc. with EACH PRODUCT
	if(!isset($referer))$referer=$_COOKIE['referer'];
	if(!count($productTable))$productTable=array(
		1 => 'SKU',
		2 => 'Name',
		3 => 'Weight',
		4 => 'Description',
		5 => 'LongDescription',
		6 => 'ManufacturerSKU',
		7 => 'Shipmethods_ID'
	);
	if(!isset($_settings['wholesalePriceField']))$_settings['wholesalePriceField']='WholesalePrice';
	if(!isset($_settings['retailPriceField']))$_settings['retailPriceField']='RetailPrice';
	if(!isset($_settings['salePriceField']))$_settings['salePriceField']='SalePrice';
	
	if(is_array($ID)){
		foreach($ID as $n=>$v){
			//don't add to cart, not all attributes were selected
			if(!trim($v))return false;
		}
		if(!$GPPSchema){
			$shopping_cart['error']='ID is an array. Value for GPPSchema not passed';
			mail($developerEmail,$shopping_cart['error'], get_globals(), $fromHdrBugs);
			return false;
		}
		if(!($a=q("SELECT * FROM finan_items_schemas WHERE ID='$GPPSchema'", O_ROW))){
			$shopping_cart['error']='No such GPPSchema value ('.$GPPSchema.')';
			mail($developerEmail,$shopping_cart['error'], get_globals(), $fromHdrBugs);
			return false;
		}
		$b=preg_split('/\([^)]+\)/',$a['Schema']);
		foreach($b as $v){
			$i++;
			$string.=$v.$ID[$i];
		}
		//get the product now
		if($ids=q("SELECT ID FROM finan_items WHERE SKU REGEXP('$string')", O_COL)){
			if(count($ids)>1){
				$shopping_cart['error']='Multiple products found for this item number ('.$string.')';
				mail($developerEmail,$shopping_cart['error'], get_globals(), $fromHdrBugs);
				return false;
			}else{
				foreach($ids as $ID){
					//we have the ID now
					break;
				}
			}
		}else{
			$shopping_cart['error']='No product found from this criteria ('.$string.')';
			return false;
		}
	}
	//localize the shopping cart
	$shopCart = $_SESSION['shopCart'][$cart];
	//get an index to assign purchase to
	if(count($shopCart) && $combineItems){
		foreach($shopCart as $n=>$v){
			if($v['ID']==$ID && !$v['SubItem']){
				$Idx=$n;
				break;
			}
		}
	}
	if(!$Idx)$Idx=max(array_keys($shopCart))+1;

	//price structure default fields
	$upw=(isset($OverridePriceWholesale) ? "'".$OverridePriceWholesale."'" : $_settings['wholesalePriceField']); //normally WholesalePrice
	$up1=(isset($OverridePrice) ? "'".$OverridePrice."'" : $_settings['retailPriceField']); //normally UnitPrice
	$up2=(isset($OverridePriceSale) ? "'".$OverridePriceSale."'" : $_settings['salePriceField']); //normally UnitPrice2
	
	if(!($item=q("SELECT
		a.".implode(', a.',$productTable).",
		a.ID AS SystemID, a.$upw AS WholesalePrice, a.$up1 AS RetailPrice, a.$up2 AS SalePrice,
		b.Items_ID AS IsPackage,
		b.OverallDescription,
		b.OverallLongDescription,
		b.PricingType,
		b.PriceValue,
		c.ChildItems_ID,
		c.Idx,
		c.Quantity,
		c.BonusItem,
		c.PricingType AS ChildPricingType,
		c.PriceValue AS ChildPriceValue,
		c.OverrideName,
		c.OverrideDescription,
		c.OverrideLongDescription
		FROM 
		finan_items a LEFT JOIN finan_items_packages b ON a.ID=b.Items_ID
		LEFT JOIN finan_ItemsItems c ON a.ID=c.ParentItems_ID
		WHERE a.ID=$ID ORDER BY c.Idx, IF(c.BonusItem, 2,1), IF(c.PricingType='Free',2,1)", O_ARRAY))){
		error_alert('fail');
		return false;
	}
	
	//build array
	$_SESSION['shopCartModified']=time();
	$shopCart[$Idx]['ID']=$item[1]['SystemID'];
	$shopCart[$Idx]['Quantity']+=$qty; //this may already be there
	if($item[1]['IsPackage']){
		$shopCart[$Idx]['IsPackage']=1;
		$shopCart[$Idx]['PricingType']=$item[1]['PricingType'];
		$shopCart[$Idx]['PriceValue']=$item[1]['PriceValue'];
		$shopCart[$Idx]['_system_note_']='prices not included for root package item';
	}else{
		$shopCart[$Idx]['WholesalePrice']=$item[1]['WholesalePrice'];
		$shopCart[$Idx]['RetailPrice']=$item[1]['RetailPrice'];
		$shopCart[$Idx]['SalePrice']=$item[1]['SalePrice'];
	}
	
	if(isset($GPPSchema))$shopCart[$Idx]['GPPSchema']=$GPPSchema;
	if(isset($Subscriptions_ID))$shopCart[$Idx]['Subscriptions_ID']=$Subscriptions_ID;
	if(isset($SubscriptionDeferCharge))$shopCart[$Idx]['SubscriptionDeferCharge']=$SubscriptionDeferCharge;
	if(isset($SubscriptionInterval)){
		if($SubscriptionInterval)$shopCart[$Idx]['SubscriptionInterval']=$SubscriptionInterval;
		//this field might be blank (unlimited subscription)
		$shopCart[$Idx]['SubscriptionQuantity']=$SubscriptionQuantity;
	}else if($item[1]['SubscriptionInterval'] && strtolower($item[1]['SubscriptionInterval'])!='none' /*Subscription Quantity may be blank or zero - if so it means indefinite */){
		$shopCart[$Idx]['SubscriptionInterval']=$item[1]['SubscriptionInterval'];
		$shopCart[$Idx]['SubscriptionQuantity']=$item[1]['SubscriptionQuantity'];
	}
	
	//this will not override the price fields or anything above below the comment line "build array"
	foreach($productTable as $v){
		if(isset($item[1][$v]) && !isset($shopCart[$Idx][$v]))$shopCart[$Idx][$v]=$GLOBALS[$v.'_PREFIX'].$item[1][$v].$GLOBALS[$v.'_SUFFIX'];
	}
	if($term)$shopCart[$Idx]['term']=$term;
	if($referer)$shopCart[$Idx]['referer']=$referer;

	//these apply to all types of products including singles, p3, and gpp
	if($SKUPrefix)$shopCart[$Idx]['SKUPrefix']=$SKUPrefix;
	if($SKUSuffix)$shopCart[$Idx]['SKUSuffix']=$SKUSuffix;
	if($NamePrefix)$shopCart[$Idx]['NamePrefix']=$NamePrefix;
	if($NameSuffix)$shopCart[$Idx]['NameSuffix']=$NameSuffix;
	if($DescriptionPrefix)$shopCart[$Idx]['DescriptionPrefix']=$DescriptionPrefix;
	if($DescriptionSuffix)$shopCart[$Idx]['DescriptionSuffix']=$DescriptionSuffix;

	if($item[1]['IsPackage']){
		//these are normally used for the on-site descriptions, NOT for the shopping cart but are included anyway
		$shopCart[$Idx]['OverallDescription']=$item[1]['OverallDescription'];
		$shopCart[$Idx]['OverallLongDescription']=$item[1]['OverallLongDescription'];
	}
	
	if($item[1]['IsPackage']){
		//handle packages - iteratively add each subitem to the cart
		$packageIdx=$Idx;
		foreach($item as $p){
			if(is_null($p['ChildItems_ID'])){
				//back out - we can't add an empty package
				$shopping_cart['error']='This package ('.$item[1]['SKU'].') is empty and cannot be added to your order';
				mail($developerEmail,$shopping_cart['error'], get_globals(), $fromHdrBugs);
				return false;
			}
			$Idx++;
			$b=q("SELECT a.".implode(', a.',$productTable).",
			a.$upw AS WholesalePrice, a.$up1 AS RetailPrice, a.$up2 AS SalePrice
			FROM finan_items a 
			WHERE a.ID='".$p['ChildItems_ID']."'", O_ROW);
			$shopCart[$Idx]['ID']=$p['ChildItems_ID'];
			$shopCart[$Idx]['Quantity']=$p['Quantity'];
			$shopCart[$Idx]['SubItem']=1;
			$shopCart[$Idx]['WholesalePrice']=$b['WholesalePrice'];
			$shopCart[$Idx]['RetailPrice']=$b['RetailPrice'];
			$shopCart[$Idx]['SalePrice']=$b['SalePrice'];
			//these will not overrride the prices above
			foreach($productTable as $v)if(isset($b[$v]) && !isset($shopCart[$Idx][$v]))$shopCart[$Idx][$v]=$b[$v];
			
			$shopCart[$Idx]['BonusItem']=$p['BonusItem'];
			$shopCart[$Idx]['PricingType']=$p['ChildPricingType'];
			$shopCart[$Idx]['PriceValue']=$p['ChildPriceValue'];
			$shopCart[$Idx]['OverrideName']=$p['OverrideName'];
			$shopCart[$Idx]['OverrideDescription']=$p['OverrideDescription'];
			$shopCart[$Idx]['OverrideLongDescription']=$p['OverrideLongDescription'];

			//set up relationship table
			$_SESSION['shopCartSubItems'][$cart][$packageIdx][$Idx]=1; //true for now, later may contain more information
		}
	}
	$_SESSION['shopCart'][$cart]=$shopCart;
	return true;
}
function shopping_cart_modify($idx, $qty=0, $cart='default'){
	/*
	2008-09-24 - v4.00
	This will remove or requantify all of an index including sub-items, OR it will remove the specific subitem.  We now use idx vs ID because of the 4.0 revision and because we can store multiple line items of the same product ID in the cart
	*/
	global $shopping_cart_modify;
	$shopCart=$_SESSION['shopCart'][$cart];
	if($shopCart[$idx]){
		if(!preg_match('/^-*[0-9]+$/',$qty)){
			$shopping_cart_modify['error']='Quantity input is not an integer ('.$qty.')';
			return false;
		}else{
			if($qty==0){
				//remove the item and subitems	
				foreach($shopCart as $n=>$v){
					if($n<$idx)$a[$n]=$v;
					if($n==$idx){
						if($v['IsPackage'])$clearPackage=1;
						continue;
					}
					if($n>$idx){
						if($v['SubItem'] && $clearPackage){
							continue;
						}else{
							$clearPackage=0;
							$a[count($a)+1]=$n;
						}
					}
				}
				if(count($a)){
					$_SESSION['shopCart'][$cart]=$a;
				}else unset($_SESSION['shopCart'][$cart]);
				
				//remove the reference array node
				unset($_SESSION['shopCartSubItems'][$cart][$idx]);
				if(!count($_SESSION['shopCartSubItems'][$cart])) unset($_SESSION['shopCartSubItems'][$cart]);
				return true;
			}else{
				$shopCart[$idx]['Quantity']=$qty;
				$_SESSION['shopCart'][$cart]=$shopCart;
				return true;
			}
		}
	}else{
		$shopping_cart_modify['error']='That index does not exist ('.$idx.')';
		return false;
	}
}
function shopping_cart_calculate($idx, $cart='default'){
	/*
	Added 2008-10-01; calculates per-item and overall data as an array and returns it; can be then integrated into the shopgCart array if desired.
	*/
	global $shopping_cart, $fl, $ln, $qr, $productTable, $_settings, $addCartMode, $GPPSchema, $developerEmail, $fromHdrBugs;
	$shopping_cart_calculate_version='1.00';
	if(!($a=$_SESSION['shopCartSubItems'][$cart][$idx])){
		return false;
	}


	$integrateWithCartSession=true;
	//------------------------- codeblock "Tasmanian Devil" ----------------------------------
	$nonBonusItemsTotal=$listTotal=$packageTotal=0;
	$defer=false;
	foreach($a as $n=>$v){
		if($integrateWithCartSession){
			//this was developed for function shopping_cart_calculate() for best fit
			$a[$n]=$v=&$_SESSION['shopCart'][$cart][$v]; //change #1: merge shopcart data in
			if(!$rdp)$rdp=&$_SESSION['shopCart'][$cart][$n]; //change #2: get root item params
		}
		//this is the "price" of the item just as with any other sale item
		$comparison=$a[$n]['comparison']=($v['SalePrice']>0 ? $v['SalePrice'] : $v['RetailPrice']);
		if($v['BonusItem']){
			$pricingType=$a[$n]['PricingType']=strtolower($v['PricingType']);
			if($pricingType=='free'){
				$a[$n]['ListPriceColumn']=round($v['Quantity'] * $comparison,2);
				$a[$n]['YourPriceColumn']=round(0,2);
			}else if($pricingType=='price'){
				$a[$n]['ListPriceColumn']=round($v['Quantity'] * $comparison,2);
				$a[$n]['YourPriceColumn']=round($v['Quantity'] * $v['PriceValue'],2);
			}else if($pricingType=='percent'){
				$a[$n]['ListPriceColumn']=round($v['Quantity'] * $comparison,2);
				$a[$n]['YourPriceColumn']=round($v['Quantity'] * $comparison * (1 - $v['PriceValue']/100),2);
			}
		}else{
			$pricingType=strtolower($rdp['PricingType']);
			$nonBonusItemsTotal+=round($v['Quantity'] * $comparison,2);
			if($pricingType=='no price change'){
				$a[$n]['YourPriceColumn']=
				$a[$n]['ListPriceColumn']=round($v['Quantity'] * $comparison,2);
			}else if($pricingType=='specific package price'){
				//sum for later use - in this case we don't have the actual price column yet
				$defer=true;
			}else if($pricingType=='auto discount'){
				$a[$n]['ListPriceColumn']=round($v['Quantity'] * $comparison,2);
				$a[$n]['YourPriceColumn']=round($v['Quantity'] * $comparison * (1 - $rdp['PriceValue']/100),2);
			}
		}
	}
	//prn($nonBonusItemsTotal);
	//prn($a);
	if($defer)@$discountRatio=$rdp['PriceValue']/$nonBonusItemsTotal;
	foreach($a as $n=>$v){
		if(!$v['BonusItem'] && $defer){
			$a[$n]['ListPriceColumn']=round($v['Quantity'] * $v['comparison'],2);
			$a[$n]['YourPriceColumn']=round($v['Quantity'] * $v['comparison'] * $discountRatio,2);
		}
		$listTotal +=$a[$n]['ListPriceColumn'];
		$packageTotal +=$a[$n]['YourPriceColumn'];
	}
	if($integrateWithCartSession){
		//change #3: place overall calcs in root item
		$_SESSION['shopCart'][$cart][$idx]['Calculated']=$shopping_cart_calculate_version;
		$_SESSION['shopCart'][$cart][$idx]['listTotal']=number_format($packageTotal,2);
		$_SESSION['shopCart'][$cart][$idx]['packageTotal']=number_format($packageTotal,2);
		$_SESSION['shopCart'][$cart][$idx]['nonBonusItemsTotal']=number_format($nonBonusItemsTotal,2);
		
	}
	//-------------------------------------------------------------------------------------------
	
	
}
?>