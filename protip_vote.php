<?php
/**
 * Plugin Name: Pro-tip creator and voter
 * Description: Create pro-tips and let users vote on them.
 * Version: 1.0.0
 * Author: Anita Aksentowicz
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Protip_Creator_Voter {
    const VERSION = '1.0.0';

    public function __construct() {
        register_activation_hook( __FILE__, [ $this, 'activate' ] );
        register_deactivation_hook( __FILE__, [ $this, 'deactivate' ] );
    }

    //create table on plugin activation
    public function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'protip_votes';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            protip_id BIGINT(20) UNSIGNED NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id), UNIQUE KEY protip_user_vote (protip_id, user_id), KEY protip_id (protip_id),
            KEY user_id (user_id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    //deactivation hook
    public function deactivate() {
    //data left intact for future use
    }

}
new Protip_Creator_Voter();