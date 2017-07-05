<?php
/***
2006-01-15: Developed for John Gilruth, this is now shopping cart version 2.00 and I think this one will finally work as a module.  This can be added into RelateBase, with each user getting a settings page which can eventually be folded into a db vs. a hard file

The function shipping_quote() is still under constr. as of this writing.  I have some tools in c/settings/charts/tools which help parse out the ascii files I get from UPS and Fedex.

***/
//English Translation for Shipping Methods
#00 series = UPS
$shipping_module['shipMethodArray'][1]="UPS Ground";
$shipping_module['shipMethodArray'][2]="UPS 3 Day Select";
$shipping_module['shipMethodArray'][3]="UPS 2nd Day Air";
$shipping_module['shipMethodArray'][4]="UPS 2nd Day Air AM";
$shipping_module['shipMethodArray'][5]="UPS Next Day Air Saver";
$shipping_module['shipMethodArray'][6]="UPS Next Day Air";
#20 series = FEDEX
$shipping_module['shipMethodArray'][20]="Fedex Second Day Air";
#40 series = USPS PRODUCTS
$shipping_module['shipMethodArray'][40] = "USPS Priority Mail";
#60 series and > not defined
define('SHIPPER_UPS',1); define('SHIPPER_FEDEX',2); define('SHIPPER_USPS',3);
$shipping_module['vendors'][1]=1; //UPS
$shipping_module['vendors'][2]=1;
$shipping_module['vendors'][3]=1;
$shipping_module['vendors'][4]=1;
$shipping_module['vendors'][5]=1;
$shipping_module['vendors'][6]=1;
$shipping_module['vendors'][20]=2; //FEDEX
$shipping_module['vendors'][40]=3; //USPS

$shipping_module['allowNoDestinationZip']=true;
$shipping_module['rate_charts_folder']=$_SERVER['DOCUMENT_ROOT'].'/c/settings/charts';

function shipping_module($cost, $shipMethod='', $orderWeight='', $shipZip=''){
	global $_settings, $shipping_module;
	@extract($shipping_module);

	if( strlen($_settings['shippingFreePrice']) && $_settings['shippingFreePrice']>0 && $cost>=$_settings['shippingFreePrice']){
		return 0.00;
	}else{
		if($_settings['shippingType'] ==1){
			return 0.00;
		}else if($_settings['shippingType'] ==2){
			//fixed shipping price, just return it
			return $_settings['shippingBasePrice'];
		}elseif($_settings['shippingType'] ==3){
			//calculate variable shipping price
			$shipping = $_settings['shippingBasePrice'];
			if($_settings['shippingProportionMethod']==1){
				//calculate additional based on poundage, get the total weight
				if(is_array($_SESSION['shopCart'])){
					foreach($_SESSION['shopCart'] as $n => $v){
						if($v[1]>0){$orderWeight += $v[1] * $v['Weight'];}
					}
				}
				$shipping += ($_settings['shippingPricePer']/16) * $orderWeight;
			}else{
				//calculate based on price
				$shipping += $_settings['shippingPricePer']*$cost;
			}
			return $shipping;
		}else if($_settings['shippingType'] ==4){
			return shipping_exact($shipMethod, $shipZip, $orderWeight);
		}
	}
}
function shipping_exact($shipMethod, $zip='', $orderWeight='', $residential=''){
	global $shipping_module, $_settings, $developerEmail, $fromHdrBugs;
	@extract($shipping_module);

	if(!$_settings['origin_zip']) exit('No system origin zip specified');
	if((!$zip && !$allowNoDestinationZip) || ($allowNoDestinationZip && !$_settings['median_destination_zip'])) exit('Blank destination zip codes not allowed, or no median destination zip defined');
	if(!$zip)$zip=$_settings['median_destination_zip'];
	$shipper=$vendors[$shipMethod];
	$rate_charts_folder=preg_replace('/\/$/','',$rate_charts_folder);
	require($rate_charts_folder.'/master_zone_list.php');
	//the master zone list states the zone charts for a shipper and origin zip code - $zones internal to this function
	if(count($zones[$shipper]))
	foreach($zones[$shipper] as $n=>$v){
		if($v[0] <= $_settings['origin_zip'] && $v[1] >= $_settings['origin_zip']){
			require($rate_charts_folder.'/zones_'.$shipper.'_'.$v[0].'.php');
			break;
		}
	}

	//get the row of the zone tables
	if(count($zRange))
	foreach($zRange as $n=>$v){
		if($v[count($v)==2 ? 1 : 0] < $zip) continue;
		if($v[0]==$zip || $v[1]<=$zip ){
			$row=$n;
			break;
		}
	}
	//from here we break off since the charts are different
	switch(true){
		case $shipper==SHIPPER_UPS:
			if(!($zone=$zRow[$row][$shipMethod])){
				//no zone listed for this service for dest zip code
				//let's try the zone for the median zip
				$zip=$_settings['median_destination_zip'];
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
				}
			}
			//rate charts of format prices_1_2.php WHERE 1=shipper and 2=service type
			require($rate_charts_folder.'/costs_'.$shipper.'_'.$shipMethod.'.php');
			if($orderWeight<=count($cost)){ // 150# for UPS 
				$shippingCost=$cost[ceil($orderWeight)][$zone];
			}else{
				//unable to determine cost
			}
			return $shippingCost;
		break;
		case $shipper==SHIPPER_FEDEX:
			//not developed
		break;
		case $shipper==SHIPPER_USPS:
			//not developed
		break;
	}
}
?>