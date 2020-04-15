<?php

/**
 * Plugin Name:       My Custom Code
 * Plugin URI:        http://fullworks.net/wordpress-plugins
 * Description:       Custom code
 * Version:           1.0
 * Author:            Fullworks
 * Author URI:        http://fullworks.net/
 *
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}
add_action( 'rest_api_init', 'register_posts_meta_field' );

function register_posts_meta_field() {
	$object_type = 'post';
	$args1 = array( // Validate and sanitize the meta value.
		// Note: currently (4.7) one of 'string', 'boolean', 'integer',
		// 'number' must be used as 'type'. The default is 'string'.
		'type'         => 'string',
		// Shown in the schema for the meta key.
		'description'  => 'A meta key associated with a string meta value.',
		// Return a single value of the type.
		'single'       => true,
		// Show in the WP REST API response. Default: false.
		'show_in_rest' => true,
	);
	register_meta( $object_type, '_yoast_wpseo_title', $args1 );

	$args1 = array( // Validate and sanitize the meta value.
		// Note: currently (4.7) one of 'string', 'boolean', 'integer',
		// 'number' must be used as 'type'. The default is 'string'.
		'type'         => 'string',
		// Shown in the schema for the meta key.
		'description'  => 'A meta key associated with a string meta value.',
		// Return a single value of the type.
		'single'       => true,
		// Show in the WP REST API response. Default: false.
		'show_in_rest' => true,
	);
	register_meta( $object_type, '_yoast_wpseo_metadesc', $args1 );
}
