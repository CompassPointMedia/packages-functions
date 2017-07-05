<?php
$functionVersions['subsearch']=1.00;
function subsearch($options=array()){
	extract($options);
	if(!$resultsLabel)$resultsLabel='Search Group';
	if(!$navObject)$navObject='ID';
	
	//first job = query a set of records with recordPKField, navObject
	if(isset($result)){
		//OK
		$key=$primaryLabelField;
	}else if($sql=$customQuery){
		//OK
	}else{
		if(!$recordPKField || !$primaryLabelField || !$table || !$where)return;
		$sql="SELECT $recordPKField AS ID, $primaryLabelField AS Name FROM $table WHERE $where ORDER BY $primaryLabelField";
		$key='Name';
	}
	if($result || $result=q($sql, O_ARRAY)){
		?><style type="text/css">
		#subSearch{
			border:1px solid #666;
			padding:7px 12px;
			background-color:rgba(255,255,255,0.9);
			position:relative;
			}
		#subSearch .selected{
			background-color:lightyellow;
			outline:1px dotted gold;
			}
		.resultlet{
			padding:0px 4px;
			}
		#subSearchClose{
			position:absolute;
			right:0px;
			top:0px;
			border-bottom:1px solid #666;
			border-left:1px solid #666;
			color:white;
			background-color:darkred;
			font-family:Arial;
			float:right;
			padding:0px 5px;
			font-weight:bold;
			cursor:pointer;
			}
		</style>
		<div id="subSearch">
		<div id="subSearchClose" title="click to close this search group" onclick="g('subSearch').style.display='none';">x</div>
		<h4><?php echo $resultsLabel?></h4>
		<?php
		$i=0;
		$set='';
		foreach($result as $v){
			$i++;
			$url=$_SERVER['PHP_SELF'];
			parse_str($_SERVER['QUERY_STRING'],$b);
			if($navQueryString)parse_str($navQueryString,$c);
			if($b){
				foreach($b as $o=>$w){
					if(strtolower($o)==strtolower($navObject)){
						$selected=($w==$v[$recordPKField]);
						unset($b[$o]);
						$set=$b[$navObject]=$v[$recordPKField];
					}
					if(!$c)continue;
					if(array_key_exists($o,$c))unset($b[$o]);
				}
				if(!$set)$b[$navObject]=$v[$recordPKField];
				$str='';
				foreach($b as $o=>$w){
					$str.=$o.'='.urlencode($w).'&';
				}
				$str=rtrim($str,'&');
			}else{
				$str=$navObject.'='.$v[$recordPKField];
			}
			?><span class="resultlet<?php echo $selected ? ' selected':''?>"><a href="<?php echo $url . ($str || $navQueryString ? '?' : '').$str.($navQueryString ? ($str?'&':'').$navQueryString : '');?>" title="view or edit this item"><?php echo $v[$key];?></a><?php
			if(count($result)>2 && $i<count($result))echo ', ';
			?></span><?php
		}
		?>	
		</div><?php
	}
}
/*
examples of use
subsearch(array(
	'resultsLabel'=>'Items in this Batch',
	'recordPKField'=>'ID',
	'primaryLabelField'=>'SKU',
	'navObject'=>'Items_ID',
	'table'=>'finan_items',
	'where'=>'SKU LIKE \'CWAL%\'',
));
*/

?>