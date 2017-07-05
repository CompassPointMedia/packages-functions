<?php
$functionVersions['array_to_entrytable']='1.0';
function array_to_entrytable($a,$options=array()){
	global $universal_increment;
	$local_increment=0;
	/* 2011-07-16 converts a 2d array into a javascript-friendly table.  One entry is ok and will figure things out, but options help 
	
	options
		tableAttributes
		colAttributes
		extraRows
		suppressAddRows - stops the bottom add row from displaying
		splitFieldName
		arrayString: default formData
	*/
	global $qr,$developerEmail,$fromHdrBugs,$array_to_entrytable;
	extract($options);
	
	if(!$array_to_entrytable['js_set']){
		$array_to_entrytable['js_set']=true;
		?>
<script language="javascript" type="text/javascript">
		function rem(o){
			if(!confirm('Remove this entry?'))return false;
			o.parentNode.parentNode.parentNode.removeChild(o.parentNode.parentNode);
		}
		function ins(o){
			var n=o.parentNode.parentNode;
			while(true){
				n=n.previousSibling;
				if(n.tagName)break;
			}
			var n2=n.cloneNode(true);
			var v=n2.getElementsByTagName('input');
			for(var i in v){
				if(v[i].type && v[i].type.toLowerCase()=='text')v[i].value='';
			}
			insertAfter(n,n2)
			//o.parentNode.parentNode.insertBefore(n2);
		}
		</script><?php
	}
	if(!isset($extraRows))$extraRows=3;
	$attribList=array(
		'class',
		'onfocus',
		'onblur',
		'onkeyup',
		'size',
		'maxlength',		
	);
	if(!$cols)exit('currently, function array_to_entrytable() must have $col declared');
	?><table <?php echo $tableAttributes;?> border="0" cellspacing="0" cellpadding="0">
	<thead>
	  <tr>
		<th <?php if($ca=$colAttributes[0])foreach($ca as $at)echo $at . ' ' ;?>>&nbsp;</th>
		<?php 
		$i=0;
		foreach($cols as $n=>$v){
			$i++;
			?>
			<th <?php if($ca=$colAttributes[$j])foreach($ca as $at)echo $at . ' ' ;?>><?php $heading=($v['heading']?$v['heading']:$n);
			if(preg_match('/^[-_0-9a-z]+$/i',$heading))$heading=preg_replace('/([a-z])([A-Z])/','$1 $2',$heading);
			echo $heading;
			?></th>
			<?php
		}
		?>
	  </tr>
	</thead>
	<tbody>
	<?php
	if(empty($a)){
		$a=array();
		foreach($cols as $n=>$v){
			$a[0][($v['name'] ? $v['name'] : $n)]='';
		}
	}
	//now a will run at least once
	$i=0;
	foreach($a as $n=>$v){
		$i++;
		if($i==1){
			foreach($v as $o=>$w){
				//take 0-based or assoc arrays
				$j++;
				$idx[$j]=$o;
			}
		}
		?><tr>
			<td <?php if($ca=$colAttributes[0])foreach($ca as $at)echo $at . ' ' ;?>><input type="button" name="Button" value=" - " tabindex="-1" onclick="return rem(this)" <?php
			if(!$disableFirstButton){
				$disableFirstButton=true;
				echo 'disabled="disabled"';
			}
			?> /></td>
			<?php
			$j=0;
			foreach($cols as $o=>$w){
				$local_increment++;
				$j++;
				?><td <?php if($ca=$colAttributes[$j])foreach($ca as $at)echo $at . ' ' ;?>><?php

				//common attributes
				if($splitFieldName){
					$str= ' name="'.$splitFieldName.'['.$j.'][]"';
					$str.= ' id="'.$splitFieldName.'['.$j.'][]'.$local_increment.'"';
				}else{
					if(!$arrayString /* same as on form_field_translator() */)$arrayString='formData';
					$str= ' name="'.$arrayString.'['.$fieldName.']['.$j.'][]"';
					$str.= ' local_increment="'.$local_increment.'" id="'.$arrayString.'['.$fieldName.']['.$j.'][]'.$local_increment.'"';
				}
				foreach($attribList as $x)	if(strlen($w[$x]))$str.= ' '.$x.'="'.$w[$x].'"';
				if(strstr($str,'{RBUNIVERSALINCREMENT}')){
					global $universal_increment;
					$universal_increment++;
					$str=str_replace('{RBUNIVERSALINCREMENT}',$universal_increment,$str);
				}
				$str.= ' onchange="'.($w['onchange'] ? $w['onchange'].';' : '').'dChge(this);"';

				//output fields
				if($w['type']=='textarea'){
					$out= '<textarea ';
					$out.= $str;
					$out.= '>';
					$template=$out;
					$out.= h($v[$idx[$j]]);
					$template.='</textarea>';
					$out.='</textarea>';
				}else{
					$out= '<input type="'.($w['type']=='input' || !$w['type'] ?'text':$w['type']).'"';
					$out.= $str;
					$template=$out;
					$out.= ' value="'.h($v[$idx[$j]]).'"';
					$template.= ' />';
					$out.= ' />';
				}
				echo $out;
				if(!$firstRowInitiated)$templates[]=$template;
				?></td><?php
				echo "\n";
			}
			$firstRowInitiated=true;
			?>
		</tr><?php
		echo "\n";
	}
	//add extra rows
	$start=$i+1;
	$end=$i+$extraRows;
	for($i=$start; $i<=$end; $i++){
		?><tr>
		<td <?php if($ca=$colAttributes[0])foreach($ca as $at)echo $at . ' ' ;?>><input type="button" name="Button" value=" - " tabindex="-1" onclick="return rem(this)" <?php
			if(!$disableFirstButton){
				$disableFirstButton=true;
				echo 'disabled="disabled"';
			}
			?> /></td>
		<?php
		$j=0;
		foreach($templates as $v){
			$local_increment++;
			$j++;
			?><td <?php if($ca=$colAttributes[$j])foreach($ca as $at)echo $at . ' ' ;?>><?php
			echo str_replace('id="', 'id="'.$local_increment.'-', $v);
			?></td><?php
			echo "\n";
		}
		?></tr><?php
		echo "\n";
	}
	?>
	<tr<?php echo $suppressAddRows?' style="display:none;"':'';?>>
		<td <?php if($ca=$colAttributes[0])foreach($ca as $at)echo $at . ' ' ;?> colspan="100%"><input id="something" type="button" name="Submit4" value=" + " tabindex="-1" onclick="ins(this);" /></td>
	</tr>
	</tbody>
	</table><?php
}

/*
$a=array(
	array(
		'bethany',
		'norris',
		'123 anytown st.',
		'drmaer',
		'ia',
		'55123',
	),
);
array_to_entrytable($a,$options=array(
	'cols'=>array(
		'FirstName'=>array(),
		'LastName'=>array(),
		'Address'=>array(),
		'City'=>array(),
		'State'=>array(),
		'Zip'=>array(),
	),
	'arrayString'=>'ancillary',
	'fieldName'=>'behavioral',
));
*/
?>