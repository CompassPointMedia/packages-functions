<?php
function parse_name($name,$options=array()){
	/* 2010-09-23 by Samuel - really thought I had a more complex version of this function - got this from gioc osa */


	extract($options);
	if($prefix)$prefix='';
	if(!isset($acceptSingleName))$acceptSingleName=true;
	
	//don't consider periods
	$n=str_replace('.','',$name);
	//clean spaces
	$n=preg_replace('/\s+/',' ',trim($n));
	if(!preg_match('/^[-1-4a-z\', ]+$/i',$n))return false;

	//pull title from end, comma possible but not required, then re-trim
	if(preg_match('/,*\s*\b(I|II|III|IV|1st|2nd|3rd|4th|Jr|Sr|MD|PhD|MS|BS)$/i',$n,$m)){
		$r[$prefix.(preg_match('/(MD|PhD|MS|BS)/i',$m[1]) ? 'Title' : 'Suffix')]=$m[1];
		$n=trim(str_replace($m[0],'',$n));
	}

	//transpose lastname, firstname format
	if(strstr($n,',')){
		$n=explode(',',$n);
		if(count($n)>2)return false;
		$n=trim($n[1]).' '.trim($n[0]);
		$r['transposed']=true;
	}
	if(preg_match('/^(Dr|Mr|Mrs|Ms|Rev|Hon)\b/i',$n,$m)){
		$r[$prefix.'Title']=$m[1];
		$n=trim(str_replace($m[0],'',$n));
	}
	//de la, van and van der
	$n=preg_replace('/\sde\sla\s/i',' de^la^',$n);
	$n=preg_replace('/\sdi\s/i',' di^',$n);
	$n=preg_replace('/\sVan\s/i',' Van^',$n);
	$n=preg_replace('/\sVan\sder\s/i',' Van^der^',$n);
	$n=preg_replace('/\sVon\s/i',' Von^',$n);
	/*
	exceptions:
	H Stephen Hager - alternate i.e. he may go by Stephen (or Steve)
	DONE	Oscar de la Renta - both de and la are part of last name and would be entered with reasonable assurance
	DONE	Clyde Van Damme (Van is part of last name)
	DONE	Sammy Davis Jr
	DONE	Dr Hugh Ross
	
	*/
	$a=explode(' ',$n);
	if(count($a)==1 && $acceptSingleName){
		//either a last name or a first name
		global $public_cnx;
		if(q("SELECT FirstName FROM seed_firstnames WHERE FirstName='".addslashes($a[0])."'", O_VALUE, $public_cnx)){
			$r[$prefix.'FirstName']=str_replace('^',' ',$a[0]);
		}else{
			$r[$prefix.'LastName']=str_replace('^',' ',$a[0]);
		}
	}else if(count($a)==2){
		$r[$prefix.'FirstName']=str_replace('^',' ',$a[0]);
		$r[$prefix.'LastName']=str_replace('^',' ',$a[1]);
	}else if(count($a)==3){
		$r[$prefix.'FirstName']=str_replace('^','',$a[0]);
		$r[$prefix.'MiddleName']=str_replace('^','',$a[1]);
		$r[$prefix.'LastName']=str_replace('^','',$a[2]);	
	}else{
		return false;
	}
	return $r;
}
?>