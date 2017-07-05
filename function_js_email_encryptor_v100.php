<?php
$functionVersions['js_email_encryptor']=1.00;
function js_email_encryptor($email,$text='',$class='',$style=''){
	//2009-01-21: based on js function write_check() in /Library/js/common_04_i1.js
	$rand=rand(0,10000);
	?><script language="javascript" type="text/javascript">
	var v<?php echo $rand?> ='write_check("<?php 
	//email
	for($i=0;$i<strlen($email);$i++) echo ','.ord($email[$i]);
	?>","<?php 
	$text=($text?$text:$email);
	//text
	for($i=0;$i<strlen($text);$i++) echo ','.ord($text[$i]);
	?>"<?php
	if($class || $style)echo ',"'.$class.'"';
	if($style)echo ',"'.$style.'"';
	
	?>);';
	eval(v<?php echo $rand?>);
	</script><?php
}
?>