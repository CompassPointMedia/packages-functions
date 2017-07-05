<?php
$functionVersions['horizontal_loop']=1.00;
function horizontal_loop($options=array()){
	/*

Function:horizontal loop
	2009-03-22 by Samuel
	had what I think is good coding for a looping table which can start at any position and go for any length - I had originally conceived of using this for printing labels until the idea of PDF doc labels came up.  However it was not portable code.  This allows the fctn to be called and then you may declare eval($hltab['eval']);
	options
	-------
		cols
		startPosn
		tag - default=<table [id='id'] class="hltab">
		object - default=records
		loop - example: $clients as $idx=>$data
		body - example: require('file:///C|/Users/Samuel Fullman/Documents/Compass Point Media/Hosted Accounts/dev.DAC INT/somecomponent.php');
	CSS
	---
	.hltab
		td.empty|content
		td.top|mid|bottom
		td.left|center|right
	*/
	global $hltab, $cols, $startPosn;
	//reset hltab
	$hltab=array();
	extract($options);
	
	$hltab['cols']=($cols ? $cols : 3);
	$hltab['startPosn']=($startPosn ? $startPosn : 1);
	
	$hltab['init']=' ?>';
	$hltab['tag']=($tag ? $tag : '<table '.($id?'id="'.$id.'"':'').' class="'.($class ? $class : 'hltab').'">');
	$hltab['object']=($object ? $object : 'records');
	
	//can be a for or foreach loop
	$hltab['loop']='<?php '.($loop ? 'foreach('.$loop.'){' : 'foreach($$hltab[\'object\'] as $n=>$v){');
	$hltab['head']='
		//handle first row(s) and starting offset
		$hltab[\'count\']++;
		if($hltab[\'count\']==1 && $addRows = floor(($hltab[\'startPosn\']-1)/$hltab[\'cols\'])){
			//these are top offset rows, modify class and content as needed
			for($hltabI=1; $hltabI<=$addRows; $hltabI++){
				$hltab[\'row\']++;
				$hltab[\'col\']=0;
				$hltab[\'vPosition\']=($hltabI==1 ? \'top\' : \'mid\');
				?><tr><?php
					for($hltabJ=1; $hltabJ<=$hltab[\'cols\']; $hltabJ++){
						switch($hltabJ){
							case 1:
								$hltab[\'hPosition\']=\'left\';
								break;
							case $hltab[\'cols\']:
								$hltab[\'hPosition\']=\'right\';
								break;
							default:
								$hltab[\'hPosition\']=\'center\';
						}
						$hltab[\'startOffsetCells\']++; //total number of blank cells
						$hltab[\'col\']++;
						?><td class="empty <?php echo $hltab[\'hPosition\'] . \' \'. $hltab[\'vPosition\']?>">&nbsp;</td><?php
					}
				?></tr><?php
			}
		}
		//begin a row
		if( $hltab[\'col\']==0 ){
			$hltab[\'row\']++;
			$hltab[\'vPosition\']=($hltab[\'row\']==1 ? \'top\' : (count($$hltab[\'object\'])-$hltab[\'cells\']-$hltab[\'startOffsetCells\'] < $hltab[\'cols\'] || $hltab[\'row\']>=$maxRows ? \'bottom\' : \'mid\'));
			?><tr><?php
		}
		//add initial padding cells
		if($hltab[\'count\']==1 && $startPad = ($hltab[\'startPosn\'] - 1) % $hltab[\'cols\']  ){
			for($hltabI=1; $hltabI<=$startPad; $hltabI++){
				switch(true){
					case $hltabI==1:
						$hltab[\'hPosition\']=\'left\';
						break;
					default:
						$hltab[\'hPosition\']=\'center\';
				}
				$hltab[\'startOffsetCells\']++;
				$hltab[\'col\']++;
				?><td class="empty <?php echo $hltab[\'hPosition\'] . \' \' . $hltab[\'vPosition\']?>">&nbsp;</td><?php
			}
		}
		//normal cells
		$hltab[\'col\']++;
		$hltab[\'cells\']++;
		$hltab[\'hPosition\']=($hltab[\'col\']==1 ? \'left\' : ($hltab[\'col\'] % $hltab[\'cols\'] ==0 ? \'right\' : \'mid\'));
		
		?><td class="content <?php echo $hltab[\'hPosition\'] . \' \' . $hltab[\'vPosition\']?>"><?php ';
	$hltab['body']=($body ? rtrim($body,';').';' : '	
		//------------------------------------ content here -------------------------------------------
		echo "need content engine<br />";
		prn($v);
		//-----------------------------------------------------------------------------------------------------------
		');
	$hltab['foot']=' ?></td>
		<?php
		//closing cells
		if($hltab[\'count\']==count($$hltab[\'object\']) && !($hltab[\'cols\']==$hltab[\'col\'])){
			$lastCol=$hltab[\'col\'];
			for($hltabI=1; $hltabI<=($hltab[\'cols\']-$lastCol); $hltabI++){
				switch($hltabI){
					case $hltab[\'cols\']-$lastCol:
						$hltab[\'hPosition\']=\'right\';
						break;
					default:
						$hltab[\'hPosition\']=\'mid\';
				}
				$hltab[\'endOffsetCells\']++;
				$hltab[\'col\']++;
				?><td class="empty <?php echo $hltab[\'hPosition\'] . \' \'. $hltab[\'vPosition\']?>">&nbsp;</td><?php
			}
		}
		//end a row
		if( $hltab[\'col\'] % $hltab[\'cols\'] == 0){
			$hltab[\'col\']=0;
			?></tr><?php
			if(strlen($maxRows) && $hltab[\'row\']>=$maxRows)break;
		}
	}
	?></table><?php ';
	$hltab['eval']=$hltab['init'] . $hltab['tag'] . $hltab['loop'] . $hltab['head'] . $hltab['body'] . $hltab['foot'];
}

/* example use of the function:
$options=array(
	'cols'=>2,
	'startPosn'=>2,
	'loop'=>'$products as $Model=>$CanPurchase',
	'body'=>"require('file:///C|/Users/Samuel Fullman/Documents/Compass Point Media/Hosted Accounts/dev.DAC INT/components-local/comp_DAC_models.php');"
);
horizontal_loop($options);
eval($hltab['eval']);
*/
?>