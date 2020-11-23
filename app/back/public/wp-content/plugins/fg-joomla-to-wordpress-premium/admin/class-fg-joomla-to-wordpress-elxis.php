<?php
/**
 * Elxis
 *
 * @link       https://www.fredericgilles.net/fg-joomla-to-wordpress/
 * @since      3.5.0
 *
 * @package    FG_Joomla_to_WordPress_Premium
 * @subpackage FG_Joomla_to_WordPress_Premium/admin
 */

if ( !class_exists('FG_Joomla_to_WordPress_Elxis', false) ) {

	/**
	 * Elxis features
	 *
	 * @package    FG_Joomla_to_WordPress_Premium
	 * @subpackage FG_Joomla_to_WordPress_Premium/admin
	 * @author     Frédéric GILLES
	 */
	class FG_Joomla_to_WordPress_Elxis extends FG_Joomla_to_WordPress_Joomla10 {

		/**
		 * Modify the query for get_posts
		 * 
		 * @since      3.5.0
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
			if ( $this->plugin->column_exists('content', 'seotitle') ) {
				$sql = str_replace('p.alias', 'IF(p.seotitle <> "", p.seotitle, p.title) AS alias', $sql);
				$sql = str_replace('c.alias', 'c.name', $sql);
				$sql = str_replace('p.`fulltext`', 'p.maintext AS `fulltext`', $sql);
				
				if ( !$this->plugin->column_exists('content', 'state') ) {
					// Elxis 4
					$sql = str_replace('p.state,', '1 AS state,', $sql);
					$sql = str_replace('p.attribs', 'p.params AS attribs', $sql);
					$sql = str_replace('p.metakey', 'p.metakeys AS metakey', $sql);
					$sql = str_replace('p.metadesc', '"" AS metadesc', $sql);
					$sql = str_replace('p.access', 'alevel AS access', $sql);
					$sql = str_replace('p.images', 'image AS images', $sql);
					$sql = str_replace('p.created_by_alias', 'created_by_name AS created_by_alias', $sql);
					$sql = preg_replace('/WHERE p\.state.*AND p\.id/s', 'WHERE p.id', $sql);
				}
			}
			return $sql;
		}
		
		/**
		 * Modify the query for get_categories
		 * 
		 * @since 3.56.0
		 * 
		 * @param string $sql SQL
		 * @param string $prefix Tables prefix
		 * @return string SQL
		 */
		public function get_categories_sql($sql, $prefix) {
			if ( $this->plugin->column_exists('categories', 'seotitle') && !$this->plugin->table_exists('sections') ) {
				// Elxis 4
				$sql = preg_replace('/SELECT.*$/m', "SELECT c.catid AS id, c.title, c.title AS name, c.description, c.parent_id, '' AS date", $sql);
				$sql = preg_replace('/INNER JOIN.*$/m', '', $sql);
				$sql = str_replace('c.id', 'c.catid', $sql);
			}
			return $sql;
		}
		
		/**
		 * Modify the query for get_users
		 * 
		 * @since 3.56.0
		 * 
		 * @param string $sql SQL
		 * @return string SQL
		 */
		public function get_users_sql($sql) {
			if ( $this->plugin->column_exists('users', 'uid') ) {
				// Elxis 4
				$sql = str_replace('u.id,', 'u.uid AS id,', $sql);
				$sql = str_replace('u.id', 'u.uid', $sql);
				$sql = str_replace('u.name', 'CONCAT(u.firstname, u.lastname) AS name', $sql);
				$sql = str_replace('u.username', 'u.uname AS username', $sql);
				$sql = str_replace('u.password', 'u.pword AS password', $sql);
				$sql = str_replace('u.usertype', 'u.groupname AS usertype', $sql);
			}
			return $sql;
		}
		
		/**
		 * Modify the query for get_menus_count
		 * 
		 * @since 3.56.0
		 * 
		 * @param string $sql SQL
		 * @return string SQL
		 */
		public function get_menus_count_sql($sql) {
			if ( $this->plugin->column_exists('menu', 'menu_type') ) {
				// Elxis 4
				$sql = str_replace('m.type', 'm.menu_type', $sql);
			}
			return $sql;
		}
		
		/**
		 * Modify the query for get_menus
		 * 
		 * @since 3.56.0
		 * 
		 * @param string $sql SQL
		 * @return string SQL
		 */
		public function get_menus_sql($sql) {
			if ( $this->plugin->column_exists('menu', 'menu_type') ) {
				// Elxis 4
				$sql = str_replace('m.id,', 'm.menu_id AS id,', $sql);
				$sql = str_replace('m.id', 'm.menu_id', $sql);
				$sql = str_replace('m.name,', 'm.title AS name,', $sql);
				$sql = str_replace('m.name', 'm.title', $sql);
				$sql = str_replace('m.type,', 'm.menu_type AS type,', $sql);
				$sql = str_replace('m.type', 'm.menu_type', $sql);
				$sql = str_replace('m.menutype,', 'm.collection AS menutype,', $sql);
				$sql = str_replace('m.menutype', 'm.collection', $sql);
				$sql = str_replace('m.checked_out_time', '"" AS checked_out_time', $sql);
				$sql = str_replace('m.parent', 'm.parent_id AS parent', $sql);
				$sql = str_replace('m.params', '"" AS params', $sql);
			}
			return $sql;
		}
		
		/**
		 * Modify the query for get_modules
		 * 
		 * @since 3.56.0
		 * 
		 * @param string $sql SQL
		 * @return string SQL
		 */
		public function get_modules_sql($sql) {
			if ( $this->plugin->column_exists('modules', 'pubdate') ) {
				// Elxis 4
				$sql = str_replace('m.checked_out_time', 'm.pubdate AS checked_out_time', $sql);
			}
			return $sql;
		}
		
	}
}
