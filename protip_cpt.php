<?php
//Only run this file through WordPress. If someone accesses it directly, block it.
if (! defined('ABSPATH')) {
    exit;
}

function protip_register_post_type()
{
    $labels = array(
        'name'               => 'Pro-tips',
        'singular_name'      => 'Pro-tip',
        'menu_name'          => 'Pro-tips',
        'name_admin_bar'     => 'Pro-tip',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Pro-tip',
        'new_item'           => 'New Pro-tip',
        'edit_item'          => 'Edit Pro-tip',
        'view_item'          => 'View Pro-tip',
        'all_items'          => 'All Pro-tips',
        'search_items'       => 'Search Pro-tips',
        'parent_item_colon'  => 'Parent Pro-tips:',
        'not_found'          => 'No pro-tips found.',
        'not_found_in_trash' => 'No pro-tips found in Trash.',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'protip'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 20,
        'menu_icon'          => 'dashicons-lightbulb',
        'supports'           => array('title', 'editor', 'author', 'thumbnail', 'excerpt'),
        'show_in_rest'       => true, //protip CPT can be used in the block editor and accessed through REST API routes like WordPress’ built-in post types
    );

    register_post_type('protip', $args); //WordPress built-in method to register a new custom post type called protip, using the settings in $args
}
// init - When WordPress reaches its initialisation stage, run my protip_register_post_type() function.
add_action('init', 'protip_register_post_type');

function protip_register_taxonomy()
{
    $labels = array(
        'name'              => 'Topics',
        'singular_name'     => 'Topic',
        'search_items'      => 'Search Topics',
        'all_items'         => 'All Topics',
        'parent_item'       => 'Parent Topic',
        'parent_item_colon' => 'Parent Topic:',
        'edit_item'         => 'Edit Topic',
        'update_item'       => 'Update Topic',
        'add_new_item'      => 'Add New Topic',
        'new_item_name'     => 'New Topic Name',
        'menu_name'         => 'Topics',
    );

    $args = array(
        'hierarchical'       => true,
        'labels'             => $labels,
        'show_ui'            => true,
        'show_admin_column'  => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'protip-topic'),
        'show_in_rest'       => true,
    );

    register_taxonomy('protip_topic', array('protip'), $args); //WordPress built-in function, here adds a custom Topics taxonomy for Pro-tip posts.
}
//using action hook because we want to run this function during WordPress initialization, which is when custom post types and taxonomies should be registered.
add_action('init', 'protip_register_taxonomy');

// Change the default title field placeholder when editing a Pro-tip.
// The filter receives the current placeholder text and the current post object.
function protip_change_title_placeholder($title, $post)
{
    // If the current post type is "protip", return a custom placeholder.
    if ('protip' === $post->post_type) {
        return 'Enter pro-tip title';
    }
    // Otherwise, return the original placeholder unchanged.
    return $title;
}
// using filter hook because we want to modify the default behavior of WordPress.
add_filter('enter_title_here', 'protip_change_title_placeholder', 10, 2); // Run this function at priority 10, and pass it 2 arguments
