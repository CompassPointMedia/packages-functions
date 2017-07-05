<?php
/* 

todo:
----------
pass php-created objects
	slideshow('param1','param2'); in php
	outputs <div id="something" dynamicOutput="true" dynamicLibType="slideshow" .. ver="1.0" .. param1= ..></div>
	CMSB editor converts this to a placeholder
	receiving code re-constitutes all the parameters

lightly highlight the region being worked on when editing
	window.opener.g(cmssection).style.borderLeft='1px dotted #333';
	window.opener.g(cmssection).style.paddingLeft='7px';
	window.opener.g(cmssection).style.borderRight='1px dotted #333';
	window.opener.g(cmssection).style.paddingRight='7px';

2008-12-16
----------
CMS Editor Suite started 2008-11-20

the essense of what I want to do is put this code on a page:

CMSB(); // CMS-Bridge instance

and have everything take care of itself.  And to boot, be able to put this code in several locations on the page.  When this is done, in the absence of any other information, the function will "think" as follows:

1. the region name will section1; each additional region is section2, section3, section4, etc.
2. the table the content can be found in is cmsb_sections
3. the fieldset that identifies the content is thispage, thisfolder, and thissection=section1
4. I will configure myself so that the editing button is ABOVE the content
5. The content will be contained in a div or a span
6. I am then able to click the button, and live-edit the content in a popup (alternately I will be able to live-edit in place eventually)

* If the content is not present in the database then I will create placemark text.
* Alternately if directed I will "get" the content of the first div or span with an id attribute
when this is submitted I will have double-text and can then delete the static text from the page [this is the only real inconvenience present in the system]
* this could be overcome by an account giving the "keys" to the FTP and allowing RelateBase to do it itself

Beyond this, we need to handle editing content in dynamic pages, or content in descriptions of products for example; all of these are more complex.  However the objective is to be able to attach the editor to anything and have it work with as simple instructions as possible.

We also must respect objects like slideshows, represent them with placeholders, eventually editable, and sequester the actual code in a hidden field

We will need to administer the content permissions on the backend quickly and easily, with something more advanced than adminMode
	slideshow 
		version 2.1
		path=to/the/pictures
		width=
		height=




These notes are from 11/20 and may not be useful:
-------------------------------------------------
region description sample
-------------------------
1. member webpage
	this content is from table finan_clients.WebContent 
	this content's key field is found in the table as ID
	this content's key field value is found on the page as
		* part of the div id
		* part of the div attribute
		* part of the page querystring as Clients_ID
	[UPDATE finan_items SET WebContent=[string] WHERE ID=[env. variable]]
	this content is located before the editor edit object (previous id'd sibling)
2. item description
	this content is from table finan_items
	this content's key field is found in the table as ID
	[same as above]
also
how big it is to be

two ways to declare the editor
- the whole region
- after the section I want to edit

*/
$functionVersions['CMSB']=1.00;
function CMSB($section='', $method='', $options=array()){
	/*
	Created 2008-12-16 by Samuel
	options
		method
		CMSDb
		CMSTable
		CMSContentField
		primaryKeyField
		primaryKeyLabel - if different in the query string
		primaryKeyValue - if not passed the CMS will look in the query string (and pass this as URL)
		adminMode (can be overridden as needed)
		hideInitialContentEditPrompt - default=false; (link to edit will be present for initial no-content state)
		grabExistingContent - default=blank; when no initial content present, look for the first <span> or <div> tag either before or after the CMSB() call, to get it into the database.  Values should be 'before' or 'after'
		initialText - initial explanatory text when no content present (and not using the grabExistingContent method)
	*/
	global $fl,$ln,$qr,$qx, $developerEmail,$fromHdrBugs, $CMSB,$CMSBx, $thispage, $thisfolder, $adminMode;
	if(!strlen($thispage) || !isset($thisfolder)){
		$a=preg_split('/\\\|\//',$_SERVER['PHP_SELF']);
		$thispage=$a[count($a)-1];
		if(count($a)>2){
			$thisfolder=$a[count($a)-2];
		}else{
			$thisfolder='';
		}
	}
	if(!isset($CMSB['sections']))$CMSB['sections']=array();
	if(!$method)$method='static:default';

	if(!$section)$section='section1';
	extract($options);
	
	if(!$cnx)$cnx=$qx['defCnxMethod'];
	
	//get or assign the name for the section
	if(array_key_exists(strtolower($section), $CMSB['sections'])){
		if(strtolower($section)=='section1'){
			//increment
			$max=0;
			foreach($CMSB['sections'] as $n=>$v){
				if(preg_match('/^section([0-9]+)$/i',$n,$a)){
					$a[1]=ltrim($a[1],'0');
					if(is_numeric($a[1]) && $a[1]>$max){
						$max=$a[1];
					}
				}
			}
			$max++;
			$section='section'.$max;
		}else{
			mail($developerEmail,'CMSB Error: duplicate section name in file '.__FILE__.', line '.__LINE__, get_globals(), $fromHdrBugs);
			exit('Duplicate section name present in CMSBridge');
		}
	}
	$CMSB['sections'][strtolower($section)]=array();

	//where data is stored
	if(!$table)$table=$CMSBx['defaultTableName'];
	//make sure table exists
	if(!$CMSB['tables'][$table]){
		if($tables=q("SHOW TABLES ".($CMSDb?' IN '.$CMSDb:''), O_ARRAY)){
			foreach($tables as $n=>$v){
				if(!$CMSDb){
					foreach($v as $o=>$w)preg_match('/Tables_in_(.+)/',$o,$a);
					$CMSDb=$a[1];
					$CMSB['sections'][$section]['db']=$CMSDb;
				}
				if($v['Tables_in_'.$CMSDb]==$table){
					$CMSB['sections'][$section]['table']=$table;
					$haveTable=$table;
					break;
				}
			}
		}
		if(!$haveTable && $table==$CMSBx['defaultTableName']){
			//will fail if cnx is bad or permissions lacking
			ob_start();
			q($CMSBx['defaultTableStructure'], $cnx, ERR_ECHO);
			$err=ob_get_contents();
			ob_end_clean();
			if($err){
				mail($developerEmail,'CMSB Error: error creating table '.$table.' in file '.__FILE__.', line '.__LINE__, get_globals(), $fromHdrBugs);
				echo('Unable to create content table '.$CMSBx['defaultTableName'].' in db '.$CMSDb.' for CMSBridge');
			}
		}
	}

	if(!$editSectionLabel)$editSectionLabel='edit section';

	if(!$CMSB['jsCheck']){
		$jsCheck='<\'+\'script language="javascript" type="text/javascript" src="/Library/js/CMSB_v100.js"></\'+\'script>';
		$jsCheck='if(typeof CMSB==\'undefined\')document.write(\''.$jsCheck.'\');';
		$CMSB['jsCheck'];
		?><script language="javascript" type="text/javascript">
		//variables for this link here
		<?php echo $jsCheck?>
		</script><?php
	}

	//determine the fieldset that locates the content
	if(strtolower($method)=='static:default'){
		$content=q("SELECT
		IF(b.Content IS NOT NULL, b.Content, a.Content) AS Content,
		IF(b.ID IS NOT NULL,1,0) AS Edited,
		IF(b.ID IS NOT NULL,b.EditDate,a.EditDate) AS EditDate,
		IF(b.ID IS NOT NULL,b.EditNotes,a.EditNotes) AS EditNotes,
		IF(b.ID IS NOT NULL,b.ID,a.ID) AS ID
		FROM ".$table." a LEFT JOIN ".$table." b ON a.ID=b.Sections_ID WHERE
		a.ThisFolder='".addslashes($thisfolder)."' AND
		a.ThisPage='".addslashes($thispage)."' AND
		a.Section='".addslashes($section)."'
		ORDER BY b.EditDate DESC LIMIT 1", O_ROW);
		
		//we provide a link only which will by default create a div below to edit, but if not will by JS pull content from the next content [below]
		if($adminMode || (!$content && !$hideInitialContentEditPrompt) || $thispage=='fcktest.php'){
			ob_start();
			?>
			[<a id="CMSB-<?php echo $section?>" method="<?php echo $method?>" title="Edit the section <?php echo strtolower($linkPosition)=='below'?'above':'below'?>" href="#" onclick="return CMSBedit(this, '<?php echo $method?>', <?php echo $CMSBConvertExistingHTML ? 'null' : "'".addslashes($section)."'"?>);"><?php echo $editSectionLabel?></a>]<?php
			$link=ob_get_contents();
			ob_end_clean();
		}
		ob_start();
		if($content){
			//show it
			echo "\n";
			echo '<!-- printed by CMS Bridge('.$section.'); method='.$method.'; content last edited '.date('m/d/Y \a\t g:iA',$a['EditDate']) . ' -->';
			echo "\n";
			?><div id="<?php echo $section?>" ondblclick="CMSBEditFromContent(this,event);" method="<?php echo $method?>"><?php
			/*
			NOTES:
			1. how to resolve conflict between the id and class the designer might want and the vars we need to identify the region - the solution is to store everything in the javascript array keyed to that section NAME
			2. we should analyze content for cleanness
			3. if the content has no HTML we should do nl2br
			
			*/
			echo $content['Content'];
			echo "\n";
			?></div><?php
		}else if($grabExistingContent){
			//we are going to provide a link and the content to be edited by Javascript
			echo "\n";
			echo '<!-- printed by CMS Bridge('.$section.'); method='.$method.'; content last edited '.date('m/d/Y \a\t g:iA',$a['EditDate']) . ' -->';
			echo "\n";			
			?><div id="<?php echo $section?>" ondblclick="CMSBEditFromContent(this,event);" method="<?php echo $method?>"><?php 
			echo $initialText;
			echo "\n";
			?></div><?php
		}
		$body=ob_get_contents();
		ob_end_clean();
		
		//output
		echo $link;
		echo $body;
	}else if(strtolower($method)=='dynamic:simple'){
		/*
		default table is cms1_articles
		default field is "content"
		default primary key field is "ID" [presumed to be found in the query string]
		default label is "Articles_ID" (could also be ID etc.)
		*/
		if(!$CMSTable)$CMSTable='cms1_articles';
		if(!$CMSContentField)$CMSContentField='Body';
		if(!$primaryKeyField)$primaryKeyField='ID';
		if(!$primaryKeyFieldLabel)$primaryKeyFieldLabel='Articles_ID';
		if(!$primaryKeyValue){
			global $$primaryKeyFieldLabel;
			$primaryKeyValue=$$primaryKeyFieldLabel;
		}
		$content=q("SELECT a.*, a.$primaryKeyField AS ID, a.$CMSContentField AS Content FROM $CMSTable a WHERE $primaryKeyField='$primaryKeyValue'", O_ROW);
		if($adminMode || (!$content && !$hideInitialContentEditPrompt)){
			ob_start();
			?>
			[<a id="CMSB-<?php echo $section?>" method="<?php echo $method?>" CMSTable="<?php echo $CMSTable?>" CMSContentField="<?php echo $CMSContentField?>" primaryKeyField="<?php echo $primaryKeyField?>" primaryKeyFieldLabel="<?php echo $primaryKeyFieldLabel?>" <?php if($primaryKeyValue){ ?>primaryKeyValue="<?php echo $primaryKeyValue?>"<?php } ?> title="Edit the section <?php echo strtolower($linkPosition)=='below'?'above':'below'?>" href="#" onclick="return CMSBedit(this, '<?php echo $method?>', <?php echo $CMSBConvertExistingHTML ? 'null' : "'".addslashes($section)."'"?>);"><?php echo $editSectionLabel?></a>]
			<?php
			$link=ob_get_contents();
			ob_end_clean();
		}
		ob_start();
		if($content){
			//show it
			echo "\n";
			echo '<!-- printed by CMS Bridge('.$section.'); method='.$method.'; content last edited '.date('m/d/Y \a\t g:iA',$a['EditDate']) . ' -->';
			echo "\n";
			?><div id="<?php echo $section?>" ondblclick="CMSBEditFromContent(this,event);" method="<?php echo $method?>"><?php
			/*
			NOTES:
			1. how to resolve conflict between the id and class the designer might want and the vars we need to identify the region - the solution is to store everything in the javascript array keyed to that section NAME
			2. we should analyze content for cleanness
			3. if the content has no HTML we should do nl2br
			
			*/
			echo $content['Content'];
			echo "\n";
			?></div><?php
		}else if($grabExistingContent){
			//we are going to provide a link and the content to be edited by Javascript
			echo "\n";
			echo '<!-- printed by CMS Bridge('.$section.'); method='.$method.'; content last edited '.date('m/d/Y \a\t g:iA',$a['EditDate']) . ' -->';
			echo "\n";			
			?><div id="<?php echo $section?>" ondblclick="CMSBEditFromContent(this,event);" method="<?php echo $method?>"><?php 
			echo $initialText;
			echo "\n";
			?></div><?php
		}
		$body=ob_get_contents();
		ob_end_clean();
		
		//output
		echo $link;
		if(true || !$CMSBDoesNotGenerateContent)echo $body;
	}
}
function CMSBUpdate($options=array()){
	global $fl,$ln,$qr,$qx,$CMSBx,$developerEmail,$fromHdrBugs;
	//note: this will trump any local thispage, thisfolder settings present with what was passed
	$a=$_POST;
	extract($a);
	extract($options);
	global $adminMode;
	if($adminMode){

		if($method==='static:default'){
			$Sections_ID=q("SELECT ID FROM cmsb_sections WHERE ThisFolder='$thisfolder' AND ThisPage='$thispage' AND Section='$thissection' AND Sections_ID IS NULL", O_VALUE);
			prn($qr);
			$ID=q("INSERT INTO cmsb_sections SET
			".($Sections_ID?"Sections_ID='$Sections_ID'," : '')."
			ThisFolder='$thisfolder',
			ThisPage='$thispage',
			Section='$thissection',
			Content='$CMS',
			EditNotes='$EditNotes',
			Editor='".($_SESSION['systemUserName'] ? $_SESSION['systemUserName'] : 'administrator')."'", O_INSERTID);
			prn($qr);
		}else if($method=='dynamic:simple'){
			if($primaryKeyValue){
				q("UPDATE $CMSTable SET $CMSContentField='$CMS', Editor='system' WHERE $primaryKeyField=$primaryKeyValue");
			}else{
				$NewID=q("INSERT INTO $CMSTable SET $CMSContentField='$CMS', EditDate=NOW(), Editor='system'", O_INSERTID);
			}
			prn($qr);
		}
		?>
		<div id="content"><?php echo stripslashes($CMS)?></div>
		<script language="javascript" type="text/javascript">
		window.parent.g('CMSBUpdate').disabled=true;
		window.parent.HTML=document.getElementById('content').innerHTML;
		window.parent.oEditor.ResetIsDirty();
		</script><?php
	}else{
		error_alert('You are not in admin mode and not able to edit content right now.  Please log in');
	}
}

$CMSBx['defaultTableName']='cmsb_sections';
$CMSBx['defaultTableStructure']='CREATE TABLE `'.$CMSBx['defaultTableName'].'` (
 `ID` int(11) unsigned NOT NULL auto_increment,
 `Sections_ID` int(11) unsigned default NULL COMMENT \'heirarchy\',
 `ThisFolder` char(255) NOT NULL,
 `ThisPage` char(255) NOT NULL,
 `Section` char(255) default NULL,
 `Content` longtext NOT NULL,
 `EditNotes` char(255) NOT NULL,
 `EditDate` timestamp NOT NULL default CURRENT_TIMESTAMP,
 `Editor` char(20) NOT NULL default \'cmsb-system\',
 PRIMARY KEY  (`ID`),
 KEY `Regions_ID` (`Sections_ID`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COMMENT=\'Version 1.0.0 Created 2008-12-17 by CPM sam-git@compasspointmedia.com\'';

?>