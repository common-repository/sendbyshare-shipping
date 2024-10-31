<?php

if (!defined('ABSPATH')) exit;

if ( ! class_exists('SendByShare_Shipping_Method') ):

class SendByShare_Shipping_Method extends WC_Shipping_Flat_Rate {
    public $plugin_id = 'sendbyshare_';

    public function __construct( $instance_id = 0 ) {
        $this->id = SBS_SHIPPING_SERVICE;
        $this->instance_id = absint( $instance_id );
        $this->method_title = __( 'Send By Share Delivery', 'sendbyshare' );
        $this->method_description = __( 'SBS Service Point Delivery lets the customer select a servicepoint in the checkout.', 'sendbyshare' );
        $this->supports = array(
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        );
        $this->init();

        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    public function init() {
        if ( version_compare( WC()->version, '2.6', ">=" ) ) {
            $this->instance_form_fields = include(WC()->plugin_path() . '/includes/shipping/flat-rate/includes/settings-flat-rate.php');
            $this->addExtraFields($this->instance_form_fields);
            $this->title = $this->get_option('title');
            $this->tax_status = $this->get_option('tax_status');
            $this->cost = $this->get_option('cost');
            $this->type = $this->get_option('type', 'class');
        } else {
            parent::init();
        }
        $this->free_shipping_enabled = $this->get_option( 'free_shipping_enabled' );
        $this->free_shipping_min_amount = $this->get_option( 'free_shipping_min_amount' );
//        $this->carrier_select = $this->get_option( 'carrier_select' );
    }

    public function init_form_fields() {
        parent::init_form_fields();
        $this->addExtraFields($this->form_fields);
    }

    public function is_enabled() {
        return parent::is_enabled();
    }

    public function calculate_shipping( $package = array() ) {
        if ($this->checkFreeShipping()) {
            $this->add_rate(array(
                'id' => $this->id,
                'label' => $this->title,
                'cost' => 0,
                'taxes' => false,
                'package' => $package,
            ));
        } else {
            parent::calculate_shipping($package);
        }
    }

    private function checkFreeShipping() {
        if ( $this->free_shipping_enabled === 'yes' && isset( WC()->cart->cart_contents_total ) ) {
            if ( version_compare( WC()->version, '2.6', ">=" ) ) {
                $total = WC()->cart->get_displayed_subtotal();
                if ( 'incl' === WC()->cart->tax_display_cart ) {
                    $total = $total - ( WC()->cart->get_cart_discount_total() + WC()->cart->get_cart_discount_tax_total() );
                } else {
                    $total = $total - WC()->cart->get_cart_discount_total();
                }
            } else {
                if ( WC()->cart->prices_include_tax ) {
                    $total = WC()->cart->cart_contents_total + array_sum( WC()->cart->taxes );
                } else {
                    $total = WC()->cart->cart_contents_total;
                }
            }

            if ( $total >= $this->free_shipping_min_amount ) {
                return true;
            }
        }
        return false;
    }

    private function addExtraFields(&$form_fields) {
        $form_fields['title']['default'] = $this->method_title;
        $form_fields['free_shipping_enabled'] =  array(
            'title'   => __( 'SBS Enable Free Shipping', 'sendbyshare' ),
            'type'    => 'select',
            'class'   => 'wc-enhanced-select',
            'default' => '',
            'options' => array(
                'no'           => __( 'No', 'sendbyshare' ),
                'yes'     => __( 'Yes', 'sendbyshare' ),
            ),
        );
        $form_fields['free_shipping_min_amount'] = array(
            'title'       => __( 'SBS Minimum Order Amount for Free Shipping', 'sendbyshare' ),
            'type'        => 'price',
            'placeholder' => wc_format_localized_price( 0 ),
            'description' => __( 'SBS If enabled, users will need to spend this amount to get free shipping.', 'sendbyshare' ),
            'default'     => '0',
            'desc_tip'    => true,
        );
    }

}
endif;
