<?php
/**
 * Debug Info class
 *
 * @since      3.57.0
 * @package    FG_Joomla_to_WordPress_Premium
 * @subpackage FG_Joomla_to_WordPress_Premium/includes
 * @author     Frédéric GILLES
 */

if ( !class_exists('FG_Joomla_to_WordPress_DebugInfo', false) ) {
	class FG_Joomla_to_WordPress_DebugInfo {
		
		private $option_names_filter = 'fgj2wp_get_option_names';
		
		/**
		 * Display the Debug Info
		 * 
		 * @global object $wpdb
		 */
		public function display() {
			global $wpdb;
			$matches = array();
			
			$plugin_url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
			$plugin_url = preg_replace('/&tab=debuginfo/', '', $plugin_url);
			
			$theme = wp_get_theme();
			
			// Plugins
			$plugins = get_plugins();
			$current_plugin_path = preg_match('#/plugins/(.*?)/#', __DIR__, $matches)? $matches[1] : '';
			$active_plugins_paths = get_option('active_plugins');
			$active_plugins = array();
			$current_plugin = array('Name' => '', 'Version' => '');
			$addons = array();
			foreach ( $plugins as $plugin_path => $plugin ) {
				if ( in_array($plugin_path, $active_plugins_paths) ) {
					$active_plugins[] = $plugin;
					// Current plugin
					if ( preg_match('#^' . $current_plugin_path . '/#', $plugin_path) ) {
						$current_plugin = $plugin;
					}
					// Add-ons
					if ( preg_match('#^' . $current_plugin_path . '-#', $plugin_path) ) {
						$addons[] = $plugin;
					}
				}
			}
			
			// Plugin options
			$plugin_options = $this->get_plugin_options();
			
			$pdo_drivers = extension_loaded('PDO')? implode(', ', PDO::getAvailableDrivers()) : 'not loaded';
			
			echo "### BEGIN DEBUG INFO ###\n\n";
			echo "WordPress info:\n";
			echo  '  Plugin URL: '. $plugin_url . "\n";
			echo  '  Site URL: '. site_url() . "\n";
			echo  '  Home URL: '. home_url() . "\n";
			echo  '  WP version: '. get_bloginfo('version') . "\n";
			echo  '  WP Memory limit: '. WP_MEMORY_LIMIT . "\n";
			echo  '  Multisite: '. (is_multisite()? 'yes' : 'no') . "\n";
			echo  '  Permalink structure: '. get_option('permalink_structure') . "\n";
			echo  '  Media in year/month folders: '. (get_option('uploads_use_yearmonth_folders')? 'yes' : 'no') . "\n";
			echo  '  Active theme: '. $theme->Name . ' ' . $theme->Version . "\n";
			echo  "  Active plugins: \n";
			foreach ( $active_plugins as $active_plugin ) {
				echo '    ' . $active_plugin['Name'] . ' ' . $active_plugin['Version'] . "\n";
			}
			
			echo "\nPHP info:\n";
			echo  '  PHP version: '. PHP_VERSION . "\n";
			echo  '  Web server info: '. $_SERVER['SERVER_SOFTWARE'] . "\n";
			echo  '  memory_limit: '. ini_get('memory_limit') . "\n";
			echo  '  max_execution_time: '. ini_get('max_execution_time') . "\n";
			echo  '  max_input_time: '. ini_get('max_input_time') . "\n";
			echo  '  post_max_size: '. ini_get('post_max_size') . "\n";
			echo  '  upload_max_filesize: '. ini_get('upload_max_filesize') . "\n";
			echo  '  PDO: '. $pdo_drivers . "\n";
			
			echo "\nMySQL info:\n";
			echo  '  MySQL version: '. $wpdb->db_version() . "\n";
			echo  '  max_allowed_packet: '. $wpdb->get_var("SHOW VARIABLES LIKE 'max_allowed_packet';", 1) . "\n";
			
			echo "\nPlugin info:\n";
			echo '  ' . $current_plugin['Name'] . ' ' . $current_plugin['Version'] . "\n";
			echo "  Add-ons:\n";
			foreach ( $addons as $addon ) {
				echo '    ' . $addon['Name'] . ' ' . $addon['Version'] . "\n";
			}
			echo "  Options:\n";
			foreach ( $plugin_options as $option ) {
				$option_value = $option['value'];
				if ( preg_match('/password/', $option['key']) ) {
					$option_value = '***'; // Don't show the passwords
				}
				echo '    ' . $option['key'] . ': ' . $option_value . "\n";
			}
			
			echo "\n### END DEBUG INFO ###\n";
		}
		
		/**
		 * Get the plugin options
		 * 
		 * @since 3.58.0
		 * 
		 * @return array Plugin options
		 */
		private function get_plugin_options() {
			$plugin_options = array();
			$option_names = apply_filters($this->option_names_filter, array());
			foreach ( $option_names as $option_name ) {
				$options = get_option($option_name, array());
				foreach ( $options as $key => $value ) {
					$plugin_options[] = array('key' => $key, 'value' => $value);
				}
			}
			return $plugin_options;
		}
		
	}
}
