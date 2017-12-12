<?php
function write_menu($options=array()){
    /*
    2017-12-12 - this function was also in gf5/console/css.balloon.php

    Created 2009-11-15 by Samuel - this is similar to a component in that it carries its own js and css, but I don't believe I'd refreshComponent here.


    HOW TO USE THIS FUNCTION:
    pass the $inner content of the balloon
    pass $options
    then call the function with the options

    if you set preReplaceCSS any <style> declaration i the inner content will be removed and placed before the balloon div

    */
	global $refreshComponent;


	extract($options);
	if(!$menuID)$menuID='definitionTools';

    if(!$objectRegex) error_alert('You must declare the regular expression for the object(s) you wish to bind this context menu to');
    if(!$alignment) $alignment='mouse,20,-40';
    if(!strlen($inner)) exit('value $inner must be declared');
    if(!$refreshComponent){
        ?><style type="text/css">
            /* is this going to work 2 */
            .balloonWrap{
                width:50px;
                position:absolute;
                visibility:hidden;
                left:50px;
                top:50px;
            }
            .dropshadow{
                left:10px; /* offset to left */
                top:24px; /* height of tick mark - 1 to cover border + 10 for offset */
                width:250px;
                height:145px;
                background-color:#333333;
                opacity:.45;
                filter:alpha(opacity=45);
                position:absolute;
                z-index:499;
            }
            .indices{
                position:absolute;
                z-index:501;
                background-image:url('/images/i/arrows/indices-style01-up.png');
                background-position:top left;
                background-repeat:no-repeat;
                top:0px;
                right:0px;
                width:17px; /* actual size of the tick mark */
                height:15px;
            }
            .balloonContent{
                background-color:white;
                border:1px solid sienna;
                width:250px;
                height:145px;
                position:absolute;
                z-index:500;
                top:14px; /* height of tick mark minus 1 */
            }
            .balloonContent .spd{
                padding:5px 10px;
            }
            #balloonKill{
                float:right;
                text-align:center;
                width:15px;
                height:15px;
                color:white;
                background-color:darkred;
                font-size:10px;
                margin:1px;
                cursor:pointer;
            }
        </style>
        <?php
        if($preReplaceCSS && preg_match('/<style[^>]*>(.|\s)+<\/style>/i',$inner,$a)){
            $inner=str_replace($a[0],'',$inner);
            //show stylesheet
            echo $a[0];
        }
        ?>
        <script language="javascript" type="text/javascript">
			try{
				AssignMenu('<?php echo $objectRegex?$objectRegex:'none'?>','<?php echo $menuID?>', '<?php echo $alignment?>');
			}catch(e){ }
        </script><?php
    }
    ?>
    <div id="<?php echo $menuID;?>" class="balloonWrap" precalculated="<?php echo $precalculated?>">
        <div id="dropshadow1" class="dropshadow"> </div>
        <div id="balloonContent1" class="balloonContent">
            <div id="balloonKill" onclick="hidemenuie5(event);hidemenuie5(event)">X</div>
            <div id="<?php echo $menuID?>_content" class="spd">
                <?php echo $inner;?>
            </div>
        </div>
        <div class="indices"> </div>
    </div><?php
}
