<?php
$functionVersions['tabs_enhanced']=3.0;
function tabs_enhanced($tabs,$options=array()){
	/* 
	2013-03-03: added jquery fadeIn/fadeOut, BUT with folliwng shortcomings
		* code is not abstracted and wouldn't work for multiple objects
		* 
	2012-06-07: new tabs interface, improved from tabs 2.0 
	
	tabon/off
	options
		aColor
		aHoverColor
	*/
	extract($options);
	if(!$tabGroup)$tabGroup='default';
	if(!$location)$location='top';
	if(!$bgColor)$bgColor='#fff';
	if(!$brdColor)$brdColor='#444';
	foreach($tabs as $n=>$v)if(is_null($v))unset($tabs[$n]);
	
	global $tabOutput;
	if(!isset($fade)){
		global $tabFade;
		$fade=$tabFade;
	}
	ob_end_clean(); //this is to clear out the last ob_start() called in get_contents_tabsection()
	
	//declare the tabs output 
	foreach($tabs as $n=>$v){
		if($n==$_COOKIE['tenhanced_'.$tabGroup])$selectedLayer=$n;
	}
	?>
	<style type="text/css">
	<?php if($location=='top'){ ?>
	#tabWrap{
		position:relative;
		margin-top:35px;
		}
	#tabWrap a:hover{
		text-decoration:none;
		<?php echo $aHoverColor?'color:'.$aHoverColor.';':'';?>
		}
	.tabon, .taboff{
		float:left;
		margin-right:5px;
		background-color:<?php echo $bgColor;?>;
		border-left:1px solid <?php echo $brdColor?>;
		border-right:1px solid <?php echo $brdColor?>;
		border-top:1px solid <?php echo $brdColor?>;
		-moz-border-radius: 4px 4px 0px 0px;
		border-radius: 4px 4px 0px 0px;
		cursor:pointer;
		}
	.tabon{
		padding:3px 5px 8px 5px;
		margin-top:5px;
		border-bottom:1px solid white;
		}
	.taboff{
		padding:3px 5px;
		margin-top:10px;
		}
	.lowerline{
		border-top:1px solid <?php echo $brdColor?>;
		clear:both;
		margin-top:-1px;
		background-color:#99CCFF;
		}
	.tabRaise{
		position:absolute;
		top:-33px;
		left:15px;
		}
	.tabSectionStyleIII{
		padding:15px;
		border-left:1px solid <?php echo $brdColor;?>;
		border-right:1px solid <?php echo $brdColor;?>;
		border-bottom:1px solid <?php echo $brdColor;?>;
		margin-bottom:10px;
		min-height:250px;
		}
	<?php }else if($location=='bottom'){ ?>
	#tabWrap{
		position:relative;
		margin-top:35px;
		}
	#tabWrap a:hover{
		text-decoration:none;
		<?php echo $aHoverColor?'color:'.$aHoverColor.';':'';?>
		}
	.tabon, .taboff{
		float:left;
		margin-right:5px;
		background-color:<?php echo $bgColor;?>;
		border-left:1px solid <?php echo $brdColor?>;
		border-right:1px solid <?php echo $brdColor?>;
		border-bottom:1px solid <?php echo $brdColor?>;
		-moz-border-radius: 0px 0px 4px 4px;
		border-radius: 0px 0px 4px 4px;
		cursor:pointer;
		}
	.tabon a, .taboff a{
		<?php echo $aColor?'color:'.$aColor.';':'';?>
		}
	.tabon{
		padding:8px 5px 3px 5px;
		margin-bottom:5px;
		border-top:1px solid <?php echo $bgColor;?>;
		}
	.taboff{
		padding:3px 5px;
		margin-bottom:10px;
		border-top:1px solid <?php echo $brdColor?>;
		}
	.lowerline{
		border-top:1px solid <?php echo $brdColor?>;
		clear:both;
		margin-top:-1px;
		background-color:#99CCFF;
		}
	.tabRaise{
		position:absolute;
		bottom:-33px;
		left:15px;
		}
	.xtabSectionStyleIII{
		padding:15px;
		border-left:1px solid <?php echo $brdColor?>;
		border-right:1px solid <?php echo $brdColor?>;
		border-bottom:1px solid <?php echo $brdColor?>;
		margin-bottom:10px;
		min-height:250px;
		}
	<?php } ?>
	</style>
	<script language="javascript" type="text/javascript">
	<?php if($fade){ ?>
	$(document).ready(function(){
		$('.tabRaise a').click(function(){
			if($(this).hasClass('current'))return false;
			if($('#overallWrap').find(":animated").length>0)return false;
			var newList=$(this).attr('href').substring(1);
			//get id of current layer
			var currentList=$('.tabRaise .current').attr('href').substring(1);
			//fade out current layer
			$('#'+currentList).fadeOut(200, function(){
				//set cookie early in case of failure
				sCookie('tenhanced_<?php echo $tabGroup;?>',newList);
				//fade in clicked layer
				$('#'+newList).fadeIn(200, function(){
					// Remove highlighting - Add to just-clicked tab
					$('#tab_'+currentList).removeClass('tabon');
					$('#tab_'+currentList).addClass('taboff');
					$('#tab_'+newList).addClass('tabon');
					$('#tab_'+newList).removeClass('taboff');
	
					$('#tab_'+currentList+' a').removeClass('current');
					$('#tab_'+newList+' a').addClass('current');
					<?php if($status_field){ ?>
					try{
					g('tenhanced_<?php echo $tabGroup;?>').value=newList;
					}catch(e){}
					<?php } ?>
				});
			});
			return false;
		});
	});
	<?php }else{ ?>
	tabGroup='<?php echo $tabGroup;?>';
	var tabSections={<?php
	$i=0;
	foreach($tabs as $n=>$v){
		$i++;
		echo ($i>1?', ':'').'\''.$n.'\':\''.$n.'\'';
	}
	?>};
	function tabon(o,r){
		if(o.className=='tabon')return false;
		for(var i in tabSections){
			g('tab_'+tabSections[i]).className='taboff';
			g(tabSections[i]).style.display='none';
		}
		sCookie('tenhanced_'+tabGroup,o.id.replace('tab_',''));
		<?php if($status_field){ ?>
		try{
		g('tenhanced_'+tabGroup).value=o.id.replace('tab_','');
		}catch(e){ alert(e); }
		<?php } ?>
		o.className='tabon';
		g(o.id.replace('tab_','')).style.display='block';
		if(typeof r!=='undefined')return false;
		return false;
	}
	<?php } ?>
	</script>
	<?php 
	ob_start();
	?>
	<div id="tabWrap">
		<div class="lowerline"> </div>
		<div class="tabRaise">
			<?php
			$i=0;
			$tabon='return tabon(this.parentNode);';
			foreach($tabs as $n=>$v){
				$i++;
				if($i==1)$defaultLayer=$n;
				?>
				<div id="tab_<?php echo $n?>" class="<?php 
				echo $class=($n==$selectedLayer || ($i==1 && !$selectedLayer)
				?
				'tabon'
				:
				'taboff');
				if($fade){
					$onclick=$v['jsbefore']?rtrim($v['jsbefore'],';').';':($v['jsreplace']?$v['jsreplace']:'');
				}else{
					$onclick=$v['jsbefore']?rtrim($v['jsbefore'],';').';'.$tabon:($v['jsreplace']?$v['jsreplace']:$tabon);
				}
				if(trim($onclick))$onclick='onclick="'.$onclick.'"';
				?>"><a href="#<?php echo $n;?>" <?php echo $onclick;?> <?php if($class=='tabon'){ ?> class="current" <?php } ?>><?php echo $v['label'];?></a></div>
				<?php
			}
			?>
		</div><?php
		if($status_field){
			?><input type="hidden" name="<?php echo 'tenhanced_'.$tabGroup;?>" id="<?php echo 'tenhanced_'.$tabGroup;?>" value="<?php echo $selectedLayer ? $selectedLayer : $defaultLayer;?>" /><?php echo "\n";
		}
		?>
	</div><?php
	$taboutput=ob_get_contents();
	ob_end_clean();
	ob_start();
	?><div id="layerWrap"><?php
	//first see if a tab is selected
	$i=0;
	foreach($tabs as $n=>$v){
		$i++;
		echo "\n";
		?><div id="<?php echo $n;?>" class="tabSectionStyleIII" style="display:<?php echo $n==$selectedLayer || ($i==1 && !$selectedLayer)?'block':'none'?>;"><?php 
		echo "\n";
		echo $tabOutput['generic']['tabSet'][$n];
		echo "\n";
		?></div><?php
		echo "\n";
	}
	?></div><?php
	$bodyoutput=ob_get_contents();
	ob_end_clean();
	?><div id="overallWrap"><?php
	if($location=='bottom'){
		echo $bodyoutput . "\n". $taboutput;
	}else{
		echo $taboutput . "\n". $bodyoutput;	
	}
	?></div><?php
}

?>