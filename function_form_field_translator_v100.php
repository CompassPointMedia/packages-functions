<?php
if(false){
	/* ---------- example usage of the function --------------- */
	$formData[preferences]='thai';
	$formData[something]='green';
	$formData[myta]='how are you doing?';
	$formData['can be long text']='No';
	ob_start(); ?>
	  <p>[input:something default=red]</p>
	  <p>[select:preferences options='mexican, italian, Thai' default='mexican']</p>
	  <p>[select:state option='{state_list:config}']</p>
	  <p>[checkbox:somethingelse default=checked label='I really like this']</p>
	  <p>[radio:wantbananas option='Yes' option='No' default='Yes']</p>
	  <p>[textarea:myta cols=45 rows=5] </p>
	<?php 
	$out=ob_get_contents();
	ob_end_clean();
	echo form_field_translator($out);
}
$functionVersions['form_field_translator']='1.0';
function form_field_translator($out,$options=array()){
	/* 
	created 2011-06-24 by Samuel to rapidly type forms 
	options:
		globalAttributes = array(),e.g. class=th1
		arrayString; default = formData;

	Rules:
	1. elements are surrounded by square brackets; no method exists for escaping a square bracket so limits javascript
	2. first string = input|select|checkbox|radio|textarea - element type
	3. field name is defined after element type as [select:state .. ]
	4. attribute values with a space or anything besides [._a-z0-9] must be enclosed in single quotes
	5. no method exists for escaping a single quote
	6. options may be declared as either
		option=1 option=2 option='ham sandwich' option='hot dog'
		or
		options='1, 2, ham sandwich, hot dog' - 
		* values are trimmed
		* note no method exists for escaping a comma
	
	*/
	extract($options);
	if(!$arrayString)$arrayString='formData';
	
	if($m=preg_match_all('/\[(hidden|input|select|checkbox|radio|textarea):([a-z0-9_]+)(\s+([^]]+))*\s*\]/i',$out,$a)){
		$fn=$a[2];
		$s=$a[4];
		for($i=0; $i<$m; $i++){
			
			unset($field);
			
			//parse all atrributes
			//preg_match_all('/(\s|^)([][.-a-z0-9_]+)(=((\'([^\']*)\')|([._a-z0-9%]+)))*/i',$s[$i],$b);
			preg_match_all('/(\s|^)([]:[.a-z0-9_-]+)(=((\'([^\']*)\')|([._a-z0-9%]+)))*/i',$s[$i],$b);
			
			$attributes=array();
			$directives=array();
			foreach($b[2] as $n=>$v){
				//note this allows passing value=1 value=2 value=3 consectively; value is now an array
				if(preg_match('/^:/',$v)){
					$directives[strtolower(str_replace(':','',$v))]=( strlen($b[6][$n]) ? $b[6][$n] : $b[7][$n] );
				}else if(strtolower($v)=='options'){
					//format options='1,2,3,4,5'
					$o=explode(',',( strlen($b[6][$n]) ? $b[6][$n] : $b[7][$n] ));
					foreach($o as $w){
						$attributes['option'][]=trim($w);
					}
				}else if(isset($attributes[strtolower($v)])){
					if(is_array($attributes[strtolower($v)])){
						$attributes[strtolower($v)][]=( strlen($b[6][$n]) ? $b[6][$n] : $b[7][$n] );
					}else{
						$temp=$attributes[strtolower($v)];
						unset($attributes[strtolower($v)]);
						$attributes[strtolower($v)][]=$temp;
						$attributes[strtolower($v)][]=( strlen($b[6][$n]) ? $b[6][$n] : $b[7][$n] );
					}
				}else{
					$attributes[strtolower($v)]=( strlen($b[6][$n]) ? $b[6][$n] : $b[7][$n] );
				}
			}
			if($globalAttributes)
			foreach($globalAttributes as $n=>$v){
				if(!$attributes['overrideglobal'])$attributes[$n]=$v;
			}
			ob_start();

			//field name
			$name='';
			if(!$attributes['noarray'])$name.=$arrayString.'[';
			$atom=$fn[$i];
			$name.=$atom;
			if(!$attributes['noarray'])$name.=']';
			
			//default or declared value
			if($attributes['noarray']){
				if(isset($GLOBALS[$atom])){
					$value=h($GLOBALS[$atom]);
				}else{
					$value=h($attributes['default']);
				}
			}else{
				if(isset($GLOBALS[$arrayString][$atom])){
					$value=$GLOBALS[$arrayString][$atom];
				}else{
					$value=h($attributes['default']);
				}
			}
			switch(strtolower($a[1][$i])){
				case 'input':
					?><input type="text" name="<?php echo $name;?>" id="<?php echo $name;?>" onchange="<?php echo $attributes['onchange'] ? $attributes['onchange'] : 'dChge(this)';?>" value="<?php 
					echo $value;
					?>"<?php

					//output remaining atrributes
					unset($attributes['name'], $attributes['default'], $attributes['onchange'], $attributes['noarray']);
					if(count($attributes)){
						foreach($attributes as $n=>$v){
							if(!is_array($v))echo ' '.$n.'="'.h($v).'"';
						}
					}
					?> /><?php
				break;
				case 'textarea':
					?><textarea name="<?php echo $name;?>" id="<?php echo $name;?>" onchange="<?php echo $attributes['onchange'] ? $attributes['onchange'] : 'dChge(this)';?>"<?php

					//output remaining atrributes
					unset($attributes['name'], $attributes['default'], $attributes['onchange'], $attributes['noarray']);
					if(count($attributes)){
						foreach($attributes as $n=>$v){
							if(strtolower($n)=='dynexpta'){
								echo ' style="height:16px;" class="dynExpTA" onkeyup="ta(this,\'keyup\')" onfocus="ta(this,\'focus\');" onblur="ta(this,\'blur\');"';
								continue;
							}
							if(!is_array($v))echo ' '.$n.'="'.h($v).'"';
						}
					}
					?>><?php echo h($value);?></textarea><?php
				break;
				case 'select':
					?><select name="<?php echo $name?>" id="<?php echo $name?>" onchange="<?php echo $attributes['onchange'] ? $attributes['onchange'] : 'dChge(this)';?>"<?php
					
					//output remaining atrributes
					unset($attributes['name'], $attributes['default'], $attributes['onchange'], $attributes['noarray']);
					if(count($attributes)){
						foreach($attributes as $n=>$v){
							if($n=='option')continue;
							if(!is_array($v))echo ' '.$n.'="'.h($v).'"';
						}
					}
					?>><?php
					echo "\n";
					?><option value="">&lt;Select..&gt;</option><?php
					echo "\n";
					$option=$attributes['option'];
					if(!is_array($option)){
						$n=$option;
						unset($option);
						$option[]=$n;
					}
					foreach($option as $v){
						if(preg_match('/\{([_a-z:0-9]+)\}/i',$v,$c)){
							//handle through function
							$list=$c[1];
							?><option value=""><?php echo $list;?></option><?php
							echo "\n";
							break;
						}
						?><option value="<?php echo h($v);?>" <?php

						//selection logic
						echo strtolower($value)==strtolower($v) ? 'selected' : '';
						?>><?php echo h($v);?></option><?php
						echo "\n";
					}
					?></select><?php
				break;
				case 'radio':
					if(!$attributes['option'])continue;
					if($attributes['option'] && !is_array($attributes['option'])){
						$temp=$attributes['option'];
						unset($attributes['option']);
						$attributes['option'][]=$temp;
					}
					$rand=md5(rand(1,1000000));
					ob_start();
					foreach($attributes['option'] as $v){
						if(!isset($directives['nolabel'])){
							?><label><?php echo "\n";
						}
						$radioIncrementer++;
						?><input type="radio" name="<?php echo $name?>" id="<?php echo $name.'_'.$radioIncrementer;?>" value="<?php echo h($v);?>" <?php echo $value==$v?'checked':''?> <?php echo $rand;?> /><?php
						if(!isset($directives['nolabel'])){
							?> <?php echo $v;?></label><?php
						}
						echo "\n";
						echo $attributes['break'] ? preg_replace('/^br$/i','<br />',$attributes['break']) : '&nbsp;';
						echo "\n";
					}
					$radios=ob_get_contents();
					ob_end_clean();
					unset($attributes['name'], $attributes['default'], $attributes['onchange'], $attributes['noarray'], $attributes['option'], $attributes['break']);
					$str='';
					if(count($attributes)){
						foreach($attributes as $n=>$v){
							if($n=='option')continue;
							if(!is_array($v))$str.=' '.$n.'="'.h($v).'"';
						}
					}
					echo str_replace($rand,$str,$radios);
				break;
				case 'checkbox':
					if($attributes['label']){
						$endlabel=$attributes['label'];
						?><label><?php
					}else $endlabel=false;
					?><input type="checkbox" name="<?php echo $name?>" id="<?php echo $name?>" value="1" <?php 
					echo $value ? 'checked' : '';
					?> onchange="<?php echo $attributes['onchange'] ? $attributes['onchange'] : 'dChge(this);'?>"<?php
					//output remaining atrributes
					unset($attributes['name'], $attributes['default'], $attributes['onchange'], $attributes['noarray'], $attributes['label']);
					if(count($attributes)){
						foreach($attributes as $n=>$v){
							if(!is_array($v))echo ' '.$n.'="'.h($v).'"';
						}
					}
					?> /><?php
					if($endlabel){
						echo ' '.$endlabel;
						?></label><?php
					}
				break;
			}
			$field=ob_get_contents();
			ob_end_clean();
			if($field)$out=str_replace($a[0][$i],$field,$out);
		}
	}
	return $out;
}
?>