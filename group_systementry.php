<?php
$defaultProfileSettingsString=trim('<?php 
\'sub_table\'=>array(
	\'active\'=>true, /* eventually this needs to be calculated - depends on parent */
	/* --------------------------------
	things to add:

	NOTES:
	post_processing_component will contain all the error checking and required fields at this point.  But we certainly need client-side error checking also
	ppc must also be stored in a brother folder named components
	foreign_key=[default:auto]
	.columns[ID] - primary key column will be created automatically
	----------------------------------- */
	\'version\'=>1.0,

	\'title\'=>\'Items\',
	\'instructions\'=>\'Enter at least one item for this transaction\',
	\'custom_css\'=>\'.subTable input[type=text]{border-width:1px;}\',
	\'table\'=>\'finan_transactions\', /* string, or array, will be assigned a,b,.. */
	\'exclusion\'=>\'root.Accounts_ID!=a.Accounts_ID\',
	\'post_processing_component\'=>\'comp_ppc_01_lineitems_v100.php\',
	\'fieldset\'=>\'a.ID, a.Items_ID, a.SKU, a.Quantity, a.Description, a.UnitPrice, a.Extension\', /* default a.* */
	\'order_clause\'=>\'a.Idx, a.SKU\',
	\'blank_rows\'=>10,
	\'heading\'=>true, /* headings to table */
	\'columns\'=>array(
		\'Quantity\'=>array(
			\'attributes\'=>array(
				\'size\'=>3,
				\'class\'=>\'tar\',
			),
			\'heading\'=>\'Qty.\',
		),
		\'SKU\'=>array(
			\'attributes\'=>array(
				\'size\'=>10,
			),
		),
		\'Description\'=>array(
			\'attributes\'=>array(
				\'size\'=>22,
				\'class\'=>\'tar\',
			),
		),
		\'UnitPrice\'=>array(
			\'attributes\'=>array(
				\'size\'=>5,
				\'class\'=>\'tar\',
			),
			\'heading\'=>\'Price\',
		),
		\'Extension\'=>array(
			\'attributes\'=>array(
				\'size\'=>5,
				\'class\'=>\'tar\',
			),
			\'heading\'=>\'Ext.\',
		),
	),
),
/* -- this was present already before 2012-12-09; have no reason not to stick with this right now -- */
\'collection\' => array (
	\'array_wrapper\' => \'\',
),
\'columns\' => array (
	\'resourcetype\' => array (
		\'flags\' => array (
			\'type\' => \'none\',
		),
	),
	\'resourcetoken\' => array(\'flags\' =>array ( \'type\' => \'none\',),),
	\'sessionkey\' => array(\'flags\' =>array ( \'type\' => \'none\',),),
	\'exporter\' => array(\'flags\' =>array ( \'type\' => \'none\',),),
	\'exporttime\' => array(\'flags\' =>array ( \'type\' => \'none\',),),
	\'tobeexported\' => array(\'flags\' =>array ( \'type\' => \'none\',),),
	\'Clients_ID\'=>array(
		\'relations_label\'=>\'ClientName\',
	),
	\'Contacts_ID\'=>array(
		\'relations_label\'=>\'CONCAT(LastName,\\\', \\\',FirstName)\',
	),
	\'Accounts_ID\'=>array(
		\'relations_label\'=>\'Name\',
	),
	\'xxx\' => array (
		\'attributes\' => array (),
		\'flags\' => array (
			\'array_wrapper\' => \'\',
			\'build_array\' => false,
			\'type\' => \'none\',
			\'relation\' => array (),
			\'distinct\' => \'\',
			\'counter\' => \'\',
			\'do_not_convert_value\' => false,
		),
	),
),
?>');
ob_start();
?>
<h2>Help</h2>
<ul>
<li><a href="#do_main">Main</a></li>
<li><a href="#do_doparams">Data Object Parameters</a></li>
<li>Settings<br />
	<ul>
	  <li><a href="#do_colors">Colors</a></li>
    </ul>
