<?php
$functionVersions['mm']=1.0;
function mm($options=array()){
	/*
	2012-12-14: this is kind of one of the "holy grails" that I have been needing; how to push subsections of a well-developed (and established) component up to the parent minimally invasively.  There are two requirements on the coding that is written:
		1. each section is presumed to continue until the next mincemeat call.  Wrappers will grab anything up to that point.
		2. each section must be written to not rely on the coding of any section not called prior to the sequence
		3. unfortunately due to goto limitations, that cumbersome switch/case block must define all potential sections each time
	this involves calling the function before any section is declared:
	mincement is called with an array as follows:
	mm(array(
		'sections'=>array(
			'section7'=>array(
				method=>innerhtml /only one developed/, 
				target=>'searches',
				[source=>(default same as parent || name a div in the section),]
				wrap=>(default false, if true the wrap div will be used as source node regardless of source) 
			), /undefined as yet/
			'section1'=>array(), /note out-of-sequence ok/
			'section3'=>array(),
		)
	));
	
	-------------------------------------------------------------
	then setting namespaces/markers as:
	
	
	$m=mm();
	switch(true){
		case $m=='section1': goto section1;
		case $m=='section2': goto section2;
		case $m=='compbypass': goto compbypass;
	}
	section1:
	
	(code block)
	
	$m=mm();
	switch(true){
		case $m=='section1': goto section1;
		case $m=='section2': goto section2;
		case $m=='compbypass': goto compbypass;
	}
	section2:
	
	(code block)
	
	mm('end');
	compbypass: //the standard end name
	
	
	It will then by default go directly to the first requested section and begin the mincemeat process, create its own wrappers as needed (buffering to a variable, wrapping in a div, or parsing as JSON, or even emailing or storing the data or a value, or perhaps nothing)
	
	
	options
		sections - main array
		immediate_call - declare the section the same time we 
	todo
		some way to update the options of a select element
	*/
	global $_mm, $qr, $qx, $developerEmail, $fromHdrBugs;
	extract($options);
	if($sections){
		//we are starting
		$_mm['sections']=$sections; //initialize section sequence
		$_mm['keys']=array_keys($sections); //easier for me
		$_mm['idx']=0; //starting index of keys
		
		if($immediate_call){
			$_mm['key']=$_mm['keys'][$_mm['idx']];
			$_mm['section']=$_mm['sections'][$_mm['key']];
		}
		return;
	}
	if(!$_mm['sections']){
		/*
		mail($developerEmail, 'Error in '.$MASTER_USERNAME.':'.end(explode('/',__FILE__)).', line '.__LINE__,get_globals($err='improper call of mincemeat function'),$fromHdrBugs);
		error_alert($err);
		*/
		//for now we just assume we're not in sequence; mm() was never intialized
		return;
	}
	
	if($_mm['key']){
		//do anything with this section before advancing
		$s=$_mm['section'];
		if($s['method']=='basic'){
			
			if($n=$s['call']){
				?><script language="javascript" type="text/javascript"><?php
				echo $n;
				?></script><?php
			}else{
				if(!$s['source'])$s['source']=$_mm['key'];
				if(!$s['target'])$s['target']=$s['source'];
				?><script language="javascript" type="text/javascript">
				try{
				window.parent.g('<?php echo $s['target'];?>').innerHTML=document.getElementById('<?php echo $s['source'];?>').innerHTML;
				}catch(e){ }
				</script><?php
			}
		}
		
		//advance to next key
		if($k=$_mm['keys'][$_mm['idx']+1]){
			$_mm['idx']++;
			$_mm['key']=$k;
			$_mm['section']=$_mm['sections'][$_mm['key']];
			return;
		}
	}else{
		//set first section
		$_mm['key']=$_mm['keys'][$_mm['idx']];
		$_mm['section']=$_mm['sections'][$_mm['key']];
		
		//do any pre-ops with this section
		return;
	}

	//last action
	$_mm['key']='compend';
}

/* 
first and simplest example of mincemeat: just output sections in the given order; no parameters required.
IMPORTANT: each section is defined as the identifier: up until another call to mm() and following goto cases.
ALSO NOTE: each goto case group needs to have all possible identifiers - until PHP enhances the goto operator
	http://www.sitepoint.com/php6-gets-a-comefrom-statement/
*/
if(false)mm(array(
	'sections'=>array(
		'section3'=>array(),
		'section4'=>array(),
		'section1'=>array(),
		'section2'=>array(),
	)
));
/*
next example I need: pop a section into a parent window
*/
if(false)mm(array(
	'sections'=>array(
		'section3'=>array(),
		'section4'=>array(
			'method'=>'basic',
			/*
			source={section_name} by default
			target=source || {section_name} by default
			call=> normal expression is window.parent.g(target).innerHTML=document.getElementById(source).innerHTML;
			
			*/
		),
		'section1'=>array(
		),
		'section2'=>array(),
	)
));

if(false){
	//----------------------- here is a sample component -----------------
	
	#here is code that will always be called or needed
	$name='Sam';
	$intern='Krishna';
	
	
	//call mincemeat prior to naming section:
	mm();
	switch($_mm['key']){
		case 'section1':goto section1;
		case 'section2':goto section2;
		case 'section3':goto section3;
		case 'section4':goto section4;
		case 'compend':goto compend;
	}
	section1:
	
	?><div class="red">hi, this is code block 1</div><?php
	
	//call mincemeat prior to naming section:
	mm();
	switch($_mm['key']){
		case 'section1':goto section1;
		case 'section2':goto section2;
		case 'section3':goto section3;
		case 'section4':goto section4;
		case 'compend':goto compend;
	}
	section2:
	
	?><div class="red">hi, this is section 2</div><?php
	
	//call mincemeat prior to naming section:
	mm();
	switch($_mm['key']){
		case 'section1':goto section1;
		case 'section2':goto section2;
		case 'section3':goto section3;
		case 'section4':goto section4;
		case 'compend':goto compend;
	}
	section3:
	
	?><div class="red">hi, this is section 3</div><?php
	
	//call mincemeat prior to naming section:
	mm();
	switch($_mm['key']){
		case 'section1':goto section1;
		case 'section2':goto section2;
		case 'section3':goto section3;
		case 'section4':goto section4;
		case 'compend':goto compend;
	}
	section4:
	
	?><div class="red">hi, this is section 4</div><?php
	
	//call mincemeat for final closure (default name is compend)
	mm();
	switch($_mm['key']){
		case 'section1':goto section1;
		case 'section2':goto section2;
		case 'section3':goto section3;
		case 'section4':goto section4;
		case 'compend':goto compend;
	}
	compend:
	
	prn('that\'s all folks');
	//---------------------------------------------------------------
}


?>