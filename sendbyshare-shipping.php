<?php
/**
 * Plugin Name: SendByShare Shipping
 * Plugin URI: https://sendbyshare.com/
 * Description: SendByShare - Plugin connect to shipping service
 * Version: 1.0
 * Author: SendByShare
 * Author URI: https://sendbyshare.com/about
 *
 * Text Domain: sendbyshare-shipping
 * Domain Path: /languages/
 */

if (!defined('ABSPATH')) exit;

require_once 'autoload.php';

if (!defined('SBS_PLUGIN_DIR')) define('SBS_PLUGIN_DIR', plugin_dir_path(__FILE__));
if (!defined('SBS_PLUGIN_URL')) define('SBS_PLUGIN_URL', plugin_dir_url(__FILE__));
if (!defined('SBS_VERSION')) define('SBS_VERSION', '1.0.0');

register_activation_hook(__FILE__, 'sendbyshare_activate');

add_action('init', 'sendbyshare_init');
add_action('plugins_loaded', 'sendbyshare_bootstrap');
add_action('admin_notices', 'sendbyshare_general_admin_notice');

function sendbyshare_general_admin_notice()
{
    if (get_option('sbs_status') == 401) {
        $url = admin_url("admin.php?page=wc-settings&tab=sendbyshare&func=login");
        echo '<div class="notice notice-warning is-dismissible">
             <p><strong>SendByShare Key and Secret Key are not available.</strong> Re-input your Key and Secret Key <a href="' . $url . '">here</a>.</p>
         </div>';
    }
}

function sendbyshare_activate()
{
    if (!class_exists('WooCommerce')) {
        return;
    }
}

function sendbyshare_init()
{
    if (!class_exists('WooCommerce')) {
        return;
    }
    load_plugin_textdomain('sendbyshare', false, basename(dirname(__FILE__)) . '/languages/');
}

function sendbyshare_bootstrap()
{
    if (!class_exists('WooCommerce')) {
        require_once(ABSPATH . '/wp-admin/includes/plugin.php');
        deactivate_plugins(plugin_basename(__FILE__));
        add_action('admin_notices', 'sendbyshare_deactivate_notice');
    } else {
        add_action('woocommerce_shipping_init', 'sendbyshare_service_point_shipping_method_init');
        add_filter('woocommerce_shipping_methods', 'sendbyshare_add_service_point_shipping_method');
        add_filter('woocommerce_get_settings_pages', 'sendbyshare_settings_page');

        add_option('sbs_api_enable', 'no');
    }
}

function sendbyshare_service_point_shipping_method_init()
{
    require_once plugin_dir_path(__FILE__) . 'includes/method/class-sbs-shipping-method.php';
}

function sendbyshare_add_service_point_shipping_method($methods)
{
    if (version_compare(WC()->version, '2.6', ">=")) {
        $methods[SBS_SHIPPING_SERVICE] = 'SendByShare_Shipping_Method';
    } else {
        $methods[] = 'SendByShare_Shipping_Method';
    }
    return $methods;
}

function sendbyshare_deactivate_notice()
{
    $message = __('SendByShare | SendByShare plugin deactivated because WooCommerce is not available.', 'sendbyshare-shipping');
    printf('<div class="error"><p>%s</p></div>', $message);
}


function sendbyshare_settings_page($settings)
{
    $settings[] = include('includes/class-sbs-settings.php');
    return $settings;
}

/**
 * Start Add Dropped Shipping Status
 */
function sendbyshare_register_dropped_shipment_order_status()
{
    register_post_status('wc-delivering', array(
        'label' => 'SBS Delivering',
        'public' => true,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('SBS Delivering <span class="count">(%s)</span>', 'SBS Delivering <span class="count">(%s)</span>')
    ));
    register_post_status('wc-dropped', array(
        'label' => 'SBS Dropped',
        'public' => true,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('SBS Dropped <span class="count">(%s)</span>', 'SBS Dropped <span class="count">(%s)</span>')
    ));
}

add_action('init', 'sendbyshare_register_dropped_shipment_order_status');
// Add to list of WC Order statuses
function sendbyshare_add_dropped_shipment_to_order_statuses($order_statuses)
{
    $new_order_statuses = array();
    // add new order status after processing
    foreach ($order_statuses as $key => $status) {
        $new_order_statuses[$key] = $status;
        if ('wc-processing' === $key) {
            $new_order_statuses['wc-delivering'] = 'SBS Delivering';
            $new_order_statuses['wc-dropped'] = 'SBS Dropped';
        }
    }
    return $new_order_statuses;
}

