<?php
$functionVersions['pJprocess_folder']='0.1';
function pJprocess_folder($options=array()){
	/*
Rules as of 2012-02-14:
-----------------------------------------------------

So, whoever you are reading this, here is how you create components for Juliet.  This system is in effect until a new system is created, and template relatebase_05_generic.php reads and understands this from the ComponentLocation field

currently, the dropdown list ComponentLocation (but with name desiredAction) is a "code" wher there is a colon between the path and the file, file being without the .php extension; this is necessary because we have JULIET components and master components (from RelateBase)
the values goes in field gen_nodes.ComponentLocation
NOTE that there is no current way to store multiple components

filenames are [acct_.]cognate[_v100][.extension].php
where all brackets shown are mutually exclusive (can only have one)
AND, if you intend to have versions, your whole suite of filenames should ALL have versions
the [.extension] is a sub-component and will not be shown on the dropdown list.

you can have component_v100 and component_v110 in the folder, this would read:
	LABEL								VALUE
	Awesome Component (v1.10)		-	component_v110
		(version 1.00)				-	component_v100
		(etc..)

you can have account versions in there as such however there can be no version and the "root" (cognate") must match:
	cpm024.component.php
	
*/
	/*
	2012-02-14 SF
	options
		data
		folder
		requireDocumentation - must have / * name=component; description=whatever * / present if true
	*/
	global $adminMode, $acct, $pJprocess_folder; 
	extract($options);
	global $$folder;
	if($adminMode==ADMIN_MODE_GOD){
		//get all accounts that I might want to see as relatebase agent
	}else{
		$regexAccounts=$acct;
	}
	if(!count($files))return;
	foreach($files as $n=>$v){
		if(!preg_match("/^(($regexAccounts)\.)*([-_a-z0-9]+?)(_v[0-9]{3})*\.php$/i",$v['name'],$m))continue;
		if(strstr($m[3],'.settings'))continue;
		$_acct_=$m[2];
		$cog=$m[3];
		$ver=str_replace('_v','',$m[4]);
		$components[$cog]['name']=$cog;
		$components[$cog]['label']=$cog;
		if($_acct_)$components[$cog]['accounts'][$_acct_]=$_acct_;
		if($ver>$components[$cog]['highestVersion'])$components[$cog]['highestVersion']=$ver;
		if($ver)$components[$cog]['versions'][$ver]=$ver;
		$components[$cog]['assets'][$ver]=$v;
		if(!$_acct_){
			$components[$cog]['julietOnlyPresent']=true;
		}else if($_acct_==$acct){
			$components[$cog]['this_account']=$acct;
		}
	}
	if($components){
		foreach($components as $cog=>$v){
			$ver=$v['highestVersion'];
			$f=file($$folder.'/'.($v['julietOnlyPresent']?'':current($v['accounts']).'.').$cog.($ver ? '_v'.$ver : '').'.php');
			$f=trim($f[1]);
			if(preg_match('/^\/\*/',$f) && preg_match('/\*\//',$f) && strstr($f,'=')){
				$f=trim(str_replace('/*','',str_replace('*/','',$f)));
				$f=explode(';',$f);
				foreach($f as $o=>$w){
					if(!trim($w))continue;
					$x=explode('=',trim($w));
					$components[$cog]['attributes'][strtolower(trim($x[0]))]=trim($x[1]);
					if(strtolower(trim($x[0]))=='name')$components[$cog]['label']=trim($x[1]);
				}
			}else{
				if($requireDocumentation){
					unset($components[$cog]);
					continue;
				}
				$components[$cog]['attributes']=array(
					'name'=>$cog.' (no description)',
				);
			}
		}
		if(!$components)return;
		$components=subkey_sort($components,'label');
		foreach($components as $n=>$v){
			$location=(
				($v['julietOnlyPresent'] ? '' : ($v['this_account'] ? $v['this_account'] : current($v['accounts'])) .'.').
				 $v['name'].
				($v['highestVersion']?'_v'.$v['highestVersion']:'')
			);
			$pJprocess_folder['locations'][$folder][strtolower(preg_replace('/\.php$/','',$location))]=true;
			$pJprocess_folder['output']['$'.$folder.':'.$location]=$v['label'].(count($v['versions'])>1?' (version '.($v['highestVersion']/100).')':'');
			@krsort($v['assets']);
			foreach($v['assets'] as $o=>$w){
				if($o==$v['highestVersion'])continue;
				//show account versions later
				if(preg_match("/^$regexAccounts\./i",$w['name']))continue;
				$w['name']=preg_replace('/\.php$/','',$w['name']);
				if($pJprocess_folder['locations'][$folder][strtolower($w['name'])])continue;
				$pJprocess_folder['locations'][$folder][strtolower($w['name'])]=true;
				$pJprocess_folder['output']['$'.$folder.':'.$w['name']]='   (version '.($o/100).')';
			}
			foreach($v['assets'] as $o=>$w){
				if(!preg_match("/^($regexAccounts)\./i",$w['name'],$m))continue;
				$w['name']=preg_replace('/\.php$/','',$w['name']);
				if($pJprocess_folder['locations'][$folder][strtolower($w['name'])])continue;
				$pJprocess_folder['locations'][$folder][strtolower($w['name'])]=true;
				$pJprocess_folder['output']['$'.$folder.':'.$w['name']]='   (account: '.$m[1].')';
			}
		}
	}
	#prn($components);
}
?>