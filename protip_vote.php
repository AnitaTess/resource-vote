<?php
/**
 * Plugin Name: Pro-tip creator and voter
 * Description: Create pro-tips and let users vote on them.
 * Version: 1.0.0
 * Author: Anita Aksentowicz
 */

if (! defined('ABSPATH')) {
    exit;
}

class Protip_Creator_Voter
{
    const VERSION = '1.0.0';

    public function __construct()
    {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    //create table on plugin activation
    public function activate()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'protip_votes';
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
        dbDelta($sql);
    }

    //deactivation hook
    public function deactivate()
    {
        //data left intact for future use
    }

    public static function uninstall()
    {
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

    $protip_id = isset( $_POST['protip_id'] ) ? absint( $_POST['protip_id'] ) : 0;

    if ( ! $protip_id || 'protip' !== get_post_type( $protip_id ) ) {
        wp_send_json_error(
            array(
                'message' => 'Invalid pro-tip.',
            ),
            400
        );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'protip_votes';
    $user_id    = get_current_user_id();

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
        409
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
        500
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
        403
    );
}
add_action( 'wp_ajax_nopriv_protip_vote', 'protip_handle_vote_logged_out' );

require_once plugin_dir_path(__FILE__) . 'protip_rest.php';
require_once plugin_dir_path(__FILE__) . 'protip_cpt.php';
require_once plugin_dir_path(__FILE__) . 'protip_shortcode.php';

register_uninstall_hook(__FILE__, ['Protip_Creator_Voter', 'uninstall']);

new Protip_Creator_Voter();
