<?php
$functionVersions['prn']=1.00;
function prn($value,$exit=0, $show_constants=0, $hide_numeric=0){
	$non_numeric=1;
	//cleans up print_r and includes <pre> tags before
	echo "<pre>";
	ob_start();
	print_r($value);
	$v=ob_get_contents();
	ob_end_clean();
	$v=str_replace('    ','  ',$v);
	$v=str_replace("\n\n","\n",$v);
	//for displaying fetch_arrays from d b
	if($hide_numeric){
		$w=explode("\n",$v);
		if(is_array($w)){
			$output=1;
			foreach($w as $x){
				if(preg_match('/\s+\[[0-9]+\]\s+=>/',$x)){ //this line is a number
					$output=0;
				}else{
					if(preg_match('/\s+\[[^]*]+\]\s+=>/',$x)){ //this line is start of a text
						$output = 1;
						$y[]=$x;
					}else if($output==1){
						$y[]=$x;
					}
				}
			}
			$v=implode("\n",$y);
		}
	}
	//for showing constants
	$constants[1]='DNAME';
	$constants[2]='DCOMMENT';
	$constants[3]='DATTRIB';
	$constants[4]='DDISPLAY';
	$constants[5]='DDEFAULT';
	$constants[6]='DTYPE';
	$constants[7]='DNULL';
	$constants[8]='DAUTOINC';
	$constants[9]='DPRIMARY';
	$constants[10]='DUNSIGNED';
	$constants[11]='DZEROFILL';
	$constants[12]='DINDEX';
	$constants[13]='DUNIQUE';
	$constants[14]='DBINARY';
	$constants[15]='DFOREIGN';
	$constants[16]='R16';
	$constants[17]='R17';
	$constants[18]='FGRPIDX';
	$constants[19]='FGROUP';
	$constants[20]='FLABEL';
	$constants[21]='FCOMMENT';
	$constants[22]='FINDEX';
	$constants[23]='FTYPE';
	$constants[24]='FMASK';
	$constants[25]='FDEFAULT';
	$constants[26]='FATTRIBA';
	$constants[27]='FATTRIBB';
	$constants[28]='FATTRIBC';
	$constants[29]='R29';
	$constants[30]='XSTYLE';
	$constants[31]='XCLASS';
	$constants[32]='XID';
	$constants[33]='XUDCTNCN';
	$constants[34]='XERROR';
	$constants[35]='XJAVASCRIPT';
	$constants[36]='R36';
	$constants[37]='R37';
	$constants[38]='R38';
	$constants[39]='R39';
	$constants[40]='R40';

	if($show_constants==1){
		$w=explode("\n",$v);
		if(is_array($w)){
			foreach($w as $key=>$x){
				if(preg_match('/^\s+\[[1-4]*[0-9]\]/',$x,$y)){
					$constant=$constants[preg_replace('/(\s|\[|\])*/','',$y[0])];
					$new = preg_replace('/[1-4]*[0-9]/',$constant,$y[0]);
					$until=strlen($y[0]);
					$w[$key]=$new.substr($x,-(strlen($x)-$until));
				}
			}
			$v=implode("\n",$w);
		}
	
	}	
	
	
	echo htmlspecialchars($v, ENT_COMPAT, 'ISO-8859-15');
	echo "</pre>";
	if($exit)exit;
}
?>