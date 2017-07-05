<?php
$functionVersions['imgmanager']=1.0;
function imgmanager_vignette($options=array()){
	/* 
	example use:
	vignette(
		array(
			'ID'=>1,
			'width'=>250,
			'height'=>150,
			'marginLeft'=>50,
			'marginRight'=>50,
			'marginTop'=>50,
			'marginBottom'=>50,
			'leftOffset'=>300,
			'topOffset'=>300,
		)
	);
	and the HTML code would look like this:
	<div id="vignette_1"><img src="grid.jpg" alt="image"></div>
	*/
	/*
	//OK, this moved the image over and it would go right off the left end of the viewport..
	.vignette img {
		position: absolute;
		clip: rect(0 100px 100px 0px);
		left: -15px;
		}
	
	//then this worked! so if I want w,h,l, and t where l=left offset and t=top offset with optional bt,br,bb, and bl (border top,right,bottom,left)
	
	 rect(t,r,b,l), actually rect(
	
	*/

	extract($options);
	$nums=array( 'width','marginLeft','marginRight','height','marginTop','marginBottom','leftOffset','topOffset' );
	if(!$objectFloat)$objectFloat='left';
	if(!$objectMargin)$objectMargin='0';
	if(!$objectBorder)$objectBorder='1px solid #777';
	foreach($nums as $v)(!$$v ? $$v=0 : false);
	ob_start();
	if($cssTagWrap){ ?><style type="text/css"><?php }
	?>
	.vignette {
		float: <?php echo $objectFloat?>;
		margin: <?php echo $objectMargin?>px;
		position: relative;
		width: <?php echo $width + $marginLeft + $marginRight?>px;	/* width + marginLeft + marginRight */
		height: <?php echo $height + $marginTop + $marginBottom?>px; /* height + marginTop + marginBottom */
		border: <?php echo $objectBorder?>;
		}
	.vignette img {
		position: absolute;
		clip: rect(
			<?php echo $topOffset?>px	/* topOffset */
			<?php echo $leftOffset + $width?>px 	/* leftOffset + width */
			<?php echo $topOffset + $height?>px	/* topOffset + height */
			<?php echo $leftOffset?>px	/* leftOffset */
		);
		top: <?php echo $marginTop-$topOffset?>px;	/* -topOffset + marginTop */
		left: <?php echo $marginLeft-$leftOffset?>px;	/* -leftOffset + marginLeft */
		}
	<?php
	if($cssTagWrap){ ?></style><?php }
	$out=ob_get_contents();
	ob_end_clean();
	if($ID) $out=str_replace('.vignette','#vignette_'.$ID,$out);
	echo $out;
}
?>