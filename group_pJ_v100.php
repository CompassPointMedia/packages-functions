<?php
$functionVersions['pJ']=1.00;
function pJ(){
	//placeholder
	return true;
}
function pJ_call_edit($options=array()){
	/*
	2012-03-09: ONE less thing a component needs to do; if declared right an edit link will make the right calls automatically with this function
	
	*/
	global $MASTER_USERNAME, $fromHdrBugs, $developerEmail, $pJEditability;
	extract($options);
	if(!empty($level)){
		global $adminMode;
		if($adminMode<$level)return;
	}
	if(empty($editURL)) $editURL='/_juliet_.editor.php';
	if(!empty($thisnode)){
		//OK
	}else if(empty($pJCurrentContentRegion)){
		global $pJCurrentContentRegion;
		if(!$pJCurrentContentRegion)return;
	}
	if(empty($handle)){
		global $handle;
		if(!$handle){
			mail($developerEmail, 'Error in '.$MASTER_USERNAME.':'.end(explode('/',__FILE__)).', line '.__LINE__,get_globals($err='no handle declared for component file'),$fromHdrBugs);
			return;
		}
	}
	$f = __FILE__;
	if(empty($formNode))$formNode='default';
	if(empty($location))$location=(strstr($f, 'components-juliet')?'JULIET_COMPONENT_ROOT':'MASTER_COMPONENT_ROOT');
	if(empty($file))return;
	
	?><a href="<?php echo $editURL;?>?<?php echo !empty($thissection) ? 'thissection='.$thissection : (!empty($thisnode) ? '_thisnode_='.$thisnode : 'pJCurrentContentRegion='.$pJCurrentContentRegion); ?>&handle=<?php echo $handle;?>&location=<?php echo $location;?>&file=<?php echo $file;?>&formNode=<?php echo $formNode;?><?php
	if(!empty($parameters))
	foreach($parameters as $n=>$v){
		echo '&'.$n.'='.urlencode($v);
	}
	?>" class="_editLink_<?php echo $pJEditability ? $pJEditability : 1;?>" onclick="return ow(this.href,'l1_editor','720,600');"><img src="/images/i/plusminus-plus.gif" alt="edit" /><?php
	if(!empty($label))echo '&nbsp;'.$label;
	?></a><?php
}
function pJ_getdata($options, $default=NULL){
	/*
	2012-03-09: way of getting field values in multiple locations
	pass a single value = field to get value for
	pass two values = field, default (if zero)
	example:
	pJ_getdata(array(
		'field'=>'credits',
		'subKey'=>'2013-collection',
	),'default_value_here');
	*/
	global $pJ, $handle;
	if(is_array($options)){
		if(count($options)==1){
			$field=$options[0];
		}else extract($options);
		if(!$field)exit('improper call of pJ_getdata');
	}else{
		if(isset($default)){
			$options=func_get_args();
			$field=$options[0];
		}else $field=$options;
	}
	//assume the location is in componentFiles
	//prn($pJ['componentFiles'][$handle]['data'],1);	
	if(!$pJ['componentFiles'])return (isset($default) ? $default : '');
	foreach($pJ['componentFiles'] as $n=>$v){
		if(strtolower($n)==strtolower($handle)){
			$a=$pJ['componentFiles'][$n]['data'];
			break;
		}
	}
	if(!$a){if(isset($default)){
		return $default;
	}else{
		return;
	}}
	foreach($a as $n=>$v){
		/* this could be a conflict but hey, I'm just a function trying to give you what you need.. */
		if(strtolower($n)==strtolower($field))return $v;
		if(is_array($v))
		foreach($v as $o=>$w){
			if(strtolower($o)==strtolower($field)){
				if(is_array($w) && isset($subKey) /* NOTE: we return the "subkey" value of the array even if it doesn't exist - && isset($w[$subKey])*/){
					return $w[$subKey];
				}else{
					return $w;
				}
			}
		}
	}
	if(isset($default))return $default;
}
?>