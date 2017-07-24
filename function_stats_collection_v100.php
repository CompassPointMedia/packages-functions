<?php
$functionVersions['stats_collection']=1.00;
function stats_collection($secure=false){
	global $fromHdrBugs, $developerEmail, $enhanced_parse_url, $MASTER_DATABASE;
	/**
	2007-01-15: Stats Collection version 1.0
	This uses a very obvious relational structure for the tables.  The only unobvious aspect are the tables stats_visitors and stats_VisitorsPageserved; first one has a unique structure 1) contacts_id has null for ip and machine; we should have all pertinent info on a Contact in another place, plus stats_VisitorsIps has all IPs that person has come in from, and 2) Ips_ID with NULL for machine is a generic anonymous "all users who wouldn't accept cookies", and 3) Ips_ID and Machines_ID together indicate someone who is not logged in.  In optimal practice we go back and either a)update this user to a contact upon login or b) merge it over to the contact for all records.  Basically, stats_visitorsIps is a joiner table but it is not the usual construction I've used.
	
	Quite a few things not considered here including what http protocol was used (1,2), what cookies present, and the ABSOLUTE path of the file.  We're still looking at this as a self-contained unit.
	
	Plans to test this include:
	1. failed queries for now email to me
	2. determine what would be bad or meaningless data
	3. determine what would violate ref. integrity
	**/
		if($page=='/stats2.php')prn($_COOKIE);
	$nonCookieLapseTime=10; //minutes
	//get the current url
	$page=$_SERVER['REQUEST_URI'];
	$file=($secure ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	enhanced_parse_url($file);
	if($enhanced_parse_url['scheme'] && $enhanced_parse_url['domain'] && $enhanced_parse_url['rawpath'] && $enhanced_parse_url['file']){
		//ob_start();
		$a=$enhanced_parse_url;
		if(preg_match('/^www\./i',$a['domain'])){
			//the www. extension is was well-intentioned but for our purposes we consider it identical
			$a['domain']=preg_replace('/^www\./i','',$a['domain']);
			$a['www_flag']=1;
		}
		if(!($Schemes_ID=q("SELECT ID FROM stats_protocols WHERE Name='".strtolower($a['scheme'])."'",O_VALUE,ERR_ECHO))){
			$Schemes_ID=q("INSERT INTO stats_protocols SET Name='".strtolower($a['scheme'])."'",O_INSERTID,ERR_ECHO);
		}
		//remember Name is binary
		if(!($Domains_ID=q("SELECT ID FROM stats_domains WHERE Name='".strtolower($a['domain'])."'",O_VALUE,ERR_ECHO))){
			$Domains_ID=q("INSERT INTO stats_domains SET Name='".strtolower($a['domain'])."'",O_INSERTID,ERR_ECHO);
		}
		if(!($Paths_ID=q("SELECT ID FROM stats_paths WHERE Name='".$a['rawpath']."'",O_VALUE,ERR_ECHO))){
			$Paths_ID=q("INSERT INTO stats_paths SET Name='".$a['rawpath']."'",O_INSERTID,ERR_ECHO);
		}
		if(!($Files_ID=q("SELECT ID FROM stats_files WHERE Name='".$a['file']."'",O_VALUE,ERR_ECHO))){
			$Files_ID=q("INSERT INTO stats_files SET Name='".$a['file']."'",O_INSERTID,ERR_ECHO);
		}
		$NameHash=substr(md5(strtolower($a['query'])),0,12);
		if(!($Querystrings_ID=q("SELECT ID FROM stats_querystrings WHERE NameHash='$NameHash'",O_VALUE,ERR_ECHO))){
			$Querystrings_ID=q("INSERT INTO stats_querystrings SET Name='".$a['query']."', NameHash='$NameHash'",O_INSERTID,ERR_ECHO);
		}
		if(!($Fragments_ID=q("SELECT ID FROM stats_bookmarks WHERE Name='".strtolower($a['fragment'])."'",O_VALUE,ERR_ECHO))){
			$Fragments_ID=q("INSERT INTO stats_bookmarks SET Name='".strtolower($a['fragment'])."'",O_INSERTID,ERR_ECHO);
		}
		if(!($Pageserved_ID=q("SELECT ID FROM stats_pageserved WHERE
			Schemes_ID='$Schemes_ID' AND
			Domains_ID='$Domains_ID' AND
			Paths_ID='$Paths_ID' AND
			Files_ID='$Files_ID' AND
			Querystrings_ID='$Querystrings_ID' AND
			Fragments_ID='$Fragments_ID'", O_VALUE,ERR_ECHO))){
			
			$Pageserved_ID=q("INSERT INTO stats_pageserved SET
			Schemes_ID='$Schemes_ID',
			Domains_ID='$Domains_ID',
			Paths_ID='$Paths_ID',
			Files_ID='$Files_ID',
			Querystrings_ID='$Querystrings_ID',
			Fragments_ID='$Fragments_ID'", O_INSERTID,ERR_ECHO);
		}
	
		$ip=explode('.',$_SERVER['REMOTE_ADDR']);
		foreach($ip as $n=>$v)$ip[$n]=preg_replace('/^0*/','',$v);
		if(!($Ips_ID=q("SELECT ID FROM stats_ips WHERE IPA='".$ip[0]."' AND IPB='".$ip[1]."' AND IPC='".$ip[2]."' AND IPD='".$ip[3]."'", O_VALUE,ERR_ECHO))){
			$Ips_ID=q("INSERT INTO stats_ips SET IPAddress='".$_SERVER['REMOTE_ADDR']."',
			IPA='".$ip[0]."',
			IPB='".$ip[1]."',
			IPC='".$ip[2]."',
			IPD='".$ip[3]."'",O_INSERTID,ERR_ECHO);
		}
	
		if(!($Useragents_ID=q("SELECT ID FROM stats_useragents WHERE Name='".$_SERVER['HTTP_USER_AGENT']."'",O_VALUE))){
			$Useragents_ID=q("INSERT INTO stats_useragents SET
			Name='".addslashes($_SERVER['HTTP_USER_AGENT'])."'",O_INSERTID,ERR_ECHO);
		}
		//see if the IP is recent (within 10 minutes) but with no cookie.m set
		if($maxViewTime=q("SELECT DATE_FORMAT(MAX(ViewTime),'%Y-%m-%d %H:%i:%s') FROM stats_VisitorsPageserved WHERE Ips_ID='$Ips_ID'", O_VALUE,ERR_ECHO)
			&&
			time()-strtotime($maxViewTime) < $nonCookieLapseTime*60
			&&
			!$_COOKIE['m']){
			//no use getting the machine ID, but one was set previously and we could put some info back on the machine as not taking cookies
			$IDLevel=0;
			echo '...';
		}else{
			if($page=='/stats2.php')prn($_COOKIE);
			if($Machine=$_COOKIE['m']){
				if($Machines_ID=q("SELECT ID FROM stats_machines WHERE Machine='".strtolower($Machine)."'",O_VALUE,ERR_ECHO)){
					//we have a match in the database
					echo '=';
				}else{
					echo '-';
					//m is not valid, attempt to reset it
					$Machine=strtolower(md5(rand(100,10000000).time()));
					$_SESSION['special']['requestSetCookie']['m']=$Machine;
					/**
					setcookie('m',$Machine,time()+(3600*24*180),$_SERVER['HTTP_HOST']);
					**/
					$Machines_ID=q("INSERT INTO stats_machines SET
					Machine='$Machine',
					Last_Ips_ID='$Ips_ID',
					Last_Useragents_ID='$Useragents_ID'",O_INSERTID,ERR_ECHO);
				}
			}else{
				echo '--';
				//m doesn't exist, set it now
				$Machine=strtolower(md5(rand(100,10000000).time()));
				$_SESSION['special']['requestSetCookie']['m']=$Machine;
				/**
				setcookie('m',$Machine,time()+(3600*24*180),$_SERVER['HTTP_HOST']);
				**/
				$Machines_ID=q("INSERT INTO stats_machines SET
				Machine='$Machine',
				Last_Ips_ID='$Ips_ID',
				Last_Useragents_ID='$Useragents_ID'",O_INSERTID,ERR_ECHO);
			}
		}
		/**
		From here we have the following approach
		A visitor is
			1) a known person by login,
			2) a presumed person because we recognize the machine-browser combination AND only have one person who's used the machine-browser combination
			3) an unidentified person because the machine-browser combination has has multiple user accesses or
			4) an anonymous person based on their IP address.
		A "visit" is a page or pages served to a person, either known or by ip address, who has not visited in the last 120 minutes or decided upon time period
		The usemod system MUST back-update the identity and should do so on login (add this to the cgi module and to RelateBase generally maybe)
		
		ANOTHER VERY IMPORTANT POINT: If we show an IP address that has been in the system in the last n minutes and we don't have cookie.m, then cookies aren't working and forget about it
	
		THIS IS THE OLD QUERY IN SITELOG WHICH IS NO LONGER USED
		$sqlString = "insert into epc_Sitelog set SESSID = '$SESSID', IP = '$IP', Agent = '$Agent', Timestamp = $Timestamp, TermA = '$TermA', TermB = '$TermB', ID = '$ID', Event = $Event, EventValue = '$EventValue', Name = '$Name', IDLevel = '$IDLevel', PageURL = '$PageURL?" . $_SERVER['QUERY_STRING'] . "'";
	
		IDLevels:
		0 = anonymous, unable to set cookie
		1 = able to set cookie, nothing else known
		2 = known as one of several possible users tied to this machine
		3 = known as the ONLY user tied to this machine, but not logged in
		4 = logged in, identity known
		
		**/
		//determine tokens we have available for the visitor
	
		unset($statsUserID);
		if($_SESSION['identity']){
			//we tie into addr_contacts for now
			$statsUserID=$_SESSION['cnx'][$acct]['primaryKeyValue'];
			$IDLevel=4;
		}else{
			$usrs=q("SELECT Contacts_ID FROM stats_MachinesContacts WHERE Machines_ID='$Machines_ID'", O_COL,ERR_ECHO);
			switch(count($usrs)){
				case 0:
					$identityLevel=1;
				break;
				case 1:
					$identityLevel=3;
					$statsUserID=$usrs[1]; #first value
				break;
				default:
					$identityLevel=2;
			}
		}
		//insert visitor record if not already recorded - order is visitors -> visits -> visitlog
		if($statsUserID){
			if(!($Visitors_ID=q("SELECT ID FROM stats_visitors WHERE Contacts_ID='".$statsUserID."'", O_VALUE,ERR_ECHO))){
				$Visitors_ID=q("INSERT INTO stats_visitors SET Contacts_ID='$statsUserID'",O_INSERTID,ERR_ECHO);
			}
		}else{
			/**
			from here we have either IP addresses with the machine or without it
			
			we have a problem here: two people could use the same IP; one could allow cookies, one could NOT allow cookies.  All anonymous IPs are assumed to be one unidentified visitor (though it is actually "all" visitors that are using that IP anonymously).  Note also that for the anonymous IP we *could* get more information such as the user agent however a user may be viewing the site in both IE and Firefox; this information is not reliable enough.
			
			**/
			if(!($Visitors_ID=q("SELECT ID FROM stats_visitors WHERE Ips_ID='$Ips_ID' AND Machines_ID".($Machines_ID?"='$Machines_ID'":" IS NULL"),O_VALUE,ERR_ECHO))){
				$Visitors_ID=q("INSERT INTO stats_visitors SET Ips_ID='$Ips_ID', Machines_ID=".($Machines_ID ? "'".$Machines_ID."'" : 'NULL'),O_INSERTID,ERR_ECHO);
			}
		}
		//add the relationship
		q("REPLACE INTO stats_VisitorsIps SET Visitors_ID='$Visitors_ID', Ips_ID='$Ips_ID'",ERR_ECHO);
	
		//referrer
		unset($Referrers_ID);
		if($_SERVER['HTTP_REFERER']){
			enhanced_parse_url($_SERVER['HTTP_REFERER']);
			$b=$enhanced_parse_url;
			if(preg_match('/^www\./i',$b['domain'])){
				//the www. extension is was well-intentioned but for our purposes we consider it identical
				$b['domain']=preg_replace('/^www\./i','',$b['domain']);
				$b['www_flag']=1;
			}
			if(!($Schemes_ID=q("SELECT ID FROM stats_protocols WHERE Name='".strtolower($b['scheme'])."'",O_VALUE,ERR_ECHO))){
				$Schemes_ID=q("INSERT INTO stats_protocols SET Name='".strtolower($b['scheme'])."'",O_INSERTID,ERR_ECHO);
			}
			if(!($Domains_ID=q("SELECT ID FROM stats_domains WHERE Name='".strtolower($b['domain'])."'",O_VALUE,ERR_ECHO))){
				$Domains_ID=q("INSERT INTO stats_domains SET Name='".strtolower($b['domain'])."'",O_INSERTID,ERR_ECHO);
			}
			if(!($Paths_ID=q("SELECT ID FROM stats_paths WHERE Name='".$b['rawpath']."'",O_VALUE,ERR_ECHO))){
				$Paths_ID=q("INSERT INTO stats_paths SET Name='".$b['rawpath']."'",O_INSERTID,ERR_ECHO);
			}
			if(!($Files_ID=q("SELECT ID FROM stats_files WHERE Name='".$b['file']."'",O_VALUE,ERR_ECHO))){
				$Files_ID=q("INSERT INTO stats_files SET Name='".$b['file']."'",O_INSERTID,ERR_ECHO);
			}
			$NameHash=substr(md5(strtolower($b['query'])),0,12);
			if(!($Querystrings_ID=q("SELECT ID FROM stats_querystrings WHERE NameHash='$NameHash'",O_VALUE,ERR_ECHO))){
				$Querystrings_ID=q("INSERT INTO stats_querystrings SET Name='".$b['query']."', NameHash='$NameHash'",O_INSERTID,ERR_ECHO);
			}
			if(!($Fragments_ID=q("SELECT ID FROM stats_bookmarks WHERE Name='".strtolower($b['fragment'])."'",O_VALUE,ERR_ECHO))){
				$Fragments_ID=q("INSERT INTO stats_bookmarks SET Name='".strtolower($b['fragment'])."'",O_INSERTID,ERR_ECHO);
			}
			if(!($Referrers_ID=q("SELECT ID FROM stats_referrers WHERE
				Schemes_ID='$Schemes_ID' AND
				Domains_ID='$Domains_ID' AND
				Paths_ID='$Paths_ID' AND
				Files_ID='$Files_ID' AND
				Querystrings_ID='$Querystrings_ID' AND
				Fragments_ID='$Fragments_ID'",O_VALUE,ERR_ECHO))){
				
				$Referrers_ID=q("INSERT INTO stats_referrers SET
				Schemes_ID='$Schemes_ID',
				Domains_ID='$Domains_ID',
				Paths_ID='$Paths_ID',
				Files_ID='$Files_ID',
				Querystrings_ID='$Querystrings_ID',
				Fragments_ID='$Fragments_ID'",O_INSERTID,ERR_ECHO);
			}
		}
	
		//add the pagelog
		q("INSERT INTO stats_VisitorsPageserved SET
		Visitors_ID='$Visitors_ID',
		Ips_ID='$Ips_ID',
		Machines_ID='$Machines_ID',
		SessionID='".$_SESSION['sessionKey']."',
		Pageserved_ID='$Pageserved_ID',
		Referrers_ID='$Referrers_ID',
		W3Flag='".$a['www_flag']."',
		Version='1.0'",ERR_ECHO);
		//$err=ob_get_contents();
		//ob_end_clean();
		if($err){
			mail($developerEmail,'Query Exception', 'File: ' . __FILE__."\n".'Line: '.__LINE__."\n".'At least one query caused an exception in function stats_collection()'."\n\n".$out,$fromHdrBugs);
		}
	}else{
		//mail administrator: error parsing URL
		ob_start();
		print_r($GLOBALS);
		$out=ob_get_contents();
		ob_end_clean();
		mail($developerEmail,'error in Stats Collection', 'File: ' . __FILE__."\n".'Line: '.__LINE__."\n".'The referenced file could not be read by enhanced_parse_url()'."\n\n".$out,$fromHdrBugs);
	}
}
?>