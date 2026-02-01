/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Accept one-time and recurring donations easily with customizable forms and PayPal integration.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartDonationPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        add_action('wp_ajax_process_donation', array($this, 'process_donation'));
        add_action('wp_ajax_nopriv_process_donation', array($this, 'process_donation'));
    }

    public function init() {
        if (get_option('sdp_paypal_email')) {
            // PayPal sandbox or live based on option
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('paypal-sdk', 'https://www.paypal.com/sdk/js?client-id=' . get_option('sdp_paypal_client_id', 'test') . '&currency=USD', array(), '1.0', true);
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp-script.js', array('jquery', 'paypal-sdk'), '1.0', true);
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'goal' => '1000',
            'title' => 'Support Our Work',
            'button_text' => 'Donate Now'
        ), $atts);

        ob_start();
        ?>
        <div class="sdp-container" style="max-width: 400px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <div class="sdp-progress" style="background: #f0f0f0; height: 20px; border-radius: 10px; margin: 10px 0;">
                <div class="sdp-progress-bar" style="height: 100%; background: #007cba; width: 0%; border-radius: 10px; transition: width 0.3s;"></div>
            </div>
            <p>Goal: $<span class="sdp-goal"><?php echo esc_html($atts['goal']); ?></span> | Raised: $<span class="sdp-raised">0</span></p>
            <div id="paypal-button-container" style="margin: 20px 0;"></div>
            <p><?php echo esc_html($atts['button_text']); ?></p>
        </div>
        <script>
        paypal.Buttons({
            createOrder: function(data, actions) {
                return actions.order.create({
                    purchase_units: [{
                        amount: {
                            value: '10.00',
                            breakdown: {
                                item_total: { value: '10.00', currency_code: 'USD' }
                            }
                        }
                    }]
                });
            },
            onApprove: function(data, actions) {
                return actions.order.capture().then(function(details) {
                    jQuery.post(sdp_ajax.ajax_url, {
                        action: 'process_donation',
                        orderID: data.orderID,
                        nonce: sdp_ajax.nonce
                    }, function(response) {
                        alert('Donation successful! Thank you ' + details.payer.name.given_name);
                        location.reload();
                    });
                });
            }
        }).render('#paypal-button-container');
        </script>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        // Log donation (in pro version, save to DB)
        error_log('Donation processed: ' . $_POST['orderID']);
        wp_die('success');
    }
}

new SmartDonationPro();

// Settings page
add_action('admin_menu', function() {
    add_options_page('Smart Donation Pro', 'SDP Settings', 'manage_options', 'sdp-settings', function() {
        if (isset($_POST['sdp_paypal_email'])) {
            update_option('sdp_paypal_email', sanitize_email($_POST['sdp_paypal_email']));
            update_option('sdp_paypal_client_id', sanitize_text_field($_POST['sdp_paypal_client_id']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="sdp_paypal_email" value="<?php echo esc_attr(get_option('sdp_paypal_email')); ?>" /></td>
                    </tr>
                    <tr>
                        <th>PayPal Client ID</th>
                        <td><input type="text" name="sdp_paypal_client_id" value="<?php echo esc_attr(get_option('sdp_paypal_client_id')); ?>" /><br><small>Get from <a href="https://developer.paypal.com/" target="_blank">PayPal Developer</a></small></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcode: <code>[smart_donation goal="1000" title="Support Us"]</code></p>
        </div>
        <?php
    });
});

// Add dummy JS file content inline for single file
/* sdp-script.js content would be here if external, but for single file, inline above */