<?php
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
            'permission_callback' => '__return_true',
        )
    );
}
add_action( 'rest_api_init', 'protip_register_rest_routes' );

function protip_rest_get_protips( WP_REST_Request $request ) {
    $limit = absint( $request->get_param( 'limit' ) );

    if ( $limit < 1 ) {
        $limit = 12;
    }

    if ( $limit > 18 ) {
        $limit = 18;
    }

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

    wp_reset_postdata();

    return new WP_REST_Response(
        array(
            'items' => $items,
        ),
        200
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
