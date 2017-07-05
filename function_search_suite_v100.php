<?php
$functionVersions['search_compare_strings']=1.00;
function search_compare_strings($needle, $haystack, $mode=''){
	global $developerEmail,$fromHdrBugs;
	/*
	2008-09-27 - by Samuel
	----------------------
	posted this on phpbuilder.com - http://phpbuilder.com/board/showthread.php?p=10887849#post10887849
	so I have this idea, a search phrase like "candy apple red" against another string of equal or more words (if lesser, we do the search comparision backwards).

	So, I put my finger on the word candy in candy apple red on the left side, and then start going through my comparison string:
	
	"I want an apple and my red candy is hot",
	
	let's say. I hit I, want, an, apple, .. , then CANDY - so I found one word. I now move my finger to APPLE on the left, and "is" on the right, then move to hot - now, I "flip" or roll back to the first entry on the right - I've had one flip. now I put my finger on I, want, an, APPLE - so I found two words. I then move to RED on the left, and on the right move my finger to and, my, RED ..
so we found 3 words and 1 flip. The more flips (rollovers), the more out of order we are, and the more words we find the better the match. You geniuses can do what you want with it - long as you share it with me but is my code flawed in any way.

	The parameter that would be good to add is closeness or proximity - less number of words between matches, the tighter the cluster and we can evaluate that too.
	
	This particular function does not evaluate the results but just provides the results.
	
	*/
	if(!$mode /* early version */){
		return (strtolower($needle)==strtolower($haystack) ? 1 : 0);
	}else if($mode=='tight phrases'){
		/* "tight" means one space between, a small phrase of words
		
		Apples and Oranges
		------------------
		apples:
		all of the words in sub are in super 
		some of the words in sub are in super
		
		oranges:
		------------------
		the words are in exact sequence
		the words are in less than exact sequence (need a ranking - 312 on 123 might be better than 213 on 123)
		the words have more or less spacing in their sequence (for tight strings this should be less significant)
		
		and let''s not forget bananas:
		-----------------------------
		plural is accounted for
		case is accounted for (if "fe" was a common English word then Santa Fe might be different than Santa (Claus) fe)
		
		*/
		//we are not looking for repeated instances, just a percentage match of one instance
		if(!$needle || !$haystack) return 0;
		if($needle==$haystack) return 1;
		if(strtolower($needle)==strtolower($haystack))return .95;
		$needle=explode(' ',$needle);
		$haystack=explode(' ',$haystack);
		$sub=(count($haystack)>count($needle) ? $needle : $haystack);
		$super=($sub==$needle ? $haystack : $needle);
		$supercount=count($super);
		while(list(,$a) = each($sub)){
			$fs++;
			if($fs > 200){
				echo 'fail1<br />';
				return false;
			}
			#echo 'loop level 1<br>';
			#echo 'each word[1] = '.$a . '<br>';
			while(list(,$b) = each($super)){
				$fs++;
				if($fs > 200){
					#echo 'fail2<br />';
					return false;
				}
				$j++;
				#echo 'loop level 2, super = '.$b . '<br>';
				if(isset($lastIdx) && $j==$lastIdx){
					//couldn't find the word, move to next word
					#echo 'cannot find the word<br>';
					if(!(list(,$a)=each($sub))) break; //get the next word from sub
					#echo 'each word[2] = '.$a . '<br>';
				}
				//case mismatch not currently considered
				if(strtolower($a)==strtolower($b)){
					$wordMatch++;
					#echo 'found the word '.$a . '<br>';
					$lastIdx=$j;
					//get the next word
					#echo '.<br>';
					if(!(list(,$a)=each($sub))) break;
					#echo 'each word[3] = '.$a . '<br>';
				}
				//continue to roll around the super list
				if(current($super)===false){
					#echo 'resetting<br>';
					$j=0;
					$flips++;
					reset($super);
				}
			}
		}
		//stats
		#echo 'flips: ' . $flips.'<br />';
		#echo 'words: ' . $wordMatch.'<br />';
		/*2008-09-28 - this is a crude scoring method not yet proven */
		if(!$wordMatch){
			return 0;
		}else{
			$outOfOrder = $flips/$wordMatch;
			if($outOfOrder>1){
				mail($developerEmail,'flips/wordMatch > 1', get_globals(), $fromHdrBugs);
				$outOfOrder=1;
			}
			$order=1-$outOfOrder;
			$orderWeight = .5 * $order + .5; //y = mx + b
			#echo 'returning '. (($wordMatch / count($needle)) * $orderWeight).'<br />';
			return (($wordMatch / count($needle)) * $orderWeight);
		}
	}
}
/* ----- example ------
$a='istocome was cool is';
$b='I am he who is and was and istocome';
search_compare_strings($a,$b,'tight phrases');
*/



function search_is_plural($n){

}

function search_precedence($needle, $haystack){
	#echo 'needle = '.$needle.', haystack=<strong>'.$haystack.'</strong><br />';
	/*
	2008-09-27 - evaluates the precedence of a specific term, accounting for the plural, in a comma separated list.  Max possible value is 1.0
	earlier the term appears = higher the proportional value - see my paper workup on WEIGHTING KEYWORDS	
	
	*/
	//get a clean needle
	$needle=trim(preg_replace('/\s+/',' ',$needle));
	//get a clean haystack
	$a=explode(',',$haystack);
	if(!count($a))return 0;
	//clean up terms in a, clear exact repeats
	foreach($a as $n=>$v) $a[$n]=trim(preg_replace('/\s+/',' ',$v));
	foreach($a as $n=>$v){
		if(!$a[$n])continue;
		if($b[strtolower($a[$n])])$a[$n]=''; //set value to blank, this is a repeat and penalizes the set
		$b[strtolower($a[$n])]=1;
	}
	//get constants
	if(implode('',$a)=='')return 0;
	$c=count($a);
	foreach($a as $n=>$v){
		$i++;
		$wmax= (2 * $c - 2 * $i + 2) / ($c * $c + $c);
		$out = search_compare_strings($needle,$a[$n],'tight phrases');
		#echo '<hr />comparing '. $needle . ' vs. '.$a[$n].', out is: '.$out . '<br />';
		#echo 'out is '.$out . '<br />';
		$w += $out * $wmax;
	}
	return $w;
}

?>