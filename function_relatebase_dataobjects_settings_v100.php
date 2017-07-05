<?php
$functionVersions['relatebase_dataobjects_settings']=1.00;
function relatebase_dataobjects_settings($configNode, $options=array()){
	/*
	created 2008-12-13 by Samuel - see notes in the wiki
	2008-12-19:	
	//this hijacks the function to do a SELECT DISTINCT list with add-new capability
	$options=array(
		'a'=>array(
			'AddThroughModification'=>'distinct',
			'ForeignKeyField'=>'Category',
			'AllowAddNew'=>true,
			'AddThrough'=>'simple',
			'InsertLabel'=>'< Select.. >',
			'MapsToField'=>'DISTINCT Category',
			'LabelField'=>'Category',
			'InTable'=>'finan_items',
			'JoinType'=>'oneToMany',
			'oneToManyDatasetWhere'=>'Category!=\'\''
		),
		'configNode'=>'Category',
	);

	TODO
	------------------------------------------------------------------------
	addNew methods - generic - creating this will be a big step forward
	

	*/


	global $fl,$ln,$qr,$qx,$developerEmail,$fromHdrBugs;
	global $fieldReplacements,$moduleConfig;
	global $ID,$mode,$insertMode,$updateMode;
	if(!$maxPixelWidth)$maxPixelWidth='195';
	if(!$maxCharLen)$maxCharLen=25;

	$a=$moduleConfig['dataobjects']['finan_items']['joins'][$configNode];
	extract($options);
	
	if(!isset($a['cancelNewEntryButton']))$a['cancelNewEntryButton']=true;
	
	ob_start();
	$randwidth=md5(time().rand(1000,100000)).'_width';
	if($a['JoinType']=='oneToMany' && $a['AllowAddNew']){
		if($a['AddThrough']=='simple'){
			$addMethod='simple';
			$addNewFunction='simpleNew(this);';
		}else if($a['AddThrough']=='link'){
			$addMethod='link';
			$addNewFunction='newOption(this, \''.$a['LinkToPage'].'\', \'l1_addnew\', \'700,700\');';
			$cbTable=$a['InTable'];
		}
	}
	?><select name="<?php echo $a['JoinType']=='manyToMany'? $a['JoinTableFKRemote'] : ($a['OverrideFieldName'] ? $a['OverrideFieldName'] : $a['ForeignKeyField'])?><?php echo $a['JoinType']=='manyToMany' && $a['AllowMultiple']?'[]':''?>" id="<?php echo $a['JoinType']=='manyToMany'? $a['JoinTableFKRemote'] : $a['JoinType']=='manyToMany'? $a['JoinTableFKRemote'] : ($a['OverrideFieldName'] ? $a['OverrideFieldName'] : $a['ForeignKeyField'])?><?php echo $a['JoinType']=='manyToMany' && $a['AllowMultiple']?'[]':''?>" <?php echo $a['JoinType']=='manyToMany' && $a['ListHeight']>1?'size="'.$a['ListHeight'].'"':''?> <?php echo $a['JoinType']=='manyToMany' && $a['AllowMultiple'] ? 'multiple':''?> onChange="<?php echo $a['AllowAddNew']?$addNewFunction:''?>dChge(this);" <?php if($addMethod=='simple'){ ?>onkeyup="window.status=this.value;<?php echo $addNewFunction?>;" onmouseup="window.status=this.value;<?php echo $addNewFunction?>;"<?php }?> style="<?php echo $randwidth?>;" <?php if($cbTable){ ?>cbTable="<?php echo $cbTable?>"<?php } ?>>
	<?php if($mode==$insertMode || $a['AllowBlankOnUpdates']){ ?>
	<option value="" style="font-style:italic;"><?php echo $mode==$insertMode ? ($a['InsertLabel'] ? h($a['InsertLabel']) : '&lt; Select.. &gt;') : ($a['BlankUpdateLabel'] ? $a['BlankUpdateLabel'] : '(none)');?></option>
	<?php } ?>
	<?php
	//build query
	if($a['JoinType']=='oneToMany'){
		$sql='SELECT '.$a['MapsToField'].' AS ID, '.$a['LabelField'].' AS Label FROM '.$a['InTable'].($a['oneToManyDatasetWhere'] ? ' WHERE '.$a['oneToManyDatasetWhere']:'').' ORDER BY '.$a['LabelField'];
	}else if($a['JoinType']=='manyToMany'){
		$sql='SELECT a.'.$a['ValueTablePK'].', '.$a['ValueTableLabel'].' AS Label, b.'.$a['JoinTableFKLocal'].' AS Selected FROM '.$a['ValueTableName'].' a LEFT JOIN '.$a['JoinTable'].' b ON a.'.$a['ValueTablePK'].'=b.'.$a['JoinTableFKRemote'].' AND b.'.$a['JoinTableFKLocal'].'=\''.$ID.'\' GROUP BY a.'.$a['ValueTablePK'];
	}
	//output options
	if($b=q($sql, O_ARRAY_ASSOC)){
		$len=0;
		if(!empty($a['ForeignKeyField'])){
		    $str = $a['ForeignKeyField'];
		    global $$str;
        }

		foreach($b as $n=>$v){
			//"selected" keyword
			if($a['JoinType']=='oneToMany'){
				$selected=($n==$$a[($a['OverrideFieldName'] ? 'OverrideFieldName' : 'ForeignKeyField')]?'selected':'');
			}else if($a['JoinType']=='manyToMany'){
				$selected=($v['Selected']?'selected':'');
			}
			//width of select
			if(strlen($v['Label'])>$len)$len=strlen($v['Label']);
			
			?><option value="<?php echo h($n)?>" <?php echo $selected?>><?php echo h($v['Label'])?></option><?php
		}
	}
	if($a['JoinType']=='oneToMany' && $a['AllowAddNew']){
		?><option value="{RBADDNEW}" style="font-style:italic">&lt; Add new.. &gt;</option><?php
	}
	?>
	</select><?php
	if($addMethod=='simple'){
		//this element must be tight to the previous for javascript to work
		//NOTE: this element should never be an array element
		?><input name="<?php echo ($a['OverrideFieldName'] ? $a['OverrideFieldName'] : $a['ForeignKeyField']);?>_RBADDNEW" type="text" id="<?php echo ($a['OverrideFieldName'] ? $a['OverrideFieldName'] : $a['ForeignKeyField']);?>_RBADDNEW" style="<?php echo $randwidth;?>;<?php echo 'display:none';?>;" onBlur="simpleNew(this);if(this.value!=='')dChge(this);" onKeyUp="simpleNew(this,event);" value="" <?php if($l=$a['AddNewFieldLength']){ ?>maxlength="<?php echo $l?>"<?php } ?> /><?php if($a['cancelNewEntryButton']){ ?><input id="<?php echo ($a['OverrideFieldName'] ? $a['OverrideFieldName'] : $a['ForeignKeyField']);?>_RBADDNEWCXL" type="button" onclick="simpleNew(this);" value="X" title="Cancel new entry for this field" style="<?php echo 'display:none;'?>" class="cancelNewEntryButton" /><?php } ?><?php
	}
	if($a['AddThroughModification']){
		?><input type="hidden" name="<?php echo ($a['OverrideFieldName'] ? $a['OverrideFieldName'] : $a['ForeignKeyField']);?>_RBADDNEWMODIFICATION" id="<?php echo ($a['OverrideFieldName'] ? $a['OverrideFieldName'] : $a['ForeignKeyField'])?>_RBADDNEWMODIFICATION" value="<?php echo $a['AddThroughModification']?>" /><?php
	}
	$out=ob_get_contents();
	ob_end_clean();
	//width of select and also RBADDNEW element
	$stylewidth=($len>$maxCharLen?'width:'.$maxPixelWidth.'px;':'');
	$out=str_replace($randwidth,$stylewidth,$out);
	return $out;
}
?>