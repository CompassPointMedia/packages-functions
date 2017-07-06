<?php
/*

TODO:						(a)=2012-11-30
(a) Add in for AK and HI
(a) test various states and zones
(a) what about UPS ground is free, but they pay the difference for Next Day Air for example
(a) put back in shippingOverridePrice
(a) in introducing the new shipping_object I've got redundant code in the table_1 and also index_01_exe.php - clean this up..
(a) add multiple types - and verify the file is there first i.e. costs_1_6 - email ME if it's not there!!!
(a) add a footnote * you have not selected a state yet.  this will affect shipping charges
(a) have a comparison chart pop-up! and also make sure the handling charge is invisible here, or give options
(a) introduce "I am a residential location" option
(a) res location is variable - use it
(a) for table_1.php - add additional handling charge
(a) enter the true shipments_id values into finan_transactions and have ths show on the invoices - so we can manage shipping from back end
	include confirmation number and etc.
(a) MAKE SURE this is planned well so we can do an API.  All the talk I did with Alan Basinger in Houston, that is something I want available on mine and DO NOT want someone else developing


----------------------------------
currently I have the following well-defined vars:
	shipMethods = array of key=>method; all ship methods I have on the cart
	shipMethod = var AND a field passed to final exe - this is what the customer wants for that sale
	_settings.shippingDefaultMethod [soon to change to shippingMethodDefault
	_settings.shippingMethods = options my CLIENT makes available (a subset of shipMethods)
		* note the "ping" in this case for distinction. $_settings is NEVER extracted

----------------------------------


get rid of definitions and $shipping_module array?
store the shipping_object
	DONE	shipping_object_exact does not use it
	other pages do not use it?

2012-10-30: this replaces function_shipping_module(), creates a visual "shipping object" that fully expresses what the shipping department would need to do.  Eventually I intend to have it consider multiple shipping methods based on product specs (say very large hundredweight items), and which warehouse I can pull the product from in my profile.

2006-01-15: Originally for John Gilruth, this is now shopping cart version 2.00 and I think this one will finally work as a module.  This can be added into RelateBase, with each user getting a settings page which can eventually be folded into a db vs. a hard file

The function shipping_quote() is still under constr. as of this writing.  I have some tools in c/settings/charts/tools which help parse out the ascii files I get from UPS and Fedex.

*/
//English Translation for Shipping Methods

define('SHIPPER_UPS',1); 
define('SHIPPER_FEDEX',2); 
define('SHIPPER_USPS',3);

$shipping_module['allowNoDestinationZip']=true;
$shipping_module['rate_charts_folder']=$DOCUMENT_ROOT.'/c/settings/charts';

define('BASKET_DIGITAL',1);
define('BASKET_PHYSICAL',2);
define('BASKET_MIXED',3);

$basketMakeups=array(
	BASKET_DIGITAL=>'Digital products',
	BASKET_PHYSICAL=>'Shippable products',
	BASKET_MIXED=>'Mixed Digital/Physical products',
);

