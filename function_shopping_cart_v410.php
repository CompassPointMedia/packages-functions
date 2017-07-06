<?php
/*
2013-02-04
* look for javascript function buy() in coding esp. cpm190.options_v200.php; 2nd time using
* 2nd time passing a comma-separated list of IDs was in KG for focus page -
	NOTE string can now allow diverse quantities [ID=34,37,825:1,15:5,7:2.5]
* A new parameter/flag to be passed:
	grouped=0 - these items are not related and should not be grouped
	grouped=1 - these items should be grouped using _SESSION.shopCartSubItems for organized display on the shopping cart table, however no discount calculations are in effect (it is also likely these are not a "package" as defined in finan_ItemsItems)
	grouped=64 - these items are a package
* Another paramter to be passed if desired: key_item=[parent ID of the group leader]

2012-06-02
	* starting to come back to life here
	* allow comma-separated lists of items for multi-adding [ID=34,37,825,15,7]
	
2008-10-01
----------
* integrated shopping_cart_calculate() function using the "Tasmanian Devil" code used in ecom_flex1_003.php - we can now modify the cart array by reference, and only need call the function one time normally
* improved side-array session.shopCartSubItems to be easier to traverse

2008-09-24
----------
this page will eventually need a way to sum shopping carts and present shopping cart data, and calculate shipping
*/
if(!defined('GROUP_NONE')){
	define('GROUP_NONE',0);
	define('GROUP_MINIMUM',1);
	define('GROUP_MAXIMUM',64);
}

