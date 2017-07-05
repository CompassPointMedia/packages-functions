<?php
$functionVersions['super_SKU']=1.00;
function super_SKU($SuperSKUs){
	/*
	2006-12-14 Takes an array of Super SKUs and returns a meaningful array which can be used for dropdown lists and to select the price.  This requires an n-dimensional array as follows:
	1. the cube must not have any missing nodes or "holes"
	2. only one dimension of the cube can (at least ideally) change the price.  I can have Sm, Med, Large on one axis, Red Yellow and Green on another, and Wool, Crochet and Knit on another - only Sm, Med, and Large can have an effect on the price. IOW, a Small Sweater can have 3 x 3 = 9 forms and they all cost the same.
	
	
	*/
	global $qr, $ln, $fl;
	if(!is_array($SuperSKUs)){
		if(!strlen($SuperSKUs))return;
		$x=$SuperSKUs;
		unset($SuperSKUs);
		$SuperSKUs[0]=$x;
	}
	if(!count($SuperSKUs))return;
	foreach($SuperSKUs as $SuperSKU){
		if($productGroup=q("SELECT 
			b.ID AS SchemaID,
			b.Name AS SchemaName,
			b.Description AS SchemaDescription,
			b.*,
			a.*
			FROM finan_items a LEFT JOIN finan_items_schemas b ON  a.Schemas_ID = b.ID WHERE SuperSKU='$SuperSKU' /* how do we best sort this? */", O_ARRAY)){
			$j=0;
			foreach($productGroup as $v){
				$j++;
				if($j==1){
					$products[$SuperSKU]['params']['GPPVersion']=$v['GPPVersion'];
					$products[$SuperSKU]['params']['GPPSchema']=$v['SchemaID'];
				}
				//parse the schema
				if(!preg_match('/'.$v['Schema'].'/'.(!$caseSensitiveSKU ? 'i' : ''),$v['SKU'],$b)){
					//here we'd want to mail an administrator on a mismatch
					continue;
				}
				for($i=1;$i<=count($b)-1;$i++){
					//populate the attributes if something specified  for this section of the sku
					if($v['Title_'.$i]){
						$b[$i]=str_replace('-','',$b[$i]);
						if(!isset($products[$SuperSKU]['attributes'][$i][$b[$i]]))
							$products[$SuperSKU]['attributes'][$i][$b[$i]]=$b[$i]; //initially synch name/value pair
						if($v['PriceDeltaAttribute']==$i){
							$products[$SuperSKU]['params']['priceDeltaAttribute']=$i;
							if(!isset($products[$SuperSKU]['stats']['priceDelta'][$b[$i]])){
								$products[$SuperSKU]['params']['priceDelta'][$b[$i]]['RetailPrice']=$v['UnitPrice'];
								$products[$SuperSKU]['params']['priceDelta'][$b[$i]]['WholesalePrice']=$v['WholesalePrice'];
							}
						}
					}
				}
			}
			//now, fill in the values, this is a quick fix
			foreach($products[$SuperSKU]['attributes'] as $idx=>$reflexpairs){
				//$v represents the last product pulled
				if($v['Title_'.$idx])
					$products[$SuperSKU]['params']['attributeQualities'][$idx]['title']=$v['Title_'.$idx];
				if($v['Description_'.$idx])
					$products[$SuperSKU]['params']['attributeQualities'][$idx]['description']=$v['Description_'.$idx];
				if(!$v['Label_'.$idx]){
					//the label is implicit in the SKU itself
					unset($products[$SuperSKU]['params']['attributeQualities'][$idx]['valueLookup']);
					continue;
				}else{
					$products[$SuperSKU]['params']['attributeQualities'][$idx]['valueLookup']=true;
					//we have two modes of doing this:
					#1 from a lookup table, PK must be called ID and must contain the values in the SKU:
					//parse the table
					$table=preg_replace('/\.[^.]+$/','',$v['Label_'.$idx]);
					foreach($reflexpairs as $o=>$w){
						$sql="SELECT ".$v['Label_'.$idx]." FROM $table WHERE ID='$o'";
						if($dbvalue=q("SELECT ".$v['Label_'.$idx]." FROM $table WHERE ID='$o'", O_VALUE)){
							$products[$SuperSKU]['attributes'][$idx][$o]=$dbvalue;
						}else{
							//this keeps the label the same as the value
							//email administrator if desired
							$products[$SuperSKU]['params']['attributeQualities'][$idx]['missingLabels'][]=$o;
						}
					}
					
					#2 from a field in the product itself - not developed
	
				}
			}
			$products[$SuperSKU]['products']=$productGroup;
		}
	}
	return $products;
}
?>