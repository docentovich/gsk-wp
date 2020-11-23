<?php

/**
 * Users module
 *
 * @link       https://www.fredericgilles.net/fg-joomla-to-wordpress/
 * @since      2.0.0
 *
 * @package    FG_Joomla_to_WordPress_Premium
 * @subpackage FG_Joomla_to_WordPress_Premium/admin
 */

if ( !class_exists('FG_Joomla_to_WordPress_Users', false) ) {

	/**
	 * Users class
	 *
	 * @package    FG_Joomla_to_WordPress_Premium
	 * @subpackage FG_Joomla_to_WordPress_Premium/admin
	 * @author     Frédéric GILLES
	 */
	class FG_Joomla_to_WordPress_Users {

		private $users = array();

		/**
		 * Initialize the class and set its properties.
		 *
		 * @since    2.0.0
		 * @param    object    $plugin       Admin plugin
		 */
		public function __construct( $plugin ) {

			$this->plugin = $plugin;

		}

		/**
		 * Add user cols in the get_posts request
		 *
		 * @param string $cols
		 * @return string Cols separating by commas (with a comma at start)
		 */
		public function add_user_cols_in_get_posts($cols) {
			$cols .= ', p.created_by, p.created_by_alias';
			return $cols;
		}

		/**
		 * Delete all users except the current user
		 *
		 * @param string $action all|imported
		 */
		public function delete_users($action) {
			global $wpdb;
			
			$sql_queries = array();

			$current_user = get_current_user_id();
			
			if ( $action == 'all' ) {
				
				// Delete all users except the current user
				if ( is_multisite() ) {
					$blogusers = get_users(array('exclude' => $current_user));
					foreach ( $blogusers as $user ) {
						wp_delete_user($user->ID);
					}
				} else { // monosite (quicker)
					$sql_queries[] = <<<SQL
-- Delete User meta
DELETE FROM $wpdb->usermeta
WHERE user_id != '$current_user'
SQL;

				$sql_queries[] = <<<SQL
-- Delete Users
DELETE FROM $wpdb->users
WHERE ID != '$current_user'
SQL;

					// Execute SQL queries
					if ( count($sql_queries) > 0 ) {
						foreach ( $sql_queries as $sql ) {
							$wpdb->query($sql);
						}
					}
				}
				$this->reset_users_autoincrement();
				
			} else {
				
				// Delete only the imported users
				
				// Truncate the temporary table
				$sql_queries[] = <<<SQL
TRUNCATE {$wpdb->prefix}fg_data_to_delete;
SQL;
				
				// Insert the imported users IDs in the temporary table
				$sql_queries[] = <<<SQL
INSERT IGNORE INTO {$wpdb->prefix}fg_data_to_delete (`id`)
SELECT user_id FROM $wpdb->usermeta
WHERE meta_key LIKE '_fgj2wp_%'
AND user_id != '$current_user'
SQL;
				
				$sql_queries[] = <<<SQL
-- Delete Users and user metas
DELETE u, um
FROM $wpdb->users u
LEFT JOIN $wpdb->usermeta um ON um.user_id = u.ID
INNER JOIN {$wpdb->prefix}fg_data_to_delete del
WHERE u.ID = del.id;
SQL;

				// Execute SQL queries
				if ( count($sql_queries) > 0 ) {
					foreach ( $sql_queries as $sql ) {
						$wpdb->query($sql);
					}
				}

			}
			wp_cache_flush();
			
			// Reset the Joomla last imported user ID
			update_option('fgj2wp_last_user_id', 0);

			$this->plugin->display_admin_notice(__('Users deleted', $this->plugin->get_plugin_name()));
		}

		/**
		 * Reset the wp_users autoincrement
		 */
		private function reset_users_autoincrement() {
			global $wpdb;
			
			$sql = "SELECT IFNULL(MAX(ID), 0) + 1 FROM $wpdb->users";
			$max_id = $wpdb->get_var($sql);
			$sql = "ALTER TABLE $wpdb->users AUTO_INCREMENT = $max_id";
			$wpdb->query($sql);
		}
		
		/**
		 * Define the array of users
		 * 
		 */
		public function get_users_array() {
			$users = $this->get_authors();
			$users = apply_filters('fgj2wpp_post_get_authors', $users);
			foreach ( $users as $user ) {
				$user['roles'] = $this->user_roles($user);
				$this->users[$user['id']] = $user;
			}
		}
		
		/**
		 * Get all the Joomla users
		 * 
		 * @param int $limit Number of users max
		 * @return array Users
		 */
		protected function get_users($limit=1000) {
			$users = array();
			$last_user_id = (int)get_option('fgj2wp_last_user_id'); // to restore the import where it left
			$prefix = $this->plugin->plugin_options['prefix'];
			$extra_cols = '';
			if ( version_compare($this->plugin->joomla_version, '1.5', '<=') ) {
				$extra_cols = ', u.usertype'; // User group
			}
			$sql = "
				SELECT u.id, u.name, u.username, u.email, u.password, u.registerDate
				$extra_cols
				FROM ${prefix}users u
				WHERE u.id > '$last_user_id'
				ORDER BY u.id
				LIMIT $limit
			";
			$sql = apply_filters('fgj2wpp_get_users_sql', $sql, $prefix);
			$result = $this->plugin->joomla_query($sql);
			foreach ( $result as $row ) {
				$users[$row['id']] = $row;
			}
			return $users;
		}
		
		/**
		 * Get all the Joomla authors
		 * 
		 * @return array Users
		 */
		protected function get_authors() {
			$users = array();
			$prefix = $this->plugin->plugin_options['prefix'];
			$extra_cols = '';
			if ( version_compare($this->plugin->joomla_version, '1.5', '<=') ) {
				$extra_cols = ', u.usertype'; // User group
			}
			$sql = "
				SELECT DISTINCT u.id, u.name, u.username, u.email, u.password, u.registerDate
				$extra_cols
				FROM ${prefix}users u
				INNER JOIN ${prefix}content c ON c.created_by = u.id
			";
			$sql = apply_filters('fgj2wp_get_authors_sql', $sql, $prefix);
			$users = $this->plugin->joomla_query($sql);
			return $users;
		}
		
		/**
		 * Determine the Joomla user roles
		 *
		 * @param array $user
		 * @return array User roles
		 */
		protected function user_roles($user) {
			$user_roles = array();
			if ( version_compare($this->plugin->joomla_version, '1.5', '<=') ) {
				if ( isset($user['usertype']) ) {
					$roles = array($user['usertype']);
				} else {
					$roles = array();
				}
			} else {
				$roles = $this->get_user_roles($user['id']);
			}
			foreach ( $roles as $role ) {
				$user_roles[] = FG_Joomla_to_WordPress_Tools::convert_to_latin($role);
			}
			return $user_roles;
		}

		/**
		 * Get the Joomla user roles
		 *
		 * @param int $user_id User ID
		 * @return array User roles
		 */
		protected function get_user_roles($user_id) {
			$user_roles = array();
			$prefix = $this->plugin->plugin_options['prefix'];
			$sql = "
				SELECT ug.title AS role
				FROM ${prefix}usergroups ug
				INNER JOIN ${prefix}user_usergroup_map m ON m.group_id = ug.id AND m.user_id = '$user_id'
			";
			$result = $this->plugin->joomla_query($sql);
			foreach ( $result as $row ) {
				$user_roles[] = $row['role'];
			}
			return $user_roles;
		}

		/**
		 * Import the author of a post
		 * 
		 * @param array $newpost WordPress post
		 * @param array $joomla_post Joomla post
		 * @return array WordPress post
		 */
		public function import_author($newpost, $joomla_post) {
			$joomla_user_id = $joomla_post['created_by'];
			if ( array_key_exists($joomla_user_id, $this->users) ) {
				$user = $this->users[$joomla_user_id];
				// Check if the user is administrator or not
				$role = $this->is_admin($user)? 'administrator': 'author';
				$user_id = $this->plugin->add_user($user['name'], $user['username'], $user['email'], $user['password'], $user['registerDate'], $role);
				do_action('fgj2wpp_post_add_user', $user_id, $user);
				if ( !is_wp_error($user_id) ) {
					$newpost['post_author'] = $user_id;
					add_user_meta($user_id, '_fgj2wp_old_user_id', $joomla_user_id, true);
				}
			}
			return $newpost;
		}

		/**
		 * Import the author alias of a post
		 * 
		 * @param int $new_post_id WordPress post ID
		 * @param array $joomla_post Joomla post
		 */
		public function import_author_alias($new_post_id, $joomla_post) {
			if ( !empty($joomla_post['created_by_alias']) ) {
				update_post_meta($new_post_id, 'author_alias', $joomla_post['created_by_alias']);
			}
		}

		/**
		 * Import all the users
		 * 
		 */
		public function import_users() {
			if ( isset($this->plugin->premium_options['skip_users']) && $this->plugin->premium_options['skip_users'] ) {
				return;
			}
			if ( $this->plugin->import_stopped() ) {
				return;
			}
			
			$this->plugin->log(__('Importing users...', $this->plugin->get_plugin_name()));
			
			// Hook for other actions
			do_action('fgj2wpp_pre_import_users', $this->users);
			
			do {
				if ( $this->plugin->import_stopped() ) {
					return;
				}
				$users = $this->get_users($this->plugin->chunks_size);
				$users_count = count($users);
				foreach ( $users as &$user ) {
					// Check if the user is administrator or not
					$user['roles'] = $this->user_roles($user);
					$role = $this->is_admin($user)? 'administrator': 'subscriber';
					$user_id = $this->plugin->add_user($user['name'], $user['username'], $user['email'], $user['password'], $user['registerDate'], $role);
					do_action('fgj2wpp_post_add_user', $user_id, $user);
					if ( !is_wp_error($user_id) ) {
						$user['new_id'] = $user_id;
						add_user_meta($user_id, '_fgj2wp_old_user_id', $user['id'], true);
					}
					// Increment the Joomla last imported user ID
					update_option('fgj2wp_last_user_id', $user['id']);
				}
				
				// Hook for other actions
				do_action('fgj2wpp_post_import_users', $users);
				
				$this->plugin->progressbar->increment_current_count($users_count);
				
			} while ( !is_null($users) && ($users_count > 0) );
			
			$this->plugin->display_admin_notice(sprintf(_n('%d user imported', '%d users imported', $this->plugin->users_count, $this->plugin->get_plugin_name()), $this->plugin->users_count));
		}
		
		/**
		 * Test if the user is an administrator
		 */
		private function is_admin($user) {
			foreach ( $user['roles'] as $role ) {
				if ( (stripos($role, 'Admin') !== false) || (stripos($role, 'Super') !== false) ) {
					return true;
				}
			}
			return false;
		}
		
		/**
		 * Update the number of total elements found in Joomla
		 * 
		 * @param int $count Number of total elements
		 * @return int Number of total elements
		 */
		public function get_total_elements_count($count) {
			if ( !isset($this->plugin->premium_options['skip_users']) || !$this->plugin->premium_options['skip_users'] ) {
				$count += $this->get_users_count();
			}
			return $count;
		}

		/**
		 * Get the number of Joomla users
		 * 
		 * @return int Number of users
		 */
		private function get_users_count() {
			$prefix = $this->plugin->plugin_options['prefix'];
			$sql = "
				SELECT COUNT(*) AS nb
				FROM ${prefix}users u
			";
			$sql = apply_filters('fgj2wpp_get_users_count_sql', $sql);
			$result = $this->plugin->joomla_query($sql);
			$users_count = isset($result[0]['nb'])? $result[0]['nb'] : 0;
			return $users_count;
		}

		/**
		 * Get users info
		 *
		 * @param string $message Message to display when displaying Joomla info
		 * @return string Message
		 */
		public function get_users_info($message) {
			// Users
			$users_count = $this->get_users_count();
			$message .= sprintf(_n('%d user', '%d users', $users_count, $this->plugin->get_plugin_name()), $users_count) . "\n";
			
			return $message;
		}
		
		/**
		 * Allow Unicode characters in usernames
		 * 
		 * @since 3.55.0
		 */
		public function allow_unicode_usernames() {
			if ( isset($this->plugin->premium_options['unicode_usernames']) && $this->plugin->premium_options['unicode_usernames'] ) {
				add_filter('sanitize_user', array($this, 'sanitize_user'), 10, 3);
				add_filter('wp_pre_insert_user_data', array($this, 'pre_insert_user_data'));
			}
		}
		
		/**
		 * Filter the sanitize_user function to allow Unicode characters in usernames
		 * 
		 * @since 3.55.0
		 * 
		 * @param string $username Username
		 * @param string $raw_username Raw username
		 * @param boolean $strict Strict mode
		 * @return string Username
		 */
		public function sanitize_user($username, $raw_username, $strict = false) {
			$username = $raw_username;

			$username = wp_strip_all_tags( $username );
			// Kill octets
			$username = preg_replace( '|%([a-fA-F0-9][a-fA-F0-9])|', '', $username );
			$username = preg_replace( '/&.+?;/', '', $username ); // Kill entities

			// If strict, reduce to Unicode letters, marks or numbers for max portability.
			// cf https://www.regular-expressions.info/unicode.html
			if ( $strict ) {
				$username = preg_replace( '|[^\p{L}\p{M}\p{N} _.\-@]|iu', '', $username );
			}

			$username = trim( $username );
			// Consolidate contiguous whitespace
			$username = preg_replace( '|\s+|', ' ', $username );

			return $username;
		}
		
		/**
		 * Set the user nicename to the user login
		 * It avoids plenty of %xx characters in the nicename and a too long nicename for Unicode usernames
		 * Required to allow the user to login with a Unicode username
		 * 
		 * @since 3.55.0
		 * 
		 * @param array $data User data
		 * @return array User data
		 */
		public function pre_insert_user_data($data) {
			$data['user_nicename'] = mb_substr($data['user_login'], 0, 50);
			return $data;
		}
		
	}
}
