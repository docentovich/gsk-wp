<?php

/**
 * Tags module
 *
 * @link       https://www.fredericgilles.net/fg-joomla-to-wordpress/
 * @since      2.0.0
 *
 * @package    FG_Joomla_to_WordPress_Premium
 * @subpackage FG_Joomla_to_WordPress_Premium/admin
 */

if ( !class_exists('FG_Joomla_to_WordPress_Tags', false) ) {

	/**
	 * Tags class
	 *
	 * @package    FG_Joomla_to_WordPress_Premium
	 * @subpackage FG_Joomla_to_WordPress_Premium/admin
	 * @author     Frédéric GILLES
	 */
	class FG_Joomla_to_WordPress_Tags {

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
		 * Import the tags related to a post
		 * 
		 * @param array $newpost WordPress post
		 * @param array $joomla_post Joomla post
		 * @return array WordPress post
		 */
		public function import_tags($newpost, $joomla_post) {
			if ( version_compare($this->plugin->joomla_version, '3.1', '>=') ) {
				// the tags are available for Joomla ≥ 3.1 only
				$tags = $this->plugin->get_tags($joomla_post['id']);
				$this->plugin->import_tags($tags, 'post_tag');
				if ( !empty($tags) ) {
					if ( !isset($newpost['tags_input']) ) {
						$newpost['tags_input'] = array();
					}
					$newpost['tags_input'] = array_merge($newpost['tags_input'], $tags);
				}
			}
			return $newpost;
		}
		
		/**
		 * Display the number of imported tags
		 * 
		 */
		public function display_tags_count() {
			if ( version_compare($this->plugin->joomla_version, '3.1', '>=') ) {
				// the tags are available for Joomla ≥ 3.1 only
				$this->plugin->display_admin_notice(sprintf(_n('%d tag imported', '%d tags imported', $this->plugin->tags_count, $this->plugin->get_plugin_name()), $this->plugin->tags_count));
			}
		}
		
		/**
		 * Get a WordPress tag that matches a Joomla URL
		 * 
		 * @since 3.19.0
		 * 
		 * @param $term WP_Term WordPress term | null
		 * @param string $url URL
		 * @return WP_Term WordPress term | null
		 */
		public function get_tag_from_joomla_url($term, $url) {
			if ( !$term ) {
				$matches = array();

				// Try to find tags that match the patterns /tag/XXX or /tag/DD-XXX
				if ( preg_match('#/tag/(\d+-)?(.*)#', $url, $matches) ) {
					$term_slug = $matches[2];
					$terms = get_terms( array(
						'slug' => $term_slug,
						'hide_empty' => false,
					) );
					if ( count($terms) > 0 ) {
						$term = $terms[0];
					}
				}
			}
			return $term;
		}
		
	}
}
