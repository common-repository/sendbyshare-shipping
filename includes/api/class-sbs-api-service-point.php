<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

add_action('rest_api_init', function () {
    register_rest_route('sendbyshare/v1', '/order/call-back', array(
        'methods' => 'POST',
        'callback' => 'sendbyshare_orderCallBack',
    ));
});

function sendbyshare_orderCallBack()
{

    if ($_SERVER['PHP_AUTH_USER'] != get_option('sbs_key') || $_SERVER['PHP_AUTH_PW'] != get_option('sbs_secret_key')) {
        wp_send_json_error(['code' => 0, 'message' => 'Wrong Credentials.'], 401);
    }

    $orderId = sanitize_text_field($_POST['orderId']);
    $status = sanitize_text_field($_POST['status']);

    if (!$orderId) wp_send_json_error(['code' => 0, 'message' => 'Order ID is missing.']);
    if (!$status) wp_send_json_error(['code' => 0, 'message' => 'Status is missing.']);

    global $wpdb;
    $query = $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} where meta_key = '_sbs_orderId' and meta_value = %s LIMIT 1", $orderId );
    $resultObj = $wpdb->get_row($query);

    //Get OrderId by mapped data
    $orderId = isset($resultObj->post_id) ? $resultObj->post_id : null;
    if (!$orderId) wp_send_json_error(['code' => 0, 'message' => 'Order does not exists in the system.']);

    $orderInfo = wc_get_order($orderId);
    if (!$orderInfo) wp_send_json_error(['code' => 0, 'message' => 'Order does not exists in the system.']);

    //['draft', 'waiting', 'pending', 'delivering', 'dropped', 'completed', 'awaitingCancel', 'cancelled', 'deleted']
    switch ($status) {
        case 'draft':
        case 'waiting':
        case 'pending':
            $orderInfo->set_status('wc-processing');
            break;
        case 'delivering':
            $orderInfo->set_status('wc-delivering');
            break;
        case 'dropped':
            $orderInfo->set_status('wc-dropped');
            break;
        case 'completed':
            $orderInfo->set_status('wc-completed');
            break;
        case 'awaitingCancel':
        case 'cancelled':
        case 'deleted':
            $orderInfo->set_status('wc-cancelled');
            break;
        default:
            wp_send_json_error(['code' => 0, 'message' => 'Status not in [\'draft\', \'waiting\', \'pending\', \'delivering\', \'dropped\', \'completed\', \'awaitingCancel\', \'cancelled\', \'deleted\'].']);
            break;
    }
    if ($orderInfo->get_changes()) {
        if ($orderInfo->save()) {
            wp_send_json_success(['code' => 1, 'message' => 'Status was changed success.']);
        }
    }
    wp_send_json_success(['code' => 0, 'message' => 'There is nothing to update.']);
}