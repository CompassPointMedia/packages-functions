<?php
$functionVersions['get_text_region']=2.00;
function get_text_region($Name){
	global $get_text_region, $adminMode, $developerEmail, $fromHdrBugs, $qr, $fl, $ln;

	$text=q("SELECT Body, Type FROM gen_textareas WHERE Name = '$Name'",O_ROW);
	@extract($text);

	//admin mode coding
	if($adminMode){
		?><script language="javascript" type="text/javascript">
		function toggletr(o){
			var state=o.nextSibling.style.display;
			o.innerHTML=(o.innerHTML=='Show text editor' ? 'Hide text editor' : 'Show text editor');
			o.nextSibling.style.display=op[state];
			return false;
		}
		</script><div class="textRegionEditor">
		<a id="toggle-<?php echo $Name?>" href="#" onclick="return toggletr(this);">Show text editor</a><div style="display:none;">
		<form id="textForm-<?php echo $Name?>" method="post" action="/index_01_exe.php" style="display:inline;" target="w2">
		<br>
		Type: 
		<select name="Type" id="Type">
			<option value="2">HTML</option>
			<option value="1">Text</option>
			<option value="3">Smart HTML</option>
		</select>
		<br>
		<textarea name="Body" cols="55" rows="18" id="Body"><?php echo h($Body);?></textarea>
		<input name="mode" type="hidden" id="mode" value="editTextRegion" />
		<input name="Name" type="hidden" id="Name" value="<?php echo $Name;?>" />
		<br>
		<input type="submit" name="Submit" value="Update" />
		<br>
		</form>
		</div>
		</div><?php
	}
	?><div id="textRegion-<?php echo $Name?>">
	<!-- function get_text_region(), v1.0, passed name: <?php echo $Name?> --><?php
	if($text['Type']==2){
		echo $text['Body'];
	}else if($text['Type']==1 || true){
		echo $text['Body'];
	}
	?></div><?php
}
?>