<?php
//Only run this file through WordPress. If someone accesses it directly, block it.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
//This endpoint is public because it only returns published public content. 
// If it returned user-specific or private data, a permission callback with capability checks would have to be used.
function protip_register_rest_routes() {
    register_rest_route(
        'protip-votes/v1',
        '/protips',
        array(
            'methods'             => 'GET',
            'callback'            => 'protip_rest_get_protips',
            'permission_callback' => function () {
    return is_user_logged_in();
},
        )
    );
}
// Register custom REST API routes when WordPress initialises the REST API system.
add_action( 'rest_api_init', 'protip_register_rest_routes' );
//Get the requested limit from the REST API URL, clean it into a positive integer,
//default it to 12 if it’s missing/invalid, and cap it at 18 so the endpoint can’t return too many posts at once.
function protip_rest_get_protips( WP_REST_Request $request ) {
    $limit = absint( $request->get_param( 'limit' ) );

    if ( $limit < 1 ) {
        $limit = 12;
    }

    if ( $limit > 18 ) {
        $limit = 18;
    }
//Get the topic from the REST API URL, clean it into a safe taxonomy slug, and use it to filter Pro-tips by topic
    $topic = sanitize_title( $request->get_param( 'topic' ) );

    $args = array(
        'post_type'      => 'protip',
        'post_status'    => 'publish',
        'posts_per_page' => $limit,
        'no_found_rows'  => true,
    );

    if ( ! empty( $topic ) ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'protip_topic',
                'field'    => 'slug',
                'terms'    => $topic,
            ),
        );
    }
//WP_Query for getting the pro-tips to return in the REST API response, using the same query arguments as the shortcode
    $query = new WP_Query( $args );

    $items = array();

    while ( $query->have_posts() ) {
        $query->the_post();

        $items[] = array(
            'id'         => get_the_ID(),
            'title'      => get_the_title(),
            'excerpt'    => get_the_excerpt(),
            'link'       => get_permalink(),
            'vote_count' => protip_get_vote_count( get_the_ID() ),
        );
    }
//when done with the custom query, ask WordPress to restore the original page/post context.
    wp_reset_postdata();

    return new WP_REST_Response(
        array(
            'items' => $items,
        ),
        200 //request was successful
    );
}

function protip_get_vote_count( $protip_id ) {
    global $wpdb;
    //absint converts a value to non-negative integer and returns 0 for non-numeric values
    //it is a good way to sanitize the input and ensure it is a valid integer.
    $protip_id = absint( $protip_id );
    $table_name = $wpdb->prefix . 'protip_votes';

      if ( ! $protip_id ) {
        return 0;
    }
//custom SQL + $wpdb->prepare() in the REST endpoint
    $vote_count = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE protip_id = %d",
            $protip_id
        )
    );

    return absint( $vote_count );
}
