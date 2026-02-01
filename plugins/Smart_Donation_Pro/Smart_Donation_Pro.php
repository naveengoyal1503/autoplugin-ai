/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Add customizable donation buttons and forms to monetize your WordPress site with one-time or recurring payments.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartDonationPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
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
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'sdp-style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'smart-donation-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['sdp_paypal_email'])) {
            update_option('sdp_paypal_email', sanitize_email($_POST['sdp_paypal_email']));
            update_option('sdp_button_text', sanitize_text_field($_POST['sdp_button_text']));
            update_option('sdp_amount', floatval($_POST['sdp_amount']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $paypal_email = get_option('sdp_paypal_email', '');
        $button_text = get_option('sdp_button_text', 'Donate Now');
        $amount = get_option('sdp_amount', 5.00);
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="sdp_paypal_email" value="<?php echo esc_attr($paypal_email); ?>" required /></td>
                    </tr>
                    <tr>
                        <th>Button Text</th>
                        <td><input type="text" name="sdp_button_text" value="<?php echo esc_attr($button_text); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Default Amount ($)</th>
                        <td><input type="number" step="0.01" name="sdp_amount" value="<?php echo esc_attr($amount); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Pro:</strong> Unlock recurring donations, progress bars, analytics, and more! <a href="https://example.com/pro" target="_blank">Get Pro</a></p>
            <p>Shortcode: <code>[smart_donation]</code></p>
        </div>
        <?php
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array('amount' => get_option('sdp_amount', 5)), $atts);
        $paypal_email = get_option('sdp_paypal_email');
        if (!$paypal_email) {
            return '<p>Please configure PayPal email in settings.</p>';
        }
        ob_start();
        ?>
        <div id="sdp-container" class="sdp-widget">
            <form id="sdp-form" method="post" action="https://www.paypal.com/cgi-bin/webscr">
                <input type="hidden" name="cmd" value="_xclick">
                <input type="hidden" name="business" value="<?php echo esc_attr($paypal_email); ?>">
                <input type="hidden" name="item_name" value="Donation via <?php echo get_bloginfo('name'); ?>">
                <input type="hidden" name="currency_code" value="USD">
                <input type="number" id="sdp-amount" name="amount" value="<?php echo esc_attr($atts['amount']); ?>" step="0.01" min="0.01" placeholder="Enter amount">
                <input type="submit" id="sdp-button" value="<?php echo esc_attr(get_option('sdp_button_text', 'Donate Now')); ?>">
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        // For pro version AJAX handling
        wp_die();
    }
}

new SmartDonationPro();

// Inline styles and scripts for self-contained plugin
?>
<style>
.sdp-widget { max-width: 300px; margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; text-align: center; background: #f9f9f9; }
#sdp-amount { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; }
#sdp-button { width: 100%; padding: 12px; background: #007cba; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; }
#sdp-button:hover { background: #005a87; }
</style>
<script>
jQuery(document).ready(function($) {
    $('#sdp-button').click(function(e) {
        var amount = $('#sdp-amount').val();
        if (amount <= 0) {
            alert('Please enter a valid amount.');
            e.preventDefault();
            return false;
        }
    });
});
</script>