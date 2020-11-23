<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.fredericgilles.net/fg-joomla-to-wordpress/
 * @since      2.0.0
 *
 * @package    FG_Joomla_to_WordPress_Premium
 * @subpackage FG_Joomla_to_WordPress_Premium/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    FG_Joomla_to_WordPress_Premium
 * @subpackage FG_Joomla_to_WordPress_Premium/admin
 * @author     Frédéric GILLES
 */
class FG_Joomla_to_WordPress_Premium_Admin extends FG_Joomla_to_WordPress_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    2.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    2.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	public $premium_options = array();				// Options specific for the Premium version
	public $users_count = 0;
	
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    2.0.0
	 * @param    string    $plugin_name       The name of this plugin.
	 * @param    string    $version           The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		parent::__construct($plugin_name, $version);
		
		$this->faq_url = 'https://www.fredericgilles.net/fg-joomla-to-wordpress/faq/';

	}

	/**
	 * Initialize the plugin
	 */
	public function init() {
		$this->deactivate_free_version();
		parent::init();
	}

	/**
	 * Get the Premium options
	 */
	public function get_premium_options() {
		
		// Default options values
		$this->premium_options = array(
			'unicode_usernames'			=> false,
			'create_submenus'			=> false,
			'import_meta_seo'			=> false,
			'get_metadata_from_menu'	=> false,
			'get_slug_from_menu'		=> false,
			'keep_joomla_id'			=> false,
			'url_redirect'				=> false,
			'skip_categories'			=> false,
			'skip_articles'				=> false,
			'skip_weblinks'				=> false,
			'skip_users'				=> false,
			'skip_menus'				=> false,
			'skip_modules'				=> false,
		);
		$this->premium_options = apply_filters('fgj2wpp_post_init_premium_options', $this->premium_options);
		$options = get_option('fgj2wpp_options');
		if ( is_array($options) ) {
			$this->premium_options = array_merge($this->premium_options, $options);
		}
	}
	
	/**
	 * Get the WP options name
	 * 
	 * @since 3.57.0
	 * 
	 * @param array $option_names Option names
	 * @return array Option names
	 */
	public function get_option_names($option_names) {
		$option_names = parent::get_option_names($option_names);
		$option_names[] = 'fgj2wpp_options';
		return $option_names;
	}

	/**
	 * Deactivate the free version of FG Joomla to WordPress to avoid conflicts between both plugins
	 */
	private function deactivate_free_version() {
		deactivate_plugins( 'fg-joomla-to-wordpress/fg-joomla-to-wordpress.php' );
	}
	
	/**
	 * Add information to the admin page
	 * 
	 * @param array $data
	 * @return array
	 */
	public function process_admin_page($data) {
		$data['title'] = __('Import Joomla Premium (FG)', $this->plugin_name);
		$data['description'] = __('This plugin will import sections, categories, posts, tags, medias (images, attachments), web links, navigation menus and users from a Joomla database into WordPress.', $this->plugin_name);
		$data['description'] .= "<br />\n" . sprintf(__('For any issue, please read the <a href="%s" target="_blank">FAQ</a> first.', $this->plugin_name), $this->faq_url);

		// Premium options
		foreach ( $this->premium_options as $key => $value ) {
			$data[$key] = $value;
		}
		return $data;
	}

	/**
	 * Get the WordPress database info
	 * 
	 * @param string $database_info Database info
	 * @return string Database info
	 */
	public function get_premium_database_info($database_info) {
		// Users
		$count_users = count_users();
		$users_count = $count_users['total_users'];
		$database_info .= sprintf(_n('%d user', '%d users', $users_count, $this->plugin_name), $users_count) . "<br />";

		// Navigation menus
		$menus_count = $this->count_posts('nav_menu_item');
		$database_info .= sprintf(_n('%d menu item', '%d menu items', $menus_count, $this->plugin_name), $menus_count) . "<br />";

		return $database_info;
	}
	
	/**
	 * Save the Premium options
	 *
	 */
	public function save_premium_options() {
		$this->premium_options = array_merge($this->premium_options, $this->validate_form_premium_info());
		update_option('fgj2wpp_options', $this->premium_options);
	}

	/**
	 * Validate POST info
	 *
	 * @return array Form parameters
	 */
	private function validate_form_premium_info() {
		$result = array(
			'unicode_usernames'			=> filter_input(INPUT_POST, 'unicode_usernames', FILTER_VALIDATE_BOOLEAN),
			'create_submenus'			=> filter_input(INPUT_POST, 'create_submenus', FILTER_VALIDATE_BOOLEAN),
			'import_meta_seo'			=> filter_input(INPUT_POST, 'import_meta_seo', FILTER_VALIDATE_BOOLEAN),
			'get_metadata_from_menu'	=> filter_input(INPUT_POST, 'get_metadata_from_menu', FILTER_VALIDATE_BOOLEAN),
			'get_slug_from_menu'		=> filter_input(INPUT_POST, 'get_slug_from_menu', FILTER_VALIDATE_BOOLEAN),
			'keep_joomla_id'			=> filter_input(INPUT_POST, 'keep_joomla_id', FILTER_VALIDATE_BOOLEAN),
			'url_redirect'				=> filter_input(INPUT_POST, 'url_redirect', FILTER_VALIDATE_BOOLEAN),
			'skip_categories'			=> filter_input(INPUT_POST, 'skip_categories', FILTER_VALIDATE_BOOLEAN),
			'skip_articles'				=> filter_input(INPUT_POST, 'skip_articles', FILTER_VALIDATE_BOOLEAN),
			'skip_weblinks'				=> filter_input(INPUT_POST, 'skip_weblinks', FILTER_VALIDATE_BOOLEAN),
			'skip_users'				=> filter_input(INPUT_POST, 'skip_users', FILTER_VALIDATE_BOOLEAN),
			'skip_menus'				=> filter_input(INPUT_POST, 'skip_menus', FILTER_VALIDATE_BOOLEAN),
			'skip_modules'				=> filter_input(INPUT_POST, 'skip_modules', FILTER_VALIDATE_BOOLEAN),
		);
		$result = apply_filters('fgj2wpp_validate_form_premium_info', $result);
		return $result;
	}

	/**
	 * Delete all the Yoast SEO data
	 * 
	 * @since 3.61.0
	 * 
	 * @global object $wpdb WPDB object
	 * @param string $action Action
	 */
	public function delete_yoastseo_data($action) {
		global $wpdb;
		if ( $action == 'all' ) {
			$wpdb->hide_errors();
			$sql_queries = array();
			
			// Delete the Yoast SEO tables
			$sql_queries[] = "TRUNCATE {$wpdb->prefix}yoast_seo_links";
			$sql_queries[] = "TRUNCATE {$wpdb->prefix}yoast_seo_meta";
			
			// Execute SQL queries
			if ( count($sql_queries) > 0 ) {
				foreach ( $sql_queries as $sql ) {
					$wpdb->query($sql);
				}
			}
		}
	}
	
	/**
	 * Set the truncate option in order to keep use the "keep Joomla ID" feature
	 * 
	 * @param string $action	newposts = removes only new imported posts
	 * 							all = removes all
	 */
	public function set_truncate_option($action) {
		if ( $action == 'all' ) {
			update_option('fgj2wp_truncate_posts_table', 1);
		} else {
			delete_option('fgj2wp_truncate_posts_table');
		}
	}

	/**
	 * Actions to do before the import
	 * 
	 * @param bool $import_doable Can we start the import?
	 * @return bool Can we start the import?
	 */
	public function pre_import_premium_check($import_doable) {
		if ( $import_doable ) {
			if ( $this->premium_options['keep_joomla_id'] && !get_option('fgj2wp_truncate_posts_table') ) { 
				$this->display_admin_error(__('You need to fully empty the database if you want to use the "Keep Joomla ID" feature.', 'fgj2wpp'));
				$import_doable = false;
			}
		}
		return $import_doable;
	}

	/**
	 * Set the posts table autoincrement to the last Joomla ID + 100
	 * 
	 */
	public function set_posts_autoincrement() {
		global $wpdb;
		if ( $this->premium_options['keep_joomla_id'] ) {
			$last_joomla_article_id = $this->get_last_joomla_article_id() + 100;
			$sql = "ALTER TABLE $wpdb->posts AUTO_INCREMENT = $last_joomla_article_id";
			$wpdb->query($sql);
		}
	}

	/**
	 * Get the last Joomla article ID
	 *
	 * @return int Last Joomla article ID
	 */
	private function get_last_joomla_article_id() {
		$prefix = $this->plugin_options['prefix'];
		$sql = "
			SELECT max(id) AS max_id
			FROM ${prefix}content
		";
		$sql = apply_filters('fgj2wp_get_last_joomla_article_id_sql', $sql, $prefix);
		$result = $this->joomla_query($sql);
		$max_id = isset($result[0]['max_id'])? $result[0]['max_id'] : 0;
		return $max_id;		
	}

	/**
	 * Keep the Joomla ID
	 * 
	 * @param array $new_post New post
	 * @param array $post Joomla Post
	 * @return array Post
	 */
	public function add_import_id($new_post, $post) {
		if ( $this->premium_options['keep_joomla_id'] ) {
			$new_post['import_id'] = $post['id'];
		}
		return $new_post;
	}

	/**
	 * Sets the meta fields used by the SEO by Yoast plugin
	 * 
	 * @param int $new_post_id WordPress ID
	 * @param array $post Joomla Post
	 */
	public function set_meta_seo($new_post_id, $post) {
		if ( $this->premium_options['import_meta_seo'] ) {
			if ( array_key_exists('metatitle', $post) && !empty($post['metatitle']) ) {
				update_post_meta($new_post_id, '_yoast_wpseo_title', $post['metatitle']);
			}
			if ( array_key_exists('metadesc', $post) && !empty($post['metadesc']) ) {
				update_post_meta($new_post_id, '_yoast_wpseo_metadesc', $post['metadesc']);
			}
			if ( array_key_exists('metakey', $post) && !empty($post['metakey']) ) {
				update_post_meta($new_post_id, '_yoast_wpseo_metakeywords', $post['metakey']);
			}
			if ( array_key_exists('canonical', $post) && !empty($post['canonical']) ) {
				update_post_meta($new_post_id, '_yoast_wpseo_canonical', $post['canonical']);
			}
		}
	}
	
	/**
	 * Add a user if it does not exists
	 *
	 * @param string $name User's name
	 * @param string $login Login
	 * @param string $email User's email
	 * @param string $password User's password in Joomla
	 * @param string $register_date Registration date
	 * @param string $role User's role - default: subscriber
	 * @return int User ID
	 */
	public function add_user($name, $login, $email, $password, $register_date='', $role='subscriber') {
		$matches = array();
		
		$login = sanitize_user($login, true);
		if ( empty($login) ) { // Use the name or the email if the login is empty
			$login = !empty($name)? $name : $email;
			$login = sanitize_user($login, true);
		}
		if ( empty($name) ) { // Use the first part of the email if the name is empty
			$name = preg_replace('/@.*$/', '', $email);
		}
		$email = sanitize_email($email);

		$display_name = $name;

		// Get the first and last name
		if ( preg_match("/(\w+) *(.*)/u", $name, $matches) ) {
			$first_name = $matches[1];
			$last_name = $matches[2];
		}
		else {
			$first_name = $name;
			$last_name = '';
		}
		$user = get_user_by('slug', $login);
		if ( !$user ) {
			$user = get_user_by('email', $email);
		}
		if ( !$user ) {
			// Create a new user
			$userdata = array(
				'user_login'		=> $login,
				'user_pass'			=> wp_generate_password( 12, false ),
				'user_email'		=> $email,
				'display_name'		=> $display_name,
				'first_name'		=> $first_name,
				'last_name'			=> $last_name,
				'user_registered'	=> $register_date,
				'role'				=> $role,
			);
			$userdata = apply_filters('fgj2wpp_pre_insert_user', $userdata);
			$user_id = wp_insert_user( $userdata );
			if ( is_wp_error($user_id) ) {
				//$this->display_admin_error(sprintf(__('Creating user %s: %s', $this->get_plugin_name()), $login, $user_id->get_error_message()));
			} else {
				$this->users_count++;
				if ( !empty($password) ) {
					// Joomla password to authenticate the users
					add_user_meta($user_id, 'joomlapass', $password, true);
				}
				//$this->display_admin_notice(sprintf(__('User %s created', $this->get_plugin_name()), $login));
			}
		}
		else {
			$user_id = $user->ID;
			global $blog_id;
			if ( is_multisite() && $blog_id && !is_user_member_of_blog($user_id) ) {
				// Add user to the current blog (in multisite)
				add_user_to_blog($blog_id, $user_id, $role);
				$this->users_count++;
			}
		}
		return $user_id;
	}
	
	/**
	 * Get the Joomla tags
	 * 
	 * @since 3.60.0
	 * 
	 * @param int $content_id Joomla ID
	 * @param string $type Type
	 * @return array Tags
	 */
	public function get_tags($content_id, $type='com_content.article') {
		$tags = array();
		$prefix = $this->plugin_options['prefix'];
		$sql = "
			SELECT t.title
			FROM ${prefix}tags t
			INNER JOIN ${prefix}contentitem_tag_map m ON m.tag_id = t.id
			WHERE m.content_item_id ='$content_id'
			AND m.type_alias = '$type'
		";
		$result = $this->joomla_query($sql);
		foreach ( $result as $row ) {
			$tags[] = $row['title'];
		}
		return $tags;
	}

}
