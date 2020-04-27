<?php

/**
 * Plugin Name:       My Custom Code
 * Description:       Custom code
 * Version:           1.0
 * Author:            Andrey
 *
 */
//if ( ! defined( 'WPINC' ) ) {
//	die;
//}
//add_action( 'rest_api_init', 'register_posts_meta_field' );
//
//function register_posts_meta_field() {
//	$object_type = 'post';
//	$args1 = array( // Validate and sanitize the meta value.
//		// Note: currently (4.7) one of 'string', 'boolean', 'integer',
//		// 'number' must be used as 'type'. The default is 'string'.
//		'type'         => 'string',
//		// Shown in the schema for the meta key.
//		'description'  => 'A meta key associated with a string meta value.',
//		// Return a single value of the type.
//		'single'       => true,
//		// Show in the WP REST API response. Default: false.
//		'show_in_rest' => true,
//	);
//	register_meta( $object_type, '_yoast_wpseo_title', $args1 );
//
//	$args1 = array( // Validate and sanitize the meta value.
//		// Note: currently (4.7) one of 'string', 'boolean', 'integer',
//		// 'number' must be used as 'type'. The default is 'string'.
//		'type'         => 'string',
//		// Shown in the schema for the meta key.
//		'description'  => 'A meta key associated with a string meta value.',
//		// Return a single value of the type.
//		'single'       => true,
//		// Show in the WP REST API response. Default: false.
//		'show_in_rest' => true,
//	);
//	register_meta( $object_type, '_yoast_wpseo_metadesc', $args1 );
//}

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
	return [ "watch_counter" => A3Rev\PageViewsCount\A3_PVC::pvc_fetch_post_total( $object['id'] ) ?? "0" ];
}

add_filter( 'rest_pre_echo_response',
	/**
	 * @param $response Object
	 * @param $object
	 * @param $request WP_REST_Request
	 */
	function ( $response, $object, $request ) {
		if ( $response['id'] && in_array( $response['type'], [ 'news', 'posts', 'pages' ] ) ) {
			A3Rev\PageViewsCount\A3_PVC::pvc_stats_update( $response['id'] );
		}

		return $response;
	}, 10, 3 );
