<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include(PATH_THIRD.'/lovely_sorted_addons/config.php');

require PATH_THIRD.'/lovely_sorted_addons/lib/phpQuery.php';

class Lovely_sorted_addons_ext {

	public $settings 		= array();
	public $description		= LOVELY_SORTED_ADDONS_DESCRIPTION;
	public $docs_url		= LOVELY_SORTED_ADDONS_DOCS;
	public $name			= LOVELY_SORTED_ADDONS_NAME;
	public $settings_exist	= 'n';
	public $version			= LOVELY_SORTED_ADDONS_VERSION;

	private $EE;

	/**
	 * Constructor
	 *
	 * @param 	mixed	Settings array or empty string if none exist.
	 */
	public function __construct($settings = '')
	{
		$this->EE =& get_instance();
		$this->settings = $settings;
	}

	// ----------------------------------------------------------------------

	/**
	 * Settings Form
	 *
	 * If you wish for ExpressionEngine to automatically create your settings
	 * page, work in this method.  If you wish to have fine-grained control
	 * over your form, use the settings_form() and save_settings() methods
	 * instead, and delete this one.
	 *
	 * @see http://expressionengine.com/user_guide/development/extensions.html#settings
	 */
	public function settings()
	{
		return array(

		);
	}

	// ----------------------------------------------------------------------

	/**
	 * Activate Extension
	 *
	 * This function enters the extension into the exp_extensions table
	 *
	 * @see http://codeigniter.com/user_guide/database/index.html for
	 * more information on the db class.
	 *
	 * @return void
	 */
	public function activate_extension()
	{
		// Setup custom settings in this array.
		$this->settings = array();

		$data = array(
			'class'		=> __CLASS__,
			'method'	=> 'rewrite_cp_source',
			'hook'		=> 'sessions_end',
			'settings'	=> serialize($this->settings),
			'version'	=> $this->version,
			'enabled'	=> 'y'
		);

		$this->EE->db->insert('extensions', $data);

	}

	// ----------------------------------------------------------------------

	/**
	 * rewrite_cp_source
	 *
	 * @param
	 * @return
	 */
	public function rewrite_cp_source()
	{
		if(defined('REQ') and REQ == 'CP')
        {
        	ob_start();
        	register_shutdown_function('exit_callback');
        }
	}



	// ----------------------------------------------------------------------

	/**
	 * Disable Extension
	 *
	 * This method removes information from the exp_extensions table
	 *
	 * @return void
	 */
	function disable_extension()
	{
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->delete('extensions');
	}

	// ----------------------------------------------------------------------

	/**
	 * Update Extension
	 *
	 * This function performs any necessary db updates when the extension
	 * page is visited
	 *
	 * @return 	mixed	void on update / false if none
	 */
	function update_extension($current = '')
	{
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
	}

	// ----------------------------------------------------------------------
}


function exit_callback()
{
	$EE =& get_instance();

	$out = ob_get_contents();
	if (ob_get_length() > 0) ob_end_clean();
	if(!$EE->input->get('M')){
		switch($EE->input->get('C')) {
			case 'addons_extensions':
				echo rewrite_extensions_html($out);
				break;

			case 'addons_modules':
				echo rewrite_modules_html($out);
				break;

			case 'addons_fieldtypes':
				echo rewrite_fieldtypes_html($out);
				break;

			case 'addons_accessories':
				echo rewrite_accessories_html($out);
				break;

			default:
				echo $out;
				break;
		}
	} else {
		echo $out;
	}
}

// Check which EE version we are using, so that the html is re-written correctly
function rewrite_extensions_html($markup){
	if (APP_VER >= '2.8.0')
	{
	  return rewrite_extensions_html_28($markup);
	} else {
		return rewrite_extensions_html_27($markup);
	}
}

