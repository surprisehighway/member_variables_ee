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
	    foreach($groups->result() as $group)
	    {
	    	// see if there's a saved setting for this group
	    	$current_setting = "";
	    	if (isset($current['group_'.$group->group_id]))
	    	{
	    		$current_setting = $current['group_'.$group->group_id];
	    	}

	    	$vars['settings']['groups'][] = array(
				'id'   => $group->group_id,
				'name' => $group->group_title,
				'value' => form_input('group_' . $group->group_id, $current_setting)
	    	);
	    }
	    //print_r($vars);
/*
	    $max_length = isset($current['max_link_length']) ? $current['max_link_length'] : 20;

	    $trunc_cp_links = (isset($current['truncate_cp_links'])) ? $current['truncate_cp_links'] : 'no';

	    $yes_no_options = array(
	        'yes'   => lang('yes'),
	        'no'    => lang('no')
	    );

	    $vars['settings'] = array(
	        'max_link_length'   => form_input('max_link_length', $max_length),
	        'truncate_cp_links' => form_dropdown(
	                    'truncate_cp_links',
	                    $yes_no_options,
	                    $trunc_cp_links)
	        );
*/
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

	    /*
	    $len = $this->EE->input->post('max_link_length');

	    if ( ! is_numeric($len) OR $len <= 0)
	    {
	        $this->EE->session->set_flashdata(
	                'message_failure',
	                sprintf($this->EE->lang->line('max_link_length_range'),
	                    $len)
	        );
	        $this->EE->functions->redirect(
	            BASE.AMP.'C=addons_extensions'.AMP.'M=extension_settings'.AMP.'file=sh_member_variables'
	        );
	    }
	    */

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
			$new_gvars['gv:is_site_admin'] = TRUE;
		} else {
			$new_gvars['gv:is_site_admin'] = FALSE;
		}

		$your_group = 'group_' . $SESS->userdata['group_id'];

		// if there are settings for the group of the currently logged in user
		if (isset($this->settings[$your_group]))
		{
			$vars = $this->parse_values($this->settings[$your_group]);
			foreach ($vars as $name => $value)
			{
				$new_gvars[$name] = $value; // add a global variable for each one
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
	
	/**
	 * Parse Values
	 *
	 * This is a private function that parses a comma separated list of settings
	 * and returns an array of name => value pairs.
	 *
	 * $settings = "a=b,c=d"
	 * @return array / valse if none
	 */
	private function parse_values($settings = '')
	{
		if ($settings != '')
		{
			$settings = explode(',', $settings);

			$vars = array();
			foreach ($settings as $setting)
			{
				$setting = explode('=', $setting);

				if (count($setting) == 2)
				{
					$vars[$setting[0]] = $setting[1]; 
				}
			}
			return $vars;
		} else {
			return false;
		}
	}
	// ----------------------------------------------------------------------
}

/* End of file ext.sh_member_variables.php */
/* Location: /system/expressionengine/third_party/sh_member_variables/ext.sh_member_variables.php */