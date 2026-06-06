<?php
/**
 * Plugin Name: Pro-tip creator and voter
 * Description: Create pro-tips and let users vote on them.
 * Version: 1.0.1
 * Author: Anita Aksentowicz
 */

//Only run this file through WordPress. If someone accesses it directly, block it.
if (! defined('ABSPATH')) {
    exit;
}

class Protip_Creator_Voter
{
    const VERSION = '1.0.1';

    public function __construct()
    {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

  //activation hook
    public function activate()
    {
        //create table on plugin activation
        global $wpdb;
        $table_name = $wpdb->prefix . 'protip_votes';
        //$charset_collate tells MySQL what character set and collation your custom table should use
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            protip_id BIGINT(20) UNSIGNED NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id), UNIQUE KEY protip_user_vote (protip_id, user_id), KEY protip_id (protip_id),
            KEY user_id (user_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        //dbDelta() compares this CREATE TABLE SQL with the table that already exists in the database.
        //So if the table does not exist, it creates it.
        //If the table does exist, it can update the structure, for example add missing columns or indexes.
        dbDelta($sql);
    }

    //deactivation hook
    public function deactivate()
    {
        //data left intact for future use
    }

    public static function uninstall()
    {
        //delete table on plugin uninstall
        global $wpdb;
        $table_name = $wpdb->prefix . 'protip_votes';
        $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
    }
}

function protip_enqueue_assets() {
    wp_enqueue_style(
        'protip-votes',
        plugin_dir_url( __FILE__ ) . 'assets/css/styles.css',
        array(),
        Protip_Creator_Voter::VERSION,
        'all'
    );

    wp_enqueue_script(
        'protip-votes',
        plugin_dir_url( __FILE__ ) . 'assets/js/protip-votes.js',
        array(),
        Protip_Creator_Voter::VERSION,
        true
    );

    //use wp_localize_script() to safely pass the AJAX URL and nonce from PHP to JavaScript 
    wp_localize_script(
    'protip-votes',
    'ProtipVotes',
    array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'protip_vote_nonce' ),
        'restUrl' => esc_url_raw( rest_url( 'protip-votes/v1/protips' ) ),
        'restNonce' => wp_create_nonce( 'wp_rest' ),
    )
);
}
add_action( 'wp_enqueue_scripts', 'protip_enqueue_assets' );


function protip_handle_vote() {
    check_ajax_referer( 'protip_vote_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error(
            array(
                'message' => 'You must be logged in to vote.',
            ),
            403
        );
    }
    //If the AJAX request includes a pro-tip ID, clean it into a positive integer. If not, set it to 0.
    $protip_id = isset( $_POST['protip_id'] ) ? absint( $_POST['protip_id'] ) : 0;

    if ( ! $protip_id || 'protip' !== get_post_type( $protip_id ) ) {
        wp_send_json_error(
            array(
                'message' => 'Invalid pro-tip.',
            ),
            400 //If there is no valid pro-tip ID, or the ID does not belong to a Pro-tip post, stop the request and return a 400 error.
        );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'protip_votes';
    $user_id    = get_current_user_id(); //built-in WordPress function

    $existing_vote = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM $table_name WHERE protip_id = %d AND user_id = %d",
            $protip_id,
            $user_id
        )
    );
    if ( $existing_vote ) {
    wp_send_json_error(
        array(
            'message' => 'You already voted for this tip.',
        ),
        409 //request to a server could not be completed because it would create duplicate entities (voting twice for same card)
    );
}
$inserted = $wpdb->insert(
    $table_name,
    array(
        'protip_id' => $protip_id,
        'user_id'   => $user_id,
        'created_at' => current_time( 'mysql' ),
    ),
    array(
        '%d',
        '%d',
        '%s',
    )
);
if ( ! $inserted ) {
    wp_send_json_error(
        array(
            'message' => 'Failed to record your vote. Please try again.',
        ),
        500 //server error when something goes wrong on the server side (e.g. database insert fails)
    );
}
//Count votes with $wpdb->prepare() to prevent SQL injection
$vote_count = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE protip_id = %d",
        $protip_id
    )
);
wp_send_json_success(
    array(
        'message'    => 'Thanks, your vote has been saved.',
        'vote_count' => absint( $vote_count ),
    )
);
}
add_action( 'wp_ajax_protip_vote', 'protip_handle_vote' );

function protip_handle_vote_logged_out() {
    wp_send_json_error(
        array(
            'message' => 'You must be logged in to vote.',
        ),
        403 //request was understoof but the server refuses to authorize it (user not logged in so forbidden to vote)
    );
}
// Handle AJAX requests from logged-out users.
// Without this nopriv hook, WordPress may return a plain "0" response
// when a visitor who is not logged in tries to vote.
add_action( 'wp_ajax_nopriv_protip_vote', 'protip_handle_vote_logged_out' );

//the order of the includes is important because the shortcode relies on the CPT and REST API functions, and the REST API relies on the vote count function.
require_once plugin_dir_path(__FILE__) . 'protip_rest.php';
require_once plugin_dir_path(__FILE__) . 'protip_cpt.php';
require_once plugin_dir_path(__FILE__) . 'protip_shortcode.php';

register_uninstall_hook(__FILE__, ['Protip_Creator_Voter', 'uninstall']);

new Protip_Creator_Voter();
