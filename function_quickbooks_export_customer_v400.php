<?php
//---------------------------------------------------------------------------
$IIFExportComments="Compass Point Media - QuickBooks download v2.0
This is a comment section.  This file should be saved with an .iif extension on the end.  Normally your browser will do this by default.  We recommend you save all the files in a single folder (named iif_imports for example) for your records.

To import this file into QuickBooks, select File > Utilities > Import, then select this file

Things which might happen:
1) You may be prompted that the transaction number is a duplicate.  This can happen if you import the same transaction twice, OR if you have transactions imported from two different sources
2) If you import invoices without the underlying chart of accounts or items, they may be assigned to the wrong category.  Data should be entered into QuickBooks in a heirarchy as follows:

 [Customers]	[Chart of Accounts Items]
	 |					|
	 |			[Invoice Items] (depend on chart of accounts)
	 |					|
	 --------------------
				|
			Invoices (depend on all three lists above)

 
For errors or problems contact sam-git@samuelfullman.com.
For further information go to: http://www.compasspoint-sw.com/docs/quickbooks";
//---------------------------------------------------------------------------
$IIFExportComments=explode("\n",$IIFExportComments);
$IIFExportComments='!'."\t".implode("\n!\t",$IIFExportComments);

$functionVersions['quickbooks_export_customer']=4.00;
function quickbooks_export_customer($where='',$options=array()){
	global $quickbooks_export_customer, $qr, $fl, $ln, $qx;
	/***********
	version 4.0 2008-07-24
	----------------------
	uses finan_clients structure as default: for most current example see table creation string below
	options:
		CUSTFLD[1-15] decs as a field from the db query
		customSQL=default blank - use a custom SQL query (for single pass data fetch) with field list marked as <FROMCLAUSE>
		cnx=array() - for custom connection string
		filterExported [false] (if set to true will filter records that already have been exported)
		setAsExported [true] (set ToBeExported=0 for each record)
		setExportTime - [NOW()] otherwise pass a string literal e.g. '2008-07-24 12:15:03'
		
	
	created 2004-10-31
	
	***********/
	extract($options);
	if(!$where){
		$where=1;
	}else if(is_array($where)){
		$where = 'a.ID IN('.implode(',',$where).')';
	}else{
		//literal
	}
	if($filterExported)$filterWhere=' AND a.ToBeExported=1';
	if(!isset($setAsExported))$setAsExported=true;
	$quickbooks_export_customer=array();
	if(!isset($useCreditLimit))$useCreditLimit=false;
	
	//current header I have
	$quickbooks_export_customer['header']= <<<aleftarightarockstep
!HDR	PROD	VER	REL	IIFVER	DATE	TIME	ACCNTNT	ACCNTNTSPLITTIME
HDR	QuickBooks Pro	Version 17.0D	Release R1P	1	{_ExportCreateDate_}	{_ExportTimeStamp_}	N	0

aleftarightarockstep;
	
	$structure=array(
		"NAME"=>"CONCAT(IF(aa.ID IS NOT NULL, CONCAT(a.ClientName,':'), ''),a.ClientName)",
		"REFNUM"=>"a.ID",
		"TIMESTAMP"=>"UNIX_TIMESTAMP(a.EditDate)",
		"BADDR1"=>"a.ClientName",
		"BADDR2"=>"a.Address1",
		"BADDR3"=>"IF(a.Address2!='', a.Address2, CONCAT(a.City,', ',a.State,'  ',a.Zip))",
		"BADDR4"=>"IF(a.Address2!='', CONCAT(a.City,', ',a.State,'  ',a.Zip),'')",
		"BADDR5"=>"",
		"SADDR1"=>"IF(a.ShippingAddress!='', a.ClientName, '')",
		"SADDR2"=>"IF(a.ShippingAddress!='', a.ShippingAddress, '')",
		"SADDR3"=>"IF(a.ShippingAddress!='' AND a.ShippingAddress2!='', a.ShippingAddress2, CONCAT(a.ShippingCity,', ',a.ShippingState,'  ',a.ShippingZip))",
		"SADDR4"=>"IF(a.ShippingAddress!='' AND a.ShippingAddress2!='', CONCAT(a.ShippingCity,', ',a.ShippingState,'  ',a.ShippingZip), '')",
		"SADDR5"=>"",
		"PHONE1"=>"a.Phone",
		"PHONE2"=>"a.Phone2",
		"FAXNUM"=>"a.Fax",
		"EMAIL"=>"a.Email",
		"NOTE"=>"",
		"CONT1"=>"CONCAT(c.FirstName,' ',c.LastName)",
		"CONT2"=>"IF(c.HomeMobile, CONCAT('Mobile: ',c.HomeMobile),'')",
		"CTYPE"=>"a.Category",
		"TERMS"=>"IF(t.Name IS NOT NULL, t.Name, '')",
		"TAXABLE"=>"",
		"SALESTAXCODE"=>"",
		"'LIMIT'"=>($useCreditLimit ? "a.CreditLimit" : '0'),
		"RESALENUM"=>"c.WholesaleNumber",
		"REP"=>"",
		"TAXITEM"=>"",
		"NOTEPAD"=>"a.Notes",
		"SALUTATION"=>"c.Title",
		"COMPANYNAME"=>"a.CompanyName",
		"FIRSTNAME"=>"c.FirstName",
		"MIDINIT"=>"c.MiddleName",
		"LASTNAME"=>"c.LastName",
		"CUSTFLD1"=>$CUSTFLD1,
		"CUSTFLD2"=>$CUSTFLD2,
		"CUSTFLD3"=>$CUSTFLD3,
		"CUSTFLD4"=>$CUSTFLD4,
		"CUSTFLD5"=>$CUSTFLD5,
		"CUSTFLD6"=>$CUSTFLD6,
		"CUSTFLD7"=>$CUSTFLD7,
		"CUSTFLD8"=>$CUSTFLD8,
		"CUSTFLD9"=>$CUSTFLD9,
		"CUSTFLD10"=>$CUSTFLD10,
		"CUSTFLD11"=>$CUSTFLD11,
		"CUSTFLD12"=>$CUSTFLD12,
		"CUSTFLD13"=>$CUSTFLD13,
		"CUSTFLD14"=>$CUSTFLD14,
		"CUSTFLD15"=>$CUSTFLD15,
		"JOBDESC"=>"",
		"JOBTYPE"=>"",
		"JOBSTATUS"=>"",
		"JOBSTART"=>"",
		"JOBPROJEND"=>"",
		"JOBEND"=>"",
		"HIDDEN"=>"IF(a.Active=0,'Y','')",
		"DELCOUNT"=>"",
		"PRICELEVEL"=>""
	);
	foreach($structure as $n=>$v){
		$fields[]=(strlen($v) ? $v : "''").' AS '.$n;
	}
	$fields=implode(",\n",$fields);
	if($customSQL){
		$sql=preg_replace('/<FROMCLAUSE>/i',$fields,$customSQL);
	}else{
		$sql="SELECT
		$fields
		FROM
		finan_clients a LEFT JOIN finan_clients aa ON a.Clients_ID=aa.ID LEFT JOIN finan_terms t ON a.Terms_ID=t.ID, finan_ClientsContacts cc, addr_contacts c WHERE a.ID=cc.Clients_ID AND cc.Contacts_ID=c.ID AND cc.Type='primary' AND $where $filterWhere GROUP BY a.ID ORDER BY a.CreateDate";
	}

	$c= <<<aleftarightarockstep
!CUSTNAMEDICT	INDEX	LABEL	CUSTOMER	VENDOR	EMPLOYEE
!ENDCUSTNAMEDICT
CUSTNAMEDICT	0	{_CF1_LABEL_}	{_CF1_CUST_}	{_CF1_VEND_}	{_CF1_EMP_}	
CUSTNAMEDICT	1	{_CF2_LABEL_}	{_CF2_CUST_}	{_CF2_VEND_}	{_CF2_EMP_}
CUSTNAMEDICT	2	{_CF3_LABEL_}	{_CF3_CUST_}	{_CF3_VEND_}	{_CF3_EMP_}
CUSTNAMEDICT	3	{_CF4_LABEL_}	{_CF4_CUST_}	{_CF4_VEND_}	{_CF4_EMP_}
CUSTNAMEDICT	4	{_CF5_LABEL_}	{_CF5_CUST_}	{_CF5_VEND_}	{_CF5_EMP_}
CUSTNAMEDICT	5	{_CF6_LABEL_}	{_CF6_CUST_}	{_CF6_VEND_}	{_CF6_EMP_}
CUSTNAMEDICT	6	{_CF7_LABEL_}	{_CF7_CUST_}	{_CF7_VEND_}	{_CF7_EMP_}
CUSTNAMEDICT	7	{_CF8_LABEL_}	{_CF8_CUST_}	{_CF8_VEND_}	{_CF8_EMP_}
CUSTNAMEDICT	8	{_CF9_LABEL_}	{_CF9_CUST_}	{_CF9_VEND_}	{_CF9_EMP_}
CUSTNAMEDICT	9	{_CF10_LABEL_}	{_CF10_CUST_}	{_CF10_VEND_}	{_CF10_EMP_}
CUSTNAMEDICT	10	{_CF11_LABEL_}	{_CF11_CUST_}	{_CF11_VEND_}	{_CF11_EMP_}
CUSTNAMEDICT	11	{_CF12_LABEL_}	{_CF12_CUST_}	{_CF12_VEND_}	{_CF12_EMP_}
CUSTNAMEDICT	12	{_CF13_LABEL_}	{_CF13_CUST_}	{_CF13_VEND_}	{_CF13_EMP_}
CUSTNAMEDICT	13	{_CF14_LABEL_}	{_CF14_CUST_}	{_CF14_VEND_}	{_CF14_EMP_}
CUSTNAMEDICT	14	{_CF15_LABEL_}	{_CF15_CUST_}	{_CF15_VEND_}	{_CF15_EMP_}
ENDCUSTNAMEDICT

aleftarightarockstep;

	if($setAsExported){
		q("UPDATE finan_clients a SET a.ToBeExported=0, a.ExportTime=".($setExportTime ? "'".$setExportTime."'" : 'NOW()').", a.Exporter='".($_SESSION['admin']['userName'] ? $_SESSION['admin']['userName'] : 'system')."' WHERE $where ".str_replace(' AND a.ToBeExported=1','',$filterWhere));
	}

	if($result=q($sql, O_ARRAY, ($cnx ? $cnx : $qx['defCnxMethod']))){
		
		//customnamedictionary
		for($i=1;$i<=15;$i++){
			 
			if($x=$structure['CUSTFLD'.$i]){
				//remove table alias
				$x=preg_replace('/[a-z]+\./i','',$x);
				$c=str_replace('{_CF'.$i.'_LABEL_}',$x,$c);
				$c=str_replace('{_CF'.$i.'_CUST_}','Y',$c);
				$c=str_replace('{_CF'.$i.'_VEND_}','N',$c);
				$c=str_replace('{_CF'.$i.'_EMP_}','N',$c);
			}else{
				$c=str_replace('{_CF'.$i.'_LABEL_}','',$c);
				$c=str_replace('{_CF'.$i.'_CUST_}','N',$c);
				$c=str_replace('{_CF'.$i.'_VEND_}','N',$c);
				$c=str_replace('{_CF'.$i.'_EMP_}','N',$c);
			}
		}
		$c=str_replace('{_ExportCreateDate_}',date('m/d/y'),$c);
		$c=str_replace('{_ExportTimeStamp_}',time(),$c);

		$i=0;
		foreach($result as $rd){
			$i++;
			if($i==1){
				$header='!CUST';
				foreach($structure as $n=>$v){
					$n=str_replace("'",'',$n);
					$header.="\t$n";
				}
				$header.= "\n";
			}
			$body.='CUST';
			foreach($structure as $n=>$v){
				$str=trim($rd[$n]);
				if(strstr($str,"\n") || strstr($str,"\t") || strstr($str,'"')){
					$str=str_replace("\n",'\\n',$str);
					$str=str_replace('"',"'",$str);
					$str='"'.$str.'"';
				}
				$body.="\t".$str;
			}
			$body.="\n";
		}
		$body.="ENDCUST\n";
		$quickbooks_export_customer['customnamedictionary']=$c;
		$quickbooks_export_customer['records']=$header.$body;
		return true;
	}
}
/* ----------------- finan_clients -----------------------
CREATE TABLE `finan_clients` (
 `ID` int(9) unsigned NOT NULL auto_increment,
 `Version` varchar(10) NOT NULL default '',
 `Owner` varchar(30) NOT NULL default 'cpm006',
 `CreateDate` datetime NOT NULL default '0000-00-00 00:00:00',
 `Creator` varchar(30) NOT NULL default 'cpm006',
 `EditDate` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
 `Editor` varchar(30) NOT NULL default '',
 `ResourceType` tinyint(1) unsigned default NULL,
 `SessionKey` char(32) NOT NULL,
 `ResourceToken` char(30) NOT NULL,
 `Active` tinyint(1) NOT NULL default '1', --matches inactive field
 `Statuses_ID` mediumint(4) unsigned NOT NULL default '0',
 `ToBeExported` tinyint(1) NOT NULL default '1',
 `Exporter` varchar(30) NOT NULL default '',
 `ExportTime` datetime NOT NULL default '0000-00-00 00:00:00',
 `Terms_ID` int(9) unsigned NOT NULL default '0',
 `CreditLimit` float(10,2) unsigned NOT NULL default '0.00',
 `Clients_ID` int(9) unsigned default NULL,
 `ClientName` varchar(75) NOT NULL default '',
 `ClientAccountNumber` char(30) NOT NULL,
 `CompanyName` char(75) NOT NULL,
 `BillingSystem` tinyint(1) unsigned NOT NULL default '0',
 `Category` varchar(30) NOT NULL default '',
 `PrimarySalutation` char(20) NOT NULL,
 `PrimaryFirstName` varchar(35) NOT NULL default '',
 `PrimaryMiddleName` char(35) NOT NULL,
 `PrimaryLastName` varchar(35) NOT NULL default '',
 `Email` varchar(75) NOT NULL default '',
 `ShowEmailPublicly` tinyint(1) unsigned NOT NULL default '0',
 `EmailCC` char(85) NOT NULL,
 `WebPage` char(255) NOT NULL,
 `ContactPage` text NOT NULL,
 `LandingPage` text NOT NULL,
 `Description` char(255) NOT NULL,
 `Keywords` text NOT NULL,
 `UserName` varchar(30) NOT NULL default '',
 `Password` varchar(16) NOT NULL default '',
 `Password_MD5` varchar(32) NOT NULL default '',
 `PasswordMD5` varchar(32) NOT NULL,
 `Address1` varchar(75) NOT NULL default '',
 `Address2` varchar(75) NOT NULL default '',
 `City` varchar(45) NOT NULL default '',
 `State` char(3) NOT NULL default '',
 `Zip` varchar(10) NOT NULL default '',
 `Country` char(3) NOT NULL default 'USA',
 `ShippingAddress` varchar(128) NOT NULL default '',
 `ShippingAddress2` char(75) NOT NULL,
 `ShippingCity` varchar(45) NOT NULL default '',
 `ShippingState` char(3) NOT NULL default '',
 `ShippingZip` varchar(10) NOT NULL default '',
 `ShippingCountry` char(3) NOT NULL default 'USA',
 `Phone` varchar(24) NOT NULL default '',
 `Phone2` char(25) NOT NULL,
 `Mobile` varchar(24) NOT NULL default '',
 `Fax` varchar(24) NOT NULL default '',
 `Referral` varchar(30) NOT NULL default '',
 `Notes` text NOT NULL,
 `newrenewal` char(255) NOT NULL,
 `newjoin` char(255) NOT NULL,
 `newcategory` char(255) NOT NULL,
 `MembershipType` char(35) default NULL,
 `MembershipLevel` char(40) NOT NULL,
 `MembershipStart` date default NULL,
 `MembershipEnd` date default NULL,
 `ClientHandle` char(75) NOT NULL,
 `Employed` char(10) NOT NULL,
 `CommitteeMemberCandidate` char(10) NOT NULL,
 `SpecialActivities` text NOT NULL,
 PRIMARY KEY  (`ID`),
 KEY `Clients_ID` (`Clients_ID`),
 KEY `Description` (`Description`)
) ENGINE=MyISAM AUTO_INCREMENT=1006 DEFAULT CHARSET=latin1



CREATE TABLE `finan_ClientsContacts` (
 `Clients_ID` int(7) unsigned NOT NULL default '0',
 `Contacts_ID` int(7) unsigned NOT NULL default '0',
 `Type` char(30) NOT NULL default '',
 `Notes` char(70) NOT NULL default '',
 `EditDate` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
 PRIMARY KEY  (`Clients_ID`,`Contacts_ID`),
 KEY `Contacts_ID` (`Contacts_ID`),
 KEY `clients_id` (`Clients_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1



CREATE TABLE `addr_contacts` (
 `ID` int(7) unsigned NOT NULL auto_increment,
 `Version` varchar(10) NOT NULL default 'v00.00.00',
 `Owner` varchar(30) NOT NULL default 'cpm035',
 `CreateDate` datetime NOT NULL default '0000-00-00 00:00:00',
 `Creator` varchar(30) NOT NULL default 'cpm035',
 `EditDate` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
 `Editor` varchar(30) NOT NULL default '',
 `Active` tinyint(1) unsigned NOT NULL default '1',
 `Category` char(75) NOT NULL,
 `FirstName` varchar(35) NOT NULL default '',
 `MiddleName` varchar(35) NOT NULL default '',
 `LastName` varchar(40) NOT NULL default '',
 `Title` varchar(50) NOT NULL default '',
 `Display` varchar(50) NOT NULL default '',
 `Nickname` varchar(30) NOT NULL default '',
 `Email` varchar(85) NOT NULL default '',
 `Email2` varchar(85) NOT NULL default '',
 `HomeAddress` text,
 `HomeCity` varchar(35) NOT NULL default '',
 `HomeState` char(3) NOT NULL default '',
 `HomeZip` varchar(10) NOT NULL default '',
 `HomeCountry` char(3) NOT NULL default '',
 `HomeDefault` smallint(1) unsigned NOT NULL default '0',
 `HomePhone` varchar(24) NOT NULL default '',
 `HomeFax` varchar(24) NOT NULL default '',
 `HomeMobile` varchar(24) NOT NULL default '',
 `HomeWebsite` varchar(255) NOT NULL default '',
 `Clients_ID` mediumint(6) NOT NULL default '0',
 `Company` varchar(75) NOT NULL default '',
 `BusAddress` text,
 `BusCity` varchar(35) NOT NULL default '',
 `BusState` char(3) NOT NULL default '',
 `BusZip` varchar(10) NOT NULL default '',
 `BusCountry` char(3) NOT NULL default '',
 `BusTitle` varchar(35) NOT NULL default '',
 `BusDepartment` varchar(45) NOT NULL default '',
 `BusOffice` varchar(40) NOT NULL default '',
 `BusPhone` varchar(24) NOT NULL default '',
 `BusFax` varchar(24) NOT NULL default '',
 `BusPager` varchar(24) NOT NULL default '',
 `BusWebsite` varchar(85) NOT NULL default '',
 `Spouse` varchar(100) NOT NULL default '',
 `Children` text,
 `Gender` smallint(1) unsigned NOT NULL default '0',
 `Birthday` date NOT NULL default '0000-00-00',
 `Anniversary` date NOT NULL default '0000-00-00',
 `Notes` text,
 `StaffNotes` text,
 `EntryType` mediumint(3) NOT NULL default '0',
 `Familiarity` varchar(25) NOT NULL default '',
 `LastContactMailer` int(9) unsigned NOT NULL default '0',
 `LastContactDate` datetime NOT NULL default '0000-00-00 00:00:00',
 `ToBeExported` tinyint(1) unsigned NOT NULL default '0',
 `ExportDate` datetime NOT NULL default '0000-00-00 00:00:00',
 `Exporter` varchar(20) NOT NULL default '',
 `ImportSource` varchar(20) NOT NULL default '',
 `UserName` varchar(30) default NULL,
 `Password` varchar(30) NOT NULL default '',
 `PasswordMD5` varchar(32) NOT NULL default '',
 `EnrollmentAuthToken` varchar(32) default NULL,
 `EnrollmentAuthDuration` char(2) NOT NULL default '',
 `Image_Files_ID` int(7) unsigned NOT NULL default '0',
 `ReferralSource` varchar(25) NOT NULL default '',
 `ReferralCode` varchar(16) NOT NULL default '',
 `ReferralTerm` varchar(45) NOT NULL default '',
 `ReferralHTTP` text NOT NULL,
 PRIMARY KEY  (`ID`),
 UNIQUE KEY `UserName` (`UserName`),
 KEY `FirstName` (`FirstName`),
 KEY `LastName` (`LastName`),
 KEY `Email` (`Email`),
 KEY `Email2` (`Email2`),
 KEY `Image_Files_ID` (`Image_Files_ID`)
) ENGINE=MyISAM AUTO_INCREMENT=991 DEFAULT CHARSET=latin1 COMMENT='*Ctc Ver.1.0 [Updated 2005-10-15] registered 2006-10-28 16:3'






*/
?>