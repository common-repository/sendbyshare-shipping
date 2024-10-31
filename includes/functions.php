<?php

if (!defined('ABSPATH')) exit;

function sendbyshare_get_shop_base_address()
{
    $address[] = WC()->countries->get_base_address() ? WC()->countries->get_base_address() : WC()->countries->get_base_address_2();
    $addressCityCode[] = WC()->countries->get_base_postcode();
    $addressCityCode[] = WC()->countries->get_base_city();
    $address[] = implode(' ', array_filter($addressCityCode));
    $address[] = WC()->countries->get_base_state();
    $countries = WC()->countries->get_countries();
    $address[] = isset($countries[WC()->countries->get_base_country()]) ? $countries[WC()->countries->get_base_country()] : WC()->countries->get_base_country();

    $address = array_filter($address);
    return implode(', ', $address);
}

function sendbyshare_package_weight($weight)
{
    switch (true) {
        case ($weight <= 5):
            $type = '1small-parcel';
            break;
        case ($weight <= 10):
            $type = '1normal-parcel';
            break;
        case ($weight <= 20):
            $type = '1big-parcel';
            break;
        case ($weight <= 40):
            $type = '2big-parcel';
            break;
        case ($weight <= 60):
            $type = '3big-parcel';
            break;
        case ($weight <= 80):
            $type = '4big-parcel';
            break;
        case ($weight <= 100):
            $type = '5big-parcel';
            break;
        case ($weight <= 120):
        default:
            $type = '6big-parcel';
            break;
    }
    return $type;
}

function sendbyshare_formatted_price($price)
{
    $fm_price = '';

    if (!$price) {
        $fm_price = '';
    } else {
        $thous_sep = wc_get_price_thousand_separator();
        $dec_sep = wc_get_price_decimal_separator();
        $decimals = wc_get_price_decimals();
        $price = number_format($price, $decimals, $dec_sep, $thous_sep);

        $format = get_option('woocommerce_currency_pos');
        $csymbol = get_woocommerce_currency_symbol();
        $csymbol = html_entity_decode($csymbol, ENT_QUOTES, 'UTF-8');

        switch ($format) {
            case 'left':
                $fm_price = $csymbol . $price;
                break;

            case 'left_space':
                $fm_price = $csymbol . '&nbsp;' . $price;
                break;

            case 'right':
                $fm_price = $price . $csymbol;
                break;

            case 'right_space':
                $fm_price = $price . '&nbsp;' . $csymbol;
                break;

            default:
                $fm_price = $csymbol . $price;
                break;
        }
    }
    return $fm_price;
}

function sendbyshare_write_logs($text = '', $file_name = '')
{
    if (WP_DEBUG !== true) return;

    if (empty($file_name)) {
        $t = date('Ymd');
        $file_name = "errors-{$t}.txt";
    }

    $folder_path = SBS_PLUGIN_DIR . '/logs';
    $file_path = $folder_path . '/' . $file_name;

    if (!file_exists($folder_path)) {
        mkdir($folder_path, 0755, true);
    }

    $file = fopen($file_path, "a");

    $date = date('Y-m-d H:i:s', time());

    $body = "\n" . $date . ' ';
    $body .= $text;

    fwrite($file, $body);
    fclose($file);
}
