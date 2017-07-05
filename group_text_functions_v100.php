<?php
$functionVersions['text_functions']=1.00;
function text_functions(){
	//placeholder
	return true;
}

$functionVersions['text_highlight']=1.00;
function text_highlight($needles, $haystack, $options=array()){
	/*
	created 2009-11-11 by Samuel
	- prep_function could be strip_tags or a custom function
	- expandCount (default 5) = how many words before and after a highlighted word
	
	bolded words are set in <strong class="text_highlight">...</strong>
	*/
	extract($options);
	if(!$expandCount)$expandCount=5;
	if(!$interestingThreshold)$interestingThreshold=5;

	if(!is_array($needles))$needles=array($needles);
	$haystack=preg_split('/\s+/', $prep_function ? $prep_function($haystack) : $haystack);
	//now recurse the haystack and get word-match indexes
	for($i=0; $i<count($haystack); $i++){
		for($j=0; $j<count($needles); $j++){
			//find out if we want the word to be searched on as a partial - this logic will grow but it's basically, "if exact match, OR if interesting and starts like or ends like [or really interesting and middle-like]"
			$interesting=(
				strlen($needles[$j])>=$interestingThreshold || strtolower($needles[$j])<>$needles[$j] ? 
				true : 
				false
			);
			if(
				substr( strtolower(preg_replace('/[,.\'"]+/','',$haystack[$i])), 0, strlen($interesting ? $needles[$j] : $haystack[$i]))
				== 
				strtolower(preg_replace('/[,.\'"]+/','',$needles[$j]))
			){
				$k++;
				$positions[$k]=array(
					'position'=>$i,
					'word'=>$needles[$j]
				);
			}
		}
	}
	if($positions){
		for($k=1; $k<=count($positions); $k++){
			//how far to left
			if($positions[$k-1]){
				if($positions[$k-1]['right']>$positions[$k]['position']-$expandCount){
					$positions[$k]['left']=$positions[$k-1]['right']+1;
				}else{
					$positions[$k]['left']=$positions[$k]['position']-$expandCount;
				}
			}else if($k==1){
				if($positions[$k]['position']-$expandCount<0){
					$positions[$k]['left']=0;
				}else{
					$positions[$k]['left']=$positions[$k]['position']-$expandCount;
				}
			}
			//how far to right
			if($k==count($positions)){
				if($positions[$k]['position']+$expandCount>count($haystack)-1){
					$positions[$k]['right']=count($haystack)-1;
				}else{
					$positions[$k]['right']=$positions[$k]['position']+$expandCount;
				}
			}else{
				if($positions[$k]['position']+$expandCount > $positions[$k+1]['position']){
					$positions[$k]['right']=$positions[$k+1]['position']-1;
				}else{
					$positions[$k]['right']=$positions[$k]['position']+$expandCount;
				}
			}
		}
		//now build the text output
		for($k=1; $k<=count($positions); $k++){
			$v=$positions[$k];
			extract($v);
			//left ellipsis if needed
			if($left<>0 && $positions[$k-1]['right']+1<$left)$str.= ' ... ';
			
			for($m=$left; $m<=$right; $m++){
				$str.=' ';
				if($m==$position)$str.='<strong class="text_highlight">';
				$str.=$haystack[$m];
				if($m==$position)$str.='</strong>';
			}
			
			//right ellipsis if needed
			if($k==count($positions) && $right<count($haystack))$str.=' ... ';
			
		}
		/*
		?><div style="border:1px solid #ccc;padding:25px;margin-bottom:5px;"><?php echo implode(' ',$haystack);?></div><?php
		?><div style="border:1px solid #ccc;padding:25px;"><?php echo ($str);?></div><?php
		echo '<pre>';
		print_r($positions);
		*/
		return $str;
	}
}
function text_truncate($text, $length, $options=array()){
	/* created 2010-01-19 by Samuel - truncates text to either a number of words or a width in pixels eventually based on font size and type
	options
		strip_tags=>p,a,i,b (exceptions, separated by commas)<br />

	*/
	extract($options);
	if(!$lengthMode)$lengthMode='words'; //pixels, characters
	if(!isset($retainAnchors))$retainAnchors=true;
	if(!isset($useEllipsis))$useEllipsis=true;
	if(!isset($showFullTitle))$showFullTitle=true;
	
	//show content removed of images
	if(isset($strip_tags)){
		if($strip_tags==1 || is_bool($strip_tags)){
			$exceptions='<nosuchtag>';
		}else if($strip_tags=='default'){
			$exceptions='<p><strong><em><u><i>'.($retainAnchors ? '<a>' : '');
		}else{
			$a=explode(',',$x);
			foreach($a as $v)if(trim($v))$exceptions[]='<'.trim($v).'>';
			$exceptions=implode('',$exceptions);
		}
		$text=strip_tags($text,$exceptions);	
	}
	
	if($lengthMode=='words'){
		$text=(strlen($text)<2000 ? preg_split('/[ ]+/',$text) : explode(' ',$text));
		$j=-1;
		while(true){
			//don't end inside an <a>..
			$j++;
			if(!isset($text[$j]))break;
			$inA=( ($inA || preg_match('/^<a/i',$text[$j]) ) && !preg_match('/<\/a>/i',$text[$j]) 
			? true : false);
			$str.= $text[$j] .' ';
			
			if($j>$length && !$inA && $useEllipsis){
				if(!$omitEllipsisMarkup)$str.= '<span class="ellipsis">';
				$str.= '...';
				if(!$omitEllipsisMarkup)$str.= '</span>';
				break;
			}
		}
		return $str;
	}else if($lengthMode=='letters'){
		if(strlen($text)<=$length){
			return $text;
		}
		if(!$munch)$munch='middle';
		if($munch=='middle'){
			$first=floor(strlen($text)/2);
			$last=substr($text,$first,strlen($text));
			$first=substr($text,0,$first);
			while(true){
				$i++;
				!fmod($i,2) ? $first=substr($first,0,strlen($first)-1) : $last=substr($last,1,strlen($last));
				if(strlen($first . '..'.$last)<=$length){
					if($showFullTitle)$str.= '<span title="'.h($text).'">';
					$str.= $first.'..'.$last;
					if($showFullTitle)$str.= '</span>';
					return $str;
				}
			}
		}else{
			return substr($text,0,$length).'..';
		}
	}
}
function convert_smart_quotes($string) 
{ 
	$search = array(chr(145), 
					chr(146), 
					chr(147), 
					chr(148), 
					chr(151)); 
 
	$replace = array("'", 
					 "'", 
					 '"', 
					 '"', 
					 '-'); 
 
	return str_replace($search, $replace, $string); 
} 


?>