<?php
$functionVersions['banner']=1.00;
function banner($region,$page=''){
	global $thispage, $thisfolder, $defaultBannerAd, $adminEmail, $fromHdrBugs;
	if(!$page)$page=$thispage;
	$page=strtolower($page);
	$region=strtolower($region);
	$regions=array('upperleft','upperright','lowerleft','lowerright');
	
	if($a=q("SELECT * FROM kacc_banners WHERE ThisPage='".(in_array($region,$regions)?'all':strtolower($thispage))."' AND Region='$region' AND Active=1 ORDER BY RenewDate DESC", O_ARRAY)){
		if(count($a)>1){
			//mail of conflict
		}else{
			$a=$a[1];
		}
		if(strlen($a['FileName']) && file_exists(($thisfolder=='cgi'?'../':'').'images/banners/'.$a['FileName'])){
			?><a title="Click here to learn more" href="m.php?Banners_ID=<?php echo $a['ID']?>" <?php if($a['Popup']=='1'){ echo'target="_blank"'; }?>><img src="images/banners/<?php echo $a['FileName']?>" alt="banner ad" /></a><?php
		}else{
			//mail of error
			mail($adminEmail,'Missing image for banner ad','In region "'.$region.'", the specified banner ad file "images/banners/'.$a['FileName'].'" is not present.  Please sign into the control panel and either 1)upload the image or 2)make sure the file name specified for this region is correct',$fromHdrBugs);
			?><a title="Buy this ad slot" href="/BannerAdvertisement.php?thispage=<?php echo $page?>&region=<?php echo $region?>"><?php
			if(file_exists($defaultBannerAd)){
				$size=getimagesize($defaultBannerAd);
				?><img src="<?php echo $defaultBannerAd?>" <?php echo $size[2]?> border="0" alt="banner ad" /><?php
			}else{
				//email of no default image found
				mail($adminEmail,'Missing default image for banner ad','The default banner ad file "images/banners/'.$defaultBannerAd.'" is not present.  Please sign into the control panel and either upload the default image',$fromHdrBugs);
				?>Purchase this ad slot!<?php
			}
			?></a><?php
		}
	}else{
		?><a title="Buy this ad slot" href="/BannerAdvertisement.php?thispage=<?php echo $page?>&region=<?php echo $region?>"><?php
		if(file_exists($defaultBannerAd)){
			$size=getimagesize($defaultBannerAd);
			?><img src="<?php echo $defaultBannerAd?>" <?php echo $size[2]?> border="0" alt="banner ad" /><?php
		}else{
			//email of no default image found
			mail($adminEmail,'Missing default image for banner ad','The default banner ad file "images/banners/'.$defaultBannerAd.'" is not present.  Please sign into the control panel and either upload the default image',$fromHdrBugs);
			?>Purchase this ad slot!<?php
		}
		?></a><?php
	}
}
?>