$functionVersions['shopping_cart']=4.00;
function shopping_cart($ID, $qty=1, $options=array()){
	//2012-06-02: allow for passage of multiple products simultaneously
	if(strstr($ID,',')){
		$a=explode(',',$ID);
		//2013-02-04: set flag that we are in a comma-separated group of items
		if(!isset($options['grouped']))$options['grouped']=GROUP_MINIMUM;
		foreach($a as $ID){
			$ID=explode(':',$ID);
			shopping_cart($ID[0],$ID[1]?$ID[1]:$qty,$options);
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
		7 => 'Shipmethods_ID',
		8 => 'Taxable',
	);
	if(!isset($_settings['wholesalePriceField']))$_settings['wholesalePriceField']='WholesalePrice';
	if(!isset($_settings['retailPriceField']))$_settings['retailPriceField']='UnitPrice';
	if(!isset($_settings['salePriceField']))$_settings['salePriceField']='UnitPrice2';
	
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
	if(!$Idx){
		@$Idx=max(array_keys($shopCart))+1;
		$shopping_cart['lastIdx']=$Idx;
		if($options['key_item']){
			$shopping_cart['lastKeyIdx']=$Idx;
		}else{
			#unset($shopping_cart['lastKeyIdx']);
		}
	}else{
		unset($shopping_cart['lastIdx'] /*, $shopping_cart['lastKeyIdx']*/);
	}

	//price structure default fields
	$upw=(isset($OverridePriceWholesale) ? "'".$OverridePriceWholesale."'" : $_settings['wholesalePriceField']); //normally WholesalePrice
	$up1=(isset($OverridePrice) ? "'".$OverridePrice."'" : $_settings['retailPriceField']); //normally UnitPrice
	$up2=(isset($OverridePriceSale) ? "'".$OverridePriceSale."'" : $_settings['salePriceField']); //normally UnitPrice2
	
	/* this query is also used in ecom_flex_v200a and on */
	if(!($item=q("SELECT i.".implode(', i.',$productTable).",
		i.ID AS SystemID, 
		i.$upw AS WholesalePrice, 
		i.$up1 AS RetailPrice, 
		i.$up2 AS SalePrice,
		p.Items_ID AS IsPackage,
		p.OverallDescription,
		p.OverallLongDescription,
		p.PricingType,
		p.PriceValue,
		ii.ChildItems_ID,
		ii.Idx,
		ii.Quantity,
		ii.BonusItem,
		ii.PricingType AS ChildPricingType,
		ii.PriceValue AS ChildPriceValue,
		ii.OverrideName,
		ii.OverrideDescription,
		ii.OverrideLongDescription
		FROM 
		finan_items i LEFT JOIN finan_items_packages p ON i.ID=p.Items_ID
		LEFT JOIN finan_ItemsItems ii ON i.ID=ii.ParentItems_ID
		WHERE i.ID=$ID ORDER BY ii.Idx, IF(ii.BonusItem, 2,1), IF(ii.PricingType='Free',2,1)", O_ARRAY))){
		prn($qr);
		error_alert('fail');
		return false;
	}
	
	//------------------------ ## NEW CODING ## ----------------------
	
	//build array
	$shopCart[$Idx]=$item[1];
	unset(
		$shopCart[$Idx]['ChildItems_ID'],
		$shopCart[$Idx]['Idx'],
		$shopCart[$Idx]['Quantity'],
		$shopCart[$Idx]['BonusItem'],
		$shopCart[$Idx]['ChildPricingType'],
		$shopCart[$Idx]['ChildPriceValue'],
		$shopCart[$Idx]['OverrideName'],
		$shopCart[$Idx]['OverrideDescription'],
		$shopCart[$Idx]['OverrideLongDescription']
	);
	$shopCart[$Idx]['ID']=$item[1]['SystemID'];
	$shopCart[$Idx]['Quantity']+=$qty; //this may already be there
	
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
		if(
			isset($item[1][$v]) && 
			(!isset($shopCart[$Idx][$v]) || isset($GLOBALS[$v.'_PREFIX']) || isset($GLOBALS[$v.'_SUFFIX']))
		)$shopCart[$Idx][$v]=$GLOBALS[$v.'_PREFIX'].$item[1][$v].$GLOBALS[$v.'_SUFFIX'];
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
		//save root item index
		$packageIdx=$Idx;
		
		//add the other items
		if($a=q("SELECT 
			/* --- productTable fields here --- */
			i2.Name, 
			i2.SKU, 
			i2.Description, 
			i2.LongDescription,
			i2.Weight,
			i2.ManufacturerSKU,
			i2.Shipmethods_ID,
			i2.Taxable,
			i2.ID AS SystemID,
			i2.ID,
			i2.WholesalePrice, 
			i2.UnitPrice AS RetailPrice, 
			i2.UnitPrice2 AS SalePrice, 
			ii.Idx,
			ii.Quantity, 
			ii.BonusItem, 
			ii.PricingType AS ChildPricingType, 
			ii.PriceValue AS ChildPriceValue,
			ii.OverrideName, 
			ii.OverrideDescription, 
			ii.OverrideLongDescription 
			FROM finan_ItemsItems ii JOIN finan_items i2 ON ii.ChildItems_ID=i2.ID WHERE ii.ParentItems_ID='$ID' ORDER BY ii.Idx, IF(ii.BonusItem,2,1), IF(ii.PricingType='Free',2,1), i2.Category, i2.SubCategory", O_ARRAY)){
			//precalc prices and costs
			$rdp=$item[1];
			//------------------------- codeblock "Tasmanian Devil" ----------------------------------
			//2013-04-24: new coding - this correctly calculates any combination
			$setPrices=$scalable=$scalablePrices=$scaledPrices=$lastPrice=$listTotal=$packageTotal=0;
			$i=0;
			$pricingType=strtolower($rdp['PricingType']);
			foreach($a as $n=>$v){
				$a[$n]['SubItem']=true;
				$comparison=$a[$n]['comparison']=($v['SalePrice']>0 ? $v['SalePrice'] : $v['RetailPrice']);
				$childPricingType=$a[$n]['ChildPricingType']=strtolower($v['ChildPricingType']);
				if($pricingType=='no price change'){
					if($v['BonusItem']){
						//------------ cblock 1 -------------
						if($childPricingType=='free'){
							$a[$n]['ListPriceColumn']=round($v['Quantity'] * $comparison,2);
							$a[$n]['YourPriceColumn']=round(0,2);
							$listTotal+=$a[$n]['ListPriceColumn'];
							$packageTotal+=0;
						}else if($childPricingType=='price'){
							$a[$n]['ListPriceColumn']=round($v['Quantity'] * $comparison,2);
							$a[$n]['YourPriceColumn']=round($v['Quantity'] * $v['ChildPriceValue'],2);
							$listTotal+=$a[$n]['ListPriceColumn'];
							$packageTotal+=$a[$n]['YourPriceColumn'];
						}else if($childPricingType=='percent'){
							$a[$n]['ListPriceColumn']=round($v['Quantity'] * $comparison,2);
							$a[$n]['YourPriceColumn']=round($v['Quantity'] * $comparison * (1 - $v['ChildPriceValue']/100),2);
							$listTotal+=$a[$n]['ListPriceColumn'];
							$packageTotal+=$a[$n]['YourPriceColumn'];
						}
						$setPrices+=$a[$n]['YourPriceColumn'];
						//------------------------------------
					}else{
						//
						$a[$n]['ListPriceColumn']=round($v['Quantity'] * $v['RetailPrice'],2);
						$a[$n]['YourPriceColumn']=round($v['Quantity'] * ($v['SalePrice']>0?$v['SalePrice']:$v['RetailPrice']),2);
						$listTotal+=$a[$n]['ListPriceColumn'];
						$packageTotal+=$a[$n]['YourPriceColumn'];
						$scaledPrices+=$a[$n]['YourPriceColumn'];
					}
				}else if($pricingType=='specific package price'){
					if($v['BonusItem']){
						//these are NOT adjusted based on the package price
						//------------ cblock 1 -------------
						if($childPricingType=='free'){
							$a[$n]['ListPriceColumn']=round($v['Quantity'] * $comparison,2);
							$a[$n]['YourPriceColumn']=round(0,2);
							$listTotal+=$a[$n]['ListPriceColumn'];
							$packageTotal+=0;
						}else if($childPricingType=='price'){
							$a[$n]['ListPriceColumn']=round($v['Quantity'] * $comparison,2);
							$a[$n]['YourPriceColumn']=round($v['Quantity'] * $v['ChildPriceValue'],2);
							$listTotal+=$a[$n]['ListPriceColumn'];
							$packageTotal+=$a[$n]['YourPriceColumn'];
						}else if($childPricingType=='percent'){
							$a[$n]['ListPriceColumn']=round($v['Quantity'] * $comparison,2);
							$a[$n]['YourPriceColumn']=round($v['Quantity'] * $comparison * (1 - $v['ChildPriceValue']/100),2);
							$listTotal+=$a[$n]['ListPriceColumn'];
							$packageTotal+=$a[$n]['YourPriceColumn'];
						}
						$setPrices+=$a[$n]['YourPriceColumn'];
						//------------------------------------
					}else{
						$scalable++;
						$scalablePrices+=round($v['Quantity'] * $comparison,2);
						continue;//calculated later
					}
				}
			}
			if($pricingType=='specific package price'){
				$rootPrice=($rdp['SalePrice']>0?$rdp['SalePrice']:$rdp['RetailPrice']);
				foreach($a as $n=>$v){
					if($v['BonusItem'])continue;
					$i++;
					$comparison=$a[$n]['comparison']=($v['SalePrice']>0 ? $v['SalePrice'] : $v['RetailPrice']);
					$a[$n]['ListPriceColumn']=round($v['Quantity'] * $comparison,2);
					$listTotal+=$a[$n]['ListPriceColumn'];
					if($i<$scalable){
						$a[$n]['YourPriceColumn']=round(
						/*your price = comparison price * ( package price - setPrices / scalablePrices )*/
						$v['Quantity'] * $comparison * ($rootPrice - $setPrices)/$scalablePrices
						,2);
						$packageTotal+=$a[$n]['YourPriceColumn'];
						$scaledPrices+=$a[$n]['YourPriceColumn'];
					}else{
						//last item - whatever is left
						$lastPrice = $a[$n]['YourPriceColumn']=$rootPrice - $setPrices - $scaledPrices;
						$packageTotal+=$a[$n]['YourPriceColumn'];
					}
				}
			}
			//-------------------------------------------------------------------------------------------
			//add to shopping cart
			foreach($a as $n=>$v){
				$Idx++;
				$shopCart[$Idx]=$v;
				//true for now, later may contain more information
				$_SESSION['shopCartSubItems'][$cart][$packageIdx][$Idx]=1; 
			}
			//modify root item's RetailPrice and SalePrice
			$shopCart[$packageIdx]['RetailPrice']=$listTotal;
			$shopCart[$packageIdx]['RetailPrice']=$packageTotal;
		}else{
			//mail
			mail($developerEmail,'no package items!',get_globals(),$fromHdrBugs);
			?><script language="javascript" type="text/javascript">
			setTimeout('window.location="/";',4000);
			alert('No items in this package! Staff have been alerted.  Redirecting to home page');
			</script><?php
		}
	}
	
	if($grouped && !$key_item){
		//set up relationship table
		$_SESSION['shopCartSubItems'][$cart][$shopping_cart['lastKeyIdx']][$Idx]=1;
	}

	$_SESSION['shopCartModified']=time();
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


	//------------------------- codeblock "Tasmanian Devil" ----------------------------------
	//2013-04-24: new coding - this correctly calculates any combination
	$setPrices=$scalable=$scalablePrices=$scaledPrices=$lastPrice=$listTotal=$packageTotal=0;
	$i=0;
	$pricingType=strtolower($rdp['PricingType']);
	foreach($a as $n=>$v){
		$comparison=$a[$n]['comparison']=($v['SalePrice']>0 ? $v['SalePrice'] : $v['RetailPrice']);
		$childPricingType=$a[$n]['PricingType']=strtolower($v['PricingType']);
		if($pricingType=='no price change'){
			if($v['BonusItem']){
				//------------ cblock 1 -------------
				if($childPricingType=='free'){
					$a[$n]['ListPriceColumn']=round($v['Quantity'] * $comparison,2);
					$a[$n]['YourPriceColumn']=round(0,2);
					$listTotal+=$a[$n]['ListPriceColumn'];
					$packageTotal+=0;
				}else if($childPricingType=='price'){
					$a[$n]['ListPriceColumn']=round($v['Quantity'] * $comparison,2);
					$a[$n]['YourPriceColumn']=round($v['Quantity'] * $v['PriceValue'],2);
					$listTotal+=$a[$n]['ListPriceColumn'];
					$packageTotal+=$a[$n]['YourPriceColumn'];
				}else if($childPricingType=='percent'){
					$a[$n]['ListPriceColumn']=round($v['Quantity'] * $comparison,2);
					$a[$n]['YourPriceColumn']=round($v['Quantity'] * $comparison * (1 - $v['PriceValue']/100),2);
					$listTotal+=$a[$n]['ListPriceColumn'];
					$packageTotal+=$a[$n]['YourPriceColumn'];
				}
				$setPrices+=$a[$n]['YourPriceColumn'];
				//------------------------------------
			}else{
				//
				$a[$n]['ListPriceColumn']=round($v['Quantity'] * $v['RetailPrice'],2);
				$a[$n]['YourPriceColumn']=round($v['Quantity'] * ($v['SalePrice']>0?$v['SalePrice']:$v['RetailPrice']),2);
				$listTotal+=$a[$n]['ListPriceColumn'];
				$packageTotal+=$a[$n]['YourPriceColumn'];
				$scaledPrices+=$a[$n]['YourPriceColumn'];
			}
		}else if($pricingType=='specific package price'){
			if($v['BonusItem']){
				//these are NOT adjusted based on the package price
				//------------ cblock 1 -------------
				if($childPricingType=='free'){
					$a[$n]['ListPriceColumn']=round($v['Quantity'] * $comparison,2);
					$a[$n]['YourPriceColumn']=round(0,2);
					$listTotal+=$a[$n]['ListPriceColumn'];
					$packageTotal+=0;
				}else if($childPricingType=='price'){
					$a[$n]['ListPriceColumn']=round($v['Quantity'] * $comparison,2);
					$a[$n]['YourPriceColumn']=round($v['Quantity'] * $v['PriceValue'],2);
					$listTotal+=$a[$n]['ListPriceColumn'];
					$packageTotal+=$a[$n]['YourPriceColumn'];
				}else if($childPricingType=='percent'){
					$a[$n]['ListPriceColumn']=round($v['Quantity'] * $comparison,2);
					$a[$n]['YourPriceColumn']=round($v['Quantity'] * $comparison * (1 - $v['PriceValue']/100),2);
					$listTotal+=$a[$n]['ListPriceColumn'];
					$packageTotal+=$a[$n]['YourPriceColumn'];
				}
				$setPrices+=$a[$n]['YourPriceColumn'];
				//------------------------------------
			}else{
				$scalable++;
				$scalablePrices+=round($v['Quantity'] * $comparison,2);
				continue;//calculated later
			}
		}
	}
	if($pricingType=='specific package price'){
		$rootPrice=($rdp['SalePrice']>0?$rdp['SalePrice']:$rdp['RetailPrice']);
		foreach($a as $n=>$v){
			if($v['BonusItem'])continue;
			$i++;
			$comparison=$a[$n]['comparison']=($v['SalePrice']>0 ? $v['SalePrice'] : $v['RetailPrice']);
			$a[$n]['ListPriceColumn']=round($v['Quantity'] * $comparison,2);
			$listTotal+=$a[$n]['ListPriceColumn'];
			if($i<$scalable){
				$a[$n]['YourPriceColumn']=round(
				/*your price = comparison price * ( package price - setPrices / scalablePrices )*/
				$v['Quantity'] * $comparison * ($rootPrice - $setPrices)/$scalablePrices
				,2);
				$packageTotal+=$a[$n]['YourPriceColumn'];
				$scaledPrices+=$a[$n]['YourPriceColumn'];
			}else{
				//last item - whatever is left
				$lastPrice = $a[$n]['YourPriceColumn']=$rootPrice - $setPrices - $scaledPrices;
				$packageTotal+=$a[$n]['YourPriceColumn'];
			}
		}
	}
	//-------------------------------------------------------------------------------------------
	
	
}
?>