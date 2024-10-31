<?php
if (!defined('ABSPATH')) {
    exit;
}

$sbs_key = get_option('sbs_key', '');
$sbs_secret_key = get_option('sbs_secret_key', '');

?>
<h2>Connect to Send By Share</h2>
<div id="description"><p>Connect to SendByShare to let you manage your orders in our panel. </p>
</div>
<table class="form-table">
    <tbody>
    <tr valign="top">
        <th scope="row" class="titledesc">
            <label for="sbs_key">Key <span class="woocommerce-help-tip" data-tip="Input your 'Key' from 'API Settings' on SBS Admin Panel"></span></label>
        </th>
        <td class="forminp forminp-text">
            <input name="sbs_key" id="sbs_key" type="text" value="<?php echo esc_html($sbs_key) ?>" required>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row" class="titledesc">
            <label for="sbs_secret_key">Secret Key <span class="woocommerce-help-tip" data-tip="Input your 'Secret Key' from 'API Settings' on SBS Admin Panel"></span></label>
        </th>
        <td class="forminp forminp-text">
            <input name="sbs_secret_key" id="sbs_secret_key" type="password" value="<?php echo esc_html($sbs_secret_key) ?>" required>
        </td>
    </tr>
    </tbody>
</table>

<p class="submit">
    <?php wp_nonce_field( 'sbs_save_setting', 'sbs_save_security' ); ?>
    <button name="save" class="button-primary" type="submit" value="save">Save Settings</button>
    <button name="save" class="button-primary" type="submit" value="reset" formnovalidate>Reset Settings</button>
</p>

<style>
    .updated.inline {
        display: none;
    }
</style>