function rewrite_extensions_html_27($markup)
{
	$doc = phpQuery::newDocumentHTML($markup);
	$doc['.pageContents']->append('<table class="mainTable" id="enabledExtensions" border="0" cellspacing="0" cellpadding="0"></table>');
	$doc['#enabledExtensions']->append('<thead></thead>');
	$doc['#enabledExtensions thead']->append('<tr class="even"></tr>');
		$doc['#enabledExtensions thead tr']->append('<th class="header">Enabled Extension Name</th>');
		$doc['#enabledExtensions thead tr']->append('<th class="header">Settings</th>');
		$doc['#enabledExtensions thead tr']->append('<th class="header">Documentation</th>');
		$doc['#enabledExtensions thead tr']->append('<th class="header">Version</th>');
		$doc['#enabledExtensions thead tr']->append('<th class="header" style="width: 126px;">Action</th>');
	$doc['#enabledExtensions']->append('<tbody></tbody>');
	$doc['.pageContents']->append('<table class="mainTable" id="disabledExtensions" border="0" cellspacing="0" cellpadding="0"></table>');
	$doc['#disabledExtensions']->append('<tbody></tbody>');
	$doc['#disabledExtensions']->append('<thead></thead>');
	$doc['#disabledExtensions thead']->append('<tr class="even"></tr>');
		$doc['#disabledExtensions thead tr']->append('<th class="header">Disabled Extension Name</th>');
		$doc['#disabledExtensions thead tr']->append('<th class="header">Settings</th>');
		$doc['#disabledExtensions thead tr']->append('<th class="header">Documentation</th>');
		$doc['#disabledExtensions thead tr']->append('<th class="header">Version</th>');
		$doc['#disabledExtensions thead tr']->append('<th class="header" style="width: 126px;">Action</th>');
	$disPtrn = array('Disabled (','e?','>)');
	$disRepl = array('','e','>');
	$enPtrn = array('Enabled (','e?','>)');
	$enRepl = array('','e','>');
	foreach($doc['.mainTable tbody tr'] as $tr) {
		switch (substr(pq($tr)->find('td:nth-child(5)')->html(),0,1)){
			case 'D':
				pq($tr)->find('td:nth-child(5) a')->addClass('less_important_link');
				$doc['#disabledExtensions tbody']->append('<tr>'.str_replace($disPtrn,$disRepl,pq($tr)->html()).'</tr>');
				break;
			case 'E':
				pq($tr)->find('td:nth-child(5) a')->addClass('less_important_link');
				pq($tr)->find('td:nth-child(5) a')->attr('title','Remove');
				$doc['#enabledExtensions tbody']->append('<tr>'.str_replace($enPtrn,$enRepl,pq($tr)->html()).'</tr>');
				break;
		}
	}
	$doc['.mainTable:first']->remove();
	return $doc;
}


function rewrite_extensions_html_28($markup)
{
	$doc = phpQuery::newDocumentHTML($markup);
	$doc['.pageContents']->append('<table class="mainTable" id="enabledExtensions" border="0" cellspacing="0" cellpadding="0"></table>');
	$doc['#enabledExtensions']->append('<thead></thead>');
	$doc['#enabledExtensions thead']->append('<tr class="even"></tr>');
		$doc['#enabledExtensions thead tr']->append('<th class="header">Enabled Extension Name</th>');
		$doc['#enabledExtensions thead tr']->append('<th class="header">Settings</th>');
		$doc['#enabledExtensions thead tr']->append('<th class="header">Documentation</th>');
		$doc['#enabledExtensions thead tr']->append('<th class="header">Version</th>');
		//$doc['#enabledExtensions thead tr']->append('<th class="header">Status</th>');
		$doc['#enabledExtensions thead tr']->append('<th class="header" style="width: 126px;">Action</th>');
	$doc['#enabledExtensions']->append('<tbody></tbody>');
	$doc['.pageContents']->append('<table class="mainTable" id="disabledExtensions" border="0" cellspacing="0" cellpadding="0"></table>');
	$doc['#disabledExtensions']->append('<tbody></tbody>');
	$doc['#disabledExtensions']->append('<thead></thead>');
	$doc['#disabledExtensions thead']->append('<tr class="even"></tr>');
		$doc['#disabledExtensions thead tr']->append('<th class="header">Disabled Extension Name</th>');
		$doc['#disabledExtensions thead tr']->append('<th class="header">Settings</th>');
		$doc['#disabledExtensions thead tr']->append('<th class="header">Documentation</th>');
		$doc['#disabledExtensions thead tr']->append('<th class="header">Version</th>');
	//	$doc['#disabledExtensions thead tr']->append('<th class="header">Status</th>');
		$doc['#disabledExtensions thead tr']->append('<th class="header" style="width: 126px;">Action</th>');
	$disPtrn = array('Disabled (','e?','>)');
	$disRepl = array('','e','>');
	$enPtrn = array('Enabled (','e?','>)');
	$enRepl = array('','e','>');
	foreach($doc['.mainTable tbody tr'] as $tr) {
		switch (substr(pq($tr)->find('td:nth-child(5) span')->html(),0,1)){
			case 'U':
				pq($tr)->find('td:nth-child(5)')->remove();
				pq($tr)->find('td:nth-child(5) a')->addClass('less_important_link');
				$doc['#disabledExtensions tbody']->append('<tr>'.str_replace($disPtrn,$disRepl,pq($tr)->html()).'</tr>');
				break;
			case 'I':
				pq($tr)->find('td:nth-child(5)')->remove();
				pq($tr)->find('td:nth-child(5) a')->addClass('less_important_link');
				pq($tr)->find('td:nth-child(5) a')->attr('title','Remove');
				$doc['#enabledExtensions tbody']->append('<tr>'.str_replace($enPtrn,$enRepl,pq($tr)->html()).'</tr>');
				break;
		}
	}
	$doc['.mainTable:first']->remove();
	return $doc;
}

