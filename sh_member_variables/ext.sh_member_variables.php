<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * Member Variables Extension
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Extension
 * @author		Surprise Highway Inc.
 * @link		http://surprisehighway.com
 */

class Sh_member_variables_ext {
	
	public $settings 		= array();
	public $description		= 'Set global variables based on member groups';
	public $docs_url		= 'https://github.com/surprisehighway';
	public $name			= 'Member Variables';
	public $settings_exist	= 'y';
	public $version			= '1.0';
	
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
	 * @param   Array   Settings
	 * @return  void
	 */
	function settings_form($current)
	{
	    $this->EE->load->helper('form');
	    $this->EE->load->library('table');

	    $vars = array();
	    $vars['settings'] = array();

	    // get the member groups
	    $this->EE->load->model('member_model');
	    $groups = $this->EE->member_model->get_member_groups();

	    // build a dropdown list of groups
	    foreach($groups->result() as $group)
	    {
			$vars['settings']['groups'][$group->group_id] = $group->group_title;
	    }

		$vars['settings']['items'] = array();

		$next_id = 0;
	    if (isset($current))
	    {
			$next_id = count($current);

		    foreach ($current as $id => $item)
		    {
			    $vars['settings']['items'][] = array(
			    	'group_id' => form_dropdown($id.'[group_id]', $vars['settings']['groups'], $item['group_id']),
			    	'name' => form_input($id.'[name]', $item['name']),
			    	'value' => form_input($id.'[value]', $item['value']),
			    	'tag' => '{' . $item['value'] . '}',
			    );
			}
		}

	    $vars['settings']['new_item'] = array(
		    'group_id' => form_dropdown('item'.$next_id.'[group_id]', $vars['settings']['groups']),
	    	'name' => form_input('item'.$next_id.'[name]', ''),
	    	'value' => form_input('item'.$next_id.'[value]', ''),
	    	'tag' => ''
	    );

	   	$vars['settings']['new_var'] = array(
	    	'name' => form_input('item'.$next_id.'[name]', ''),
	    	'tag' => ''
	    );

	    foreach ($vars['settings']['groups'] as $group) 
	    {

	    	$vars['settings']['new_var'][$group] = form_input('item'.$next_id.'[value]', '');
	    }

		$this->settings = $vars['settings'];
	    return $this->EE->load->view('index', $vars, TRUE);
	}


	/**
	 * Save Settings
	 *
	 * This function provides a little extra processing and validation
	 * than the generic settings form.
	 *
	 * @return void
	 */
	function save_settings()
	{
	    if (empty($_POST))
	    {
	        show_error($this->EE->lang->line('unauthorized_access'));
	    }

	    unset($_POST['submit']);

	    $this->EE->lang->loadfile('sh_member_variables');

	  	// don't save any items that don't have a name
	  	foreach ($_POST as $id => $item)
	  	{
	  		if (trim($item['name']) == '')
	  		{
	  			unset($_POST[$id]);
	  		}
	  	}

	    $this->EE->db->where('class', __CLASS__);
	    $this->EE->db->update('extensions', array('settings' => serialize($_POST)));

	    $this->EE->session->set_flashdata(
	        'message_success',
	        $this->EE->lang->line('preferences_updated')
	    );

	    // back to the settings page
	    $this->EE->functions->redirect(BASE.AMP.'C=addons_extensions'.AMP.'M=extension_settings'.AMP.'file=sh_member_variables');
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
			'method'	=> 'sessions_end',
			'hook'		=> 'sessions_end',
			'settings'	=> serialize($this->settings),
			'version'	=> $this->version,
			'enabled'	=> 'y'
		);

		$this->EE->db->insert('extensions', $data);			
		
	}	

	// ----------------------------------------------------------------------
	
	/**
	 * sessions_end
	 *
	 * @param 
	 * @return 
	 */
	public function sessions_end($SESS)
	{
		// Add Code for the sessions_end hook here.  
		$new_gvars = array();

		// Are they an admin regardless of member group?
		if ($SESS->userdata['can_access_cp'] == 'y')
		{
			$new_gvars['is_site_admin'] = TRUE;
		} else {
			$new_gvars['is_site_admin'] = FALSE;
		}

		if ($SESS->userdata['can_access_edit'] == 'y')
		{
			$new_gvars['can_edit'] = TRUE;
		} else {
			$new_gvars['can_edit'] = FALSE;
		}

		// check the settings for any that are assigned to my group
		foreach ($this->settings as $setting)
		{
			if ($setting['group_id'] == $SESS->userdata['group_id'])
			{
				$new_gvars[$setting['name']] = $setting['value'];
			}
		}

		// Make them global variables
		$this->EE->config->_global_vars = array_merge($new_gvars, $this->EE->config->_global_vars);
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

/* End of file ext.sh_member_variables.php */
/* Location: /system/expressionengine/third_party/sh_member_variables/ext.sh_member_variables.php */