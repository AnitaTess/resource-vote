<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function protip_check_for_plugin_update( $transient ) {
    if ( empty( $transient->checked ) ) {
        return $transient;
    }

    $remote = wp_remote_get(
        PROTIP_VOTES_UPDATE_JSON_URL,
        array(
            'timeout' => 10,
        )
    );

    if ( is_wp_error( $remote ) ) {
        return $transient;
    }

    $body = wp_remote_retrieve_body( $remote );

    if ( empty( $body ) ) {
        return $transient;
    }

    $data = json_decode( $body );

    if ( empty( $data->version ) || empty( $data->download_url ) ) {
        return $transient;
    }

    $current_version = Protip_Creator_Voter::VERSION;

    if ( version_compare( $current_version, $data->version, '<' ) ) {
        $plugin_data = array(
            'slug'        => 'protip-votes',
            'plugin'      => PROTIP_VOTES_PLUGIN_BASENAME,
            'new_version' => sanitize_text_field( $data->version ),
            'url'         => esc_url_raw( $data->details_url ),
            'package'     => esc_url_raw( $data->download_url ),
            'tested'      => isset( $data->tested ) ? sanitize_text_field( $data->tested ) : '',
            'requires'    => isset( $data->requires ) ? sanitize_text_field( $data->requires ) : '',
        );

        $transient->response[ PROTIP_VOTES_PLUGIN_BASENAME ] = (object) $plugin_data;
    }

    return $transient;
}
add_filter( 'pre_set_site_transient_update_plugins', 'protip_check_for_plugin_update' );

function protip_plugin_info_popup( $result, $action, $args ) {
    if ( 'plugin_information' !== $action ) {
        return $result;
    }

    if ( empty( $args->slug ) || 'protip-votes' !== $args->slug ) {
        return $result;
    }

    $remote = wp_remote_get(
        PROTIP_VOTES_UPDATE_JSON_URL,
        array(
            'timeout' => 10,
        )
    );

    if ( is_wp_error( $remote ) ) {
        return $result;
    }

    $data = json_decode( wp_remote_retrieve_body( $remote ) );

    if ( empty( $data->version ) ) {
        return $result;
    }

    return (object) array(
        'name'          => 'Pro-tip Creator and Voter',
        'slug'          => 'protip-votes',
        'version'       => sanitize_text_field( $data->version ),
        'author'        => 'Anita Aksentowicz',
        'homepage'      => isset( $data->details_url ) ? esc_url_raw( $data->details_url ) : '',
        'requires'      => isset( $data->requires ) ? sanitize_text_field( $data->requires ) : '',
        'tested'        => isset( $data->tested ) ? sanitize_text_field( $data->tested ) : '',
        'sections'      => array(
            'description' => 'Create pro-tips and let users vote on them.',
            'changelog'   => 'Latest version from GitHub main branch.',
        ),
        'download_link' => esc_url_raw( $data->download_url ),
    );
}
add_filter( 'plugins_api', 'protip_plugin_info_popup', 10, 3 );