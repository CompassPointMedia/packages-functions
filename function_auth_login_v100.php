<?php
$functionVersions['auth_login']=1.00;
function auth_login($options=array(),$secondary=array(),$tertiary=array()){
	/* 2013-01-05 - generalizes many of my previous login logics
	
	RULE IS: MORE SENSITIVE -> LESS SENSITIVE
	un/email: md5(master_password . pw) (pw itself md5'd)
	AuthKey and SuperAuthKey were used for certificates
	comp_loginA_v1.32 - authKey=md5(master_pw . pw)
	but for components - I used md5(component . master_pw) - NOTE INCORRECT REVERSAL
	
	Here is the most general case:
	authKey= md5(master_pw . salt . pw|component . t)	
	
	options
		MASTER_PASSWORD
		salt
		PW
		token
	
	methods:
		auth_login(UN,PW)
		auth_login(UN,authKey)
		auth_login(UN,authKey,t)
		auth_login(array(
		
		))
	*/
	global $auth_login,$qr, $ln, $fl;
	if(is_string($options) && is_string($secondary)){
		//standard username|email + unencrypted password login, BUT we also check if the password is 32-byte md5
		$UN=$options;
		$PW=$secondary;
		if(is_string($tertiary))$t=$tertiary;
		$auth_login['login']['method']=($t?'time-sensitive':'standard');
		if(!strlen($UN) || !strlen($PW)){
			$auth_login['login']['error']='missing values passed';
			return false;
		}
		@extract($auth_login);
		if(!$table)$table='addr_contacts';
		if(!$userNameField)$userNameField='UserName';
		if(!$passwordField)$passwordField='PasswordMD5';
		if(!$masterPasswordNamespace)$masterPasswordNamespace='MASTER_PASSWORD';
		if(!$database)$database=$GLOBALS['MASTER_DATABASE'];
		
		if(strlen($t) && $t < time()){
			$auth_login['login']['error']='valid time expired';
			return false;
		}
		//we now only allow stored passwords to be encrypted (md5)
        $sql = "SELECT * FROM $table WHERE 
		$passwordField!='' AND $userNameField!='' AND
		($userNameField='$UN' ".($emailField ? "OR $emailField='$UN'":'').")";
		$auth_login['login']['record']=q($sql, O_ARRAY, $database);
		if(count($auth_login['login']['record'])!=1){
			$auth_login['login']['error']=(count($auth_login['login']['record']) ? 'ambiguous login, UN value returns multiple' : 'no matches for username');
			return false;
		}
		$auth_login['login']['record']=$auth_login['login']['record'][1];
		$PW=stripslashes($PW);
		if(
			//authToken || authKey - time optional
			$PW==md5($GLOBALS[$masterPasswordNamespace].$auth_login['login']['record'][$passwordField].$t) ||
			//password
			md5($PW)==$auth_login['login']['record'][$passwordField] ||
			//encrypted password
			$PW==$auth_login['login']['record'][$passwordField] ||
			//master password match
			$PW==$GLOBALS[$masterPasswordNamespace] || 
			//encrypted master password match
			$PW==md5($GLOBALS[$masterPasswordNamespace])
		){
			if($PW==$GLOBALS[$masterPasswordNamespace] || $PW==md5($GLOBALS[$masterPasswordNamespace])) $auth_login['login']['master']=true;
			return true;
		}
		$auth_login['login']['error']='authKey'.($t?' or timestamp(t) ':'').' not valid';
	}else{
		extract($options);
		
	}
	return false;
}
