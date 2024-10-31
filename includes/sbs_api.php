<?php

if (!defined('ABSPATH')) exit;

require_once 'config.php';
require_once 'functions.php';

class SendByShare_API
{
    public function submitOrder($body = [])
    {
        $username = get_option('sbs_key', false);
        $password = get_option('sbs_secret_key', false);

        if (!$username || !$password) return false;

        $args = array(
            'body' => $body,
            'timeout' => '20',
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(
                'Authorization' => "Basic " . base64_encode( $username . ':' . $password ),
                "Content-Type: application/x-www-form-urlencoded",
            ),
            'cookies' => array()
        );

        $response = wp_remote_post( SBS_API_SUBMIT_ORDER, $args );
        if ( is_wp_error( $response ) ) {
            return false;
        }

        $result = wp_remote_retrieve_body($response);
        $result = json_decode($result, true);

        if (isset($result['success']) && $result['success'] == 1) {
            return $result;
        } elseif (isset($result['status']) && $result['status'] == 401) {
            update_option('sbs_api_enable', 'no');
            update_option('sbs_status', 401);
        }

        sendbyshare_write_logs(json_encode($body), 'error.txt');
        sendbyshare_write_logs($response, 'error.txt');
        return false;
    }
}