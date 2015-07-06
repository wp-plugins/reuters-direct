<?php

if ( ! defined( 'ABSPATH' ) ) exit;

require_once( plugin_dir_path( __FILE__ ) . '/log-widget.php' );

if(isset($_GET['logoff']))
{
	delete_option('rd_username_field');
	delete_option('rd_password_field');
	delete_option('rd_status_radiobuttons');
	delete_option('rd_channel_checkboxes');
	delete_option('rd_category_checkboxes');
	header("Location: options-general.php?page=Reuters_Direct_Settings");
	exit();
}

class Reuters_Direct_Settings {


	private static $_instance = null;
	public $parent = null;
	public $base = '';
	public $settings = array();

	public function __construct ( $parent ) {

		$this->parent = $parent;

		$this->base = 'rd_';

		// Initialise settings
		add_action( 'admin_init', array( $this, 'init_settings' ) );

		// Register Reuters Direct
		add_action( 'admin_init' , array( $this, 'register_settings' ) );

		// Add settings page to menu
		add_action( 'admin_menu' , array( $this, 'add_menu_item' ) );

		// Add settings link to plugins page
		add_filter( 'plugin_action_links_' . plugin_basename( $this->parent->file ) , array( $this, 'add_settings_link' ) );

		// Add dashboard for logs  
		add_action( 'wp_dashboard_setup', array( $this, 'remove_dashboard_widgets' ) );
        add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widgets' ) );

	}

