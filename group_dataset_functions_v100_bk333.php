<?php
//these functions started 2010-04-04 - we are becoming more structured on the dataset->component generator
/*
These functions are specifically used to work in the environment provided by dataset_component_110.php..

*/

$functionVersions['dataset_functions']=1.00;
function dataset_functions(){
	//placeholder
	return true;
}
$functionVersions['standard_cols']=1.00;
function standard_cols($cell, $options=array()){
	global $record,$colPosition,$visibleColCount;
	extract($options);
	switch(true){
		case $cell=='BusinessAddress' || $cell=='HomeAddress' || $cell=='ClientAddress':
			$cog=($cell=='HomeAddress' ? 'Home' : ($cell=='HomeAddress' ? 'Home' : 'Client'));
			$str.=$record[$cog.'Address'].($colPosition==$visibleColCount?'&nbsp;&nbsp;&nbsp;':'').'<br />';
			$str.=$record[$cog.'City'].', '.$record[$cog.'State'].'&nbsp;&nbsp;'.$record[$cog.'Zip'];
			$str.=(strtolower($record[$cog.'Country'])!=='us' && strtolower($record[$cog.'Country'])!=='usa' ? '&nbsp;&nbsp;'.$record[$cog.'Country'] : '').($colPosition==$visibleColCount?'&nbsp;&nbsp;&nbsp;':'');
		break;
		case $cell=='Phones':
			if($record['HomePhone'])$str.=$record['HomePhone'] . '(H)<br />';
			if($record['HomeMobile'])$str.= $record['HomeMobile'] . '(M)<br />';
			if($record['Pager'])$str.= $record['Pager'] . '(P/V)<br />';
			if($record['BusPhone'])$str.= $record['BusPhone'] . '(W)<br />';
		break;
		case $cell=='Email':
			if($record['Email']){
				$str='<a href="mailto:'.$record['Email'].'">'.$record['Email'].'</a>';
			}
			if($record['AlternateEmail']){
				$str.='<br /><a href="mailto:'.$record['AlternateEmail'].'">'.$record['AlternateEmail'].'</a>';
			}
		break;
		case $cell=='CreateDate':
			$str=t($record['CreateDate'], f_dspst, thisyear);
		break;
	}
	return $str;
}
$functionVersions['dataset_breaks_calcs']='1.00';
function dataset_breaks_calcs($options=array()){
	/* Created 2010-04-07 by Samuel
	this function has the useful attribute of being able to call after each record row is printed.  It reads that record and the next record, and outputs summaries AND/OR headers based on breaks in specified $datasetBreakFields.
	
	*/
	global $dataset_breaks_calcs, $record, $nextRecord, $datasetBreakFields, $datasetCalcs, $datasetCalcFields, $submode, $datasetExportCategoryHeaders,$datasetSecondaryRowDataFunction;
	global $qr, $developerEmail, $fromHdrBugs, $fl, $ln;
	extract($options);
	if(!$section)$section='mid';
	if(!$disposition)$disposition='html-table';

	foreach($datasetBreakFields as $v)$a[]=strtolower($record[$v['column']]);
	if($nextRecord)foreach($datasetBreakFields as $v)$b[]=strtolower($nextRecord[$v['column']]);
	foreach($a as $n=>$v){
		if($a[$n]!=$b[$n])break;
		$depth++;
	}

	//create group data calcs
	$i=0;
	unset($dataset_breaks_calcs['keys']);
	foreach($datasetBreakFields as $v){
		$i++;
		$k.=strtolower($record[$v['column']]).'|';
		$dataset_breaks_calcs['keys'][$i]=$k;
		
		//important - calcs only processed in mid section
		if($section!=='mid')continue;
		if(count($datasetCalcFields))
		foreach($datasetCalcFields as $field){
			$datasetCalcs[$k][$field['name']]['count']++;
			if(is_numeric($record[$field['name']])){
				$datasetCalcs[$k][$field['name']]['numeric_count']++;
				$datasetCalcs[$k][$field['name']]['sum']+=$record[$field['name']];
				if(!isset($datasetCalcs[$k][$field['name']]['min']) || $datasetCalcs[$k][$field['name']]['min']>$record[$field['name']])
					$datasetCalcs[$k][$field['name']]['min']=$record[$field['name']];
				if(!isset($datasetCalcs[$k][$field['name']]['max']) || $datasetCalcs[$k][$field['name']]['max']<$record[$field['name']])
					$datasetCalcs[$k][$field['name']]['max']=$record[$field['name']];
				$datasetCalcs[$k][$field['name']]['average']=$datasetCalcs[$k][$field['name']]['sum']/$datasetCalcs[$k][$field['name']]['numeric_count'];
				//be nice to get the median
			}
		}
		//also..
		$datasetCalcs[$k]['*']++;
	}
	/* ----------------
	2010-04-07: Here we are asking some very complex logic in order to standardize output for headers (top and mid) and calc footers (mid and bottom) */
	   
	//---------------- calc footers - mid and bottom ------------------
	if(
	   ($section=='mid' && ($nextRecord && (count($a) - $depth))) ||
		$section=='bottom'
		){
		//print ascending subtotals
		for($k=count($datasetBreakFields); $k>count($datasetBreakFields)-(count($a) - $depth); $k--){
			if(count($datasetCalcFields))
			foreach($datasetCalcFields as $field){
				if($disposition=='html-table'){
					?><tr>
					<td colspan="100%">
					<?php
					echo '<h'.$k.'>'.'Total '.$record[$datasetBreakFields[$k]['column']].': '.$datasetCalcs[$dataset_breaks_calcs['keys'][$k]][$field['name']][$field['calc']].'</h'.$k.'>';
					?>
					</td>
					</tr><?php
				}else if($disposition=='rawdata'){
					//exporting totals in a CSV or other format
					global $datasetOutput;
					$datasetOutput.=($datasetOutput?"\n":'').str_repeat('-',($k+1)*3) . ' Total '.$record[$datasetBreakFields[$k]['column']].': '.$datasetCalcs[$dataset_breaks_calcs['keys'][$k]][$field['name']][$field['calc']].' '.str_repeat('-',($k+1)*3);
				}
			}
		}
	}
	
	//---------------- grouping headers - top and mid -----------------
	if(
	   ($section=='mid' && ($nextRecord && (count($a) - $depth))) ||
		$section=='top'
		){
		//print descending headers
		for($k = ($section=='top' ? 0 : count($datasetBreakFields)-(count($a) - $depth)); 
			$k<count($datasetBreakFields); 
			$k++){
			if($disposition=='html-table'){
				$object=($section=='top'?'record':'nextRecord');
				global $$object;
				?><tr>
				<td class="dataobjectHeading level<?php echo $k+1?>" colspan="100%">
				<?php
				echo '<h'.($k+1).'>';
				if($datasetBreakFields[$k+1]['label']=='default'){
					//simply output the value of the column
					echo
					($section=='top' ? 
						(    $record[$datasetBreakFields[$k+1]['column']] ?     $record[$datasetBreakFields[$k+1]['column']] : $datasetBreakFields[$k+1]['blank']) : 
						($nextRecord[$datasetBreakFields[$k+1]['column']] ? $nextRecord[$datasetBreakFields[$k+1]['column']] : $datasetBreakFields[$k+1]['blank'])
					);
				}else if(stristr($datasetBreakFields[$k+1]['label'],'function:')){
					$n=preg_replace('/function:/','',$datasetBreakFields[$k+1]['label']);
					eval('echo $label='.$n.';');
				}else if($label=$datasetBreakFields[$k+1]['label']){
					$value=
					($section=='top' ? 
						(    $record[$datasetBreakFields[$k+1]['column']] ?     $record[$datasetBreakFields[$k+1]['column']] : $datasetBreakFields[$k+1]['blank']) : 
						($nextRecord[$datasetBreakFields[$k+1]['column']] ? $nextRecord[$datasetBreakFields[$k+1]['column']] : $datasetBreakFields[$k+1]['blank'])
					);
					$field=$datasetBreakFields[$k+1]['column'];
					$label=str_replace('{rb:field}',$field,$label);
					echo $label=str_replace('{rb:value}',$value,$label);
				}else{
					//legacy coding before 2010-09-14, e.g.: Category (Ferrets) - never to my liking
					echo $datasetBreakFields[$k+1]['column'] .' ('.
					($section=='top' ? 
						(    $record[$datasetBreakFields[$k+1]['column']] ?     $record[$datasetBreakFields[$k+1]['column']] : $datasetBreakFields[$k+1]['blank']) : 
						($nextRecord[$datasetBreakFields[$k+1]['column']] ? $nextRecord[$datasetBreakFields[$k+1]['column']] : $datasetBreakFields[$k+1]['blank'])
					)
					.')';
				}
				echo '</h'.($k+1).'>';
				?>
				</td>
				</tr><?php
				if($function=$datasetSecondaryRowDataFunction[$k+1]){
					?><tr class="secondaryRowData">
					<td colspan="100%">
					<?php $function($k+1);?>
					</td>
					</tr><?php
				}
			}else if($disposition=='rawdata'){
				//exporting totals in a CSV or other format
				global $datasetOutput;
				$datasetOutput.= ($datasetOutput?"\n":'').str_repeat('-',($k+1)*3).' '.$datasetBreakFields[$k+1]['column'] .' ('.
				($section=='top' ? 
					($record[$datasetBreakFields[$k+1]['column']] ? $record[$datasetBreakFields[$k+1]['column']] : $datasetBreakFields[$k+1]['blank']) : 
					($nextRecord[$datasetBreakFields[$k+1]['column']] ? $nextRecord[$datasetBreakFields[$k+1]['column']] : $datasetBreakFields[$k+1]['blank'])
				)
				.') '.str_repeat('-',($k+1)*3);
			}
		}
	}
}

