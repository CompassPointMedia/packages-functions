<?php
$functionVersions['text_region_i1']=1.00;
function text_region_i1($region='mainbody', $folder='', $page='', $options=array()){
	/* 2008-06-09 
	should be placed outside any form; the controls are a bit ugly
	options - 
	mode=>submit
	editorHeight=>[int]
	*/
	global $thispage,$thisfolder,$adminMode,$developerEmail,$fromHdrBugs,$hideCtrlSection;
	@extract($options);
	if(!$folder)$folder=$thisfolder;
	if(!$page)$page=$thispage;
	if(!$page)exit('page or folder not present');
	if($r=q("SELECT * FROM site_textregions WHERE ThisPage='".addslashes($page)."' AND ThisFolder='".addslashes($folder)."' AND Region='$region'", O_ROW)){
		extract($r);
		$trimbody=@preg_replace('/^<p[^>]*>/i','',trim($Body));
		$trimbody=@preg_replace('/<\/p>$/i','',trim($trimbody));
	}
	
	if($mode=='submit'){
		if(!$adminMode)error_alert('You must be in admin mode to update page content');
		if($a=q("SELECT * FROM site_textregions WHERE
			ThisPage='$page' AND
			ThisFolder='$folder' AND
			Region='$region'", O_ROW)){
			q("UPDATE site_textregions SET Body='".$_POST['Body']."' WHERE
			ThisPage='$page' AND
			ThisFolder='$folder' AND
			Region='$region'");
		}else{
			q("INSERT INTO site_textregions SET 
			Creator='system',
			CreateDate=NOW(),
			Body='".$_POST['Body']."',
			ThisPage='$page',
			ThisFolder='$folder',
			Region='$region'");
		}
	}else if($adminMode){
		$hideCtrlSection=false;
		//control section
		?>
		<script id="3rdpartyfckeditor" language="javascript" type="text/javascript" src="/Library/fckeditor4/fckeditor.js"></script>
		<script language="javascript" type="text/javascript">
		function toggletextregion(n){
			g('<?php echo $region?>_edit').style.display=(n=='view'?'none':'block');
			g('<?php echo $region?>_view').style.display=(n=='view'?'block':'none');
			g('xToolbar').style.display=(n=='view'?'none':'block');
		}
		</script>
		<div style="background-color:#DDD;border:1px solid #272727;padding:5px 15px;-moz-opacity:.5;opacity:.5;">
			<label>
			<input name="toview" type="radio" value="view" onClick="toggletextregion(this.value);" />
			View Text
			</label>&nbsp;&nbsp;  
			<label>
			<input name="toview" type="radio" value="edit" onClick="toggletextregion(this.value);" checked="checked" />
			Edit Text
			</label>
		</div>
		<div id="xToolbar">&nbsp;</div>
		<div id="<?php echo $region?>_view" style="display:none;">
			<?php echo $trimbody ? $Body : '(no text present)'; ?>
		</div>
		<form name="edit_texarea" action="/index_01_exe.php" method="post" target="w2" style="display:inline;">
		<div id="<?php echo $region?>_edit">
			<input type="submit" name="Submit" value="Submit">
			<script type="text/javascript">
			var sBasePath= '/Library/fckeditor4/';
			var oFCKeditor = new FCKeditor('Body') ;
			oFCKeditor.BasePath	= sBasePath ;
			oFCKeditor.ToolbarSet = 'Transitional' ;
			oFCKeditor.Height = <?php echo $editorHeight ? $editorHeight : 250?> ;
			oFCKeditor.Config[ 'ToolbarLocation' ] = 'Out:xToolbar' ;
			oFCKeditor.Value = '<?php
			//output section text
			$a=@explode("\n",$Body);
			foreach($a as $n=>$v){
				$a[$n]=trim(str_replace("'","\'",$v));
			}
			echo implode('\n',$a);
			?>';
			oFCKeditor.Create() ;
			</script>
			<input name="mode" type="hidden" id="mode" value="editTextRegion">
			<input name="ThisPage" type="hidden" id="ThisPage" value="<?php echo $page?>">
			<input name="ThisFolder" type="hidden" id="ThisFolder" value="<?php echo $folder?>">
			<input name="Region" type="hidden" id="Region" value="<?php echo $region?>">
		</div>
		</form>
		
		<?php
	}else{
		if(strlen($trimbody)){
			//presume rich HTML text
			echo $trimbody;
		}else{
			mail($developerEmail,'error line '.__LINE__.', page '.$_SERVER['PHP_SELF'],get_globals(),$fromHdrBugs);
			?>
			<div style="border:1px dotted DARKBLUE;padding:35px;">
				No content present for text region <?php echo $region?>; you must be in admin mode to add content!<br />
				<a href="<?php echo $thisfolder ? '../' : ''?>admin.php?src=<?php echo urlencode($_SERVER['PHP_SELF']) . ($_SERVER['QUERY_STRING'] ? urlencode($_SERVER['QUERY_STRING']) : '')?>">Admin Mode</a></div>
<?php
		}
		
	}




}

?>