if(!function_exists('shipping_object')){
if(false)
$shipping_object=array(
	'structure'=>array(
		/* each node represents a "package" */
		array(
			'Shippers_ID'=>n,		/* this matches finan_shippers */
			'Shipmethods_ID'=>n,	/* this matches the system array for shipmethods which are distinct */
			'Warehouses_ID'=>n,		/* 2012-10-29 not implemented yet */ 
			'cost'=>x,				/* shipping cost of the package as a whole */
			'weight'=>y,			/* weight of the package as a whole; this may not be necessary */
			'comment'=>'As needed for this package',
			'items'=>array(
				/* the only condition currently here is set-ness, i.e. the key must match the specified shopCart */
				17=>array(),
				35=>array(),
				
			),
		),
	),
	'cost'=>z,		/* sum of all costs */
	'weight'=>z,	/* sum of all weights */
);
function shipping_object($options=array()){
	/*
	2012-10-29 SF - the purpose of this function is to fully define shipping packages for a cart, irrespective of price points etc.  Think of this as all the objects lying on the floor in grouped bags or boxes, and we declare the price (register/ring-up value), the weight, the shipping cost (our real cost based on selected method), as well as a root sum of all these quantities.  Eventually we'll bring in drop shipping, manufacturer, and warehouse location to interact rules and recommendations with each other.  The final goal: GIVE AS ACCURATE A REFLECTION OF ACTUAL OPERATIONS AS POSSIBLE, and eventually even get confirmation or Tracking numbers on each bag/box.
	
	WE HAVE SEVERAL DESIRED SEPARATION METHODS:
	1. treat everything as its own package ("I will package separately regardless of what Package field says")
	8. package separately all items which allow this in the database
	32.package items together regardless if database instructions
	
	item.Package=-1 means I am a digital or non-physical items - I do not go in a package (but it will go in the "digital package" in the array)
	item.Package=0 means "do not package me together with other items - I go by myself"
	item.Package=1 means "I can be packaged with other items"
	
	here is a results table
				_s[productPackaging]
				1		8		32		1=set indiv. always; 8=group unless item objects; 32=
			  ------------------------
	Package=-1	Z	|	Z	|	Z	 |
	Package= 0  A	|	A	|	B	 |
	Package= 1	A	|	B	|	B	 |
			  ------------------------
	Z: set in a "digital basket" for grouping sake
	A: set in an individual box
	B: add to the group package
	
	** note that for Package=n, this can mean either "ship me with items from the same warehouse" or "ship me with items from the same vendor (like Amazon booksellers)"
	
	** note this is not yet with respect to what warehouse the product is in, if it HAS to be shipped by a specific vendor or method and if that method differs from the others, or if the items in the package are full
	
	*/
	extract($options);
	global $shipping_object, $_s, $whsle, $cart_cnx, $qr, $qx, $fl, $ln, $developerEmail, $fromHdrBugs;
	if($shipping_object['do_not_calculate'])return;
	if(!$defaultCart)$defaultCart=$_SESSION['selectedCart'];
	if(!strlen($_s['productPackaging']))$_s['productPackaging']=8;
	if(!count($_SESSION['shopCart'][$defaultCart]))return;

	$ppr=$_s['productPackaging'];
	if(!$ppr)$ppr=32;

	if(true){ //only method we have for now
		if(count($_SESSION['shopCart'][$defaultCart]))
		foreach($_SESSION['shopCart'][$defaultCart] as $n=>$v){
			//2013-09-25 modified to perfect Package field
			if(!$packageFixed){
				$packageFixed=true;
				$a=q("EXPLAIN finan_items", O_ARRAY,$cart_cnx);
				$hasPackage=false;
				foreach($a as $w){
					if($w['Field']!=='Package')continue;
					if($w['Field']=='Package'){
						if($w['Type']!=='tinyint(1)' || $w['Default']!=(strlen($_s['defaultPackageFieldValue']) ? $_s['defaultPackageFieldValue'] : '1')){
							mail($developerEmail, 'Notice '.$MASTER_USERNAME.':'.end(explode('/',__FILE__)).', line '.__LINE__,get_globals('items.Package field modified'),$fromHdrBugs);
							q("ALTER TABLE `finan_items` CHANGE `Package` `Package` TINYINT(1) NOT NULL DEFAULT '".(strlen($_s['defaultPackageFieldValue']) ? $_s['defaultPackageFieldValue'] : '1')."' COMMENT 'Modified ".date('Y-m-d')."'", $cart_cnx);
							q("UPDATE finan_items SET Package='".(strlen($_s['defaultPackageFieldValue']) ? $_s['defaultPackageFieldValue'] : '0')."'", $cart_cnx);
						}
						$hasPackage=true;
						break;
					}
				}
				if(!$hasPackage){
					mail($developerEmail, 'Notice '.$MASTER_USERNAME.':'.end(explode('/',__FILE__)).', line '.__LINE__,get_globals('items.Package field added'),$fromHdrBugs);
					q("ALTER TABLE `finan_items` ADD `Package` TINYINT(1) NOT NULL DEFAULT '".(strlen($_s['defaultPackageFieldValue']) ? $_s['defaultPackageFieldValue'] : '0')."' COMMENT 'Added ".date('Y-m-d')."' AFTER `PK`, ADD INDEX (`Package`)", $cart_cnx);
				}
			}
			$pkg=q("SELECT Package FROM finan_items WHERE ID=".$v['ID'], O_VALUE, $cart_cnx);
			$generalPrice=
			($v['SalePrice']>0 && $v['SalePrice']< $v['RetailPrice'] ? $v['SalePrice'] : $v['RetailPrice']);
			if($pkg==-1){
				//this is a digital product, service agreement or subscription; relation in shipping_object is undefined!

				if(!($shipping_object['basket_makeup'] & BASKET_DIGITAL))$shipping_object['basket_makeup']+=BASKET_DIGITAL;

				//HOWEVER, for now I count it with the total register price
				$shipping_object['price']+=$v['Quantity']*($whsle ? $v['WholesalePrice'] : $generalPrice);
				continue;
			}else if($ppr==1 || ($ppr==8 && $pkg==0)){
				//set this in a new non-open package
				$shipping_object['structure'][]=array(
					'Shippers_ID'=>0,
					'Shipmethods_ID'=>0,
					'Warehouses_ID'=>0,
					'price'=> $v['Quantity']*($whsle ? $v['WholesalePrice'] : $generalPrice),
					'open'=>false,
					/* 'cost'=>NULL, */
					'weight'=>$v['Quantity'] * ($v['Weight']>0?$v['Weight']:($_s['productMinimumWeight']>0?$_s['productMinimumWeight']:1)),
					'items'=>array(
						/* this shopCart item */
						$n=>array(),
					),
				);
				$shipping_object['price']+=$v['Quantity']*($whsle ? $v['WholesalePrice'] : $generalPrice);
			}else if($ppr==32 || ($ppr==8 && $pkg==1)){
				//set this in the first open package - eventually we'll see if:
				#a. it's from the same warehouse
				#b. it has room (this could get gnarly complex)
				#c. it has weight remaining
				$added=false;
				if(count($shipping_object['structure'])){
					foreach($shipping_object['structure'] as $o=>$w){
						if($w['open']==true){
							//we have no way of closing it!!!!
							$shipping_object['structure'][$o]['items'][$n]=array();
							//add to weight
							$shipping_object['structure'][$o]['weight']+=$v['Quantity'] * ($v['Weight']>0?$v['Weight']:($_s['productMinimumWeight']>0?$_s['productMinimumWeight']:1));
							$shipping_object['structure'][$o]['price']+=$v['Quantity']*($whsle ? $v['WholesalePrice'] : $generalPrice);
							$shipping_object['price']+=$v['Quantity']*($whsle ? $v['WholesalePrice'] : $generalPrice);
							
							//cost will be calculated later
							$added=true;
							break;
						}
					}
				}
				if(!$added){
					$shipping_object['structure'][]=array(
						'Shippers_ID'=>0,
						'Shipmethods_ID'=>0,
						'Warehouses_ID'=>0,
						'price'=>$v['Quantity']*($whsle ? $v['WholesalePrice'] : $generalPrice),
						'open'=>true,
						/* 'cost'=>NULL, */
						'weight'=>$v['Quantity'] * ($v['Weight']>0?$v['Weight']:($_s['productMinimumWeight']>0?$_s['productMinimumWeight']:1)),
						'items'=>array(
							/* this shopCart item */
							$n=>array(),
						),
					);
					$shipping_object['price']+=$v['Quantity']*($whsle ? $v['WholesalePrice'] : $generalPrice);
				}
			}
			if(!($shipping_object['basket_makeup'] & BASKET_PHYSICAL))$shipping_object['basket_makeup']+=BASKET_PHYSICAL;
			$shipping_object['weight']+=$v['Quantity'] * ($v['Weight']>0?$v['Weight']:($_s['productMinimumWeight']>0?$_s['productMinimumWeight']:1));
		}
		//both of these are expressed in ounces
		if($addtlWeight=$_s['shippingBaseWeight'] + $_s['additionalPackageWeight'])$shipping_object['additional_weight_per_package']=$addtlWeight;
		

		if(count($shipping_object['structure'])){
			foreach($shipping_object['structure'] as $n=>$v){
				$thiscost=0;
				if($_s['shippingType'] ==2){
					//fixed shipping price, just return it
					$thiscost=$shipping_object[$n]['cost']+=$_s['shippingBasePrice'];
				}else if($_s['shippingType'] ==3){
					//calculate variable shipping price
					$shipping = $_s['shippingBasePrice'];
					if($_s['shippingProportionMethod']==1){
						//calculate additional based on poundage, get the total weight
						$thiscost=$shipping_object['structure'][$n]['cost']=$_s['shippingPricePer']/16 * ($v['weight'] + $addtlWeight);
					}else{
						//calculate based on price
						$thiscost=$shipping_object['structure'][$n]['cost']=$_s['shippingPricePer'] * $v['price'];
					}
				}else if($_s['shippingType'] ==4){
					$thiscost=$shipping_object['structure'][$n]['cost']=shipping_object_exact(array(
						'shipMethod'=>$GLOBALS['shipMethod'], 
						'zip'=>$GLOBALS['ShippingZip'], 
						/*convert ounces to pounds for the shipping calc*/
						'weight'=>ceil(($v['weight'] + $addtlWeight) / 16), 
						'residential'=>($GLOBALS['residential']?1:NULL),
						'object'=>&$shipping_object['structure'][$n],
					));
				}
				$shipping_object['cost']+=$thiscost;
				$shipping_object['weight']+=$addtlWeight;
			}
		}
	}
}
}

