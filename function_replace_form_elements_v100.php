<?php
$functionVersions['replace_form_elements']=1.00;
function replace_form_elements($text, $options=array()){
	/*
	leaveTextareasUntouched
	
	2009-02-17
	----------
	moved over to a_f
	
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
	if(!isset($hideElement['button']))$hideElement['button']=true;
	if(!isset($hideElement['submit']))$hideElement['submit']=true;
	$reg=array(
		'button'=>'/<input[^>]+type=("|\')button("|\')[^>]+value=("|\')([^"]*)("|\')[^>]*?>/i',
		'submit'=>'/<input[^>]+type=("|\')submit("|\')[^>]+value=("|\')([^"]*)("|\')[^>]*?>/i',
		'input'=>'/<input[^>]+type=("|\')text("|\')[^>]+value=("|\')([^"]*)("|\')[^>]*?>/i',
		'textarea'=>'/<textarea[^>]*>([^>]*?)<\/textarea>/i',
		'select'=>'/<select[^>]+>(.|\s)+?<\/select>/i'

	);
	foreach($reg as $n=>$pattern){
		$i=0;
		$textModified=false;
		preg_match($pattern,$text,$inputs);
		while($inputs){
			$textModified=true;

			$from=strstr($text,$inputs[0]);
			$buffer.=substr($text,0,strlen($text)-strlen($from));
			if(
				($n=='button' && $hideElement['button']) || ($n=='submit' && $hideElement['submit'])){
				//do nothing
			}else{
				if($n=='select'){
					preg_match('/<option[^>]+selected[^>]*>([^>]*)<\/option>/i',$inputs[0],$option);
					$buffer.=$option[1];
				}else if($n=='textarea'){
					if(!preg_match('/<(br|div|p)[^>]*>/i',$inputs[1]) && !$leaveTextareasUntouched)$inputs[1]=nl2br($inputs[1]);
					$buffer.=$inputs[1];
				}else{
					$buffer.=$inputs[($n=='input' ? 4 : 1)];
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
