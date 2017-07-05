<?php
$functionVersions['replace_form_elements']=1.10;
function replace_form_elements($text, $options=array()){
	/*
	2009-02-17
	----------
	* moved over to a_f
	* new options:
	------------
	hideElement[button|submit] - default true
	wrapTexts				- default true
	specificElementClass	- pass as array with lower case element name
	tagTypeClass			- specific class for a tag type (an array with lower case tag type)
	generalClass			- general class name, otherwise class=replace-form-elements
	omitClass				- default false
	useIDs					- default true
	convertFunction			- pass as array, also with lower case element name
	leaveTextareasUntouched - default false
	
	
	2007-07-03
	----------
	replaces input type="text" tags, textareas, and select areas for more readable printable text. Options:
	wrap[general] -> span class="mystyle"
	(or)
	wrap[input] -> span class="mystyle"
	wrap[textarea] -> div class="mystyle2"
	wrap[select] -> span class="mystyle"


	*/
	@extract($options);
	if(!isset($wrapTexts))$wrapTexts=true;
	if(is_array($specificElementClass)){
		foreach($specificElementClass as $n=>$v){
			$t[strtolower($n)]=$v;
		}
		$specificElementClass=$t;
	}
	if(!isset($useIDs))$useIDs=true;
	if(!isset($omitClass))$omitClass=true;
	if(!isset($leaveTextareasUntouched))$leaveTextareasUntouched=false;
	if(!isset($hideElement['button']))$hideElement['button']=true;
	if(!isset($hideElement['submit']))$hideElement['submit']=true;
	$reg=array(
		'button'=>'/<input[^>]+type=("|\')button("|\')[^>]+value=("|\')([^"]*)("|\')[^>]*?>/i',
		'submit'=>'/<input[^>]+type=("|\')submit("|\')[^>]+value=("|\')([^"]*)("|\')[^>]*?>/i',
		'input'=>'/<input[^>]+type=("|\')text("|\')[^>]+(value=("|\')([^"]*)("|\'))*[^>]*?>/i',
		'textarea'=>'/<textarea[^>]*>([^>]*?)<\/textarea>/i',
		'select'=>'/<select[^>]+>(.|\s)+?<\/select>/i'

	);
	foreach($reg as $tagType=>$pattern){
		$i=0;
		$textModified=false;
		preg_match($pattern,$text,$inputs);
		while($inputs){
			$textModified=true;

			$from=strstr($text,$inputs[0]);
			$buffer.=substr($text,0,strlen($text)-strlen($from));
			if(
				($tagType=='button' && $hideElement['button']) || ($tagType=='submit' && $hideElement['submit'])){
				//do nothing
			}else{
				//get the name of the object
				if(preg_match('/<[a-z]+[^>]+name=("|\')([][a-z0-9-_]+)("|\')/i',$inputs[0],$name)){
					$name=$name[2];
				}else{
					$j++;
					$name=$j;
				}
				if($tagType=='select'){
					//dropdown list
					preg_match('/<option[^>]+selected[^>]*>([^>]*)<\/option>/i',$inputs[0],$option);
					$stringValue=$option[1];
				}else{
					//others - input/textarea
					$stringValue=$inputs[($tagType=='input' ? 5 : 1)];
				}

				if($wrapTexts){
					//get class - specific to most general
					if($class=$specificElementClass[strtolower($name)]){
						//OK
					}else if($class=$tagTypeClass[$tagType]){
						//OK
					}else if($class=$generalClass){
						//OK
					}else if(!$omitClass){
						$class="-replace-form-elements";
					}
					$class=($class ? ' class="'.$class.'"' : '');
					if($useIDs){
						$id=' id="'.(is_numeric($name)?'unk':'ele').'-'.$name.'"';
					}else{
						$id='';
					}
					$buffer.='<span'.$id.$class.'>';
				}
				//convert the value if necessary
				if($fctn=$convertFunction[strtolower($name)]){
					//not developed yet
					$stringValue=$fctn($stringValue);
				}else if($tagType=='textarea'){
					$stringValue=trim($stringValue);
					if(!preg_match('/<(br|div|p)[^>]*>/i',$stringValue) && !$leaveTextareasUntouched)$stringValue=nl2br($stringValue);
				}
				//output
				$buffer.=$stringValue;
				
				if($wrapTexts){
					$buffer.='</span>';
				}
			}
			$text=substr($from,strlen($inputs[0])-strlen($from));
			preg_match($pattern,$text,$inputs);
			
			$i++;
			if($i>1000){
				//email of loop error - should not happen
				break;
			}
		}
		$textModified ? $buffer.=$text : $buffer=$text;
		$text=$buffer;
		$buffer='';
	}
	return $text;
}

/* ---------------- example use ----------------------
ob_start(); ?>

sample text here 
<input name="textfield" type="text" value="and here we go">

and more text here
<input name="textfield" type="text" value="and here we go again">

<textarea>text area here .... </textarea>
<select name="something">
	<option value="" selected="selected">label here</option>
</select>

and more text here<?php
$out=ob_get_contents();
ob_end_clean();
echo '<pre>|';
echo replace_form_elements($out);
echo '|</pre>';
*/
?>
