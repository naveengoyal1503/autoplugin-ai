/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: A powerful WordPress plugin that creates customizable donation buttons, progress bars, and one-time/recurring payment forms with PayPal integration to easily monetize your site through user donations and tips.
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
        add_action('wp_ajax_sdp_process_donation', array($this, 'process_donation'));
        add_action('wp_ajax_nopriv_sdp_process_donation', array($this, 'process_donation'));
    }

    public function init() {
        if (get_option('sdp_paypal_email')) {
            // Plugin is configured
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10',
            'button_text' => 'Donate Now',
            'goal' => '1000',
            'currency' => '$',
            'paypal_email' => get_option('sdp_paypal_email'),
        ), $atts);

        $current = get_option('sdp_total_donations', 0);
        $progress = min(100, ($current / $atts['goal']) * 100);

        ob_start();
        ?>
        <div class="sdp-container" style="max-width: 400px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9;">
            <h3 style="text-align: center;">Support Our Work</h3>
            <?php if ($atts['goal']): ?>
            <div style="background: #eee; height: 20px; border-radius: 10px; margin: 10px 0; overflow: hidden;">
                <div style="background: #28a745; height: 100%; width: <?php echo $progress; ?>%; transition: width 0.3s;"></div>
            </div>
            <p style="text-align: center; font-size: 14px;">Raised: <?php echo $atts['currency'] . number_format($current); ?> / <?php echo $atts['currency'] . number_format($atts['goal']); ?></p>
            <?php endif; ?>
            <form id="sdp-form" class="sdp-form">
                <div style="margin: 10px 0;">
                    <label>Amount: <?php echo $atts['currency']; ?></label>
                    <input type="number" name="amount" value="<?php echo $atts['amount']; ?>" min="1" step="0.01" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
                <input type="hidden" name="paypal_email" value="<?php echo esc_attr($atts['paypal_email']); ?>">
                <button type="submit" class="sdp-button" style="width: 100%; padding: 12px; background: #007cba; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer;"><?php echo esc_html($atts['button_text']); ?></button>
            </form>
            <p style="text-align: center; font-size: 12px; color: #666; margin-top: 10px;">Secure payment via PayPal</p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#sdp-form').on('submit', function(e) {
                e.preventDefault();
                var formData = $(this).serialize();
                $.post(sdp_ajax.ajax_url, formData + '&action=sdp_process_donation&nonce=' + sdp_ajax.nonce, function(response) {
                    if (response.success) {
                        window.location.href = response.data.redirect;
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        $amount = floatval($_POST['amount']);
        $paypal_email = sanitize_email($_POST['paypal_email']);

        if (!$paypal_email || $amount < 1) {
            wp_send_json_error('Invalid amount or email');
        }

        $current_total = floatval(get_option('sdp_total_donations', 0));
        update_option('sdp_total_donations', $current_total + $amount);

        $paypal_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=' . urlencode($paypal_email) . '&amount=' . $amount . '&item_name=Donation via Smart Donation Pro&currency_code=USD&return=' . urlencode(get_site_url() . '/?donation=success');

        wp_send_json_success(array('redirect' => $paypal_url));
    }
}

// Admin settings
if (is_admin()) {
    add_action('admin_menu', function() {
        add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'sdp-settings', 'sdp_settings_page');
    });

    function sdp_settings_page() {
        if (isset($_POST['sdp_paypal_email'])) {
            update_option('sdp_paypal_email', sanitize_email($_POST['sdp_paypal_email']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $paypal_email = get_option('sdp_paypal_email');
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="sdp_paypal_email" value="<?php echo esc_attr($paypal_email); ?>" class="regular-text" required></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcode: <code>[smart_donation amount="10" goal="1000" button_text="Buy Me a Coffee"]</code></p>
        </div>
        <?php
    }
}

new SmartDonationPro();

// Reset total donations button in admin
add_action('admin_init', function() {
    if (isset($_GET['sdp_reset']) && current_user_can('manage_options')) {
        update_option('sdp_total_donations', 0);
        wp_redirect(admin_url('options-general.php?page=sdp-settings'));
        exit;
    }
});