function rewrite_modules_html($markup)
{
	$doc = phpQuery::newDocumentHTML($markup);
	$doc['.pageContents']->append('<table class="mainTable" id="enabledModules" border="0" cellspacing="0" cellpadding="0"></table>');
	$doc['#enabledModules']->append('<thead></thead>');
	$doc['#enabledModules thead']->append('<tr class="even"></tr>');
		$doc['#enabledModules thead tr']->append('<th class="header">Enabled Module Name</th>');
		$doc['#enabledModules thead tr']->append('<th class="header">Documentation</th>');
		$doc['#enabledModules thead tr']->append('<th class="header">Version</th>');
		$doc['#enabledModules thead tr']->append('<th class="header" style="width: 126px;">Action</th>');
	$doc['#enabledModules']->append('<tbody></tbody>');
	$doc['.pageContents']->append('<table class="mainTable" id="disabledModules" border="0" cellspacing="0" cellpadding="0"></table>');
	$doc['#disabledModules']->append('<tbody></tbody>');
	$doc['#disabledModules']->append('<thead></thead>');
	$doc['#disabledModules thead']->append('<tr class="even"></tr>');
		$doc['#disabledModules thead tr']->append('<th class="header">Disabled Module Name</th>');
		$doc['#disabledModules thead tr']->append('<th class="header">Documentation</th>');
		$doc['#disabledModules thead tr']->append('<th class="header">Version</th>');
		$doc['#disabledModules thead tr']->append('<th class="header" style="width: 126px;">Action</th>');
	$disPtrn = array('>Install');
	$disRepl = array('>Enable');
	$enPtrn = array('>Remove');
	$enRepl = array('>Disable');
	foreach($doc['.mainTable tbody tr'] as $tr) {
		pq($tr)->find('td:nth-child(4)')->remove();
		switch (substr(pq($tr)->find('td:nth-child(4) a')->html(),0,1)){
			case 'I':
				$doc['#disabledModules tbody']->append('<tr>'.str_replace($disPtrn,$disRepl,pq($tr)->html()).'</tr>');
				break;
			case 'R':
				$doc['#enabledModules tbody']->append('<tr>'.str_replace($enPtrn,$enRepl,pq($tr)->html()).'</tr>');
				break;
		}
	}
	$doc['.mainTable:first']->remove();
	return $doc;
}