</li>
<li>&quot;<a href="#do_forthisdothis">for this .. do this</a>&quot; </li>
<li><a href="#do_gotchas">Gotchas</a></li>
<li><a href="#do_technical">Technical</a></li>
<li><a href="#do_featureshistory">Features History</a> </li>
</ul>
<h2><a name="do_main" id="do_main"></a>Data Objects and System Entry</h2>
<p>This system encapsulates code kernels I have developed for the last 12 years, hopefully with good reconciling of each one. It combines the datasets (wich had extensive hard-coded files in the /components folder of my aps) with the focus view (which was a hodgepodge of pretty good concept, but again each one was hard-coded). These are now merged into one unit via an entry in system_profiles, with Type=Data View. This help section explains the profileSettings array which is derived from system_profiles.Settings.</p>
<p>One thing to note is that the raw coding is a node itself in Settings named _raw_. This allows comments and my desired indentation to be preserved. Note that once we go to form control of the actual settings, maintaining this in synch with the form-mods will be a kernel in itself (if I choose to do this). </p>
<h3><a name="do_params" id="do_params"></a>Data Object Parameters </h3>
<p>These all reside in this node:<br />
  'dataobject' =&gt; array(<br />
  	&nbsp;&nbsp;&nbsp;&nbsp;/* settings here, as shown below.. */ <br />
  ), ...</p>
