<?php

/**
 * Joomla 2.5
 *
 * @link       https://www.fredericgilles.net/fg-joomla-to-wordpress/
 * @since      2.0.0
 *
 * @package    FG_Joomla_to_WordPress_Premium
 * @subpackage FG_Joomla_to_WordPress_Premium/admin
 */

if ( !class_exists('FG_Joomla_to_WordPress_Joomla25', false) ) {

	/**
	 * Joomla 2.5 features
	 *
	 * @package    FG_Joomla_to_WordPress_Premium
	 * @subpackage FG_Joomla_to_WordPress_Premium/admin
	 * @author     Frédéric GILLES
	 */
	class FG_Joomla_to_WordPress_Joomla25 {

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
		 * Add images col in the get_posts request (for Joomla 1.0)
		 *
		 * @param string $cols
		 * @return string Cols separating by commas (with a comma at start)
		 */
		public function add_images_col_in_get_posts($cols) {
			if ( version_compare($this->plugin->joomla_version, '2.5', '>=') ) {
				$cols .= ', p.images';
			}
			return $cols;
		}

		/**
		 * Process featured image (for Joomla 2.5)
		 *
		 * @param array $featured_image_and_post [Featured image, Post]
		 * @return array [Featured image, Post]
		 */
		public function process_featured_image($featured_image_and_post) {
			list($featured_image, $post) = $featured_image_and_post;
			if ( version_compare($this->plugin->joomla_version, '2.5', '>=') && !$this->plugin->plugin_options['skip_media']
					&& isset($post['images']) && isset($post['attribs']) ) {
				
				$image_data = json_decode($post['images'], true);
				
				// Attribs
				$post_attribs = $this->plugin->convert_post_attribs_to_array($post['attribs']);
				$intro_image = $this->image_tag('intro', $image_data);
				$full_text_image = $this->image_tag('fulltext', $image_data);
				
				// Determine the featured image
				if ( empty($featured_image) && in_array($this->plugin->plugin_options['featured_image'], array('fulltext', 'intro')) ) {
					if ( $this->plugin->plugin_options['featured_image'] == 'fulltext' ) {
						// Fulltext image in priority
						if ( !empty($full_text_image) ) {
							$featured_image = $full_text_image;
						} else {
							$featured_image = $intro_image;
						}
					} else {
						// Intro image in priority
						if ( !empty($intro_image) ) {
							$featured_image = $intro_image;
						} else {
							$featured_image = $full_text_image;
						}
					}
				}
				
				/* If intro_text is included in the extract
				 *  => the full text image goes into the full text
				 * Else (intro_text is included in the content)
				 *  => the full text image goes into the intro text
				 */
				if ( $full_text_image ) {
					if ( ! $this->plugin->plugin_options['remove_first_image'] || ($featured_image != $full_text_image) ) {
						$show_intro = (is_array($post_attribs) && array_key_exists('show_intro', $post_attribs))? $post_attribs['show_intro'] : '';
						if ( (($this->plugin->plugin_options['introtext'] == 'in_excerpt') && ($show_intro !== '1'))
							|| (($this->plugin->plugin_options['introtext'] == 'in_excerpt_and_content') && ($show_intro == '0')) ) {
							$post['fulltext'] = $full_text_image . $post['fulltext'];
						} else {
							$post['introtext'] = $full_text_image . $post['introtext'];
						}
					}
				}
			}
			return array($featured_image, $post);
		}
		
		/**
		 * Build the <img> tag
		 *
		 * @param string $location (intro | fulltext)
		 * @param array $image_data Image data
		 * @return string Image tag
		 */
		private function image_tag($location, $image_data) {
			$image_tag = '';
			if ( !empty($image_data) ) {
				$src_field = 'image_' . $location;
				$src = isset($image_data[$src_field])? $image_data[$src_field] : '';
				$float_field = 'float_' . $location;
				$float = isset($image_data[$float_field])? $image_data[$float_field] : '';
				$alt_field = 'image_' . $location . '_alt';
				$alt = isset($image_data[$alt_field])? esc_attr($image_data[$alt_field]) : '';
				$caption_field = 'image_' . $location . '_caption';
				$caption = isset($image_data[$caption_field]) && !empty($image_data[$caption_field])? ' class="caption" title="' . esc_attr($image_data[$caption_field]) . '"' : '';
				$alignment = !empty($float)? ' align="' . $float . '"' : '';
				if ( !empty($src) ) {
					$image_tag = '<img src="' . $src . '" alt="'. $alt . '"' . $caption . $alignment . ' />';
				}
			}			
			return $image_tag;
		}
		
	}
}
