<?php

$functionVersions['sE']=1.00;
function sE(){
	//placeholder
	return true;
}

if(!function_exists('form_field_presenter')){
$functionVersions['form_field_presenter']=1.00;
function form_field_presenter($options=array()){
	/*
	created 2012-10-10 for faster prototyping of forms, the objective being
		spit out equivalent php coding
		create the actual form field as HTML output if preferred
		set certain fields as the best possible type based on first, common sense presumptions and then an overlay
		eventually, have settings for a form which are entirely set by another form for the settings :)
	the common sense logic for presenting a form is as follows:
		* the primary key field should be a hidden field
		* tinyint fields should be checkboxes
		* enum or set fields.. s/e
		* char fields => imput[type=text]
		* text fields => textarea
		* cognates_id - look in database for tables with that last cognate - + bonus the ability to add new
			(do this even if multiple matches and notify this is a probationary field status) - how do we resolve this on-the-fly
		* all checkboxes have a zero-value in front of them
	the logic layer on top is as follows
		* for this view, we can skip these fields
		* we need a different table:label for a relational field
		* we need distinct values
		* we need optgroups by some condition
		* this should be a dropdown of lookup values
		* fields should be grouped into either a tabset or a control of a dropdown
		
		
	other parameters
		the view is set up or not set up
		if there are notices, warnings or even worse, errors, then this should appear
		(if there is no name of the view then the name = default)
		
	
	options
		positiveFilters=regexp
		suppress= array[relations|before_table]
		
	
	*/
	@extract($options);
	global $mode,$insertMode,$updateMode,$recordPKField,$qr,$fl,$ln;

	if(!$object)global $object;
	$cognate=strtolower(current(explode('_',$object)));
	if(!$object)exit('object variable is required');
	if(!$fields)global $objectFields, $refreshObjectFields;
	if($objectFields && !$refreshObjectFields){
		$fields=$objectFields;
	}else{
		$fields=q("EXPLAIN $object", O_ARRAY);
	}
	$add=q("SHOW CREATE TABLE $object", O_ARRAY);
	$add=explode("\n",$add[1]['Create Table']);
	foreach($add as $n=>$v){
		unset($add[$n]);
		if(preg_match('/^`([^`]+)`/',trim($v),$m)){
			$key=strtolower($m[1]);
			$add[$key]['raw']=trim(str_replace($m[0],'',$v));
			$add[$key]['Field']=$m[1];
			$a=explode(' COMMENT ',$add[$key]['raw']);
			if($a[1]){
				$comment=trim($a[1]);
				$comment=rtrim($comment,',');
				$comment=trim($comment,'\'');
				$add[$key]['comment']=$comment;
			}
		}
	}
	if(!$recordPKField[0]){
		foreach($fields as $n=>$v){
			if($v['Key']=='PRI')$recordPKField[]=$v['Field'];
			//first non-numeric field = default sorter
			if(!$sorter && preg_match('/(char|varchar)/',$v['Type']))$sorter=$v['Field'];
			if(preg_match('/resourcetype|resourcetoken/i',$v['Field']) && $v['Null']=='YES')$quasiResourceTypeField=$v['Field'];
		}
		if(count($recordPKField)<>1)exit('table has a compound or missing primary key');
		if(!$sorter)$sorter=$recordPKField[0];
	}
	$cartModuleId=(strstr($mode,'update')?'um':'im');

	if(!$usedAttributes)$usedAttributes=array('id','class','style','onclick','onchange','value','rows','cols','cbtable');
	if($suppress['relations']){
		$relations=array();
	}else{
		$relations=sql_table_relationships();
	}
	if(!$suppress['before_table']){
	?>
	<h2 class="nullTop">table: <?php echo $object;?><?php if($mode==$insertMode){ ?> <span class="gray" style="font-weight:400;">(Adding New)</span><?php } ?></h2>
	<!-- {RBSYSTEMENTRY:before_table} -->
	<?php
	}
	?>
	<table>
	<?php
	foreach($fields as $v){
		extract($v);
		
		//added 2013-01-03
		if($positiveFilters && !preg_match($positiveFilters,$Field))continue;
		
		if(strlen($Default)){
			if($Default=='0000-00-00' || $Default=='0000' || $Default=='00:00:00' || $Default=='0000-00-00 00:00:00')
			unset($Default);
		}
		/* Array
		(
		[Field] => ResourceType
		[Type] => tinyint(1) unsigned
		[Null] => YES
		[Key] => MUL
		[Default] => 
		[Extra] => 
		)
		*/
			
		$field=strtolower($Field);
		unset($col);
		if($columns)foreach($columns as $n=>$v)if(strtolower($n)==$field){
			$col=$v;
			break;
		}
		$calledType	=strtolower($col['flags']['type']);

		if($calledType=='none')continue;
		$subtype='';
		/* 
		my concerns are about data type and esp. integer, float, or date types
		
		*/
		
		//get "natural type" of field based on database
		if($Key=='PRI' && preg_match('/int\(/i',$Type)){
			$naturalType= 'hidden';
		}else if($n=$relations[$field]){
			$naturalType='select';
			$subtype='relation';
			
			if($r=$col['flags']['relation']){
				//OK
				
			}
			
			//artificially set some attributes
			$col['attributes']['cbtable']=($r['table']?$r['table']:$n['table']);
			$wparam='object='.($r['table']?$r['table']:$n['table']);
			$wname='l2_'.substr(md5($r['table']?$r['table']:$n['table']),0,6);
			$wsize=($r['wsize']?$r['wsize']:'700,700'); //for now until we get into settings better
			$col['attributes']['onchange']='>>newOption(this, \'systementry.php\', \''.$wname.'\', \''.$wsize.'\',\''.$wparam.'\');';
			
			
			//echo 'relation ('.$n['table'].':'.$n['label'].')';
		}else if(preg_match('/(createdate|creator|editdate|editor)/i',$Type,$m)){
			$naturalType='none';
			$m=$m[1];
		}else if(preg_match('/tinyint/',$Type)){
			$naturalType='checkbox';
		}else if(preg_match('/(char|varchar)/',$Type,$m)){
			$m=$m[1];
			$naturalType='input';
		}else if(preg_match('/text/',$Type)){
			$naturalType='textarea';
		}else if(preg_match('/(enum|set)/',$Type,$m)){
			$subtype=$m[1];
			$naturalType='select';
		}else{
			$naturalType='input';
		}
		
		//get called type and typeSrc from options
		if($calledType){
			$type=$calledType;
			$typeSrc='called';
		}else{
			$type=$naturalType;
			$typeSrc='natural';
		}
		//right now we don't include these CF's
		if($typeSrc=='natural' && preg_match('/\b(createdate|creator|editdate|editor)$/',$field))continue;
		
		//build a collection _attrib_ which is the attributes adjusted for current mode (insert|update)
		$possibleAttributes=array();
		$_attrib_=array();
		if(count($col['attributes']))
		foreach($col['attributes'] as $o=>$w)$possibleAttributes[]=strtolower(current(explode(':',$o)));
		if(count($possibleAttributes))
		foreach($possibleAttributes as $w)$_attrib_[$w]=(isset($col['attributes'][$w.':'.$cartModuleId]) ? $col['attributes'][$w.':'.$cartModuleId] : $col['attributes'][$w]);
		$output=array();
		
		/* special cases:
			* need to be able to handle field1, field2 for radios
			*/
		foreach($usedAttributes as $handle){
			//calculate default value
			unset($default);
			/* do calculations here on does this field need this attribute */
			switch($handle){
				case 'id':
					$default=$Field;
				break;
				case 'class':
				break;
				case 'style':
				break;
				case 'onclick':
				break;
				case 'onchange':
					$default='dChge(this);';
				break;
				case 'value':
					/*
					factors:
					if the natural table field allows null
					if the natural table field defaults to null
					what the default value is
					what the user-defined default value is for this view
					what the passed value is (even in insertMode)
					what the record value is
					* if a value is null, it is the same as being unset (even though ironically, null is an ascii value)
					
					*/
					if(isset($GLOBALS[$Field])){
						//whether insert or update mode
						$default=$GLOBALS[$Field];
					}else if($mode==$insertMode){
						if(preg_match('/[^:]::[^:]/',$col['default'])){
							eval('$default='.end(explode('::',$col['default'])).';');
						}else{
							$default=(strlen($col['default']) ? $col['default'] : (strlen($Default) ? $Default : ($Null=='YES'?'NULL':'')));
						}
					}
					if($default=='0000-00-00' || $default=='0000' || $default=='00:00:00' || $default=='0000-00-00 00:00:00')$default='';
				break;
				case 'rows':
					if($type=='textarea')$default=3;
				break;
				case 'cols':
					if($type=='textarea')$default=45;
				break;
			}
			if(isset($_attrib_[$handle])){
				$str=$_attrib_[$handle];
				if(strlen($str)){
					if(substr($str,0,2)=='<<'){
						#before
						$output[$handle]=substr($str,2,strlen($str)-2).($default?' '. $default:'');
					}else if(substr($str,0,2)=='>>'){
						#after
						$output[$handle]=($default?$default.' ':'').substr($str,2,strlen($str)-2);
					}else{
						#replace
						$output[$handle]=$str;
					}
				}else{
					#delete (do not carry)
				}
			}else if(isset($default)){
				$output[$handle]=$default;
			}
		}

		//now convert the value as needed esp. date values
		if(!$col['flags']['do_not_convert_value']){
		
		}
		
		if(strlen($output['value']))$output['value']=h($output['value']);
		
		$name=(
			$col['attributes']['field_name'] ? 
			$col['attributes']['field_name'] :
			($pre=$col['flags']['array_wrapper']?$pre.'[':'') . $Field . ($col['flags']['array_wrapper']?']':'') . ($col['flags']['build_array']?'[]':'')
		);

		ob_start();
		switch($type){
			case 'input':
				?><input type="text" name="<?php echo $name;?>" <?php foreach($output as $o=>$w)echo $o.'="'.$w.'" ';?> /><?php
			break;
			case 'hidden':
				?><input type="hidden" name="<?php echo $name;?>" <?php foreach($output as $o=>$w)echo $o.'="'.$w.'" ';?> /><?php
			break;
			case 'textarea':
				$value=$output['value'];
				unset($output['value']);
				?><textarea name="<?php echo $name;?>" <?php foreach($output as $o=>$w)echo $o.'="'.$w.'" ';?>><?php echo $value;?></textarea><?php
			break;
			case 'select':
				//this is the big one
				$hasBlank=false;
				unset($_opt_);
				$value=$output['value'];
				unset($output['value']);
				if($subtype=='enum' || $subtype=='set'){
					$opt=rtrim(substr($Type,strlen($subtype)+2),')');
					$opt=substr($opt,0,strlen($opt)-1);
					$opt=explode('\',\'',$opt);
					//convert values
					foreach($opt as $o=>$w){
						if(!strlen($w))$hasBlank=true;
						$_opt_[$w]=h($w);
					}
					ksort($_opt_);
				}else if($subtype=='relation'){
					ob_start();
					$_opt_=q("SELECT ".
					end(explode('_',$field)).", ".($col['relations_label'] ? $col['relations_label'] : ($relations[$field]['label'] ? $relations[$field]['label'] : end(explode('_',$field)))).
					" FROM ".$relations[$field]['table'].
					($col['relations_where_exclusion'] ? ' WHERE '.preg_replace('/^WHERE\b/i','',trim(systementry_parse_vars($col['relations_where_exclusion']))) : '').
					" ORDER BY ".
					($col['relations_label'] ? $col['relations_label'] : ($relations[$field]['label'] ? $relations[$field]['label'] : end(explode('_',$field)))), O_COL_ASSOC, ERR_ECHO);
					$err=ob_get_contents();
					ob_end_clean();
				}else{
					$opt='';
				}
				?><select name="<?php echo $name;?>" <?php foreach($output as $o=>$w)echo $o.'="'.$w.'" ';?>>
				<option value="<?php
					//added 2012-12-10: if the table field is default null, we want to pass a null value
					if($Null=='YES' && is_null($Default))echo 'PHP:NULL';
					?>"<?php if($cartModuleId!='im')echo ' class="gray" style="font-style:italic;"';?>><?php if($cartModuleId=='im'){ ?>&lt;Select..&gt;<?php }else{ ?>(none)<?php } ?></option><?php
				if(!empty($_opt_))
				foreach($_opt_ as $o=>$w){
					?><option value="<?php echo $o;?>" <?php echo $o==$value?'selected':''?>><?php echo $w;?></option><?php
				}
				if($subtype=='relation'){
					?><option value="{RBADDNEW}" style="background-color:thistle;">&lt;Add new entry..&gt;</option><?php
				}
				?>
				</select><?php
			break;
			case 'checkbox':
				?><input type="hidden" name="<?php echo $name;?>" value="0" /><input type="checkbox" name="<?php echo $name;?>" <?php foreach($output as $o=>$w)if($o!=='value')echo $o.'="'.$w.'" ';?><?php echo ' value="1"';?> <?php echo ($mode==$insertMode && $Default=='1') || ($output['value']=='1')?'checked':'';?> /><?php
			break;
			case 'radio':
			
			break;
			default:
				continue;
		}
		$out=ob_get_contents();
		ob_end_clean();

		if($type=='hidden'){
			$hiddenFields[$Field]=$out;
			continue;
		}

		?><tr>
		<td style="padding-top:8px;"><span class="<?php echo strlen($add[strtolower($Field)]['comment']) ? 'comment' : '';?>" title="<?php echo strlen($add[strtolower($Field)]['comment']) ? h($add[strtolower($Field)]['comment']) : '';?>"><?php echo $col['label']?$col['label']:preg_replace('/([a-z])([A-Z])/','$1 $2',$Field);?></span><?php
		
		?></td>
		<td><?php
		echo $out;
		?></td></tr><?php
	}
	?></table>
	<!-- {RBSYSTEMENTRY:after_form} -->
	<?php
	
	/* -----------------------
	started development on this 2012-12-12; first step was to get settings stored in the profile and easily editable in _raw_; that done, I can build this out pretty easily, with current version=1.0.  Pretty simple but gets me handling line items for invoices and checks, events for people, people for classes, and etc..
	
	TODO:
		the way I merge attributes for fields on the main root fields is a great system and needs encapsulated here for both, conjoining insert|updateMode, possibleAttributes and specified attributes

	----------------------- */
	if(($s=$sub_table) && $s['active']){
		//check version
		if($s['version']!==1.0)exit('only 1.0 version of sub_table developed');
		
		//get subtable information
		ob_start();
		$subTable=q('EXPLAIN '.$s['table'], O_ARRAY);
		$err=ob_get_contents();
		ob_end_clean();
		if($err)prn('Error on sub table declaration: '.$err,1);
		$cognates=explode('_',strtolower($object));
		foreach($subTable as $n=>$v){
			unset($subTable[$n]);
			$subTable[strtolower($v['Field'])]=$v;
			if(strtolower($v['Field'])==strtolower($s['foreign_key']))$foreignKey=$v['Field'];
			if($s['foreign_key'])continue;
			//auto detect foreign key
			preg_match('/([^_]+)_(id|username)$/i',$v['Field'],$m);
			if(strlen($m[1]) && in_array(strtolower($m[1]),$cognates)){
				$s['foreign_key']=$foreignKey=$v['Field'];
			}
		}
		if(!$foreignKey)exit('Foreign key in subtable not determined');

		//first develop the query
		$sql='SELECT ';
		$sql.=($s['fieldset'] ? $s['fieldset'] : 'a.*');
		$sql.="\nFROM\n".$s['table'].' a, '.$object.' root '."\nWHERE\n";
		$sql.='root.'.$recordPKField[0].'="'.$GLOBALS[$recordPKField[0]].'" AND ';
		$sql.="a.$foreignKey = root.ID";
		if($n=$s['exclusion'])$sql.=' AND '.$n."\n";
		if($n=$s['order_clause'])$sql.='ORDER BY '.$n;
		ob_start();
		$subRecords=q($sql,O_ARRAY, ERR_ECHO);
		$err=ob_get_contents();
		ob_end_clean();

		if($n=trim($s['custom_css'])){
			?><!-- subTable custom css --><style type="text/css"><?php echo "\n".$n."\n";?></style><?php echo "\n";
		}
		if($n=trim($s['custom_js'])){
			?><!-- subTable custom js --><script language="javascript" type="text/javascript"><?php echo "\n".$n."\n";?></script><?php echo "\n";
		}
		?><div id="subTable">
		<?php
		if($n=$s['title']){
			?><h3><?php echo $n;?></h3><?php echo "\n";
		}
		if($n=$s['instructions']){
			?><p class="gray"><?php echo $n;?></p><?php echo "\n";
		}
		?>
		<table class="subTable">
		<?php
		//determine if we need a footer
		unset($summaries);
		foreach($s['columns'] as $n=>$v){
			if($v['summaries'])$summaries[$n]=$v['summaries'];
		}
		if($s['heading']){
			?><thead><tr>
			<?php
			foreach($s['columns'] as $n=>$v){
				if($v['type']=='hidden')continue;
				?><th><?php echo $v['heading']?$v['heading']:$n;?></th><?php
			}
			?>
			</tr></thead><?php
		}
		if($summaries)ob_start();
		?>
		<tbody><?php
		for($i=1; $i<=count($subRecords)+$s['blank_rows']; $i++){
			?><tr><?php
			$r=$subRecords[$i];
			$j=0;
			$hidden=array(
				//primary key of sub record
				'<input type="hidden" name="sub[ID][]" value="'.h($r['ID']).'" />'
			);
			//storage of values
			if(!empty($r) && $summaries)
			foreach($summaries as $n=>$v){
				//column -> array
				foreach($v as $null=>$w){
					//null -> type
					$storage[$n][$w][]=$r[$n];
				}
			}
			foreach($s['columns'] as $n=>$v){
				$j++;
				if($v['type']=='hidden'){
					$hidden[]='<input type="hidden" name="sub['.$n.'][]" value="'.h($r[$n]).'" />';
					continue;
				}
				//attributes
				$attributes='';
				if($v['attributes'])
				foreach($v['attributes'] as $o=>$w){
					$attributes.= ' '.$o.'="'.h($w).'"';
				}
				
				?><td>
				<input type="text" name="sub[<?php echo $n;?>][]" value="<?php echo h($r[$n]);?>" onChange="dChge(this);"<?php echo $attributes;?> />
				<?php 
				//put hidden fields here
				if($j==count($s['columns']))echo "\n".implode("\n",$hidden)."\n";
				?>
				</td><?php
			}
			?></tr><?php
		}
		?></tbody>
		<?php
		if($summaries){
			$tbody=ob_get_contents();
			ob_end_clean();
			?><tfoot>
			<tr>
			<?php
			foreach($s['columns'] as $n=>$v){
				//fill the columns with the appropriate values
				?><td id="subtable_tfoot_<?php echo $n;?>"><?php
				unset($footer);
				if($o=$storage[$n]){
					foreach($o as $f=>$w){
						$footer[$f]=lib_calculate($f,$w);
					}
					$j=0;
					foreach($footer as $o=>$w){
						$j++;
						echo (count($footer)>1 ?$o.': ':'').$w.(count($footer)>1 && $j<count($footer)?'<br />':'');
					}
				}else{
					?>&nbsp;<?php
				}
				?></td><?php
			}
			?>
			</tr>
			</tfoot><?php echo "\n";
			echo $tbody;
		}
		?>
		</table>
		</div><?php
	}
	if(!empty($hiddenFields))echo implode("\n",$hiddenFields);
}
}
?>