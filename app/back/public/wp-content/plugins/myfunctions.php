<?php

/**
 * Plugin Name:       My Custom Code
 * Description:       Custom code
 * Version:           1.0
 * Author:            Andrey
 *
 */
if (!defined('WPINC')) {
    die();
}

$types = ['posts', 'pages', 'news'];
$types_to_routes = ['post' => 'posts', 'page' => 'pages', 'news' => 'news'];

// add_custom_fields
add_action('rest_api_init', 'add_custom_fields');
function add_custom_fields()
{
    global $types;
    register_rest_field(
        $types,
        'custom_fields', //New Field Name in JSON RESPONSEs
        [
            'get_callback' => 'get_custom_fields', // custom function name
            'update_callback' => null,
            'schema' => null,
        ]
    );
}
function get_custom_fields($object, $field_name, $request)
{
    global $wpdb;

    $sql = $wpdb->prepare(
        "SELECT postcount AS total FROM " .
            $wpdb->prefix .
            "total WHERE postnum = %s",
        $object['id']
    );
    $val = $wpdb->get_var($sql);

    return ["watch_counter" => $val ?? "0"];
}

// custom_api_call
add_action('rest_api_init', function () {
    register_rest_route('/wp/v2/custom_routes/slug/', "(?P<slug>\S+)", [
        'methods' => 'GET',
        'callback' => 'custom_api_call',
    ]);
});
function custom_api_call(WP_REST_Request $request)
{
    global $types;
    $params = $request->get_params();

    $material_type = slugToRoute($params['slug']);

    $request = new WP_REST_Request('GET', "/wp/v2/{$material_type}");
    $request->set_query_params(['slug' => $params['slug']]);

    // Process the request and get the response
    $response = rest_do_request($request);
    $headers = $response->get_headers();

    if ($headers['X-WP-Total'] == 0) {
        throw new Exception('not found', 404);
    }

    $material = $response->get_data()[0];
    incrementView($material['id']);

    return $material;
}

function slugToRoute($slug)
{
    global $types_to_routes;

    $query = new WP_Query(['post_type' => 'any', 'name' => $slug]);
    $material = $query->get_posts();

    if (count($material) === 0) {
        throw new Exception('not found', 404);
    }

    return $types_to_routes[$material[0]->post_type];
}

function incrementView($id)
{
    global $wpdb;

    $wpdb->query(
        $wpdb->prepare(
            "INSERT INTO " .
                $wpdb->prefix .
                "total (postnum, postcount) VALUES ('%s', 1) 
					ON DUPLICATE KEY UPDATE postcount = postcount + 1",
            $id
        )
    );
}

// sitemap
add_action('rest_api_init', function () {
    register_rest_route('/wp/v2/custom_routes/', "sitemap", [
        'methods' => 'GET',
        'callback' => 'sitemap_call',
    ]);
});
function sitemap_call()
{
    global $wpdb;

    $sql = $wpdb->prepare(
        "SELECT p.post_title, p.post_type as type, p.post_name as slug  FROM " .
            $wpdb->prefix .
            "posts as p WHERE post_type in ('post', 'news', 'page')"
    );
    $val = $wpdb->get_results($sql);
    return $val;
}

// search
add_action('rest_api_init', function () {
    register_rest_route('/wp/v2/custom_routes/', "search", [
        'methods' => 'GET',
        'callback' => 'search',
    ]);
});
function search(WP_REST_Request $request)
{
    global $wpdb;
    $params = $request->get_query_params();

    $key_word = '%' . $params['key_word'] . '%';
    $page = ($params['page'] ?? 1) - 1;
    $limit = $params['limit'] ?? 2;
    $offset = $page * $limit;

    if (empty($key_word)) {
        return [];
    }

    $where = "WHERE post_type in ('post', 'news', 'page') and (
                 p.post_title LIKE '%s' OR p.post_content LIKE '%s' OR p.post_name LIKE '%s'
             )";

    $count_sql = $wpdb->prepare(
        "SELECT COUNT(*) FROM " . $wpdb->prefix . "posts as p ${where}",
        $key_word,
        $key_word,
        $key_word
    );
    $total = (int) $wpdb->get_var($count_sql);

    $sql = $wpdb->prepare(
        "SELECT p.post_title, p.post_type as type, p.post_name as slug, p.post_content as post_content FROM " .
            $wpdb->prefix .
            "posts as p ${where} LIMIT %d, %d",
        $key_word,
        $key_word,
        $key_word,
        $offset,
        $limit
    );

    return [
        'values' => array_map(function ($post) {
            $post_content = strip_tags($post->post_content);
            $post_content = substr($post_content, 0, 340);
            $post_content =
                substr($post_content, 0, strrpos($post_content, ' ')) . " ...";
            $post->post_content = $post_content;
            return $post;
        }, $wpdb->get_results($sql)),
	    'total' => $total
    ];
}

// Install
register_activation_hook(__FILE__, 'my_install');
function my_install()
{
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

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
