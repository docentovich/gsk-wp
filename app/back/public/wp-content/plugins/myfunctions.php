<?php

/**
 * Plugin Name:       My Custom Code
 * Description:       Custom code
 * Version:           1.0
 * Author:            Andrey
 *
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'rest_api_init', 'add_custom_fields' );
function add_custom_fields() {
	register_rest_field(
		[ 'post', 'page', 'news' ],
		'custom_fields', //New Field Name in JSON RESPONSEs
		array(
			'get_callback'    => 'get_custom_fields', // custom function name
			'update_callback' => null,
			'schema'          => null,
		)
	);
}

function get_custom_fields( $object, $field_name, $request ) {
	global $wpdb;

	$sql = $wpdb->prepare( "SELECT postcount AS total FROM " . $wpdb->prefix . "total
			WHERE postnum = %s", $object['id'] );
	$val = $wpdb->get_var( $sql );

	return [ "watch_counter" => $val ?? "0" ];
}

add_filter( 'rest_pre_echo_response',
	/**
	 * @param $response Object
	 * @param $object
	 * @param $request WP_REST_Request
	 */
	function ( $response, $object, $request ) {
		global $wpdb;

		$type = array_pop( explode( '/', $request->get_route() ) );
		$slug = $request->get_param('slug')[0];


		if ( isset($slug) && $type === 'news' && is_array( $response ) && $response[0]['id'] ) {
			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO " . $wpdb->prefix . "total (postnum, postcount) VALUES ('%s', 1) 
						ON DUPLICATE KEY UPDATE postcount = postcount + 1",
					$response[0]['id']
				)
			);
		}

		return $response;
	}, 10, 3 );


function my_install() {
	global $wpdb;

	$table_name = $wpdb->prefix . 'total';

	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
	  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
	  `postnum` varchar(255) NOT NULL,
	  `postcount` int(11) NOT NULL DEFAULT '0',
	  UNIQUE KEY `postnum` (`postnum`),
	  PRIMARY KEY  (id)
	) $charset_collate;
	";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

register_activation_hook( __FILE__, 'my_install' );
