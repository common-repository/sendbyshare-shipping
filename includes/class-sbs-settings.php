<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once 'config.php';
require_once 'sbs_api.php';

if (!class_exists('SendByShare_Settings')):

    if (!session_id()) session_start();

    class SendByShare_Settings extends WC_Settings_Page
    {
        const API_DESCRIPTION = 'SendByShare API';

        public function __construct()
        {
            $this->id = 'sendbyshare';
            $this->label = __('SendByShare', 'sendbyshare-shipping');
            parent::__construct();
        }

        public function output()
        {
            $this->show_messages();

            global $hide_save_button;
            $hide_save_button = true;

            if (isset($_GET['func']) && $_GET['func'] == 'login') {
                include_once(plugin_dir_path(__FILE__) . '../views/login.php');
            } else {
                include_once(plugin_dir_path(__FILE__) . '../views/settings.php');
            }
        }

        private function show_messages()
        {
            if (isset($_SESSION['sbs_messages']) && !empty($_SESSION['sbs_messages'])) {
                foreach ($_SESSION['sbs_messages'] as $message) {
                    if ($message['type'] == 'error') {
                        echo '<div class="notice notice-error is-dismissible">
                            <p><strong>' . esc_html($message['message']) . '</strong></p>
                        </div>';
                    } else {
                        echo '<div class="notice notice-success is-dismissible">
                            <p><strong>' . esc_html($message['message']) . '</strong></p>
                        </div>';
                    }
                }
                unset($_SESSION['sbs_messages']);
            }
        }

        private function get_panel_url()
        {
            return $panel_url = SBS_PANEL_URL;
        }

        private function get_url($function = '')
        {
            return admin_url("admin.php?page=wc-settings&tab={$this->id}&func={$function}");
        }

        public function save()
        {
            if ( !current_user_can( 'manage_options' ) ) {
                echo esc_html('your account does not have privileges to save settings.');
                exit;
            }

            if (!isset( $_POST['sbs_save_security'] ) || ! wp_verify_nonce( $_POST['sbs_save_security'], 'sbs_save_setting' )
            ) {
                echo esc_html('Sorry, your nonce did not verify.');
                exit;
            }

            if (isset($_POST['save'])) {
                switch ($_POST['save']) {
                    case 'save':
                        $sbs_key = isset($_POST['sbs_key']) ? sanitize_text_field($_POST['sbs_key']) : '';
                        $sbs_secret_key = isset($_POST['sbs_secret_key']) ? sanitize_text_field($_POST['sbs_secret_key']) : '';

                        $result = false;
                        if ($sbs_key && $sbs_secret_key) {
                            update_option('sbs_key', $sbs_key);
                            update_option('sbs_secret_key', $sbs_secret_key);
                            update_option('sbs_api_enable', 'yes');
                            delete_option('sbs_status');
                            $result = true;
                        }

                        if ($result):
                            if (!headers_sent()) {
                                header('Location: ' . $this->get_url());
                                exit;
                            } else {
                                echo '<script type="text/javascript">
                                        window.location.href=' . $this->get_url() . ';
                                    </script>
                                    <noscript>
                                        <meta http-equiv="refresh" content="0;url='.$this->get_url().'" />
                                    </noscript>';
                            }
                        endif;
                        break;
                    case 'reset':
                        update_option('sbs_key', '');
                        update_option('sbs_secret_key', '');
                        update_option('sbs_api_enable', 'no');
                        delete_option('sbs_status');
                        break;
                }
            }
        }
    }
endif;

return new SendByShare_Settings();