<p><strong>Here are the sub-nodes which are currently controllable:<br />
</strong>'dataset'=&gt;'headers' [string: conceptually what this dataset represents; used in various ways] <br />
'datasetGroup'=&gt;'headers' [string: conceptually the super-dataset to link this dataset to a larger group]<br />
'datasetComponent'=&gt;'headersList', [string: this is the name of the object in HTML output]<br />
The following two parameters, and the next one after, are mutually exclusive:
<br />
'datasetQuery'=&gt;'SELECT * FROM finan_headers WHERE ResourceType IS NOT NULL',<br />
'datasetQueryValidation'=&gt;'88fde8d7bb8d913a62e6408ad797252c',[string: validates query for security, md5 of main password]</p>
<p>-vs-</p>
<p>'datasetTable'=&gt;'_v_finan_invoices_cash_sales' [string: name of a specific table vs. a query]<br />
  'datasetTableIsView'=&gt; true|false [string: !<span class="red">DEPRECATED!</span>, never used]<br />
  'datasetFile'=&gt;'comp_1000_systementry_dataobject_v100.php', [string: name of &quot;this&quot; file; may be deprecated but used in query strings for mode=refreshComponent] <br />
  'datasetFocusViewDeviceFunction'=&gt;'systementry_focus' [string(a-z0-9_): name of javascript focus function; must receive $record and globalize an array of its own name] <br />
  'columns'=&gt; ...[array: keyed case-insensitive names of table fields or otherwise (such as calculated values, control regions, etc.]<br />
  NOTE: there was used, in Simple Fostercare, a device to break a record row into multiple rows with a break command</p>
<pre style="width:100%;">

/*this was added 2013-04-07.  This is when a view contains updatable sections to multiple tables and the field prefixes match up to a section. to-do in this area includes custom transformations and parsing of one field out to two fields, or key translation or more.  REALLY COOL on the list = file uploads so that relatebase_tree is called into use! 
The other issue is when do we update.  Ideally we update live all the time - as with google docs.  Then we need to insert rows (which are new records) and columns (which are generically named fields)*/
'datasetPrimaryKey'=>array('Clients_ID','Contacts_ID'),
'datasetSections'=>array(
	'Clients'=>array(
		'table'=>'finan_clients', /*we could figure this out but it's 11:32 at night*/
		'primary_key'=>'ID', /* ditto */
	),
	'Contacts'=>array(
		'table'=>'addr_contacts',
		'primary_key'=>'ID', /* ditto */
	),
),
'columns'=>array(
	'Contacts_FirstName'=>array(
		'header'=>'First Name',
		<span class="red">'form_field'=>array( <span class="gray">/* this allows a column to be a form field */</span>
			'type'=>'text', <span class="gray">/* text, textarea, select, checkbox, radio, file, and button */</span>
			'attributes'=>array(
				'maxlength'=>'35',
				'size'=>10,
			),</span>
		),
	),
),
</pre>
<h4>  <a name="do_columns" id="do_columns"></a>Column Values List</h4>
<p>&nbsp; </p>
<h3>Sub Tables </h3>
<p>One development (2012-12-12) is the subTable protocol so that I can make an invoice entry form. Here is the protocol for this so far:</p>
<a href="#" class="gray" onclick="g('code1').style.display='block'; this.innerHTML='subTable parameters:'; return false;">(click to view code)</a>
<div id="code1" style="display:none;">
<?php 
highlight_string($defaultProfileSettingsString);
?>
</div>
<hr size="1" noshade="noshade" style="border:1px solid #666;" />
<a href="#" onclick="g('code2').style.display='block'; return false;" style="color:#000;">Click for another concept for the data update of sub tables:</a><br />
<pre id="code2" style="display:none;">
x = unusable passable row; ignore this 
xx = unpassable row; throw an error
Form Post					Table
------------------			----------------- 
= one to one correspondence
  updates take the form of either a "key" (critical) update or a non-key update
  key updates include changes in Accounts_ID or Items_ID 
  or say Hours, Reimbursable, or SubItem RLX

x x x x x x x x x x			----------------- 
= has been removed from form - prevent if there is a
  dependency for example reimb. or timesheets
  includes where user has "blanked out" a row 
  but hidden ID field remains
+ + + + + + + + + +			(not present)	  
= new record

NOTES:
The Idx field will be recalculated each time.  
Idx change is a non-critical change in a record
</pre>

</p>
<a name="do_colors"></a>
<h2>Colors</h2>

<div class="fl" style="width:45%">
<p>this is the Google Docs palette currently:<br /> 
  <img src="/images/i/bg/google_docs_color_palette_2012-12-18.png" width="235" height="333" /><br />
</p>
</div>
<div class="fl" style="width:45%">
there are three colors that the list view uses for the <code>complexData</code> class:</p>
<p> $datasetColorHeader - default is #674ea7<br />
$datasetColorRowAlt - default is #d9d2e9<br />
$datasetColorSorted - default is "wheat"<br />
 
</p>
</div>

<br />
<a name="do_forthisdothis" id="do_forthisdothis"></a>
<table class="data1 cb" cellpadding="0">
  <tr>
    <th width="158" scope="col"><a name="do_forthisdothis" id="do_forthisdothis"></a>For this.. </th>
    <th width="372" scope="col">Do this.. </th>
    <th width="437" scope="col">Comments</th>
  </tr>
  <tr>
    <td>Change columns shown </td>
    <td>update the query in datasetQuery, or create then select a different view and reference it in datasetTable </td>
    <td>As of 2012-12-24 I believe there are less features available to datasetQuery because the properties of the table in the query are not fetched. However, with columns declared you can approximate a view. </td>
  </tr>
  <tr>
    <td>Change the column label (list view) </td>
    <td>dataobject -&gt; columns -&gt; {column_node} -&gt; header =&gt; [string] </td>
    <td>HTML markup OK </td>
  </tr>
  <tr>
    <td>Change the field label (focus view) </td>
    <td>columns -&gt; {column_node} -&gt; label </td>
    <td>HTML markup OK </td>
  </tr>
  <tr>
    <td>Change colors on the table component </td>
    <td><p>dataobject -&gt; datasetColorHeader [#RRGGBB] <br />
        dataobject -&gt; datasetColorRowAlt<br />
    dataobject -&gt;datasetColorSorted</p>    </td>
    <td>overlap shading is calculated automatically - but see DropBox's light interface for file listing for example of use of RGBA() CSS </td>
  </tr>
  <tr>
    <td>Hide columns (list view) </td>
    <td>as with old component files<br />
      dataobject -&gt; columns -&gt; visibility -&gt; COL_HIDDEN|COL_AVAILABLE </td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td>Hide columns (focus view) </td>
    <td>columns -&gt; {column_node} -&gt; flags -&gt; type -&gt; 'none' </td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td>Set default value (focus view) </td>
    <td>columns -&gt; {column_node} -&gt; default </td>
    <td>you can use 'php::lib_nextHeaderNumber()' for example and call a library function if it is present </td>
  </tr>
  <tr>
    <td>Handle resets for Save and New </td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td>Filter Gadget (search) options (list view) </td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td>Manage Active/Inactive Settings (list view) </td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td>Add custom coding (focus view) </td>
    <td>HTML_inserts -&gt; {recognized_node} </td>
    <td>HTML OK. No system variables or PHP code parsed currently. As of 12/25 the only node recognized is before_table </td>
  </tr>
</table>
<h3><a name="do_main" id="do_main"></a>Gotchas</h3>
<p>These are things to be aware of when working to layout data:<br />
</p>
<table class="yat cb" cellpadding="0">
  <tr>
    <th scope="col">Item</th>
    <th scope="col">Description</th>
  </tr>
  <tr>
    <td>1</td>
    <td>When switching from a datasetTable to another one, or from a datasetTable to a datasetQuery, note that if columns are declared, then the new query, view or table may not supply output for these columns to operate - so they may show up blank. </td>
  </tr>
  <tr>
    <td>2</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td>3</td>
    <td>&nbsp;</td>
  </tr>
</table>
<h3><a name="do_technical" id="do_technical"></a>Technical</h3>
<p>system_tables table: this has no real use currently (12/25/2012) in systementry except as the Tables_ID value and the table name for dataobject and etc.. tableSettings is not used.</p>
<p>system_profiles fields:</p>
<ul>
  <li>Type=Data View - this distinguishes this profile from other types of profiles such as Export and Import profiles which were previously in use wtih HMR</li>
  <li>Identifier=default - well, that's the default value :)</li>
  <li>Settings - of course what you are reading here - it is all stored in the Settings field</li>
  <li>Category, Name, and Description - think of these as a pyramid with Category the apex and Description the base. Used on dropdown lists to select profiles, and as help bubbles for the view itself.</li>
  <li>Version - currently 1.0. To jump to 1.x or 2.x, the Settings array would be reworked for the new interpreter, and we'd need to resort to include files based on the version. <br />
  </li>
</ul>
<h3><a name="do_featureshistory" id="do_featureshistory"></a>Features History</h3>
<p>&nbsp; </p>
<table cellpadding="0">
  <tr>
    <th scope="col">Date</th>
    <th scope="col">Description</th>
  </tr>
  <tr>
    <td valign="top">4/8/2013</td>
    <td><p>Worked on making the list view function as a bulk update form generically, success. Also there is a concept I developed of a join between two or more tables creating a view, with each [or nearly each] field name having a prefix and _ before, mapping it to the respective table. It so works out that if the Clients.ID field is renamed to Clients_ID, and the Contacts.ID field is renamed to Contacts_ID, then you've formed the same compound primary you'd find in finan_ClientsContacts. The problem yet to be solved is updating the join table and identifying it also. See $datasetSections above. <br />
        <br />
    Also see $datasetPrimaryKey - when a view is created like _v_clients_contacts_join, the problem is there is no primary key per se, and passing an update with 'WHERE Contacts_ID=1 and Clients_ID=2' will not be useful (though it would be in the join table ClientsContacts -except for the Type field problem). As of 4/8/2013 the update kernel makes some basic assumptions about key relationship and it works.<br />
    <br />
    Form fields: this was added to the field attributes in the columns node, as 'form_field'; see above for examples.<br />
    To do in order:</p>
      <ol>
        <li>         live updates on blur        </li>
        <ul>
          <li>this means that field must turn red for illegal values</li>
          <li>field translation </li>
        </ul>
      
        <li>technical things - see notes that I've probably done much of this in import, and I read the  table properties and convert the text type - HERE i need to do dynamic text updating UNLESS I have properties user-specified that the field needs to remain a date field for example. This is sort of error checking but if an import starts with all dates and I enter &quot;before 4/8/2013&quot;, the column must become a CHAR(40) field now.<br />
          Also auto-fill for primary-foreign key relationship fields.<br />
          Also, what a select element defaults to - and do I even want to use a select vs. an autofill pretty soon.</li>
        <li> using up and down arrows goes to corresp. field in next row</li>
        <li> if no live updates, then sorting, filtering, and navigating need to say &quot;yey do you want to lose your changes?&quot;</li>
        <li></li>
      </ol>    </td>
  </tr>
  <tr>
    <td valign="top">4/9/2013</td>
    <td>to be developed </td>
  </tr>
  <tr>
    <td valign="top">&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
</table>
<?php
$helpString=ob_get_contents();
ob_end_clean();
?>