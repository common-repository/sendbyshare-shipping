<?php

if (!defined('ABSPATH')) exit;

define( 'SBS_SHIPPING_SERVICE', 'sendbyshare_shipping' );

if (!defined('SBS_API_SUBMIT_ORDER')) {
    define('SBS_API_SUBMIT_ORDER', 'https://api-v2.sendbyshare.com/api/v1/order/b2b-sender/submit');
}

if (!defined('SBS_PANEL_URL')) {
    define('SBS_PANEL_URL', 'https://stg-sender.sendbyshare.com');
}