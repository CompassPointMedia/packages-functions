<?php
/* added 2010-03-04: watch for conflicts in config.php */
function stripslashes_deep($value){
	$value = is_array($value) ?
	array_map('stripslashes_deep', $value) :
	stripslashes($value);
	return $value;
}
?>