if(!function_exists('explain')){
function explain($line,$command,$options=array()){
	//start, end
	global $explainX;
	@extract($options);
	if($command=='start'){
		$explainX['status']='start';
		ob_start();
		echo 'Line '.$line.': start'.(strtolower($mode)=='html' ? '<br />' : "\n");
		return;
	}else if($command=='end'){
		echo 'Line '.$line.': end'.(strtolower($mode)=='html' ? '<br />' : "\n");
		$out=ob_get_contents();
		ob_end_clean();
		$explainX['status']='end';
		$explainX['explained']++;
		
		if($explainX['output_limit'] && $explainX['output_limit']<$explainX['explained'])return;
		
		global $developerEmail,$fromHdrBugs,$MASTER_USERNAME;
		mail($developerEmail, 'Error in '.$MASTER_USERNAME.':'.end(explode('/',__FILE__)).', line '.__LINE__,get_globals($out."\n\n"),$fromHdrBugs);
		return;
	}
	if($explainX['status']!='start')return;
	echo 'Line '.$line.': ';
	if(is_array($command)){
		echo '['.(is_array($options) ? $options['name'] : $options).']';
		print_r($command);
	}else{
		echo $command.(strtolower($mode)=='html' ? '<br />' : "\n");
	}
}
}

function shipping_object_exact($options=array()){
	global $_s, $developerEmail, $fromHdrBugs, $shipping_module;
	extract($options);
	if(!$shipMethod)$shipMethod=$_s['shippingDefaultMethod'];
	if(!$zip)$zip=$_s['median_destination_zip'];
	if(!$weight)$weight=1; #pound
	
	$zip=substr($zip,0,5); //ignore +4
	
	//2012-11-30: note first use of passing object by reference in a long time
	$options['object']['exact_process']=1;
			
	explain(__LINE__,'start');//explain
	
	if(!$zip){
		explain(__LINE__,'no zip code, using mine');
		//be graceful
		mail($developerEmail, 'Error in '.$MASTER_USERNAME.':'.end(explode('/',__FILE__)).', line '.__LINE__,get_globals($err='no median dest. zip code found for this cart; needs to be fixed immediately'),$fromHdrBugs);
		$zip=78666; //san marcos texas
	}
	$options['object']['zip']=$zip;

	explain(__LINE__,"shipMethod=$shipMethod");
	$options['object']['shipMethod']=$shipMethod;
	$shipper=$shipping_module['vendors'][$shipMethod];
	explain(__LINE__,"shipper=$shipper");
	$options['object']['shipper']=$shipper;
	
	require($_SERVER['DOCUMENT_ROOT'].'/c/settings/charts/master_zone_list.php');
	//the master zone list states the zone charts for a shipper and origin zip code - $zones internal to this function

	if(count($zones[$shipper]))
	foreach($zones[$shipper] as $n=>$v){
		if($v[0] <= $_s['origin_zip'] && $v[1] >= $_s['origin_zip']){
			explain(__LINE__,"including shipper and v.0={$v[0]}");
			require($_SERVER['DOCUMENT_ROOT'].'/c/settings/charts/zones_'.$shipper.'_'.$v[0].'.php');
			break;
		}
	}

	//get the row of the zone tables
	if(count($zRange))
	foreach($zRange as $n=>$v){
		if($v[count($v)==2 ? 1 : 0] < $zip) continue;
		if($v[0]==$zip || $v[1]>=$zip ){
			$row=$n;
			explain(__LINE__,"n=$n; v=$v; row=$n and zip was $zip");
			break;
		}
	}

	//from here we break off since the charts are different
	switch(true){
		case $shipper==SHIPPER_UPS:
			explain(__LINE__,"shipper is UPS");
			if(!($zone=$zRow[$row][$shipMethod])){
				//no zone listed for this service for dest zip code
				//let's try the zone for the median zip
				$zip=$_s['median_destination_zip'];
				mail($developerEmail,'error on file '.__FILE__.', line '.__LINE__,get_globals(), $fromHdrBugs);
				//get the row of the zone tables
				foreach($zRange as $n=>$v){
					if($v[count($v)==2 ? 1 : 0] < $zip) continue;
					if($v[0]==$zip || $v[1]<=$zip ){
						$row=$n;
						break;
					}
				}
				if(!($zone=$zRow[$row][$shipMethod])){
					//no options left
					mail($developerEmail, 'Error in '.$MASTER_USERNAME.':'.end(explode('/',__FILE__)).', line '.__LINE__,get_globals($err='yae (yet another email)'),$fromHdrBugs);
				}
			}
			$zone=ltrim($zone,'0');	
			$options['object']['zone']=$zone;		
			explain(__LINE__,"zone=$zone");
			//rate charts of format prices_1_2.php WHERE 1=shipper and 2=service type'
			ob_start();
			include($_SERVER['DOCUMENT_ROOT'].'/c/settings/charts/costs_'.$shipper.'_'.$shipMethod.'.php');
			$err=ob_get_contents();
			ob_end_clean();
			if($err){
				mail($developerEmail, 'Error in '.$MASTER_USERNAME.':'.end(explode('/',__FILE__)).', line '.__LINE__,get_globals('yae (yet another email)'.$err),$fromHdrBugs);
			}
			// 150# for UPS 
			explain(__LINE__,$cost,'cost');
			$options['object']['final']=min(ceil($weight),count($cost)).':'.$zone;
			$amt=$cost[min(ceil($weight),count($cost))][$zone];
			if(ceil($weight)>count($cost)){
				//email somebody, this is an error state
				mail($developerEmail, 'Error in '.$MASTER_USERNAME.':'.end(explode('/',__FILE__)).', line '.__LINE__,get_globals($err='order weight is above poundage listed for shipping type'),$fromHdrBugs);
				$options['object']['err'][]=$err;
			}
			explain(__LINE__,'end');
			
			if($_s['shippingAssumeResidential'] || $retail){
				$amt+=$shippers[$shipper]['residential_delivery'];
			}
			if($n=$shippers[$shipper]['fuel_surcharge'])$amt*=(1 + $n);
			$amt=round($amt,2);
			
			return $amt;
		break;
		case $shipper==SHIPPER_FEDEX:
			//not developed
		break;
		case $shipper==SHIPPER_USPS:
			//not developed
		break;
	}
	explain(__LINE__,'end');
}
?>