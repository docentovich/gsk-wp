<?php

/**
 * Mambo
 *
 * @link       https://www.fredericgilles.net/fg-joomla-to-wordpress/
 * @since      3.50.0
 *
 * @package    FG_Joomla_to_WordPress_Premium
 * @subpackage FG_Joomla_to_WordPress_Premium/admin
 */

if ( !class_exists('FG_Joomla_to_WordPress_Mambo', false) ) {

	/**
	 * Mambo features
	 *
	 * @package    FG_Joomla_to_WordPress_Premium
	 * @subpackage FG_Joomla_to_WordPress_Premium/admin
	 * @author     Frédéric GILLES
	 */
	class FG_Joomla_to_WordPress_Mambo extends FG_Joomla_to_WordPress_Joomla10 {
		
		/**
		 * Test if the Mambo articles table exists
		 * 
		 * @param boolean $connection_ok
		 * @return boolean
		 */
		public function test_database_connection($connection_ok) {
			if ( $this->plugin->table_exists('articles') ) {
				// Mambo 4.0
				$this->plugin->display_admin_notice(__('Connected with success to the Mambo database', $this->plugin->get_plugin_name()));
				$connection_ok = true;
			}
			return $connection_ok;
		}
		
		/**
		 * Modify the query for get_categories
		 * 
		 * @param string $sql SQL
		 * @param string $prefix Tables prefix
		 * @return string SQL
		 */
		public function get_categories_sql($sql, $prefix) {
			if ( version_compare($this->plugin->joomla_version, '1.0', '<') ) {
				if ( !$this->plugin->table_exists('sections') ) {
					// Mambo 4.0
					$sql = str_replace('c.id,', 'c.categoryid AS id,', $sql);
					$sql = str_replace('c.id', 'c.categoryid', $sql);
					$sql = str_replace('c.title,', 'c.categoryname AS title,', $sql);
					$sql = str_replace('c.name', 'c.categoryname', $sql);
					$sql = str_replace('c.description', "'' AS description", $sql);
					$sql = str_replace("CONCAT('s', s.id) AS parent_id", '0 AS parent_id', $sql);
					$sql = str_replace("INNER JOIN ${prefix}sections AS s ON s.id = c.section", '', $sql);
				}
			}
			return $sql;
		}

		/**
		 * Modify the query for get_categories_count
		 * 
		 * @param string $sql SQL
		 * @param string $prefix Tables prefix
		 * @return string SQL
		 */
		public function get_categories_count_sql($sql, $prefix) {
			if ( version_compare($this->plugin->joomla_version, '1.0', '<') ) {
				if ( !$this->plugin->table_exists('sections') ) {
					$sql = "
						SELECT COUNT(*) AS nb
						FROM ${prefix}categories c
					";
				}
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
			if ( version_compare($this->plugin->joomla_version, '1.0', '<') ) {
				
				// Mambo 4.0
				if ( !$this->plugin->table_exists('content') && $this->plugin->table_exists('articles') ) {
					$archived_post_criteria = '';
					if ( $this->plugin->plugin_options['archived_posts'] == 'not_imported' ) {
						$archived_post_criteria = 'WHERE p.archived = 0';
					} else {
						$archived_post_criteria = 'WHERE p.archived >= 0';
					}
					$sql = "
						SELECT DISTINCT p.artid AS id, 'content' AS type, p.title, p.title AS alias, '' AS introtext, p.content AS `fulltext`, p.approved AS state, p.catid, '' AS modified, p.`date`, '' AS attribs, '' AS metakey, '' AS metadesc, '' AS access, p.ordering
						$extra_cols
						FROM ${prefix}articles p
						$extra_joins
						$archived_post_criteria
						AND p.artid > '$last_joomla_id'
						ORDER BY p.artid
						LIMIT $limit
					";
					$sql = str_replace('p.images', "'' AS images", $sql);
					$sql = str_replace('p.created_by,', 'p.userID AS created_by,', $sql);
					$sql = str_replace('p.created_by_alias', 'p.author AS created_by_alias', $sql);
				}
			}
			return $sql;
		}

		/**
		 * Modify the query for get_posts_count
		 * 
		 * @param string $sql SQL
		 * @param string $prefix Tables prefix
		 * @return string SQL
		 */
		public function get_posts_count_sql($sql, $prefix) {
			if ( version_compare($this->plugin->joomla_version, '1.0', '<') ) {
				// Mambo 4.0
				if ( !$this->plugin->table_exists('content') && $this->plugin->table_exists('articles') ) {
					$archived_post_criteria = '';
					if ( $this->plugin->plugin_options['archived_posts'] == 'not_imported' ) {
						$archived_post_criteria = 'WHERE p.archived = 0';
					} else {
						$archived_post_criteria = 'WHERE p.archived >= 0';
					}
					$sql = "
						SELECT COUNT(*) AS nb
						FROM ${prefix}articles p
						$archived_post_criteria
					";
				}
			}
			return $sql;
		}

		/**
		 * Modify the query for get_authors
		 * 
		 * @param string $sql SQL
		 * @return string SQL
		 */
		public function get_authors_sql($sql) {
			if ( version_compare($this->plugin->joomla_version, '1.0', '<') ) {
				// Mambo 4.0
				if ( !$this->plugin->table_exists('content') && $this->plugin->table_exists('articles') ) {
					$sql = str_replace('content c ON c.created_by = u.id', 'articles c ON c.userID = u.id', $sql);
					$sql = str_replace('u.registerDate', "'' AS registerDate", $sql);
				}
			}
			return $sql;
		}

		/**
		 * Modify the query for get_users
		 * 
		 * @param string $sql SQL
		 * @return string SQL
		 */
		public function get_users_sql($sql) {
			if ( version_compare($this->plugin->joomla_version, '1.0', '<') ) {
				// Mambo 4.0
				if ( !$this->plugin->table_exists('content') && $this->plugin->table_exists('articles') ) {
					$sql = str_replace('content c ON c.created_by = u.id', 'articles c ON c.userID = u.id', $sql);
					$sql = str_replace('u.registerDate', "'' AS registerDate", $sql);
				}
			}
			return $sql;
		}

		/**
		 * Modify the query for get_last_joomla_article_id_sql
		 * 
		 * @param string $sql SQL
		 * @param string $prefix Tables prefix
		 * @return string SQL
		 */
		public function get_last_joomla_article_id_sql($sql, $prefix) {
			if ( version_compare($this->plugin->joomla_version, '1.0', '<') ) {
				// Mambo 4.0
				if ( !$this->plugin->table_exists('content') && $this->plugin->table_exists('articles') ) {
					$sql = "
						SELECT max(artid) AS max_id
						FROM ${prefix}articles
					";
				}
			}
			return $sql;
		}

		/**
		 * Modify the query for get_all_menus
		 * 
		 * @param string $sql SQL
		 * @return string SQL
		 */
		public function get_all_menus_sql($sql) {
			if ( version_compare($this->plugin->joomla_version, '1.0', '<') ) {
				// Mambo 4.0
				if ( !$this->plugin->column_exists('type', 'menu') ) {
					// To develop
					$sql = '';
				}
			}
			return $sql;
		}

		/**
		 * Modify the query for get_menus
		 * 
		 * @param string $sql SQL
		 * @return string SQL
		 */
		public function get_menus_sql($sql) {
			if ( version_compare($this->plugin->joomla_version, '1.0', '<') ) {
				// Mambo 4.0
				if ( !$this->plugin->column_exists('type', 'menu') ) {
					// To develop
					$sql = '';
				}
			}
			return $sql;
		}

		/**
		 * Modify the query for get_menus
		 * 
		 * @param string $sql SQL
		 * @return string SQL
		 */
		public function get_menus_count_sql($sql) {
			if ( version_compare($this->plugin->joomla_version, '1.0', '<') ) {
				// Mambo 4.0
				if ( !$this->plugin->column_exists('type', 'menu') ) {
					// To develop
					$sql = '';
				}
			}
			return $sql;
		}

	}
}
