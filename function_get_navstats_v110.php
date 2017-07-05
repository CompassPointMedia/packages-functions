<?php
$functionVersions['get_navstats']=1.10;
function get_navstats($count=0, $position=1, $batch='', $batches=1){
	/* 2010-03-07: added 4th param to provide nextGroupIndex and prev */
	//position is "absolute" position
	global $defaultBatch;
	if(!$batch)$batch=($defaultBatch ? $defaultBatch : 20);
	$thisBatch=($count-$position+1>$batch?$batch:$count-$position+1);
	if($position+$thisBatch-1<$count){
		$nextIndex=$position+$thisBatch;
		$nextBatch=($count-$nextIndex+1>$batch?$batch:$count-$nextIndex+1);
		if($nextIndex + $nextBatch -1<$count){
			$highestIndex = ($batch==0 ? 0 : $nextIndex+ floor(($count-$nextIndex)/$batch)*$batch);
			$highestBatch = ($count - $highestIndex+1);
		}
	}
	if($position-$batch>0){
		$prevIndex=$position-$batch;
		$prevBatch=$batch;
	}elseif($position<=$batch && $position>1){
		$prevIndex=1;
		$prevBatch=$position-1;
	}
	if($prevIndex>1){
		$lowestIndex=1;
		$lowestBatch = $prevIndex-(floor($prevIndex/$batch)*$batch)-1;
		$lowestBatch == 0?$lowestBatch=$batch:'';
	}
	if($batches==1){
		$nextIndex?$b['nextGroupIndex']=$nextIndex:'';
		$nextBatch?$b['nextGroupBatch']=$nextBatch:'';
	}else{
		$afterRangeIndex=$position + ($batches * $batch);
		if($afterRangeIndex<=$count){
			$b['nextGroupIndex']=$afterRangeIndex;
			$b['nextGroupBatch']=min($count - $afterRangeIndex + 1, $batch);
		}else{
			//no nextGroupIndex
		}
	}
	$b['count']=$count;
	$b['batch']=$batch;
	$b['thisIndex']=$position;
	$b['thisBatch']=$thisBatch;
	$nextIndex?$b['nextIndex']=$nextIndex:'';
	$nextBatch?$b['nextBatch']=$nextBatch:'';
	$prevIndex?$b['prevIndex']=$prevIndex:'';
	$prevBatch?$b['prevBatch']=$prevBatch:'';
	//added 2010-03-07
	$prevIndex?$b['prevGroupIndex']=$prevIndex:'';
	$prevBatch?$b['prevGroupBatch']=$prevBatch:'';

	$highestIndex?$b['highestIndex']=$highestIndex:'';
	$highestBatch?$b['highestBatch']=$highestBatch:'';
	$lowestIndex?$b['lowestIndex']=$lowestIndex:'';
	$lowestBatch?$b['lowestBatch']=$lowestBatch:'';
	return $b;
}
?>