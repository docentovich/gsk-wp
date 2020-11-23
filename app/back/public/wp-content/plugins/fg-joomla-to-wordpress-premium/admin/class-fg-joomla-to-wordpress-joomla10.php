<?php

/**
 * Joomla 1.0 / Mambo
 *
 * @link       https://www.fredericgilles.net/fg-joomla-to-wordpress/
 * @since      2.0.0
 *
 * @package    FG_Joomla_to_WordPress_Premium
 * @subpackage FG_Joomla_to_WordPress_Premium/admin
 */

if ( !class_exists('FG_Joomla_to_WordPress_Joomla10', false) ) {

	/**
	 * Joomla 1.0 features
	 *
	 * @package    FG_Joomla_to_WordPress_Premium
	 * @subpackage FG_Joomla_to_WordPress_Premium/admin
	 * @author     Frédéric GILLES
	 */
	class FG_Joomla_to_WordPress_Joomla10 {

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
		 * Modify the query for get_sections
		 * 
		 * @param string $sql
		 * @return string
		 */
		public function get_sections_sql($sql) {
			if ( version_compare($this->plugin->joomla_version, '1.0', '<=') ) {
				// the "alias" field doesn't exist in Joomla 1.0
				$sql = str_replace("IF(s.alias <> '', s.alias, s.name)", 's.name', $sql);
			}
			return $sql;
		}

		/**
		 * Modify the query for get_categories
		 * 
		 * @param string $sql SQL
		 * @param string $prefix Tables prefix
		 * @return string SQL
		 */
		public function get_categories_sql($sql, $prefix) {
			if ( version_compare($this->plugin->joomla_version, '1.0', '<=') ) {
				// the "alias" field doesn't exist in Joomla 1.0
				$sql = str_replace("IF(c.alias <> '', c.alias, c.name)", 'c.name', $sql);
			}
			return $sql;
		}

		/**
		 * Modify the query for get_posts
		 * 
		 * @param string $sql SQL
		 * @param string $prefix Tables prefix
		 * @param string $extra_cols Extra columns
		 * @param string $extra_joins Extra joins
		 * @param int $last_joomla_id Last Joomla ID
		 * @param int $limit Limit
		 * @return string SQL
		 */
		public function get_posts_sql($sql, $prefix, $extra_cols, $extra_joins, $last_joomla_id, $limit) {
			if ( version_compare($this->plugin->joomla_version, '1.0', '<=') ) {
				$sql = str_replace('p.alias', 'IF(p.title_alias <> "", p.title_alias, p.title) AS alias', $sql);
				$sql = str_replace('c.alias', 'c.name', $sql);
			}
			return $sql;
		}

		/**
		 * Add images col in the get_posts request (for Joomla 1.0)
		 *
		 * @param string $cols
		 * @return string Cols separating by commas (with a comma at start)
		 */
		public function add_images_col_in_get_posts($cols) {
			if ( version_compare($this->plugin->joomla_version, '1.0', '<=') ) {
				$cols .= ', p.images';
			}
			return $cols;
		}

		/**
		 * Process {mosimage} field (for Joomla 1.0)
		 *
		 * @param array $post Post
		 * @return array Post
		 */
		public function process_mosimage($post) {
			if ( version_compare($this->plugin->joomla_version, '1.0', '<=') && !$this->plugin->plugin_options['skip_media'] ) {
				$images_string = str_replace("\r\n", "\n", $post['images']); // Remove empty lines
				$images = explode("\n", $images_string);
				foreach ( $images as $image ) {
					$image_tag = $this->image_tag($image);
					if ( !empty($image_tag) ) {
						if ( preg_match('/{mosimage.*?}/', $post['introtext']) ) {
							$post['introtext'] = preg_replace('/{mosimage.*?}/', $image_tag, $post['introtext'], 1);
						} elseif ( preg_match('/{mosimage.*?}/', $post['fulltext']) ) {
							$post['fulltext'] = preg_replace('/{mosimage.*?}/', $image_tag, $post['fulltext'], 1);
						}
					}
				}
			}
			return $post;
		}
		
		/**
		 * Process featured image (for Joomla 1.0)
		 *
		 * @since 3.24.0
		 * 
		 * @param array $featured_image_and_post [Featured image, Post]
		 * @return array [Featured image, Post]
		 */
		public function process_featured_image($featured_image_and_post) {
			list($featured_image, $post) = $featured_image_and_post;
			if ( version_compare($this->plugin->joomla_version, '1.0', '<=') && !$this->plugin->plugin_options['skip_media'] ) {
				$images_string = str_replace("\r\n", "\n", $post['images']); // Remove empty lines
				$images = explode("\n", $images_string);
				if ( count($images) > 0 ) {
					$featured_image = $this->image_tag($images[0]); // Take the first image as the featured image
				}
			}
			return array($featured_image, $post);
		}
		
		/**
		 * Build the <img> tag
		 *
		 * @since 3.24.0
		 * 
		 * @param string $image Image data
		 * @return string Image tag
		 */
		private function image_tag($image) {
			$image_tag = '';
			
			$image_params = explode ('|', $image);
			$image_name = array_key_exists(0, $image_params)? $image_params[0] : '';
			$image_align = array_key_exists(1, $image_params)? $image_params[1] : '';
			$image_alt = array_key_exists(2, $image_params)? $image_params[2] : '';
//			$image_legend = array_key_exists(4, $image_params)? $image_params[4] : '';
			if ( !empty($image_name) && (strlen($image_name) > 1) ) {
				$alignment = !empty($image_align)? ' align="' . $image_align . '"' : '';
				$image_tag = '<img src="images/stories/' . $image_name . '" alt="'. $image_alt . '"' . $alignment . ' />';
			}
			
			return $image_tag;
		}
		
		/**
		 * Process {mospagebreak} field (for Joomla 1.0)
		 *
		 * @param array $post Post
		 * @return array Post
		 */
		public function process_mospagebreak($post) {
			if ( version_compare($this->plugin->joomla_version, '1.0', '<=') ) {
				$post['introtext'] = preg_replace('/{mospagebreak.*?}/', '<!--nextpage-->', $post['introtext']);
				$post['fulltext'] = preg_replace('/{mospagebreak.*?}/', '<!--nextpage-->', $post['fulltext']);
			}
			return $post;
		}
		
		/**
		 * Import Joomla static article into a page (for Joomla 1.0)
		 *
		 * @param array $new_post New post
		 * @param array $post Joomla Post
		 * @return array Post
		 */
		public function process_static_pages($new_post, $post) {
			if ( version_compare($this->plugin->joomla_version, '1.0', '<=') ) {
				if ( empty($post['catid']) && ($new_post['post_type'] == 'post') ) {
					$new_post['post_type'] = 'page';
				}
			}
			return $new_post;
		}

		/**
		 * Add the Joomla 1.0 menus in the menu query
		 * 
		 * @param string $extra_criteria Extra criteria
		 * @return string Extra criteria
		 */
		public function add_menus_extra_criteria($extra_criteria) {
			$sql = "
					OR (m.type = 'components'
						AND m.link LIKE '%option=com_frontpage%'
						)
					OR (m.type = 'content_item_link'
						AND m.link LIKE '%option=com_content&task=view&id=%'
						)
					OR (m.type = 'content_blog_category'
						)
			";
			return $extra_criteria . $sql;
		}
		
		/**
		 * Get the menu item data (object_id, type, url, object)
		 * 
		 * @param array $menu Menu item row
		 * @param string $post_type Post type
		 * @return array Menu item
		 */
		public function get_menu_item($menu_item, $menu, $post_type) {
			$matches = array();
			if ( !is_null($menu_item) ) {
				return $menu_item;
			}
			$menu_item_object_id = 0;
			$menu_item_type = '';
			$menu_item_url = '';
			$menu_item_object = '';
			switch ( $menu['type'] ) {
				case 'content_item_link': // Joomla 1.0 articles
					if ( preg_match('/task=view(&.*)?&id=(\d+)/', $menu['link'], $matches) ) {
						// post
						$menu_item_type = 'post_type';
						$menu_item_object = 'post';
						$posts = get_posts(array(
							'posts_per_page'  => 1,
							'meta_key'        => '_fgj2wp_old_id',
							'meta_value'      => $matches[2],
						));
						if ( is_array($posts) && count($posts) > 0 ) {
							$menu_item_object_id = $posts[0]->ID;
						} else {
							return;
						}
					} else {
						return;
					}
					break;
				
				case 'content_blog_category': // Joomla 1.0 categories
					if ( preg_match('/task=blog(category|section)(&.*)?&id=(\d+)/', $menu['link'], $matches) ) {
						$menu_item_type = 'taxonomy';
						$menu_item_object = 'category';
						
						$taxonomy = $matches[1];
						$jterm_id = $matches[3];
						if ( $taxonomy == 'section' ) {
							$jterm_id = 's' . $jterm_id;
						}
						if ( array_key_exists($jterm_id, $this->plugin->imported_categories) ) {
							$menu_item_object_id = $this->plugin->imported_categories[$jterm_id];
						} else {
							return;
						}
					} else {
						return;
					}
					break;
				
				case 'components': // Joomla 1.0 home page
					if ( preg_match('/option=com_frontpage/', $menu['link'], $matches) ) {
						// front page
						$menu_item_type = 'custom';
						$menu_item_object = 'custom';
						$menu_item_url = home_url();
						
					} else {
						return;
					}
					break;
				
				default: return;
			}
			
			return array(
				'object_id'	=> $menu_item_object_id,
				'type'		=> $menu_item_type,
				'url'		=> $menu_item_url,
				'object'	=> $menu_item_object,
			);
		}
		
	}
}
