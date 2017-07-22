<?php
/* 

Change Log
--------------------------------------------
version 1.2
2009-04-23 
* added an options button with the concept of "options for this page"
* added ability to split a page up into sections by images, so that we can create a slideshow.  Passes all the existing query string parameters plus cmsPage[sectionname]=1,2,..
	!!!NOTE!!! Options can only be a 1-deep array right now due to stripslash limitations but this could be easily fixed by using stripslashes_deep

2009-01-27 - fixed the size at which the window opens
		  - removed bug "no action mode passed" by fixing js Library forms.js file
2009-01-26 - added the ability to use parameters for a new method: "static:parameters" - relies on the cmsb_sections table with 4 new fields primaryParameter, primaryValue, secondaryParameter and secondaryVal - there's info in the wiki on this at: 


todo:
----------
size down images by API'ing to file explorer and place in a "images/pieces" folder
fix the ../../../ issue when editing images
1/16/2009	handling of the table is out of place
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

* If the content is not present in the database I will "get" the content of the first div or span with an id attribute
when this is submitted I will have double-text and can then delete the static text from the page [this is the only real inconvenience present in the system]
* Alternately if directed I then will create placemark text
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
$functionVersions['CMSB']=1.20;
function CMSB($section='', $method='', $options=array()){
	/*
	2009-04-04 - added
	* ability to declare [shared(1)]:sectionname to be a shared item among pages where (0) is the default depth of the actual object meaning a root object
	v1.20 - 2009-03-04 - added
		setTagAs if present will set tag to a span this one time for example
		setClass - sets class of the div or span
	v1.10 - 2009-01-16 - modified entire logic structrure before it became unworkable, added ability to have multiple primary key fields
	Created 2008-12-16 by Samuel
	options
		method
		CMSDb
		CMSTable
		for static methods
		------------------
		convertExistingStaticHTML - default=false; if true will suppress div output so that CMS can grap EXISTING HTML content
		
		for dynamic methods incl. simple
		--------------------------------
		CMSContentField - default "Body"
		primaryKeyField - default array('ID')
		primaryKeyLabel - default array('Articles_ID') [if different in the query string]
		primaryKeyValue - if not passed the CMS will look in the query string (and pass this as URL)
		adminMode (can be overridden as needed)

		additional
		----------
		showEditLink
		initialText - default '&nbsp;' - initial explanatory text when no content present (and not using the grabExistingContent method)

		would like to use: 
		------------------
		hideInitialContentEditPrompt - default=false; (link to edit will be present for initial no-content state)
		grabExistingContent - default=blank; when no initial content present, look for the first <span> or <div> tag either before or after the CMSB() call, to get it into the database.  Values should be 'before' or 'after'
	*/
	global $fl,$ln,$qr,$qx, $developerEmail,$fromHdrBugs, $CMSB,$CMSBx, $thispage, $thisfolder, $adminMode;
	global $test, $testingcms;
	//thisfolder, thispage -
	if(!$thispage){
		if(substr($_SERVER['REQUEST_URI'],0,strlen($_SERVER['PHP_SELF']))==$_SERVER['PHP_SELF'] || !trim($_SERVER['REQUEST_URI'],'/')){
			//previous page/folder method
			if(!strlen($thispage) || !isset($thisfolder)){
				$a=preg_split('/\\\|\//',$_SERVER['PHP_SELF']);
				$thispage=$a[count($a)-1];
				if(count($a)>2){
					$thisfolder=$a[count($a)-2];
				}else{
					$thisfolder='';
				}
			}
		}else{
			//2009-04-24, new method: presumed 404 page masquerading as other page, get page from REQUEST_URI
			$a=explode('?',$_SERVER['REQUEST_URI']);
			$_qs_=$a[1];
			$a=preg_split('/\\\|\//',$a[0]);
			$thispage=$a[count($a)-1];
			if(count($a)>2){
				$thisfolder=$a[count($a)-2];
			}else{
				$thisfolder='';
			}
			if($_qs_){
				//globalize query string
				$a=explode('&',trim($_qs_,'&'));
				foreach($a as $pair){
					if(!stristr($pair,'='))continue;
					//safest most reliable way
					if(stristr($pair,'_SESSION') || stristr($pair,'HTTP_SESSION_VARS') || stristr($pair,'_SERVER') || stristr($pair,'HTTP_SERVER_VARS') || stristr($pair,'PHP_AUTH_USER') || stristr($pair,'PHP_AUTH_PW') || stristr($pair,'_ENV') || stristr($pair,'HTTP_ENV_VARS'))continue;

					$var=substr($pair,0,strpos($pair,'='));
					$var=str_replace('[','[\'', str_replace(']','\']',$var));
					$value=substr($pair,strpos($pair,'=')+1);
					$value=(is_numeric($value)?'':'\'') . str_replace("'","\'",urldecode($value)) . (is_numeric($value)?'':'\'');
					@eval('global $'.preg_replace('/\[.+/','',$var).';');
					@eval('$'.$var.'='.$value.';');
				}
			}
		}
	}
	@extract($options);
	//required parameters
	if(!$method)$method='static:default';
	if(!$section)$section='section1';
	if(preg_match('/^([a-z0-9]+)(\([0-9]\))*:([-_a-z0-9]+)$/i',$section,$a)){
		$commonpage=$a[1];
		$commonfolder=''; //by convention for now
		$depth=trim($a[2],'()');
		$section=$a[3];
	}
	if(!$cnx)$cnx=$qx['defCnxMethod'];

	//basic settings
	if(!isset($handleContentOutput))$handleContentOutput=true;
	if(!isset($convertExistingStaticHTML))$convertExistingStaticHTML=false;
	if(!isset($initialText))$initialText='&nbsp;';
	if(!$editSectionLabel)$editSectionLabel='edit section';
	if(!$defaultContentTagType)$defaultContentTagType='div'; //div|span
	
	//get or assign the name for the section
	if(!isset($CMSB['sections']))$CMSB['sections']=array();
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
	
	//server
	$varProcessServer=explode('.',$_SERVER['SERVER_NAME']);
	$varProcessServer=strtolower($varProcessServer[count($varProcessServer)-2].'.'.$varProcessServer[count($varProcessServer)-1]);

	//where data is stored
	if(!$table)$table=$CMSBx['defaultTableName'];
	//make sure table exists
	if(!$CMSB['tables'][$table]){
		if($tables=q("SHOW TABLES ".($CMSDb?' IN '.$CMSDb:''), O_ARRAY, $cnx)){
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


	if(!$CMSB['jsCheck']){
		$jsCheck='<\'+\'script language="javascript" type="text/javascript" src="/Library/js/CMSB_v120.js"></\'+\'script>';
		$jsCheck='if(typeof CMSB==\'undefined\')document.write(\''.$jsCheck.'\');';
		$jsCheck.=' if(typeof thispage==\'undefined\'){ ';
		$jsCheck.='var thisfolder=\''.str_replace("'","\'",$thisfolder).'\'; var thispage=\''.str_replace("'","\'",$thispage).'\';';
		$jsCheck.=' }';
		$CMSB['jsCheck']=true;
		?><script language="javascript" type="text/javascript">
		//variables for this link here
		<?php echo $jsCheck?>
		</script><?php
	}
	if(strtolower($method)=='static:default'){
		//get content
		$content=q("SELECT
		'static:default' AS Method,
		IF(b.Content IS NOT NULL, b.Content, a.Content) AS Content,
		IF(b.ID IS NOT NULL,1,0) AS Edited,
		IF(b.ID IS NOT NULL,b.EditDate,a.EditDate) AS EditDate,
		IF(b.ID IS NOT NULL,b.EditNotes,a.EditNotes) AS EditNotes,
		IF(b.ID IS NOT NULL,b.Options,a.Options) AS Options,
		IF(b.ID IS NOT NULL,b.ID,a.ID) AS ID
		FROM ".$table." a LEFT JOIN ".$table." b ON a.ID=b.Sections_ID WHERE
		a.ThisFolder='".addslashes(isset($commonfolder) ? $commonfolder : $thisfolder)."' AND
		a.ThisPage='".addslashes(isset($commonpage) ? $commonpage : $thispage)."' AND
		a.Section='".addslashes($section)."'
		ORDER BY b.EditDate DESC LIMIT 1", O_ROW, $cnx);

		//--------------- create link for static:default ------------------
		ob_start();
		?>
		<a id="CMSB-<?php echo $section?>" method="<?php echo $method?>" title="Edit the section <?php echo strtolower($linkPosition)=='below'?'above':'below'?>"<?php if(isset($commonfolder)){ ?> commonfolder="<?php echo h($commonfolder);?>"<?php } ?><?php if(isset($commonpage)){ ?> commonpage="<?php echo $commonpage?>"<?php } ?> href="#" onclick="return CMSBedit(this, '<?php echo strtolower($method);?>', [CMSB_SECTION_TOKEN]);"><img src="/images/i/plus.jpg" alt="hide" onclick="this.parentNode.style.display='none'; return false;" title="click to hide link" width="13" height="13" />[<?php echo $editSectionLabel?>]</a><?php
		$link=ob_get_contents();
		ob_end_clean();
		//-----------------------------------------------------------------
	}else if(strtolower($method)=='static:parameters'){
		//added 2009-01-26
		if(!$primaryParameter && !$secondaryParameter)return false;
		if($primaryParameter){
			//declare parameter
			$parameters=' AND a.PrimaryParameter =\''.$primaryParameter.'\'';
			$parameterAttributes=' primaryParameter="'.$primaryParameter.'"';
			//declare parameter value
			if(isset($primaryValue)){
				$val=$primaryValue;
				$parameters.=' AND a.PrimaryValue=\''.(strstr(str_replace("\'",'',$val),"'") ? addslashes($val) : $val).'\'';
			}else{
				if(strstr($primaryParameter,'.')){
					$a=explode('.',$primaryParameter);
					$e='$val=$GLOBALS[\''.implode('\'][\'',$a).'\'];';
					eval($e);
					$parameters.=' AND a.PrimaryValue=\''.(strstr(str_replace("\'",'',$val),"'") ? addslashes($val) : $val).'\'';
				}else{
					$val=$GLOBALS[$primaryParameter];
					$parameters.=' AND a.PrimaryValue=\''.(strstr(str_replace("\'",'',$val),"'") ? addslashes($val) : $val).'\'';
				}
			}
			$parameterAttributes.=' primaryValue="'.h(strstr(str_replace("\'",'',$val),"'") ? addslashes($val) : $val).'"';
		}
		//do the same for secondary parameter
		if($secondaryParameter){
			//declare parameter
			$parameters.=' AND a.SecondaryParameter =\''.$secondaryParameter.'\'';
			$parameterAttributes.=' secondaryParameter="'.$secondaryParameter.'"';
			//declare parameter value
			if(isset($secondaryValue)){
				$val=$secondaryValue;
				$parameters.=' AND a.SecondaryValue=\''.(strstr(str_replace("\'",'',$val),"'") ? addslashes($val) : $val).'\'';
			}else{
				if(strstr($secondaryParameter,'.')){
					$a=explode('.',$secondaryParameter);
					$e='$val=$GLOBALS[\''.implode('\'][\'',$a).'\'];';
					eval($e);
					$parameters.=' AND a.SecondaryValue=\''.(strstr(str_replace("\'",'',$val),"'") ? addslashes($val) : $val).'\'';
				}else{
					$val=$GLOBALS[$secondaryParameter];
					$parameters.=' AND a.SecondaryValue=\''.(strstr(str_replace("\'",'',$val),"'") ? addslashes($val) : $val).'\'';
				}
			}
			$parameterAttributes.=' secondaryValue="'.h(strstr(str_replace("\'",'',$val),"'") ? addslashes($val) : $val).'"';
		}
		$content=q("SELECT
		'static:parameters' AS Method,
		IF(b.Content IS NOT NULL, b.Content, a.Content) AS Content,
		IF(b.ID IS NOT NULL,1,0) AS Edited,
		IF(b.ID IS NOT NULL,b.EditDate,a.EditDate) AS EditDate,
		IF(b.ID IS NOT NULL,b.EditNotes,a.EditNotes) AS EditNotes,
		IF(b.ID IS NOT NULL,b.Options,a.Options) AS Options,
		IF(b.ID IS NOT NULL,b.ID,a.ID) AS ID
		FROM ".$table." a LEFT JOIN ".$table." b ON a.ID=b.Sections_ID WHERE
		a.ThisFolder='".addslashes(isset($commonfolder) ? $commonfolder : $thisfolder)."' AND
		a.ThisPage='".addslashes($thispage)."' AND
		a.Section='".addslashes($section)."'
		$parameters
		ORDER BY b.EditDate DESC LIMIT 1", O_ROW, $cnx);

		//--------------- create link for static:parameters ------------------
		ob_start();
		?>
		<a id="CMSB-<?php echo $section?>" method="<?php echo $method?>"<?php if(isset($commonfolder)){ ?> commonfolder="<?php echo h($commonfolder);?>"<?php } ?><?php if(isset($commonpage)){ ?> commonpage="<?php echo $commonpage?>"<?php } ?> title="Edit the section <?php echo strtolower($linkPosition)=='below'?'above':'below'?>" <?php echo $parameterAttributes;?> href="#" onclick="return CMSBedit(this, '<?php echo strtolower($method);?>', [CMSB_SECTION_TOKEN]);"><img src="/images/i/plus.jpg" alt="hide" onclick="this.parentNode.style.display='none'; return false;" title="click to hide link" width="13" height="13" />[<?php echo $editSectionLabel?>]</a><?php
		$link=ob_get_contents();
		ob_end_clean();
		//-----------------------------------------------------------------
	}else if(strtolower($method)=='dynamic:simple'){
		if(!$CMSTable)$CMSTable='cms1_articles';
		if(!$CMSContentField)$CMSContentField='Body';
		if(!$primaryKeyField)$primaryKeyField=array('ID');
		if(!$primaryKeyFieldLabel)$primaryKeyFieldLabel=array('Articles_ID');
		if(!$primaryKeyValue){
			//assume it's available globally
			foreach($primaryKeyFieldLabel as $v){
				global $$v;
				$primaryKeyValue[]=$$v;
			}
		}
		//build where clause
		foreach($primaryKeyField as $n=>$v){
			$whereClause[]=$v . '=\'' . addslashes($primaryKeyValue[$n]) . '\'';
		}
		//get content
		$content=q("SELECT a.*, /*a.$primaryKeyField AS ID,*/ a.$CMSContentField AS Content FROM $CMSTable a WHERE ".implode(' AND ',$whereClause), O_ROW, $cnx);

		//in non-admin mode we process the content - added 2009-07-20
		if(!$adminMode && in_array($varProcessServer,$CMSBx['varProcessAuthServers'])){
			if(preg_match_all('#\{\{([a-z0-9_.]+)\}\}#i',$content['Content'],$a))
			foreach($a[1] as $v){
				if(in_array($v, $CMSBx['authVarsProcessList'][$varProcessServer])){
					$content['Content']=str_replace('{{'.$v.'}}',$GLOBALS[$v],$content['Content']);
				}
			}
			if(preg_match_all('#<div[^>]+class="phpvar"[^>]*>([^<]*)</div>#i',$content['Content'],$a)){
				foreach($a[0] as $v){
					$b=xml_read_tags($v, 'div', $attrib='', $return=XML_RET_FIRST, $params=XML_PARAMS_POSN, $case=1);
					if(strtolower($b[1]['getmethod'])=='stdsettingstable'){
						$sql='SELECT varvalue FROM bais_settings WHERE UserName=\''.$b[1]['username'].'\' AND vargroup=\''.$b[1]['vargroup'].'\' AND varnode=\''.$b[1]['varnode'].'\' AND varkey=\''.$b[1]['varkey'].'\'';
						if($obj=q($sql,$cnx, O_VALUE)){
							$content['Content']=str_replace($v,$obj,$content['Content']);
						}else{
							
						}
					}
				}
			}
		}

		//--------------- create link for dynamic:simple ------------------
		ob_start();
		?>
		<a id="CMSB-<?php echo $section?>" method="<?php echo $method?>"<?php if(isset($commonfolder)){ ?> commonfolder="<?php echo h($commonfolder);?>"<?php } ?><?php if(isset($commonpage)){ ?> commonpage="<?php echo $commonpage?>"<?php } ?> CMSTable="<?php echo $CMSTable?>" CMSContentField="<?php echo $CMSContentField?>" primaryKeyField="<?php echo implode(',',$primaryKeyField);?>" primaryKeyFieldLabel="<?php echo implode(',',$primaryKeyFieldLabel);?>" <?php if($primaryKeyValue){ ?>primaryKeyValue="<?php echo implode('|',$primaryKeyValue);?>"<?php } ?> title="Edit the section <?php echo strtolower($linkPosition)=='below'?'above':'below'?>" href="#" onclick="return CMSBedit(this, '<?php echo $method?>', [CMSB_SECTION_TOKEN]);"><img src="/images/i/plus.jpg" alt="hide" onclick="this.parentNode.style.display='none'; return false;" title="click to hide link" width="13" height="13" />[<?php echo $editSectionLabel?>]</a>
		<?php
		$link=ob_get_contents();
		ob_end_clean();
		//-----------------------------------------------------------------
	}
	if($content){
		//2009-03-30: remove triple-up feature of FCK Editor
		$content['Content']=str_replace('"../../../images', '"/images',$content['Content']);
		
		//default method for both methods is to output a blank div with a specific id - no need to recurse to get content
		if($handleContentOutput){
			/*
			//declare the content
			NOTES:
			1. how to resolve conflict between the id and class the designer might want and the vars we need to identify the region - the solution is to store everything in the javascript array keyed to that section NAME
			2. we should analyze content for cleanness
			3. if the content has no HTML we should do nl2br
			
			*/
			
			if($content['Options']){
				$Options=unserialize(base64_decode($content['Options']));
				if(!$adminMode && $slidesPerPage=$Options['MakePageSlide']){
					//split content into sections
					$slide=preg_split('/<img\s+/i',$content['Content']);
					if(count($slide)==1){
						//do nothing
					}else{
						$i=0;
						$imgCount=0;
						foreach($slide as $v){
							$i++;
							if($i==1 && !preg_match('/^<img\s+/i',$content['Content'])){
								$preText=$v;
								continue;
							}
							$imgCount++;
							$slides[ceil($imgCount/$slidesPerPage)][]='<img '.$v;
						}
						//show just this content
						global $sectionNav;
						if($slides[$sectionNav[$section]]){
							$idx=$sectionNav[$section];
						}else{
							$idx=1;
						}
						ob_start();
						?>
						<div id="slideShowNav">
							<?php
							if(count($slides)>1){
								$url=$thispage . '?';
								$i=0;
								foreach($_GET as $n=>$v){
									$i++;
									if($n=='sectionNav')continue;
									$url.=($i==1?'':'&').$n.'='.stripslashes($v);
								}
								?>
								<?php if($idx==1){ ?>
								<a class="notclickable" onclick="return false" href="#">&laquo; Previous</a>
								<?php }else{ ?>
								<a class="clickable" title="previous set of pictures" href="<?php echo $url.'&sectionNav['.$section.']='.($idx-1)?>">&laquo; Previous</a>
								<?php } ?>
								&nbsp;&nbsp;&nbsp;
								<?php if($idx==count($slides)){ ?>
								<a class="notclickable" onclick="return false" href="#">Next &raquo;</a>
								<?php }else{ ?>
								<a class="clickable" title="next set of pictures" href="<?php echo $url.'&sectionNav['.$section.']='.($idx+1)?>">Next &raquo;</a>
								<?php } ?>
								<?php
							}
							?>
						
						</div>
						<?php
						$nav=ob_get_contents();
						ob_end_clean();
						$content['Content']=$preText . $nav . implode("\n",$slides[$idx]) . ($slidesPerPage>1 ? $nav : '');
					}
				}
			}
			
			ob_start();
			$comment="\n";
			$comment.='<!-- printed by CMS Bridge('.$section.'); method='.$method.';';
			if(strlen($content['EditDate'])) $comment.=' content last edited '.date('m/d/Y \a\t g:iA',strtotime($content['EditDate']));
			$comment.=' -->';
			$comment.="\n";
			echo $comment;
			?><div id="<?php echo $section?>" <?php if($setClass)echo 'class="'.$setClass.'"';?> ondblclick="CMSBEditFromContent(this,event);" method="<?php echo $method?>"><?php
			echo $content['Content'];
			echo "\n";
			?></div><?php
			$body=ob_get_contents();
			ob_end_clean();
			$body=preg_replace('/<div/i','<'.($setTagAs ? strtolower($setTagAs) : $defaultContentTagType),$body);
			$body=preg_replace('/\/div>/i','/'.($setTagAs ? strtolower($setTagAs) : $defaultContentTagType).'>',$body);

			//output the link and the content with a secure tie between them
			if($adminMode || $showEditLink){
				echo str_replace('[CMSB_SECTION_TOKEN]',"'".addslashes($section)."'",$link);
				echo "\n";
			}
			echo $body;
		}else{
			//output the link [above], don't pass section in function call
			if($adminMode || $showEditLink){
				echo str_replace('[CMSB_SECTION_TOKEN]','null',$link);
				echo "\n";
			}
		}
	}else{
		if($convertExistingStaticHTML){
			//just for this instance of no content, don't output the div
			if($adminMode || $showEditLink){
				echo str_replace('[CMSB_SECTION_TOKEN]','null',$link);
				echo "\n";
			}
		}else if($handleContentOutput){
			//output the link and the content with a secure tie
			ob_start();
			$comment="\n";
			$comment.='<!-- printed by CMS Bridge('.$section.'); method='.$method.';';
			$comment.=' (new content)';
			$comment.=' -->';
			$comment.="\n";
			echo $comment;
			?><div id="<?php echo $section?>" <?php if($setClass)echo 'class="'.$setClass.'"';?> ondblclick="CMSBEditFromContent(this,event);" method="<?php echo $method?>"><?php
			echo $initialText;
			?></div><?php
			$body=ob_get_contents();
			ob_end_clean();
			$body=preg_replace('/<div/i','<'.($setTagAs ? strtolower($setTagAs) : $defaultContentTagType),$body);
			$body=preg_replace('/\/div>/i','/'.($setTagAs ? strtolower($setTagAs) : $defaultContentTagType).'>',$body);

			//output the link and the content with a secure tie between them
			if($adminMode || $showEditLink){
				echo str_replace('[CMSB_SECTION_TOKEN]',"'".addslashes($section)."'",$link);
				echo "\n";
			}
			echo $body;
		}else{
			//just output the link
			if($adminMode || $showEditLink){
				echo str_replace('[CMSB_SECTION_TOKEN]','null',$link);
				echo "\n";
			}
		}
	}
}
function CMSBUpdate($options=array()){
	global $fl,$ln,$qr,$qx,$CMSBx, $developerEmail,$fromHdrBugs;
	//note: this will trump any local thispage, thisfolder settings present with what was passed
	$a=$_POST;
	extract($a);
	extract($options);
	global $adminMode;
	global $testingcms;
	global $Options; //these are "options for this page"
	if(!$adminMode)error_alert('You are not in admin mode and not able to edit content right now.  Please log in');

	if(!$cnx)$cnx=$qx['defCnxMethod'];

	/*
	--------- 2009-03-23 - begin handling of actual content based on settings ---------------
	*/
	$before=strlen($CMS);
	if($settings['RemoveMSWordMarkup']){
		//remove Word comments
		if(preg_match_all('/<!--\[if gte mso[^]]+\]>(.|\s)*?<!\[endif\]-->/i',stripslashes($CMS),$markup))
		foreach($markup[0] as $mark){
			$CMS=str_replace(addslashes($mark),'',$CMS);
		}
		//remove Word links
		if(preg_match_all('/<link[^>]+href="file:[^>]+>\s*/i',stripslashes($CMS),$markup))
		foreach($markup[0] as $mark){
			$CMS=str_replace(addslashes($mark),'',$CMS);
		}
		//remove Word style sheets (mso-nnn:value present)
		if(preg_match_all('/<style(.|\s)*?<\/style>/i',stripslashes($CMS),$markup))
		foreach($markup[0] as $mark){
			if(preg_match('/\s?mso[-a-z0-9]+:/i',$mark)) $CMS=str_replace(addslashes($mark),'',$CMS);
		}
		//remove Word class declarations
		if(preg_match('/\s+class="mso[-a-z0-9]+"/i',stripslashes($CMS)))
		$CMS=addslashes(preg_replace('/\s+class="mso[-a-z0-9]+"/i','',stripslashes($CMS)));
		//remove ...
		if(preg_match('/<o:smarttagtype[^>]+><\/o:smarttagtype>/i',stripslashes($CMS)))
		$CMS=addslashes(preg_replace('/<o:smarttagtype[^>]+><\/o:smarttagtype>/i','',stripslashes($CMS)));
		
	}
	

	//store resized images in "images/pieces" folder
	$settings['RemoveAbsoluteCurrentHostReferences']=true;
	if($settings['CopySizedDownImages'] || $settings['CopyOverRemoteImages']){
		for($i=1; $i<=1; $i++){ //--------------- begin break loop -------------
		//get images present in the content
		if(!preg_match_all('/<img\s+[^>]*src="([^"]+)"[^>]*\s*\/>/i',stripslashes($CMS),$images))break;
		foreach($images[1] as $n=>$src){
			// "src" and "img" vars are both stripped of slashes
			$img=$images[0][$n];

			//remove absolute URL's which are part of the current HTTP host
			$absoluteURLRegex='/https?(:|%3A)(\/|%2F){2}([-.%a-z0-9]+)(\/|%2F)/i';
			$tldHost=explode('.',strtolower($_SERVER['HTTP_HOST']));
			$tldHost=$tldHost[count($tldHost)-2] . '.' . $tldHost[count($tldHost)-1];
			if($settings['RemoveAbsoluteCurrentHostReferences'] && preg_match($absoluteURLRegex, $src, $remoteURL) && strtolower(substr($remoteURL[3],-strlen($tldHost)))==$tldHost){
				$x=preg_replace($absoluteURLRegex,'/',$src); 		#remove abs url from src
																	#note that the abs url must not be urlencoded for either : or // part
				if($isURLEncoded){
					$localImg=str_replace($src, $x, $img);			#update img tag
				}else{
					$localImg=str_replace($src, $x, $img);				
				}
				$CMS=str_replace(addslashes($img), addslashes($localImg), $CMS);
				$img=$localImg;										#safe to replace globally
				$src=$x;											#safe to replace globally
			}

			if(false && 'the image is a remote image'){
				if($settings['CopyOverRemoteImages']){
					//pull the image by curl if possible into images/cms.remote
					
					//now reset cms src code; if further resizing necessary
				}else{
					//settings.CopySizedDownImages would not apply, so continue
					continue;
				}
			}

			//compile DOCUMENT_ROOT, cms page folder and img src
			if(!($stats=getimagesize($_SERVER['DOCUMENT_ROOT'].'/'.ltrim(urldecode($src),'/')))){
				if(true || $notifyOf404Images)$buffer['imageNotifications']['404'][]=$src;
				continue;
			}
			preg_match('/width="([^"]+)"/',$img,$width);
			preg_match('/height="([^"]+)"/',$img,$height);
			$width=preg_replace('/px/i','',$width[1]);
			$height=preg_replace('/px/i','',$height[1]);

			//if the image is not specified as resized continue
			if(!strlen($width) && !strlen($height))continue;
			if(!
				((strlen($width) && $width!='100%' && $width<$stats[0])
				||
				(strlen($height) && $height!='100%' && $height<$stats[1]))
			)continue;
			#error_alert($width . ':' .$height . ' - ' . $stats[0] . ':' . $stats[1], true);

			/* if(the image is beyond a certain size)$buffer['imageNotifications']['oversize'][]=$v; */
			if(!is_dir($_SERVER['DOCUMENT_ROOT'].'/images/cms.pieces') && !mkdir($_SERVER['DOCUMENT_ROOT'].'/images/cms.pieces')){
				error_alert('unable to create cms.pieces folder',true);
				mail($developerEmail,'unable to create images/pieces folder',get_globals(),$fromHdrBugs);
				break;
			}
			//copy resized image
			if(preg_match('/%/',$width) || preg_match('/%/',$height)){
				//convert to numeric
				mail($developerEmail,'need to convert % to decimal',get_globals(),$fromHdrBugs);
				continue;
			}else{
				if(!$height)$height=round($stats[1] * ($width/$stats[0]));
				if(!$width)$width=round($stats[1] * ($height/$stats[0]));
			}
			if(!function_exists('create_thumbnail'))require($_SERVER['DOCUMENT_ROOT'].'/functions/function_create_thumbnail_v200.php');

			//save a copy of the file
			$decodedSrc=preg_replace('/^(\.\.\/)*/','',urldecode($src));
			$a=explode('/',ltrim($decodedSrc,'/'));
			$originalFileName=array_pop($a);
			if(count($a))
			foreach($a as $n=>$v){
				$a[$n]=substr(preg_replace('/[^a-z0-9]*/i','',$v),0,3);
			}
			$folderAbbr=strtolower(implode('-',$a));
			$newFileName=preg_replace('/\.(gif|jpg|png)$/i','-resized-to-'.$width . 'x(~'.$height.')-f='.$folderAbbr.'.'.'$1',$originalFileName);
			/* err
			false && !file_exists($_SERVER['DOCUMENT_ROOT'].'/images/cms.pieces/'.$newFileName) &&
			*/
			if($dims=create_thumbnail(
				$_SERVER['DOCUMENT_ROOT'].'/'.ltrim($decodedSrc,'/'),
				$width.','.$height, 
				'', 
				$_SERVER['DOCUMENT_ROOT'].'/images/cms.pieces/'.$newFileName
			)){
				if(!$mailedImageResize){
					$mailedImageResize=mail($developerEmail, 'CMS pieces resize of '.str_replace("'","\'",$originalFileName), get_globals(), $fromHdrBugs);
				}
			}else{
				mail($developerEmail, 'Unable to create resized piece of '.str_replace("'","\'",$originalFileName), get_globals(), $fromHdrBugs);
				error_alert('Unable to create resized piece of '.str_replace("'","\'",$originalFileName), true);
				continue;
			}

			//rewrite cms content
			$newimg=preg_replace('/width="[^"]*"/i','width="'.$dims[0].'"',$img);
			$newimg=preg_replace('/height="[^"]*"/i','height="'.$dims[1].'"',$newimg);
			$newimg=preg_replace('/src="[^"]*"/i','src="/images/cms.pieces/'.
			/* 2009-03-30: urlencode changed ' ' to '+' which is not desired; so I translate characters one by one as needed */
			str_replace(' ','%20',
			str_replace('"','%22', $newFileName))
			.'"',$newimg);
			$CMS=str_replace(addslashes($img),addslashes($newimg),$CMS);
			
			/*
			buffer resize names
			*/
		}
		/*
		if(resize names){
			if(.stats.dbr not present and cannot be created){
				mail();
				break;
			}
			foreach(resize names as $v){
				build string
				open file
				write it
			}
		}
		*/
		#mail($developerEmail, 'Updated output for image pieces resize, file '.__FILE__.', line '.__LINE__, get_globals(), $fromHdrBugs);
		} //---------------- end break loop -----------------
	}

	if($method==='static:default'){
		$Sections_ID=q("SELECT ID FROM cmsb_sections WHERE ThisFolder='".(isset($commonfolder) ? $commonfolder : $thisfolder)."' AND ThisPage='".(isset($commonpage) ? $commonpage : $thispage)."' AND Section='$thissection' AND Sections_ID IS NULL", O_VALUE, $cnx);
		prn($qr);
		//outlay known options which are unpassed
		# not needed: if(!isset($Options['MakePageSlide']))$Options['MakePageSlide']='';
		
		//get most recent section for building options
		if($currentOptions=q("SELECT Options FROM cmsb_sections WHERE ThisFolder='".(isset($commonfolder) ? $commonfolder : $thisfolder)."' AND ThisPage='".(isset($commonpage) ? $commonpage : $thispage)."' AND Section='$thissection' ORDER BY ID DESC LIMIT 1", O_VALUE, $cnx)){
			$currentOptions=unserialize(base64_decode($currentOptions));
		}
		foreach($Options as $n=>$v){
			$currentOptions[$n]=stripslashes($v);
		}
		$Options=base64_encode(serialize($currentOptions));
		
		$ID=q("INSERT INTO cmsb_sections SET
		".($Sections_ID?"Sections_ID='$Sections_ID'," : '')."
		ThisFolder='".(isset($commonfolder) ? $commonfolder : $thisfolder)."',
		ThisPage='".(isset($commonpage) ? $commonpage : $thispage)."',
		Section='$thissection',
		Content='$CMS',
		Options='$Options',
		EditNotes='$EditNotes',
		Editor='".($_SESSION['systemUserName'] ? $_SESSION['systemUserName'] : 'administrator')."'", O_INSERTID, $cnx);
		prn($qr);
		
	}else if($method=='static:parameters'){
		if(!isset($primaryParameter)){
			mail($developerEmail,'Error function_CMSB_v120.php, line '.__LINE__,get_globals(),$fromHdrBugs);
			error_alert('Not enough parameters passed, missing primaryParameter and primaryValue');
		}
		$parameters[]='PrimaryParameter=\''.$primaryParameter.'\'';
		$parameters[]='PrimaryValue=\''.$primaryValue.'\'';
		if(isset($secondaryParameter)){
			$parameters[]='SecondaryParameter=\''.$secondaryParameter.'\'';
			$parameters[]='SecondaryValue=\''.$secondaryValue.'\'';
		}
		$Sections_ID=q("SELECT ID FROM cmsb_sections WHERE ThisFolder='".(isset($commonfolder) ? $commonfolder : $thisfolder)."' AND ThisPage='".(isset($commonpage) ? $commonpage : $thispage)."' AND Section='$thissection' AND Sections_ID IS NULL AND ".implode(' AND ',$parameters), O_VALUE, $cnx);
		prn($qr);
		//outlay known options which are unpassed
		# not needed: if(!isset($Options['MakePageSlide']))$Options['MakePageSlide']='';
		
		//get most recent section for building options
		if($currentOptions=q("SELECT Options FROM cmsb_sections WHERE ThisFolder='".(isset($commonfolder) ? $commonfolder : $thisfolder)."' AND ThisPage='".(isset($commonpage) ? $commonpage : $thispage)."' AND Section='$thissection' AND ".implode(' AND ',$parameters)." ORDER BY ID DESC LIMIT 1", O_VALUE, $cnx)){
			$currentOptions=unserialize(base64_decode($currentOptions));
		}
		foreach($Options as $n=>$v){
			$currentOptions[$n]=stripslashes($v);
		}
		$Options=base64_encode(serialize($currentOptions));
		
		$ID=q("INSERT INTO cmsb_sections SET
		".($Sections_ID?"Sections_ID='$Sections_ID'," : '')."
		ThisFolder='".(isset($commonfolder) ? $commonfolder : $thisfolder)."',
		ThisPage='".(isset($commonpage) ? $commonpage : $thispage)."',
		Section='$thissection',
		".implode(', ',$parameters).",
		Content='$CMS',
		Options='$Options',
		EditNotes='$EditNotes',
		Editor='".($_SESSION['systemUserName'] ? $_SESSION['systemUserName'] : 'administrator')."'", O_INSERTID, $cnx);
		prn($qr);

	}else if($method=='dynamic:simple'){
		//build primary key
		$primaryKeyField=explode(',',$primaryKeyField);
		$primaryKeyFieldLabel=explode(',',$primaryKeyFieldLabel);
		$primaryKeyValue=explode('|',$primaryKeyValue);
		foreach($primaryKeyField as $n=>$v){
			$primaryKey[]=$v.'=\''.$primaryKeyValue[$n].'\'';
		}
		if($primaryKeyValue && $count=q("SELECT COUNT(*) FROM $CMSTable WHERE ".implode(' AND ',$primaryKey), O_VALUE, $cnx)){
			if($count>1){
				//we have a problem, email someone
				mail($developerEmail,'more than one record found for function CMSBUpdate(), line '.__LINE__,get_globals(),$fromHdrBugs);
			}
			q("UPDATE $CMSTable SET $CMSContentField='$CMS', Editor='system' WHERE ".implode(' AND ',$primaryKey), $cnx);
		}else{
			$NewID=q("INSERT INTO $CMSTable SET ".implode(', ',$primaryKey).", $CMSContentField='$CMS', EditDate=NOW(), Editor='system'", O_INSERTID, $cnx);
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
}

//a/o 2009-07-20, only relatebase-rfm is allowed to do this
$CMSBx['varProcessAuthServers']=array(
	'relatebase-rfm.com'
);
$CMSBx['authVarsProcessList']['relatebase-rfm.com']=array(
	'acct','table'
);

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
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COMMENT=\'Version 1.0.0 Created 2008-12-17 by CPM sam-git@samuelfullman.com\'';
?>