	/**
	 * Main Reuters_Direct_Settings Instance
	 *
	 * Ensures only one instance of Reuters_Direct_Settings is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Reuters_Direct()
	 * @return Main Reuters_Direct_Settings instance
	 */
	public static function instance ( $parent ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $parent );
		}
		return self::$_instance;
	} // End instance()

	/**
	 * Initialise settings
	 * @return void
	 */
	public function init_settings () {
		$this->settings = $this->settings_fields();
		$stylesheet_url = plugins_url() . '/reuters-direct/assets/css/style.css';
		wp_enqueue_style( 'stylesheet', $stylesheet_url );		
		$script_url = plugins_url() . '/reuters-direct/assets/js/script.js';
		wp_register_script( 'script-js', $script_url, array(),'',true  );
		wp_enqueue_script( 'script-js' );
	}

	/**
	 * Add settings page to admin menu
	 * @return void
	 */
	public function add_menu_item () {
		$page = add_options_page( __( 'Reuters Direct', 'reuters-direct' ) , __( 'Reuters Direct', 'reuters-direct' ) , 'manage_options' , 'Reuters_Direct_Settings' ,  array( $this, 'settings_page' ) );
	}

	/**
	 * Add settings link to plugin list table
	 * @param  array $links Existing links
	 * @return array 		Modified links
	 */
	public function add_settings_link ( $links ) {
		$settings_link = '<a href="options-general.php?page=Reuters_Direct_Settings"></a>';
  		array_push( $links, $settings_link );
  		return $links;
	}

	/**
	 * Build settings fields
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields () {

		$settings['login'] = array(
			'title'					=> __( 'Login', 'reuters-direct' ),
			'description'			=> __( 'Welcome to Reuters WordPress Direct, a content aggregator for the Reuters Connect Platform.<br><br>This plugin requires a Reuters Connect Web Services-API user to authenticate and ingest content. Please reach out to <a href="http://reutersnewsagency.com/customer/service/" target="_blank">Customer Support</a> to be put in touch with an appropriate representative to set up access.', 'reuters-direct' ),
			'page'				  	=> __( 'Reuters_Direct_Login' ),
			'fields'				=> array(
				array(
					'id' 			=> 'username_field',
					'label'			=> __( 'Username' , 'reuters-direct' ),
					'description'	=> __( 'This is a standard text field.', 'reuters-direct' ),
					'type'			=> 'text',
					'placeholder'	=> __( 'Enter Username', 'reuters-direct' )
				),
				array(
					'id' 			=> 'password_field',
					'label'			=> __( 'Password' , 'reuters-direct' ),
					'description'	=> __( 'This is a standard password field.', 'reuters-direct' ),
					'type'			=> 'password',
					'placeholder'	=> __( 'Enter Password', 'reuters-direct' )
				)
			)
		);

		$settings['settings'] = array(
			'title'					=> __( 'Settings', 'reuters-direct' ),
			'description'			=> __( '', 'reuters-direct' ),
			'page'				  	=> __( 'Reuters_Direct_Settings' ),
			'fields'				=> array(
				array(
					'id'			=> 'channel_checkboxes',
					'label'			=> __( 'Select Channels' , 'reuters-direct' ),
					'description'	=> __( 'This is a multiple checkbox field for channel selection.', 'reuters-direct' ),
					'type' 			=> 'channel_checkboxes',
					'default'		=> array()
				),
				array(
					'id'			=> 'category_checkboxes',
					'label'			=> __( 'Select Category Codes' , 'reuters-direct' ),
					'description'	=> __( 'This is a multiple checkbox field for category code selection.', 'reuters-direct' ),
					'type' 			=> 'category_checkboxes',
					'default'		=> array('Agency_Labels'),
					'info'			=> array('IPTC subject codes (These are owned by the IPTC, see their website for various lists) 
The key distinctions between N2000 and IPTC are that N2000 includes region and country codes while IPTC do not. IPTC codes can also be structured or nested.
', 'N2000 codes also known as Reuters Topic and Region codes. These are alphabetic and inclusion means some relevance to the story. You can use this code to identify stories located in a certain location and or topic. These codes are derived from the IPTC subject codes below. Use Note: Using these codes, will generate a fair amount of additional category codes as stories are coded with multiple N2 codes.', 'These are Media Category Codes or MCC codes. Often referred to as ‘desk codes’. Derived from the ANPA-1312 format. These codes are added manually by Editorial Staff at Reuters.', 'These are the same as MCC codes however, these codes are applied automatically by Open Calais after the content of the story has been analyzed.', 'Reuters Instrument Code -  Stock Symbol + Index.', 'These are legacy ANPA codes.', 'Allows you to assign a custom category for all the posts.', 'Agency Labels are pre-defined verticals introduced to help you segregate the ingested content and help map them to generic pre-defined categories such as TopNews and Entertainment.')
				),
				array(
					'id'			=> 'custom_category',
					'label'			=> __( 'Select Custom Category' , 'reuters-direct' ),
					'description'	=> __( 'This is a standard text field.', 'reuters-direct' ),
					'type' 			=> 'custom_category',
					'placeholder'	=> __( 'Enter Custom Category', 'reuters-direct' ),
					'default'		=> '',
				),
				array(
					'id' 			=> 'status_radiobuttons',
					'label'			=> __( 'Set Post Status', 'reuters-direct' ),
					'description'	=> __( 'This is a radio button field for post status selection.', 'reuters-direct' ),
					'type'			=> 'status_radiobuttons',
					'options'		=> array( 'publish' => 'Publish (Online Reports)', 'draft' => 'Draft (Online Reports)', 'publish images' => 'Publish (Online Reports with images only)'),
					'default'		=> 'draft'
				),
				array(
					'id' 			=> 'image_radiobuttons',
					'label'			=> __( 'Set Image Rendition', 'reuters-direct' ),
					'description'	=> __( 'This is a radio button field for image rendition selection.', 'reuters-direct' ),
					'type'			=> 'image_radiobuttons',
					'options'		=> array( 'rend:thumbnail' => 'Small JPEG: 150 pixels (Pictures & Online Reports)', 'rend:viewImage' => 'Medium JPEG: 640 pixels (Pictures) 450 pixels (Online Reports)', 'rend:baseImage' => 'Large JPEG: 3500 pixels (Pictures) 800 pixels (Online Reports)' ),
					'default'		=> 'rend:viewImage'
				)
			)
		);

		$settings = apply_filters( 'Reuters_Direct_Settings_fields', $settings );

		return $settings;
	}

	/**
	 * Register Reuters Direct
	 * @return void
	 */
	public function register_settings () {
		if( is_array( $this->settings ) ) {
			foreach( $this->settings as $section => $data ) {
				
				add_settings_section( $section, null, array($this, 'settings_section'), $data['page'] );

				foreach( $data['fields'] as $field ) {

					// Validation callback for field
					$validation = '';
					if( isset( $field['callback'] ) ) {
						$validation = $field['callback'];
					}

					// Register field
					$option_name = $this->base . $field['id'];
					register_setting( $data['page'], $option_name, $validation );

					// Add field to page
					add_settings_field( $field['id'], $field['label'], array( $this, 'display_field' ), $data['page'], $section, array( 'field' => $field ) );
				}
	
			}
		}
	}

	public function settings_section ( $section ) {
		$html = '<p>' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
		echo $html;
	}

	/**
	 * Generate HTML for displaying fields
	 * @param  array $args Field data
	 * @return void
	 */
	public function display_field ( $args ) {

		$field = $args['field'];
		$html = '';
		$option_name = $this->base . $field['id'];
		$option = get_option( $option_name );
		$data = '';
		if( isset( $field['default'] ) ) {
			$data = $field['default'];
			if( $option ) {
				$data = $option;
			}
		}

		switch( $field['type'] ) {

			case 'text':
				$html .= '<span id="login_field">Username:</span><input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . $data . '"/><br><br>' . "\n";
				break;
			
			case 'password':
				$html .= '<span id="login_field">Password:</span><input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . $data . '"/><br><br><br>' . "\n";
				break;
			
			case 'channel_checkboxes':
				//Getting the Token
				$token = $this->getToken();
				if(isset($token['token']))
					$user_token = $token['token'];

				//Getting the Channel List
				$channel_url = 'http://rmb.reuters.com/rmd/rest/xml/channels?&token='. $user_token;
				$channel_curl = curl_init();
			  	curl_setopt($channel_curl, CURLOPT_URL, $channel_url);
			  	curl_setopt($channel_curl, CURLOPT_RETURNTRANSFER, true);
				$channel_xml = simplexml_load_string(curl_exec($channel_curl));
			  	curl_close($channel_curl);
				$OLR = array();
				$TXT = array();
				$GRA = array();
				$PIC = array(); 
				foreach ($channel_xml->channelInformation as $channel_data) 
				{
		   			$channel = (string) $channel_data->description;
		   			$alias = (string) $channel_data->alias;
		   			if(@count($channel_data->category))
		   			{
						$category = (string) $channel_data->category->attributes()->id;
						if($category == "OLR")
						{
							$OLR[$channel] = $alias .':OLR:'. $channel;
						}
						else if($category == "TXT")
						{
							$TXT[$channel] = $alias.':TXT:'. $channel;
						}
						else if($category == "PIC")
						{
							$PIC[$channel] = $alias.':PIC:'. $channel;
						}
						else if($category == "GRA")
						{
							$GRA[$channel] = $alias.':GRA:'. $channel;
						}
					}
				}
				$html .= '<div class="settings"><div id="rd_formheader">News Feed</div>
						<div id="channel_filter">
						<span class="label" style="font-weight:bold !important;"><strong style="font-weight:bold !important; margin-left:3px;">Filter by:</strong></span>
                            <a id="ALL" name="All" href="#" onclick="setFilter(0);" class="category selected">All</a>
						<span>|</span>
							<a id="OLR" name="Online Reports" href="#" onclick="setFilter(1);" class="category">Online Reports</a>
						<span>|</span>
	                        <a id="TXT" name="Text" href="#" onclick="setFilter(2);" class="category">Text</a>
	                    <span>|</span>
	                        <a id="PIC" name="Pictures" href="#" onclick="setFilter(3);" class="category">Pictures</a>
	                    <span>|</span>
	                        <a id="GRA" name="Graphics" href="#" onclick="setFilter(4);" class="category">Graphics</a></div>';  
	  		
				ksort($OLR);
				$html .= '<table id="OLRChannels" class= "channels" style="display: none;">';
				$html .= '<tr><td colspan="2" class="header">Online Reports Channel:</td></tr>';
				$count = 1;
				foreach ($OLR as $channel => $alias) 
				{
					$checked = false;
					if( in_array( $alias, $data ) ) 
					{
						$checked = true;
					}
					if($count%2){$html .= '<tr>';}
				   	$html .= '<td><label for="' . esc_attr( $field['id'] . '_' . $alias ) . '"><input type="checkbox" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '[]" value="' . esc_attr( $alias ) . '" id="' . esc_attr( $field['id'] . '_' . $alias ) . '" /> ' . $channel . '</label></td>';
					if(!$count%2){$html .= '</tr>';}
					$count++;
				}
				$html .= '</table>';
				ksort($TXT);
				$html .= '<table id="TXTChannels" class= "channels" style="display: none;">';
				$html .= '<tr><td colspan="2" class="header">Text Channel:</td></tr>';
				$count = 1;
				foreach ($TXT as $channel => $alias) 
				{
					$checked = false;
					if( in_array( $alias, $data) ) 
					{
						$checked = true;
					}
					if($count%2){$html .= '<tr>';}
				   	$html .= '<td><label for="' . esc_attr( $field['id'] . '_' . $alias ) . '"><input type="checkbox" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '[]" value="' . esc_attr( $alias ) . '" id="' . esc_attr( $field['id'] . '_' . $alias ) . '" /> ' . $channel . '</label></td>';
					if(!$count%2){$html .= '</tr>';}
					$count++;
				}
				$html .= '</table>';
				ksort($PIC);
				$html .= '<table id="PICChannels" class= "channels" style="display: none;">';
				$html .= '<tr><td colspan="2" class="header">Pictures Channel:</td></tr>';
				$count = 1;
				foreach ($PIC as $channel => $alias) 
				{
					$checked = false;
					if( in_array( $alias, $data ) ) 
					{
						$checked = true;
					}
					if($count%2){$html .= '<tr>';}
				   	$html .= '<td><label for="' . esc_attr( $field['id'] . '_' . $alias ) . '"><input type="checkbox" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '[]" value="' . esc_attr( $alias ) . '" id="' . esc_attr( $field['id'] . '_' . $alias ) . '" /> ' . $channel . '</label></td>';
					if(!$count%2){$html .= '</tr>';}
					$count++;
				}
				$html .= '</table>';
				ksort($GRA);
				$html .= '<table id="GRAChannels" class= "channels" style="display: none;">';
				$html .= '<tr><td colspan="2" class="header">Graphics Channel:</td></tr>';
				$count = 1;
				foreach ($GRA as $channel => $alias) 
				{
					$checked = false;
					if( in_array( $alias, $data ) ) 
					{
						$checked = true;
					}
					if($count%2){$html .= '<tr>';}
				   	$html .= '<td><label for="' . esc_attr( $field['id'] . '_' . $alias ) . '"><input type="checkbox" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '[]" value="' . esc_attr( $alias ) . '" id="' . esc_attr( $field['id'] . '_' . $alias ) . '" /> ' . $channel . '</label></td>';
					if(!$count%2){$html .= '</tr>';}
					$count++;
				}
				$html .= '</table></div>';
				break;
			
			case 'category_checkboxes':
				$html .= '<div class="settings" style="margin-bottom:0;"><div id="rd_formheader">Catergory</div>';
				$html .= '<table class="setting_option">';
				$count = 1;
				$category_codes = array('SUBJ'=>'subj', 'N2'=>'N2', 'MCC'=>'MCC', 'MCCL'=>'MCCL', 'RIC'=>'RIC', 'A1312'=>'A1312', 'Custom_Category'=>'Custom_Category', 'Agency_Labels'=>'Agency_Labels');
				$info = $field['info'];
				$info_count = 0;
				foreach( $category_codes as $k => $v ) 
				{
					$checked = false;
					if( in_array( $v, $data ) ) 
					{
							$checked = true;
					}
					if($count%2){$html .= '<tr>';}
					$html .= '<td><label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="checkbox" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '[]" value="' . esc_attr( $v ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" />' . $k . '</label><p id="' . $k . '" class="category_info">' . $info[$info_count] . '</p></td>';
					if(!$count%2){$html .= '</tr>';}
					$count++;
					$info_count++;
				}
				$html .= '</table></div>';
				break;

			case 'custom_category':
				$html .= '<div class="settings" id="add_category">';
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . $data . '"/>' . "\n";
				$html .= '</div>';	
				break;
			
			case 'status_radiobuttons':
				$html .= '<div class="settings" style="margin-top:20px;"><div id="rd_formheader">Post Status</div>';
				$html .= '<table class="setting_option">';
				$count = 1;
				foreach( $field['options'] as $k => $v ) {
					$checked = false;
					if( $k == $data ) {
						$checked = true;
					}
					if($count%2){$html .= '<tr>';}
					$html .= '<td><label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="radio" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ' . $v . '</label></td>';
					if(!$count%2){$html .= '</tr>';}
					$count++;
				}
				$html .= '</table></div>';
				break;

			case 'image_radiobuttons':
				$html .= '<div class="settings"><div id="rd_formheader">Image Rendition</div>';
				$count = 1;
				$html .= '<table class="setting_option">';
				foreach( $field['options'] as $k => $v ) {
					$checked = false;
					if( $k == $data ) {
						$checked = true;
					}
					if($count%2){$html .= '<tr>';}
					$html .= '<td><label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="radio" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ' . $v . '</label></td> ';
					if(!$count%2){$html .= '</tr>';}
					$count++;
				}
				$html .= '</table></div>';
				break;
		}
		echo $html;
	}

	/**
	 * Validate individual settings field
	 * @param  string $data Inputted value
	 * @return string       Validated value
	 */
	public function validate_field ( $data ) {
		if( $data && strlen( $data ) > 0 && $data != '' ) {
			$data = urlencode( strtolower( str_replace( ' ' , '-' , $data ) ) );
		}
		return $data;
	}

	/**
	 * Load settings page content
	 * @return void
	 */
	public function settings_page () {
		
		// Build page HTML
		$html = '<div class="wrap" id="Reuters_Direct_Settings">' . "\n";
			$html .= '<div id="rd_header"><h1><span>REUTERS WORDPRESS DIRECT</span></h1>';
			$html .= '<select id="help_links">
			 <option value="" disabled selected>Help</option>
			 <option value="http://www.reutersnewsagency.com/customer/service/">Contact Us</option>
			 <option value="http://mediaexpress.reuters.com">Media Express</option>
			</select></div>';
			
			$token = $this->getToken();
			if(isset($token['token']) && $token['token']!="")
			{
				// SETTINGS DIV
				$username = get_option('rd_username_field');
				$html .= '<div id="rd_subheader"><b>Current user:&nbsp;<span>'.$username.'&nbsp;</span>|<a id="logout" href="?logoff">&nbsp;Logout</a></b></div>' . "\n";
				$html .= '<div id="rd_settings" class="rd_form"><form name="settings_form" method="post" action="options.php" enctype="multipart/form-data" onsubmit="return validate();">' . "\n";
					ob_start();
					settings_fields( 'Reuters_Direct_Settings' );
					do_settings_sections( 'Reuters_Direct_Settings' );
					$html .= ob_get_clean();
					$html .= '<input name="Submit" type="submit" class="rd_button" value="' . esc_attr( __( 'Save Settings' , 'reuters-direct' ) ) . '" />' . "\n";
				$html .= '</form></div>' . "\n";
				
			}
			else
			{
				if(isset($token['curl_error']) && $token['curl_error']!="")
					{$html .= '<script>jQuery("#setting-error-settings_updated").html("<p><strong>'.$token['curl_error'].'</strong></p>");jQuery("#setting-error-settings_updated").css("border-color","#a00000");</script>';}
				else if(isset($token['token_error']) && $token['token_error']!="")
					{$html .= '<script>jQuery("#setting-error-settings_updated").html("<p><strong>Login falied. Please enter a valid username or password and try again.</strong></p>");jQuery("#setting-error-settings_updated").css("border-color","#a00000");</script>';}
				// LOGIN DIV
				$html .= '<div id="rd_login" class="rd_form"><div id="rd_formheader">Login</div><form name="login_form" method="post" action="options.php" enctype="multipart/form-data" onsubmit="return validate();">' . "\n";	
					ob_start();
					settings_fields( 'Reuters_Direct_Login' );
					do_settings_sections( 'Reuters_Direct_Login' );
					$html .= ob_get_clean();
					$html .= '<input name="Submit" type="submit" class="rd_button" value="' . esc_attr( __( 'Validate & Save' , 'reuters-direct' ) ) . '" />' . "\n";
				$html .= '</form></div>' . "\n";
			}
		$html .= '<div id="rd_footer" class="rd_footer">
		            <p>
		                © 2015 Thomson Reuters. All rights reserved.
		                <span class="moreBar">|</span> 
		                <a href="http://www.thomsonreuters.com/products_services/financial/privacy_statement/" target="_blank" class="privacy">Privacy Statement</a>
		            </p>
		            <a class="logo" href="http://www.thomsonreuters.com" target="_blank">Reuters</a>
		        </div>';
		$html .= '</div>' . "\n";
		echo $html;
	}
	
	// FUNCTION TO GET TOKEN
	public function getToken()
	{
		$username = get_option('rd_username_field');
		$password = get_option('rd_password_field');
	  	$token_url = "https://commerce.reuters.com/rmd/rest/xml/login?username=".$username."&password=".$password;
	  	$token = array();
	  	$ch = curl_init();
	  	curl_setopt($ch, CURLOPT_URL, $token_url);
	  	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	  	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	  	curl_setopt($ch, CURLOPT_SSLVERSION, 6);
	  	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	  	$token['token'] = simplexml_load_string(curl_exec($ch));
		$token['token_error'] = $token['token']->error;
		if(!curl_exec($ch)){
    		$token['curl_error'] = 'Connection failed. ' . curl_error($ch);
		}		
	  	curl_close($ch);
	  	return $token;
	}

	// FUNCTION TO ADD DASHBOARD WIDGET
	function add_dashboard_widgets() {
	    global $custom_dashboard_widgets;
	 
	    foreach ( $custom_dashboard_widgets as $widget_id => $options ) {
	        wp_add_dashboard_widget(
	            $widget_id,
	            $options['title'],
	            $options['callback']
	        );
	    }
	}

	// FUNCTION TO REMOVE DASHBOARD WIDGET
	function remove_dashboard_widgets() {
	    global $remove_defaults_widgets;
	 
	    foreach ( $remove_defaults_widgets as $widget_id => $options ) {
	        remove_meta_box( $widget_id, $options['page'], $options['context'] );
	    }
	}

}
