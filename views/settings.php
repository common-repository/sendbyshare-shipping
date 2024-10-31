<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="submit">
    <div class="connect">
        <?php if (get_option('sbs_api_enable') !== 'yes'): ?>
            <a class="button button-primary" href="<?php echo esc_url($this->get_url('login')); ?>"
               rel="noopener noreferrer"><?php _e('Connect To SendByShare', 'sendbyshare-shipping'); ?></a>
            <p class="description">
                <?php _e('Connect to SendByShare and allow you to see your WooCommerce orders in the SendByShare Panel.', 'sendbyshare-shipping'); ?>
            </p>
        <?php else: ?>
            <a class="button success button-primary" href="<?php echo esc_url($this->get_url('login')); ?>"
               rel="noopener noreferrer"><?php _e('Connected To SendByShare', 'sendbyshare-shipping'); ?></a>
            <p class="description">
                <?php _e('Connected to SendByShare. Click again to edit Key and Secret key.', 'sendbyshare-shipping'); ?>
            </p>
        <?php endif; ?>
    </div>
    <div class="goto-panel">
        <a class="button button-primary" href="<?php echo esc_url($this->get_panel_url()); ?>" target="_blank"
           rel="noopener noreferrer"><?php _e('Go to SendByShare', 'sendbyshare-shipping'); ?></a>
        <p class="description">
            <?php _e('Open the SendByShare panel and start shipping.', 'sendbyshare-shipping'); ?>
        </p>
    </div>
</div>

<style>
    .button.success {
        background: #0f834d;
    }

</style>
