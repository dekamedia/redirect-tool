<?php
/*
Plugin Name: Redirect Tool
Plugin URI: https://sugi.info/plugins/
Description: Redirect Tool is another WordPress Plugin to send users to different URLs. The idea is that the user visits a URL and is then redirected to one of the different URLs that are being set up in the plugin.
Version: 1.0.3
Author: Sugiartha
Author URI: https://sugi.info/about/
License: GPLv2 or later
Text Domain: redirect_tool
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) die;

class redirect_tool{ 
	private $_plugin_ver = '1.0.3';
	private $_db_ver = '1.0';
	private $_num_url = 10;
	public $textdomain = 'redirect_tool';
	
	public function __construct(){
		global $wpdb;
		
		// Mysql table for redirection log 
		$this->table_logs = $wpdb->prefix . 'logs'; 
		
		// Activation & deactivation
		register_activation_hook(__FILE__, array($this, 'activate'));
		register_deactivation_hook(__FILE__, array($this, 'deactivate'));
		add_action( 'plugins_loaded', array( $this, 'upgrade_plugin') );

		// Add setting link at plugin page
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_settings_link') );
		
		// Add menu page to display various tabs usage
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 9999);

		// Methods to handle admin submission
		add_action( 'admin_post_' . $this->textdomain . '_campaign_submit', array( $this, 'callback_campaign_submit' ) );
		add_action( 'admin_post_' . $this->textdomain . '_campaign_delete', array( $this, 'callback_campaign_delete' ) );		
		add_action( 'admin_post_' . $this->textdomain . '_log_clear', array( $this, 'callback_log_clear' ) );
		add_action( 'admin_notices', array( $this, 'callback_admin_notice') );

		// Actions need tobe done after all plugins loaded
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded') );
		
		// Redirect action
		//add_action('wp_head', array( $this, 'callback_wp_head'), 10000000 );		
		add_action('init', array( $this, 'init' ), 10000000);
	}
	
	public function plugins_loaded(){
	}

	public function activate(){
		global $wpdb;
		
		// Redirection log
		$table_name = $this->table_logs;
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE $table_name (
		  id mediumint(9) NOT NULL AUTO_INCREMENT,
		  time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  log_title varchar(100) DEFAULT '' NOT NULL,
		  log_info TEXT,
		  ipaddress varchar(15) DEFAULT '' NOT NULL,
		  PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		
		$this->save_setting( $this->_db_ver, 'db_ver');
	}

	public function deactivate(){		
		//get setting
		$clear_db = $this->get_setting('clear_db');
		if( $clear_db == 'yes' ){
			delete_option($this->textdomain . '_options');
		}
	}
	
	public function add_settings_link( $links ){
		$settings_link = '<a href="' . $this->admin_url() . '">' . __( 'Settings', $this->textdomain ) . '</a>';
		array_push( $links, $settings_link );
		return $links;
	}

	/* Upgrade mysql table if necessary */
	public function upgrade_plugin(){
		if( $this->get_setting( 'db_ver') != $this->_db_ver ){
			$this->activate();
		}
	}
	
	protected function plugin_dir_url(){
		return plugin_dir_url( __FILE__ );
	}
	
	/* Debug */
	protected function d($arr, $die = true){
		echo '<pre>';
		if( is_array($arr) || is_object($arr) ) print_r($arr); else echo $arr;
		echo '</pre>';
		if($die) die;
	}
		
	/* Get all settings required for this plugin */
	public function get_setting($field = false){
		$options = get_option($this->textdomain . '_options');				
		if($field){
			if( isset($options[$field]) ) return $options[$field];
			return false;
		}
		return $options;
	}

	/* Save setting for this plugin, all or specific field */
	public function save_setting($values, $key = false){
		if( $key ){
			$options = $this->get_setting();
			$options[ $key ] = $values;
			$values = $options;
		}
		update_option( $this->textdomain . '_options', $values );	
	}
	
	/* Get listing */
	public function get_campaign($form_id = false){
		$listing = get_option($this->textdomain . '_campaign');	
		if($form_id){
			if( isset($listing[$form_id]) ) return $listing[$form_id];
			return false;
		}
		return $listing;
	}
	
	public function get_campaign_by_slug( $slug ){
		if( empty($slug) ) return false;
		
		$listing = $this->get_campaign();
		if( !$listing ) return false;
		
		foreach($listing as $form_id => $arr){
			if( $arr['campaign_slug'] == $slug ) return $arr;
		}
		
		return false;
	}

	/* Save listing */
	public function save_campaign($form_id, $value){
		$listing = $this->get_campaign();		
		$listing[$form_id] = $value;
		update_option( $this->textdomain . '_campaign', $listing );	
		return true;
	}

	/* Delete listing */
	public function delete_campaign($form_id){
		if(empty($form_id)) return false;
		
		$listing = $this->get_campaign();		
		if( isset($listing[$form_id]) ){ unset($listing[$form_id]); }
		update_option( $this->textdomain . '_campaign', $listing );	
		return true;
	}
	
	public function num_url(){
		return $this->_num_url;
	}
	
	public function admin_url( $query_vars = false, $add_nonce = false ){
		
		// Default admin url for this plugin
		$url = admin_url( 'options-general.php?page=' . $this->textdomain ); 		
		
		// Include query_vars if any
		if( $query_vars ){
			if( is_array($query_vars) ){
				$url .= '&' . implode( '&', http_build_query($query_vars) );
			}elseif( is_string($query_vars) ){
				$url .= '&' . trim($query_vars, '&');
			}
		}
		
		if($add_nonce){
			$url .= '&_wpnonce=' . wp_create_nonce( $this->textdomain );
		}
		
		return $url;
	}
	
	public function admin_menu(){
		
		add_options_page( __('Redirect Tool', $this->textdomain), __('Redirect Tool', $this->textdomain), 'manage_options', $this->textdomain, array( $this, 'admin_setting' ));
		
	}

	// Print admin notice after form submitted 
	public function callback_admin_notice() {
		$screen = get_current_screen();
		if( $screen->id != 'settings_page_' . $this->textdomain ) return false;
		if( !isset( $_GET['message'] ) ) return false;
		
		switch($_GET['message']){			
			case 'update': 
				$class = 'notice notice-success is-dismissible';
				$message = __( 'Settings saved.', $this->textdomain );
				break;
				
			case 'list_update': 
				$class = 'notice notice-success is-dismissible';
				$message = __( 'Campaign saved.', $this->textdomain );
				break;
				
			case 'list_delete': 
				$class = 'notice notice-warning is-dismissible';
				$message = __( 'A campaign has been deleted.', $this->textdomain );
				break;
				
			case 'clearlogs': 
				$class = 'notice notice-warning is-dismissible';
				$message = __( 'Log has been cleared.', $this->textdomain );
				break;
				
		}
		
		if( isset($message) && isset($class) ){
			printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ); 
		}
		
		return true;
	}	

	public function admin_setting(){
		
		echo '<div class="wrap" id="redirect-tool-page"><h1>' . __('Redirect Tool', $this->textdomain) . '</h1>';
		
		if( isset($_GET['action']) && $_GET['action'] == 'edit' ){
			include( dirname(__FILE__) . '/view/admin_edit.php' );
		
		}elseif( isset($_GET['action']) && $_GET['action'] == 'logs' ){
			include( dirname(__FILE__) . '/view/admin_logs.php' );
			
		}else{
			include( dirname(__FILE__) . '/view/admin_list.php' );
		}
		
		echo '</div>';
		
		include( dirname(__FILE__) . '/view/admin.css' );
	}
	
	// Setting submission
	public function callback_campaign_submit(){
		if($_POST){
			$form_id = ( isset($_POST['form_id']) ) ? $_POST['form_id'] : false;
			$campaign_title = ( isset($_POST['campaign_title']) ) ? wp_kses($_POST['campaign_title'], '') : '';
			$campaign_slug = ( isset($_POST['campaign_slug']) && $_POST['campaign_slug'] ) ? wp_kses($_POST['campaign_slug'], '') : '';			
			$redirect_url = ( isset($_POST['redirect_url']) ) ? $_POST['redirect_url'] : false;
			$redirect_weight = ( isset($_POST['redirect_weight']) ) ? $_POST['redirect_weight'] : false;
			$campaign_resource = ( isset($_POST['campaign_resource']) ) ? $_POST['campaign_resource'] : '';
			$campaign_message = ( isset($_POST['campaign_message']) ) ? $_POST['campaign_message'] : '';
			$campaign_delay = ( isset($_POST['campaign_delay']) ) ? (int)$_POST['campaign_delay'] : 0;
			$campaign_current_theme_css = ( isset($_POST['campaign_current_theme_css']) ) ? wp_kses($_POST['campaign_current_theme_css'], '') : 'no';
			
			$campaign_redirect = false;
			if( is_array($redirect_url) ){
				foreach($redirect_url as $i => $url){
					if($url){
						$weight = ( isset($redirect_weight[$i]) && $redirect_weight[$i] ) ? (int)$redirect_weight[$i] : 1;
						$campaign_redirect[] = array( $url, $weight );
					}
				}
			}
			
			$values = array(
				'campaign_title' => $campaign_title, 
				'campaign_slug' => $campaign_slug, 
				'campaign_redirect' => $campaign_redirect, 
				'campaign_resource' => $campaign_resource, 
				'campaign_message' => $campaign_message, 
				'campaign_delay' => $campaign_delay, 
				'campaign_current_theme_css' => $campaign_current_theme_css, 
			);

			if(!$form_id){
				$form_id = date('YmdHis');
				$values['insert_date'] = date('Y-m-d H:i:s');
				$values['update_date'] = date('Y-m-d H:i:s');
			}else{
				$prev = $this->get_campaign($form_id);
				if($prev){
					$values['insert_date'] = $prev['insert_date'];
				}
				$values['update_date'] = date('Y-m-d H:i:s');
			}
			
			$this->save_campaign($form_id, $values);
			
			wp_redirect( $this->admin_url( 'message=list_update' ) ); exit;
		}
		
		wp_redirect( $this->admin_url() ); exit;	
	}
	
	public function callback_campaign_delete(){
		check_admin_referer($this->textdomain);
		
		$form_id = ( isset($_GET['form_id']) ) ? $_GET['form_id'] : false;
		
		if($form_id){
			$this->delete_campaign($form_id);
			wp_redirect( $this->admin_url( 'message=list_delete' ) ); exit;
		}
		wp_redirect( $this->admin_url() ); exit;	
	}

	// Enqueue autocomplete javascript & css
	public function enqueue_resources(){
		wp_enqueue_script('autocomplete', plugin_dir_url( __FILE__ ) .'autocomplete/jquery.auto-complete.min.js', array('jquery'));
		wp_enqueue_style('autocomplete_css', plugin_dir_url( __FILE__ ) .'autocomplete/jquery.auto-complete.css');
	}
	
	protected function _ip_address() { 
		if ( isset($_SERVER["HTTP_X_FORWARDED_FOR"]) && $ip_address = $_SERVER["HTTP_X_FORWARDED_FOR"]) { 
			// IP addresses can be chained, separated with commas, 
			// we want the first one. 
			if (strpos($ip_address, ',') !== false) { 
				$ip_address = explode(',', $ip_address); 
				$ip_address = $ip_address[0]; 
			} 
		}else { 
			$ip_address = $_SERVER["REMOTE_ADDR"]; 
		} 
		
		return $ip_address; 
 	}
	
	protected function _get_permalink_by_slug($slug){
		return get_site_url(NULL, '/go/' . $slug);
	}	
	
	
	/* Activities logs */
	protected function log($title, $info){
		global $wpdb;
		
		$table_name = $this->table_logs;
		
		if( is_array($info) ){
			$info = print_r( $info, true );
		}elseif( is_object($info) ){
			$info = print_r( $info, true );
		}
		
		$arr = array(
			'time' => current_time( 'mysql' ),
			'log_title' => $title,
			'log_info' => $info,
			'ipaddress' => $this->_ip_address(),
		);
		
		$wpdb->insert( $table_name, $arr );
	}
	
	public function callback_log_clear(){
		global $wpdb;
		check_admin_referer($this->textdomain);
		
		$table_name = $this->table_logs;
		$wpdb->query("TRUNCATE TABLE $table_name");
		wp_redirect( $this->admin_url('message=clearlogs&tab=logs') ); exit;
	}
	
	
	//_____________________________________________________________________________
	//
	// REDIRECTION
	//_____________________________________________________________________________
	
	public function pick_one_url( $campaign ){		
		if( !is_array($campaign['campaign_redirect']) ) return false;
		
		$url = false;
		foreach($campaign['campaign_redirect'] as $i => $arr){
			$weight = (int)$arr[1];
			for( $x = 0; $x < $weight; $x++ ) $url[] = $arr[0];
		}
				
		// Pick one
		$out = $url[mt_rand(0, count($url) - 1)];

		return $out;
	}
	
	public function create_js_redirection($url, $delay = 0){
		$out = '
			<!-- REDIRECTING STARTS -->
			<noscript>
				<meta http-equiv="refresh" content="' . (int)$delay . ';URL=' . $url . '">
			</noscript>
			<!--[if lt IE 9]><script type="text/javascript">var IE_fix=true;</script><![endif]-->
			<script type="text/javascript">
				var url = "' . $url . '";
				var delay = "' . ((int)$delay * 1000) . '";
				window.onload = function (){setTimeout(GoToURL, delay);}
				function GoToURL(){
					if(typeof IE_fix != "undefined") // IE8 and lower fix to pass the http referer
					{
						var referLink = document.createElement("a");
						referLink.href = url;
						document.body.appendChild(referLink);
						referLink.click();
					}
					else { window.location.replace(url); } // All other browsers
				}
			</script>
			<!-- Credit goes to http://insider.zone/ -->
			<!-- REDIRECTING ENDS -->
			';
		return $out;
	}
	
	public function callback_wp_head(){
		global $post;
		
		// Only valid page or post
		if( !is_singular() ) return;
		if( !$post ) return;
		
		// Get slug
		$slug = $post->post_name;
		
		// Get current permalink
		$current_page = get_permalink($post->ID);
		
		// Get redirect info for that slug
		if( $campaign = $this->get_campaign_by_slug( $slug ) ){
			if( $url = $this->pick_one_url( $campaign ) ){
				
				// Expose additional resource
				if($campaign['campaign_resource']){
					echo stripslashes($campaign['campaign_resource']);					
				}
				
				// Print message
				if($campaign['campaign_message']){
					echo stripslashes($campaign['campaign_message']);
				}
				
				// Generate javascript & noscript redirection
				echo $this->create_js_redirection($url, 0);
				
				// Save log
				$info = array(
					'campaign_title' => $campaign['campaign_title'],
					'campaign_slug' => $campaign['campaign_slug'],
					'current' => $current_page,
					'redirect' => $url,
					'referer' => wp_get_referer(),
					);
				
				$this->log($campaign['campaign_slug'], json_encode($info));
				
			}else{
				return;
			}
		}else{
			return;
		}
	}

	function init($posts){
		
		$wp_short_dir = $this->get_setting('short_dir');
		if( empty($wp_short_dir) ) $wp_short_dir = 'go';

		if($wp_short_dir == '?'){
			$wp_short_dir_url = '/?';
		}elseif($wp_short_dir){
			$wp_short_dir_url = '/' . $wp_short_dir . '/';
		}else{
			return;
		}
		
		$req_uri = $_SERVER['REQUEST_URI'];
		if(strpos($req_uri, $wp_short_dir_url) !== false){
			//get short url
			if($wp_short_dir == '?'){
				$arr = explode('/?', $req_uri);
			}else{
				$arr = explode('/', $req_uri);
			}		

			$n = sizeof($arr);
			if($arr[$n - 1] && strpos($arr[$n - 1], '=') === false){
				
				// Get slug
				$slug = wp_kses($arr[$n - 1], '');
				
				// Get current page for log
				$current_page = $req_uri;
				
				// Get redirect info for that slug
				if( $campaign = $this->get_campaign_by_slug( $slug ) ){
					if( $url = $this->pick_one_url( $campaign ) ){

						echo '<!DOCTYPE html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /><link rel="profile" href="http://gmpg.org/xfn/11" /><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" /><title>' . $slug . '</title>';

						if( $campaign['campaign_current_theme_css'] == 'yes' ){
							// Include current theme css
							echo '<link rel="stylesheet" href="' . get_stylesheet_uri() . '" type="text/css" media="all" />';
						}else{
							echo '<style>
							body{background-color: #FFFFFF; font: 14px/21px "Helvetica Neue","Helvetica Neue",Helvetica,Arial,sans-serif; color: #444; -webkit-font-smoothing: antialiased; -webkit-text-size-adjust: 100%; min-height: 100%; width: 80%; margin: auto; padding: 0; border: 0; vertical-align: baseline; text-align: center; }
							</style>';
						}
						
						// Expose additional resource
						if($campaign['campaign_resource']){
							echo stripslashes($campaign['campaign_resource']);					
						}
						
						echo '</head><body class="page">';
						
						// Print message
						if($campaign['campaign_message']){
							echo stripslashes($campaign['campaign_message']);
						}
						
						// Generate javascript & noscript redirection
						$campaign_delay = (int)$campaign['campaign_delay'];
						echo $this->create_js_redirection($url, $campaign_delay);
						
						
						echo '</body></html>';
						
						// Save log
						$info = array(
							'campaign_title' => $campaign['campaign_title'],
							'campaign_slug' => $campaign['campaign_slug'],
							'current' => $current_page,
							'redirect' => $url,
							'referer' => wp_get_referer(),
							);
						
						$this->log($campaign['campaign_slug'], json_encode($info));
						exit;

					}else{
						return;
					}
				}else{
					return;
				}
			}
		}
	}
	
}//end of class

$redirect_tool = new redirect_tool();