function dataset_complexDataCSS($options){
	/* 2010-08-30 by Samuel - first real stab at nice-looking colors that don't tax my brain 
	
	todo:
	---------------
	hlrow values need addressed
	should be able to pass just ONE COLOR to make it work - highlight color chosen contextually or offset on the wheel
	gradients
	color adjustments of s and v to make text more readable
	change text color to white when things get really dark
	
	
	example of use:
	dataset_complexDataCSS(array(
		'datasetColorHeader_'=>'114d12', -- dark green
		'datasetColorRowAlt_'=>'cddccd', -- faded light green-gray
		'datasetColorSorted_'=>'wheat', -- sort column
	));
	options:
		outputStyleTag: default false
	*/
	extract($options);
	if(!$indexBase)$indexBase='a2';
	if($outputStyleTag){
		?><style type="text/css"><?php
	}
	echo "\n".'/* generated by dataset_complexDataCSS() in '.__FILE__.' */'."\n";
	?>
	.complexData thead{						/* header, non-sorted */
		background-color:#<?php echo $datasetColorHeader_ ? $datasetColorHeader_ : 'gray';?>;
		}
	.complexData th{
		color:#<?php echo $datasetColorHeader ? $datasetColorHeader : 'white';?>;
		}
	.complexData th.sorted1{				/* header, sorted level 1 */
		background-color:#<?php echo implode('',color_mix($datasetColorHeader_, $datasetColorSorted_, 0.90))?>;
		}
	.complexData th.sorted2{				/* header, sorted level 2 */
		background-color:#<?php echo implode('',color_mix($datasetColorHeader_, $datasetColorSorted_, 0.74))?>;
		}
	.complexData th.sorted3{				/* header, sorted level 3 */
		background-color:#<?php echo implode('',color_mix($datasetColorHeader_, $datasetColorSorted_, 0.44))?>;
		}
	/* sort indices */
	.complexData th.sorted1 a.asc{
		background-image:url('/images/i/arrows/<?php echo $indexBase?>-asc1.png');
		background-position:top right;
		background-repeat:no-repeat;
		padding-right:24px;
		}
	.complexData th.sorted2 a.asc{
		background-image:url('/images/i/arrows/<?php echo $indexBase?>-asc2.png');
		background-position:top right;
		background-repeat:no-repeat;
		padding-right:24px;
		}
	.complexData th.sorted3 a.asc{
		background-image:url('/images/i/arrows/<?php echo $indexBase?>-asc3.png');
		background-position:top right;
		background-repeat:no-repeat;
		padding-right:24px;
		}
	.complexData th.sorted1 a.desc{
		background-image:url('/images/i/arrows/<?php echo $indexBase?>-desc1.png');
		background-position:top right;
		background-repeat:no-repeat;
		padding-right:24px;
		}
	.complexData th.sorted2 a.desc{
		background-image:url('/images/i/arrows/<?php echo $indexBase?>-desc2.png');
		background-position:top right;
		background-repeat:no-repeat;
		padding-right:24px;
		}
	.complexData th.sorted3 a.desc{
		background-image:url('/images/i/arrows/<?php echo $indexBase?>-desc3.png');
		background-position:top right;
		background-repeat:no-repeat;
		padding-right:24px;
		}

	.complexData tr.alt{					/* row, alt color */
		background-color:#<?php echo $datasetColorRowAlt_?>;
		}

	.complexData tr.alt td.sorted1{			/* row-alt, col-sorted 1 */
		background-color:#<?php echo implode('',color_mix($datasetColorRowAlt_, $datasetColorSorted_, 0.38))?>;
		}
	.complexData tr.alt td.sorted2{			/* row-alt, col-sorted 2 */
		background-color:#<?php echo implode('',color_mix($datasetColorRowAlt_, $datasetColorSorted_, 0.18))?>;
		}
	.complexData tr.alt td.sorted3{			/* row-alt, col-sorted 3 */
		background-color:#<?php echo implode('',color_mix($datasetColorRowAlt_, $datasetColorSorted_, 0.10))?>;
		}

	.complexData td.sorted1{				/* row-normal, col-sorted 1 */
		background-color:#<?php echo implode('',color_mix('fff', $datasetColorSorted_, 0.28))?>;
		}
	.complexData td.sorted2{				/* row-normal, col-sorted 2 */
		background-color:#<?php echo implode('',color_mix('fff', $datasetColorSorted_, 0.14))?>;
		}
	.complexData td.sorted3{				/* row-normal, col-sorted 3 */
		background-color:#<?php echo implode('',color_mix('fff', $datasetColorSorted_, 0.10))?>;
		}

	.hlrow td{							/* h() with new className change */
		background-color:#f6aaaa;
		}
	.hlrow td.sorted{
		background-color:#f69b91;		/* highlight-sorted (but can't differentiate :( */
		}

	<?php
	if($outputStyleTag){
		?></style><?php
	}
}