function rewrite_fieldtypes_html($markup)
{
	$doc = phpQuery::newDocumentHTML($markup);
	$doc['.pageContents']->append('<table class="mainTable" id="enabledFieldtypes" border="0" cellspacing="0" cellpadding="0"></table>');
	$doc['#enabledFieldtypes']->append('<thead></thead>');
	$doc['#enabledFieldtypes thead']->append('<tr class="even"></tr>');
		$doc['#enabledFieldtypes thead tr']->append('<th class="header">Enabled Fieldtype Name</th>');
		$doc['#enabledFieldtypes thead tr']->append('<th class="header">Version</th>');
		$doc['#enabledFieldtypes thead tr']->append('<th class="header" style="width: 126px;">Action</th>');
	$doc['#enabledFieldtypes']->append('<tbody></tbody>');
	$doc['.pageContents']->append('<table class="mainTable" id="disabledFieldtypes" border="0" cellspacing="0" cellpadding="0"></table>');
	$doc['#disabledFieldtypes']->append('<tbody></tbody>');
	$doc['#disabledFieldtypes']->append('<thead></thead>');
	$doc['#disabledFieldtypes thead']->append('<tr class="even"></tr>');
		$doc['#disabledFieldtypes thead tr']->append('<th class="header">Disabled Fieldtype Name</th>');
		$doc['#disabledFieldtypes thead tr']->append('<th class="header">Version</th>');
		$doc['#disabledFieldtypes thead tr']->append('<th class="header" style="width: 126px;">Action</th>');
	$disPtrn = array('>Install');
	$disRepl = array('>Enable');
	$enPtrn = array('>Uninstall');
	$enRepl = array('>Disable');
	foreach($doc['.mainTable tbody tr'] as $tr) {
		pq($tr)->find('td:nth-child(3)')->remove();
		switch (substr(pq($tr)->find('td:nth-child(3) a')->html(),0,1)){
			case 'I':
				$doc['#disabledFieldtypes tbody']->append('<tr>'.str_replace($disPtrn,$disRepl,pq($tr)->html()).'</tr>');
				break;
			case 'U':
				$doc['#enabledFieldtypes tbody']->append('<tr>'.str_replace($enPtrn,$enRepl,pq($tr)->html()).'</tr>');
				break;
		}
	}
	$doc['.mainTable:first']->remove();
	return $doc;
}

function rewrite_accessories_html($markup)
{
	$doc = phpQuery::newDocumentHTML($markup);
	$doc['.pageContents']->append('<table class="mainTable" id="enabledAccessories" border="0" cellspacing="0" cellpadding="0"></table>');
	$doc['#enabledAccessories']->append('<thead></thead>');
	$doc['#enabledAccessories thead']->append('<tr class="even"></tr>');
		$doc['#enabledAccessories thead tr']->append('<th class="header">Enabled Accessory Name</th>');
		$doc['#enabledAccessories thead tr']->append('<th class="header">Available to Member Groups</th>');
		$doc['#enabledAccessories thead tr']->append('<th class="header">Specific Page?</th>');
		$doc['#enabledAccessories thead tr']->append('<th class="header" style="width: 126px;">Action</th>');
	$doc['#enabledAccessories']->append('<tbody></tbody>');
	$doc['.pageContents']->append('<table class="mainTable" id="disabledAccessories" border="0" cellspacing="0" cellpadding="0"></table>');
	$doc['#disabledAccessories']->append('<tbody></tbody>');
	$doc['#disabledAccessories']->append('<thead></thead>');
	$doc['#disabledAccessories thead']->append('<tr class="even"></tr>');
		$doc['#disabledAccessories thead tr']->append('<th class="header">Disabled Accessory Name</th>');
		$doc['#disabledAccessories thead tr']->append('<th class="header" style="width: 126px;">Action</th>');
	$disPtrn = array('>Install');
	$disRepl = array('>Enable');
	$enPtrn = array('>Uninstall');
	$enRepl = array('>Disable');
	foreach($doc['.mainTable tbody tr'] as $tr) {
		switch (substr(pq($tr)->find('td:nth-child(4) a')->html(),0,1)){
			case 'I':
				pq($tr)->find('td:nth-child(2)')->remove();
				pq($tr)->find('td:nth-child(2)')->remove();
				pq($tr)->find('td:nth-child(2) a')->addClass('less_important_link');
				$doc['#disabledAccessories tbody']->append('<tr>'.str_replace($disPtrn,$disRepl,pq($tr)->html()).'</tr>');
				break;
			case 'U':
				pq($tr)->find('td:nth-child(4) a')->addClass('less_important_link');
				pq($tr)->find('td:nth-child(4) a')->attr('title','Remove');
				$doc['#enabledAccessories tbody']->append('<tr>'.str_replace($enPtrn,$enRepl,pq($tr)->html()).'</tr>');
				break;
		}
	}
	$doc['.mainTable:first']->remove();
	return $doc;
}


/* End of file ext.hidestuff.php */
/* Location: /system/expressionengine/third_party/hidestuff/ext.hidestuff.php */
