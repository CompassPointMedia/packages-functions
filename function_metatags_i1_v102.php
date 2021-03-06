<?php
$functionVersions['metatags_i1']=1.02;
function metatags_i1($n, $options=array()){
	global $lang, $metatags, $thispage, $thisfolder, $thisnode, $qx, $qr, $functionVersions;
	/*
	2012-01-17: forked off so that I can pull from multi languages
	2010-01-14: added a wierd idea, if the first param is not meta|title, it's the default title - easier to convert old pages, for example
		metatags_i1('Fox River : Great Vacation Getaways!');
	options array:
	cnx=> can be array or q constant
	2008-02-08: added the ability to call a specific connection
	2008-01-14: Gets title or descript/keywords based on parameters in a table named site_metatags; based on the folder and page name, you can get these params either statically or dynamically - great to use to modify in admin mode
	This function depends on a table as follows:
	CREATE TABLE `site_metatags` (\n  `ThisFolder` varchar(255) NOT NULL,\n  `ThisPage` varchar(255) NOT NULL,\n  `Title` text NOT NULL,\n  `Description` text NOT NULL,\n  `Keywords` text NOT NULL,\n  `TTable` varchar(128) NOT NULL,\n  `TField` varchar(128) NOT NULL,\n  `TVar1` varchar(128) NOT NULL,\n  `TVar2` varchar(128) NOT NULL,\n  `DTable` varchar(128) NOT NULL,\n  `DField` varchar(128) NOT NULL,\n  `DVar1` varchar(128) NOT NULL,\n  `DVar2` varchar(128) NOT NULL,\n  `KTable` varchar(128) NOT NULL,\n  `KField` varchar(128) NOT NULL,\n  `KVar1` varchar(128) NOT NULL,\n  `KVar2` varchar(128) NOT NULL,\n  `EditDate` timestamp NOT NULL default CURRENT_TIMESTAMP,\n  PRIMARY KEY  (`ThisFolder`,`ThisPage`)\n) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Created 2008-01-14 by Samuel'
	NOTE: as long as the fields for the title, description and etc is a "solid" field i.e. single, it can be updated on the back end through admin mode
	*/
	@extract($options);
	if(empty($cnx)) $cnx = $qx['defCnxMethod'];
	if(empty($cnx))$cnx=$qx['defCnxMethod'];
	$str = '';
	
	if($a=q(
		$thisnode ? 
		"SELECT s.*, 
		IF(l.Title IS NOT NULL,l.Title,s.Title) AS Title,
		IF(l.Description IS NOT NULL,l.Description,s.Description) AS Description,
		IF(l.Keywords IS NOT NULL,l.Keywords,s.Keywords) AS Keywords
		FROM site_metatags s LEFT JOIN site_metatags_lang l ON 
		s.Objects_ID=l.Objects_ID AND s.ThisFolder=l.ThisFolder AND s.ThisPage=l.ThisPage
		AND l.lang='$lang' WHERE s.Objects_ID=$thisnode" :
		
		"SELECT s.*,
		IF(l.Title IS NOT NULL,l.Title,s.Title) AS Title,
		IF(l.Description IS NOT NULL,l.Description,s.Description) AS Description,
		IF(l.Keywords IS NOT NULL,l.Keywords,s.Keywords) AS Keywords
		FROM site_metatags s LEFT JOIN site_metatags_lang l ON 
		s.Objects_ID=l.Objects_ID AND s.ThisFolder=l.ThisFolder AND s.ThisPage=l.ThisPage
		AND l.lang='$lang' WHERE s.ThisPage='$thispage' AND s.ThisFolder='$thisfolder'"
		, O_ROW, $cnx)){
		unset($a['lang']);
		$metatags['record']=$a;
		extract($a);
	}
	if($n!=='title' && $n!=='meta'){
		$defaultTitle=$n;
		$n='title';
	}
	if($n=='title'){
		if($a){
			//fields first, then title
			if($TTable && $TField && $TVar1){
				$var1=explode(':',$TVar1);
				if(strlen($TVar2)){
					$var2=explode(':',$TVar2);
					$and=" AND ".$var2[0]."='".( $var2[1] ? $GLOBALS[$var2[1]] : $GLOBALS[$var2[0]] )."'";
				}
				if($a=q("SELECT $TField FROM $TTable WHERE ".$var1[0]."='".( $var1[1] ? $GLOBALS[$var1[1]] : $GLOBALS[$var1[0]] )."' $and", O_VALUE, $cnx)){
					$metatags['title']=$a;
					return $a;
				}else if($Title){
					$metatags['title']=$Title;
					return $Title;
				}
			}else if($Title){
				$metatags['title']=$Title;
				return $Title;
			}
		}
		if(!empty($defaultTitle)) {
			$metatags['title']=$defaultTitle;
			return $defaultTitle;
		}
		global $siteName;
		$metatags['title']=$siteName;
		return $siteName;
	}else if($n=='meta'){
		if($a){
			//first, the description
			if($DTable && $DField && $DVar1){
				$var1=explode(':',$DVar1);
				if(strlen($DVar2)){
					$var2=explode(':',$DVar2);
					$and=" AND ".$var2[0]."='".( $var2[1] ? $GLOBALS[$var2[1]] : $GLOBALS[$var2[0]] )."'";
				}
				if($a=q("SELECT $DField FROM $DTable WHERE ".$var1[0]."='".( $var1[1] ? $GLOBALS[$var1[1]] : $GLOBALS[$var1[0]] )."' $and", O_VALUE, $cnx)){
					$Description=$a;
				}else if($Description){
					//OK
				}
			}else if($Description){
				//OK
			}
			//next, the keywords
			if($KTable && $KField && $KVar1){
				$var1=explode(':',$KVar1);
				if(strlen($KVar2)){
					$var2=explode(':',$KVar2);
					$and=" AND ".$var2[0]."='".( $var2[1] ? $GLOBALS[$var2[1]] : $GLOBALS[$var2[0]] )."'";
				}
				if($a=q("SELECT $KField FROM $KTable WHERE ".$var1[0]."='".( $var1[1] ? $GLOBALS[$var1[1]] : $GLOBALS[$var1[0]] )."' $and", O_VALUE, $cnx)){
					$Keywords=$a;
				}else if($Keywords){
					//OK
				}
			}else if($Keywords){
				//OK
			}
			$metatags['description']=$Description;
			$metatags['keywords']=$Keywords;
			if($Description) $str.='<meta name="Description" content="'.h($Description).'" />'."\n";
			if($Keywords) $str.='<meta name="Keywords" content="'.h($Keywords).'" />'."\n";
			if($str)$str='<!-- generated by metatags_i1(), version '.$functionVersions['metatags_i1'].' -->'."\n".$str;
			return $str;
		}
	}
}

