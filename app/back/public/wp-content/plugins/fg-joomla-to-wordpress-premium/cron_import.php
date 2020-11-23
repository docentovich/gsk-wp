<?php

/**
 * Automatic import executed by cron
 *
 * @link              https://www.fredericgilles.net/fg-joomla-to-wordpress/
 * @since             3.47.0
 * @package           FG_Joomla_to_WordPress
 */

ignore_user_abort(true);

if ( isset($_SERVER['REQUEST_URI']) || !empty($_POST) || defined('DOING_AJAX') || defined('DOING_CRON') ) {
	die();
}

define('DOING_CRON', true); // Tell WordPress we are doing the CRON task

$_SERVER["HTTP_USER_AGENT"] = 'PHP'; // To avoid notices from other plugins

if ( !defined('ABSPATH') ) {
	// Set up WordPress environment
	require_once( __DIR__ . '/../../../wp-load.php' );
	require_once( __DIR__ . '/../../../wp-admin/includes/admin.php' );
	$cron = new FG_Joomla_to_WordPress_Cron();
	$cron->run();
}

/**
 * Cron class
 *
 * @package    FG_Joomla_to_WordPress_Premium
 * @author     Frédéric GILLES
 */
class FG_Joomla_to_WordPress_Cron {

	/**
	 * Run the import
	 */
	public function run() {
		$this->set_current_user_to_admin();
		
		$actions = array('import');
		foreach ( $actions as $action ) {
			$this->do_action($action);
		}
		
		echo "IMPORT COMPLETED\n";
	}
	
	/**
	 * Set the current user to the first admin user (to get the administrator capabilities)
	 */
	private function set_current_user_to_admin() {
		$admin_users = get_users(array(
			'role__in' => 'administrator',
		));
		if ( !empty($admin_users) ) {
			wp_set_current_user($admin_users[0]->ID);
		}
	}
	
	/**
	 * Do an action
	 * 
	 * @param string $action Action
	 */
	private function do_action($action) {
		global $fgj2wpp;

		echo "$action...\n";
		$time_start = date('Y-m-d H:i:s');
		$fgj2wpp->display_admin_notice("=== START $action $time_start ===");
		
		echo $fgj2wpp->dispatch($action);
		
		$time_end = date('Y-m-d H:i:s');
		$fgj2wpp->display_admin_notice("=== END $action $time_end ===\n");
	}
	
}