add_filter('wc_order_statuses', 'sendbyshare_add_dropped_shipment_to_order_statuses');

/**
 * Add Custom CSS to AdminCP
 */
add_action('admin_enqueue_scripts', 'sendbyshare_load_admin_styles');
function sendbyshare_load_admin_styles()
{
    wp_enqueue_style('sbs_admin_css', plugins_url('/assets/css/admin.css', __FILE__), array(), null, 'all');
}

/**
 * Create Order when Status is Processing
 */

add_action('woocommerce_order_status_processing', 'sendbyshare_trigger_order_processing');
function sendbyshare_trigger_order_processing($order_id)
{
    $orderInfo = wc_get_order($order_id);

    if ($orderInfo->get_meta('_sbs_orderId', true)) {
        return true;
    }

    // Check use SBS Service
//    $isSBS = false;
//    foreach ($orderInfo->get_shipping_methods() as $item) {
//        if ($item->get_method_id() == SBS_SHIPPING_SERVICE) {
//            $isSBS = true;
//            break;
//        }
//    }

    $isSBS = true;
    // Process Submit order if this order use SBS Service
    if ($isSBS) {
        $body['collectionPoint'] = sendbyshare_get_shop_base_address();
        $shipAddress[] = $orderInfo->get_shipping_address_1() ? $orderInfo->get_shipping_address_1() : $orderInfo->get_shipping_address_2();
        $shipCityCode[] = $orderInfo->get_shipping_postcode();
        $shipCityCode[] = $orderInfo->get_shipping_city();
        $shipAddress[] = implode(' ', array_filter($shipCityCode));
        $shipAddress[] = $orderInfo->get_shipping_state();
        $countries = WC()->countries->get_countries();
        $shipAddress[] = isset($countries[$orderInfo->get_shipping_country()]) ? $countries[$orderInfo->get_shipping_country()] : $orderInfo->get_shipping_country();

        $body['dropOffPoint'] = implode(', ', array_filter($shipAddress));
        $body['senderName'] = get_option('sbs_senderName', '');
        $body['senderPhone'] = get_option('sbs_senderPhone', '');
        $body['senderAddress'] = $body['collectionPoint'];

        $body['recipientName'] = $orderInfo->get_formatted_billing_full_name();
        $body['recipientPhone'] = $orderInfo->get_billing_phone();

        $orderDetails = [];
        $weight = 0;
        foreach ($orderInfo->get_items() as $item_id => $item_data) {
            $_product = $item_data->get_product();
            $product_name = $item_data['name'];
            $item_quantity = wc_get_order_item_meta($item_id, '_qty', true);
            $item_total = wc_get_order_item_meta($item_id, '_line_total', true);
            if (!$_product->is_virtual() && $_product->get_weight()) {
                $weight += $_product->get_weight() * $item_quantity;
            }
            // Add order detail to note - because SBS do not manage order items
            $orderDetails[] = 'Product name: ' . $product_name . ' - Quantity: ' . $item_quantity . ' - Item total: ' . sendbyshare_formatted_price($item_total);
        }
        if ($weight) {
            $weight = wc_get_weight($weight, 'kg');
            $orderDetails[] = "Total Weight: " . $weight . " kg";
        }
        $orderNote = $orderInfo->get_customer_note();
        if ($orderNote) {
            $orderDetails[] = "Note:" . PHP_EOL . $orderNote;
        }

        $body['note'] = implode(PHP_EOL, $orderDetails);
        $body['packageValue'] = $orderInfo->get_total();
        $body['packageType'] = 'Normal Goods';
        $body['packageWeight'] = sendbyshare_package_weight($weight);
        $body['termChecked'] = true;
        if ($body) {
            $sbsApi = new SendByShare_API();
            $result = $sbsApi->submitOrder($body);

            if ($result) {
                $orderId = isset($result['data']['orderId']) ? $result['data']['orderId'] : null;
                $orderInfo->update_meta_data('_sbs_orderId', $orderId);
                $orderInfo->add_order_note("Submit order to SBS success. OrderId: {$orderId}");
                $orderInfo->save();
            }
        }
    }
}