/* 
2010-08-01: i tried this couldn't get to work
$functionVersions['dataset_kernel']='1.00';
function dataset_kernel($handle, $scheme, $record){
	global $FUNCTION_ROOT, $dataSourceExplained, $format, $echoed, $echonotified, $developerEmail, $fromHdrBugs, $datasetTable;
	if(!$scheme['method'] || $scheme['method']=='field'){
		$out=$record[$scheme['fieldExpressionFunction'] ? $scheme['fieldExpressionFunction'] : $handle];
		switch($scheme['datatype']){
			case 'email':
			case 'url':
			case 'linkable':
				if(!function_exists('make_clickable_links'))require_once($FUNCTION_ROOT.'/function_make_clickable_links_v100.php');
				if($scheme['format']=='noformat')break;
				if($submode!=='exportDataset')$out=make_clickable_links($out);
				break;
			case 'date':
				if($scheme['format']=='noformat')break;
				//we'll assume the export wants the reformat as well
				if($scheme['format']){
					//not developed, this would be the format like F js etc. we use
				}else{
					$out=t($out, (strlen($out)==10?f_qbks:f_dspst), $scheme['thisyear']);
				}
				break;
			case 'time':
				$out=date('g:iA',strtotime($out));
				break;
			case 'logical':
				if($scheme['format']=='noformat')break;
				if(strlen($scheme['format'])){
					$out=output_logical($out,$scheme['format']);
				}
			case '':
				
				/* 2009-12-15: improved default field handling * /
				if(!$dataSourceExplained){
					$dataSourceExplained=q("EXPLAIN $datasetTable", O_ARRAY);
					foreach($dataSourceExplained as $n=>$v){
						$dataSourceExplained[$v['Field']]=$v;
						unset($dataSourceExplained[$n]);
					}
				}
				if(!($v=$dataSourceExplained[$handle]))break;
				preg_match('/^([a-z]+)(.*)/i',$v['Type'],$a);
				if($a[1]=='date'){
					$out=($out=='0000-00-00' ? '' : date('m/d/Y',strtotime($out)));
				}else if($a[1]=='datetime'){
					$out=t($out, (strlen($out)==10?f_qbks:f_dspst), thisyear);
				}else if($a[1]=='time'){
					//assume balls is a null for now
					$out=($out=='00:00:00' || $out=='00:00' || is_null($out) ? '' : date('g:iA',strtotime($out)));
				}else if($a[1]=='float'){
					$dims=trim($a[2],'()');
					$dims=explode(',',$dims);
					$out=number_format($out,$dims[1]);
					$format='float';
				}
				break;
		}
	}else if($scheme['method']=='function'){
		ob_start();
		eval('$out='.rtrim($scheme['fieldExpressionFunction'],';').';');
		$echoed=ob_get_contents();
		ob_end_clean();
		if($echoed && !$echonotified){
			$echonotified=true;
			mail($developerEmail, 'Error file '.__FILE__.', line '.__LINE__,get_globals(),$fromHdrBugs);
		}
	}
	return $out;
}
*/
?>