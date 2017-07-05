<?php
$functionVersions['make_clickable_links']=1.0;
function make_clickable_links($text) {
	//got this from http://74.125.47.132/search?q=cache:UHBp50SQA4EJ:www.totallyphp.co.uk/code/convert_links_into_clickable_hyperlinks.htm+converting+emails+and+URLs+in+text+into+Links&cd=7&hl=en&ct=clnk&gl=us&client=firefox-a
	$text = eregi_replace('(((f|ht){1}tp://)[-a-zA-Z0-9@:%_\+.~#?&//=]+)',
	'<a href="\\1">\\1</a>', $text);
	$text = eregi_replace('([[:space:]()[{}])(www.[-a-zA-Z0-9@:%_\+.~#?&//=]+)',
	'\\1<a href="http://\\2">\\2</a>', $text);
	$text = eregi_replace('([_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,3})',
	'<a href="mailto:\\1">\\1</a>', $text);
	return $text;
}
?>