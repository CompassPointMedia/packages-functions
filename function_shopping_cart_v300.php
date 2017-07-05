<?php
$functionVersions['shopping_cart']=3.00;
function shopping_cart($ID, $qty=1, $options=array()){
	/***
	2009-02-06: added the ability to pass a custom sku, custom sku prefix or suffix, and mods on description so that a product can be many products (say by adding -01, -02 etc. after the SKU).  The actual ID remains the same and it what is in Items_ID in finan_transactions.  ONE DRAWBACK is that the 3.x shopping cart array is still indexed by ID so you CANNOT have two virtual flavors of ths same product.
	Also removed technicalEmail var in favor of developerEmail
	
	2009-01-15: FIXED a nasty little bug where productTable didn't have a comma before it
	2007-01-10: added addCartMode=grouped for GPP 0.1.  For docs on this see notes in admin-local.  Basically this glues together parameters passed and finds the selected product, and sets that ID, then continues as before.
	2006-03-02: cut out the ugly fat of previous shopping carts, starting to gel on the protocol and rules.  
	***/
	global $fl, $ln, $qr, $productTable, $_settings, $productMod;
	
	
	global $addCartMode, $GPPSchema;
	if(is_array($ID)){
		foreach($ID as $n=>$v){
			//don't add to cart, not all attributes were selected
			if(!trim($v))return false;
		}
		if(!$GPPSchema || $addCartMode!=='grouped'){
			shopping_cart_alert('ID is an array. Either GPPSchema or addCartMode=grouped not passed');
		}
		if(!($a=q("SELECT * FROM finan_items_schemas WHERE ID='$GPPSchema'", O_ROW))){
			shopping_cart_alert('No such GPPSchema value ('.$GPPSchema.')');
		}
		$b=preg_split('/\([^)]+\)/',$a['Schema']);
		foreach($b as $v){
			$i++;
			$string.=$v.$ID[$i];
		}
		//get the product now
		if($ids=q("SELECT ID FROM finan_items WHERE SKU REGEXP('$string')", O_COL)){
			if(count($ids)<>1){
				shopping_cart_alert('Multiple products found for this criteria ('.$string.')');
			}else{
				foreach($ids as $ID){
					//we have the ID now
					break;
				}
			}
		}else{
			shopping_cart_alert('No product found from this criteria ('.$string.')');
		}
	}
	
	//localize the shopping cart
	$shopCart = $_SESSION['shopCart'];
	//see if the shopping cart has that value already
	if($shopCart[$ID]){
		//just increment by quantity
		$shopCart[$ID][1]+=$qty;
	}else{
		$up=$_settings['retailPriceField'];
		$up2=$_settings['wholesalePriceField'];
		$up3=$_settings['salePriceField'];
		$sql="SELECT ID AS SystemID, $up AS RetailPrice, $up2 AS WholesalePrice, $up3 AS SalePrice";
		$sql.=', '.implode(', ',$productTable);
		$sql.=" FROM finan_items WHERE ID='$ID'";
		$a=q($sql, O_ROW);
		$shopCart[$ID][1]=$qty;
		foreach($a as $n=>$v){
			if($n=='SystemID')continue;
			//customize all variables including SKU
			if($o=$GLOBALS[$n.'_OVERRIDE']){
				//override the parameter entirely
				if(strstr($o,'var:')){
					//a global reference to a variable
					$var=explode(':',$o);
					$v=$productMod[$var[1]];
				}else{
					//the actual string passed
					$v=stripslashes($o);
				}
			}else if($GLOBALS[$n.'_PREFIX'] || $GLOBALS[$n.'_SUFFIX']){
				//prepend or append the parameter
				$v=($GLOBALS[$n.'_PREFIX'] ? $GLOBALS[$n.'_PREFIX'].'-' : '').$v.($GLOBALS[$n.'_SUFFIX'] ? '-'.$GLOBALS[$n.'_SUFFIX'] : '');
			}
			
			$shopCart[$ID][$n]=$v;
		}
		if($_COOKIE['term'])$shopCart[$ID]['term']=$_COOKIE['term'];
		if($_COOKIE['referer'])$shopCart[$ID]['referer']=$_COOKIE['referer'];
	}
	$_SESSION['shopCart']=$shopCart;
	$_SESSION['shopCartModified']=time();
}
function shopping_cart_alert($string, $exit=true){
	global $developerEmail, $fromHdrBugs;
	ob_start();
	print_r($GLOBALS);
	$out=ob_get_contents();
	ob_end_clean();
	mail($developerEmail,'Error in shopping cart',$out,$fromHdrBugs);
	?><script defer>
	alert('<?php echo str_replace("'","\'",$string)?>');
	</script><?php
	if($exit)exit();
}
?>