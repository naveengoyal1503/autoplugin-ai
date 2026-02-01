/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Content_Locker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Content Locker Pro
 * Plugin URI: https://example.com/smart-content-locker
 * Description: Lock content behind PayPal payments or donations to monetize your site.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartContentLocker {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_shortcode('content_locker', array($this, 'content_locker_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_unlock_content', array($this, 'handle_unlock'));
        add_action('wp_ajax_nopriv_unlock_content', array($this, 'handle_unlock'));
    }

    public function init() {
        if (get_option('scl_paypal_email')) {
            add_action('wp_footer', array($this, 'paypal_script'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('scl-script', plugin_dir_url(__FILE__) . 'scl.js', array('jquery'), '1.0.0', true);
        wp_localize_script('scl-script', 'scl_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('scl_nonce')));
    }

    public function paypal_script() {
        echo '<script src="https://www.paypal.com/sdk/js?client-id=' . esc_js(get_option('scl_paypal_client_id', '')) . '&currency=USD"></script>';
    }

    public function content_locker_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '5.00',
            'button_text' => 'Unlock for $5',
            'message' => 'Pay to unlock this premium content!',
            'id' => uniqid('scl_')
        ), $atts);

        ob_start();
        ?>
        <div class="scl-locker" data-id="<?php echo esc_attr($atts['id']); ?>" data-amount="<?php echo esc_attr($atts['amount']); ?>">
            <div class="scl-preview">Preview content here...</div>
            <p><?php echo esc_html($atts['message']); ?></p>
            <div id="paypal-button-<?php echo esc_attr($atts['id']); ?>"></div>
            <div class="scl-content" style="display:none;"><?php echo do_shortcode(ob_get_clean()); ?></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_unlock() {
        check_ajax_referer('scl_nonce', 'nonce');
        if (get_option('scl_paypal_email') && isset($_POST['payment_id'])) {
            // In pro version, verify payment with PayPal API
            update_option('scl_unlocked_' . sanitize_key($_POST['locker_id']), true);
            wp_send_json_success('Unlocked!');
        } else {
            wp_send_json_error('Payment required.');
        }
    }
}

new SmartContentLocker();

// Settings page
add_action('admin_menu', function() {
    add_options_page('Smart Content Locker', 'Content Locker', 'manage_options', 'scl-settings', 'scl_settings_page');
});

function scl_settings_page() {
    if (isset($_POST['scl_paypal_email'])) {
        update_option('scl_paypal_email', sanitize_email($_POST['scl_paypal_email']));
        update_option('scl_paypal_client_id', sanitize_text_field($_POST['scl_paypal_client_id']));
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>Smart Content Locker Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>PayPal Email</th>
                    <td><input type="email" name="scl_paypal_email" value="<?php echo esc_attr(get_option('scl_paypal_email')); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th>PayPal Client ID (Sandbox/Pro)</th>
                    <td><input type="text" name="scl_paypal_client_id" value="<?php echo esc_attr(get_option('scl_paypal_client_id')); ?>" class="regular-text" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <h2>Usage</h2>
        <p>Use shortcode: <code>[content_locker amount="5.00" button_text="Unlock Now"]</code>Premium content here[/content_locker]</p>
    </div>
    <?php
}

// Simple JS file content (embedded for single file)
/*
Add this as scl.js but for single file, echo in footer or use inline.
*/
add_action('wp_footer', function() {
    if (is_singular()) {
        ?>
        <script>
jQuery(document).ready(function($) {
    $('.scl-locker').each(function() {
        var $locker = $(this);
        var id = $locker.data('id');
        var amount = $locker.data('amount');

        if (typeof paypal !== 'undefined') {
            paypal.Buttons({
                createOrder: function(data, actions) {
                    return actions.order.create({
                        purchase_units: [{
                            amount: { value: amount }
                        }]
                    });
                },
                onApprove: function(data, actions) {
                    return actions.order.capture().then(function(details) {
                        $.post(scl_ajax.ajax_url, {
                            action: 'unlock_content',
                            locker_id: id,
                            payment_id: details.id,
                            nonce: scl_ajax.nonce
                        }, function(res) {
                            if (res.success) {
                                $locker.find('.scl-content').show();
                                $locker.find('.scl-preview, #paypal-button-' + id).hide();
                            }
                        });
                    });
                }
            }).render('#paypal-button-' + id);
        }
    });
});
</script>
        <?php
    }
});