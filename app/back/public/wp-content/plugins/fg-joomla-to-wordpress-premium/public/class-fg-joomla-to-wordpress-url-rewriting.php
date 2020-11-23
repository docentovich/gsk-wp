<?php

/**
 * URL Rewriting module
 *
 * @link       https://www.fredericgilles.net/fg-joomla-to-wordpress/
 * @since      2.0.0
 *
 * @package    FG_Joomla_to_WordPress_Premium
 * @subpackage FG_Joomla_to_WordPress_Premium/public
 */

if ( !class_exists('FG_Joomla_to_WordPress_URL_Rewriting', false) ) {

	/**
	 * URL Rewriting class
	 *
	 * @package    FG_Joomla_to_WordPress_Premium
	 * @subpackage FG_Joomla_to_WordPress_Premium/public
	 * @author     Frédéric GILLES
	 */
	class FG_Joomla_to_WordPress_URL_Rewriting {

		private static $rewrite_rules = array(
			array( 'rule' => '^.*/tags/tag/\d+-(.+)',	'view' => 'tag',		'meta_key' => ''),
			array( 'rule' => '^.*/(\d+)-',				'view' => 'post',		'meta_key' => '_fgj2wp_old_id'),
			array( 'rule' => '^.*/(\d+)(\.html)?$',		'view' => 'post',		'meta_key' => '_fgj2wp_old_id'),
			array( 'rule' => '^.*/view/(\d+)',			'view' => 'post',		'meta_key' => '_fgj2wp_old_id'),
			array( 'rule' => '^.*/category/\d+/(\d+)',	'view' => 'term',		'meta_key' => '_fgj2wp_old_category_id'),
		);

		/**
		 * Initialize the class and set its properties.
		 *
		 * @since    2.0.0
		 */
		public function __construct() {

			$premium_options = get_option('fgj2wpp_options');
			$do_redirect = isset($premium_options['url_redirect']) && !empty($premium_options['url_redirect']);
			$do_redirect = apply_filters('fgj2wpp_do_redirect', $do_redirect);
			if ( $do_redirect ) {
				// Hook on template redirect
				add_action('template_redirect', array($this, 'template_redirect'));
				// for Joomla non SEF URLs
				add_filter('query_vars', array($this, 'add_query_vars'));
				add_action('fgj2wpp_pre_404_redirect', array($this, 'pre_404_redirect'));
			}
		}
		
		/**
		 * Add the query vars
		 *
		 * @param array $vars Query vars
		 * @return array $vars Query vars
		 */
		public function add_query_vars($vars) {
			
			$vars[] = 'id'; // Joomla post ID without URL rewriting
			$vars[] = 'view';
			$vars[] = 'task';
			return $vars;
		}
		
		/**
		 * Redirection to the new URL
		 */
		public function template_redirect() {
			$matches = array();
			do_action('fgj2wpp_pre_404_redirect');
			
			if ( !is_404() ) { // A page is found, don't redirect
				return;
			}
			
			// Optimization to avoid useless database requests: don't redirect images
			if ( preg_match('/\.(jpg|png|gif)$/', $_SERVER['REQUEST_URI']) ) {
				return;
			}
			
			do_action('fgj2wpp_post_404_redirect');
			
			// Process the rewrite rules
			$rewrite_rules = apply_filters('fgj2wpp_rewrite_rules', self::$rewrite_rules);
			// Joomla configured with SEF URLs
			foreach ( $rewrite_rules as $rewrite_rule ) {
				// Note: Can't use filter_input(INPUT_SERVER, 'REQUEST_URI') because of FastCGI side-effect
				// http://php.net/manual/fr/function.filter-input.php#77307
				if ( preg_match('#'.$rewrite_rule['rule'].'#', $_SERVER['REQUEST_URI'], $matches) ) {
					$old_id = $matches[1];
					if ( isset($rewrite_rule['callback']) ) {
						$old_id = $rewrite_rule['callback']($old_id);
					}
					self::redirect($rewrite_rule['meta_key'], $old_id, $rewrite_rule['view']);
				}
			}
			
			// Try to redirect the post by its name
			$this->post_404_redirect($_SERVER['REQUEST_URI']);
		}
		
		/**
		 * Try to redirect the Joomla non SEF URLs
		 */
		public function pre_404_redirect() {
			$matches = array();
			// Joomla configured without SEF URLs: view=article&id=xxx
			$view = get_query_var('view');
			if ( in_array($view, array('article', 'category', 'section')) ) {
				if ( preg_match('/(\d+)/', get_query_var('id'), $matches) ) {
					$old_id = $matches[1];
					switch ( $view ) {
						case 'article':
							self::redirect('_fgj2wp_old_id', $old_id, 'post');
							break;
						case 'category':
							self::redirect('_fgj2wp_old_category_id', $old_id, 'term');
							break;
						case 'section':
							self::redirect('_fgj2wp_old_category_id', 's' . $old_id, 'term');
							break;
					}
				}
			}
			
			// Joomla configured without SEF URLs: task=view&id=xxx
			$task = get_query_var('task');
			if ( $task == 'view' ) {
				if ( preg_match('/(\d+)/', get_query_var('id'), $matches) ) {
					$old_id = $matches[1];
					self::redirect('_fgj2wp_old_id', $old_id, 'post');
				}
			}
		}
		
		/**
		 * Query and redirect to the new URL
		 *
		 * @param string $meta_key Meta Key to search in the postmeta or termmeta table
		 * @param int $old_id Joomla ID
		 * @param string $view post|term
		 */
		public static function redirect($meta_key, $old_id, $view='post') {
			if ( !empty($old_id) ) {
				switch ( $view ) {
					case 'post':
						// Get the post by its old ID
						$known_post_types = array_keys(get_post_types(array('public' => 1)));
						$posts = get_posts(array(
							'meta_key' => $meta_key,
							'meta_value' => $old_id,
							'posts_per_page' => 1,
							'ignore_sticky_posts' => 1,
							'post_type' => $known_post_types,
							'post_status' => 'publish',
						));
						if ( count($posts) > 0 ) {
							self::redirect_to_post($posts[0]);
						}
						break;
					
					case 'term':
					case 'tag':
						if ( $view == 'tag' ) {
							// Search a tag by its name
							$args = array(
								'hide_empty' => false, // also retrieve terms which are not used yet
								'taxonomy' => 'post_tag',
								'slug' => $old_id, // it is a slug and not an ID in this case
							);
						} else {
							// Search a term by its id
							$args = array(
								'hide_empty' => false, // also retrieve terms which are not used yet
								'meta_query' => array(
									array(
									   'key'       => $meta_key,
									   'value'     => $old_id,
									   'compare'   => '='
									)
								)
							);
						}
						$terms = get_terms($args);
						if ( count($terms) > 0 ) {
							$url = get_term_link($terms[0]->term_id);
							//die($url);
							wp_redirect($url, 301);
							wp_reset_query();
							exit;
						}
						break;
				}
				// else continue the normal workflow
			}
		}
		
		/**
		 * Try to redirect the SEF URLs using the post name
		 * 
		 * @since 3.45.0
		 * 
		 * @param string $request_uri Requested URL
		 */
		private function post_404_redirect($request_uri) {
			// Try to find the post by its post name
			$post_name = basename($request_uri);
			$post_name = preg_replace('/\.html$/', '', $post_name); // Remove the .html suffix
			$known_post_types = array_keys(get_post_types(array('public' => 1)));
			$posts = get_posts(array(
				'name' => $post_name,
				'posts_per_page' => 1,
				'ignore_sticky_posts' => 1,
				'post_type' => $known_post_types,
				'post_status' => 'publish',
			));
			if ( count($posts) > 0 ) {
				self::redirect_to_post($posts[0]);
			}
		}
		
		/**
		 * Redirect to the post
		 * 
		 * @since 3.45.0
		 * 
		 * @param WP_Post $post Post
		 */
		private static function redirect_to_post($post) {
			$url = get_permalink($post);
//			die($url);
			wp_redirect($url, 301);
			wp_reset_query();
			exit;
		}
		
	}
}
