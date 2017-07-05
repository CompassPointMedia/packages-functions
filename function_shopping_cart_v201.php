<?php
$functionVersions['shopping_cart']=2.01;
function shopping_cart($ID, $qty=1, $options=array()){
	/***
	2006-03-02: cut out the ugly fat of previous shopping carts, starting to gel on the protocol and rules.  
	***/
	global $fl, $ln, $qr, $productTable, $_settings, $WholesaleOverridePrice;
	if($WholesaleOverridePrice)$WholesaleOverridePrice=number_format($WholesaleOverridePrice,2);
	//localize the shopping cart
	$shopCart = $_SESSION['shopCart'];
	//see if the shopping cart has that value already
	if($shopCart[$ID]){
		//just increment by quantity
		$shopCart[$ID][1]+=$qty;
	}else{
		$up=$_settings['retailPriceField'];
		$up2=($WholesaleOverridePrice ? "'".$WholesaleOverridePrice."'" : $_settings['wholesalePriceField']);
		$sql="SELECT ID AS SystemID, $up AS RetailPrice, $up2 AS WholesalePrice, ";
		$sql.=implode(', ',$productTable);
		$sql.=" FROM finan_items WHERE ID='$ID'";
		$a=q($sql, O_ROW);
		$shopCart[$ID][1]=$qty;
		foreach($a as $n=>$v){
			if($n=='SystemID')continue;
			$shopCart[$ID][$n]=$v;
		}
		if($_COOKIE['term'])$shopCart[$ID]['term']=$_COOKIE['term'];
		if($_COOKIE['referer'])$shopCart[$ID]['referer']=$_COOKIE['referer'];
	}
	$_SESSION['shopCart']=$shopCart;
}
?>