/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Easily add donation buttons and forms to monetize your WordPress site with PayPal.
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
        add_action('wp_ajax_sdp_donate', array($this, 'handle_donation'));
        add_action('wp_ajax_nopriv_sdp_donate', array($this, 'handle_donation'));
    }

    public function init() {
        if (get_option('sdp_paypal_email')) {
            // Plugin active
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'sdp-style.css', array(), '1.0.0');
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'smart-donation-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
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
                        <td><input type="email" name="sdp_paypal_email" value="<?php echo esc_attr($paypal_email); ?>" class="regular-text" required /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcode: <code>[smart_donation amount="10" label="Buy Me a Coffee"]</code></p>
        </div>
        <?php
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '5',
            'label' => 'Donate',
            'button_text' => 'Donate Now',
            'currency' => 'USD'
        ), $atts);

        $paypal_email = get_option('sdp_paypal_email');
        if (!$paypal_email) {
            return '<p>Please configure PayPal email in settings.</p>';
        }

        ob_start();
        ?>
        <div class="sdp-donation-widget">
            <h3><?php echo esc_html($atts['label']); ?></h3>
            <button class="sdp-donate-btn" data-amount="<?php echo esc_attr($atts['amount']); ?>" data-currency="<?php echo esc_attr($atts['currency']); ?>"><?php echo esc_html($atts['button_text']); ?></button>
            <form id="sdp-paypal-form" action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank" style="display:none;">
                <input type="hidden" name="cmd" value="_xclick">
                <input type="hidden" name="business" value="<?php echo esc_attr($paypal_email); ?>">
                <input type="hidden" name="amount" value="">
                <input type="hidden" name="currency_code" value="<?php echo esc_attr($atts['currency']); ?>">
                <input type="hidden" name="item_name" value="Donation via Smart Donation Pro">
                <input type="hidden" name="return" value="<?php echo esc_url(get_site_url()); ?>">
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        // AJAX handler if needed for premium features
        wp_die();
    }
}

new SmartDonationPro();

// Inline styles and scripts for self-contained plugin
function sdp_add_inline_assets() {
    ?>
    <style>
    .sdp-donation-widget { text-align: center; padding: 20px; border: 1px solid #ddd; border-radius: 5px; background: #f9f9f9; }
    .sdp-donate-btn { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
    .sdp-donate-btn:hover { background: #005a87; }
    </style>
    <script>
    jQuery(document).ready(function($) {
        $('.sdp-donate-btn').click(function(e) {
            e.preventDefault();
            var amount = $(this).data('amount');
            $('#sdp-paypal-form input[name="amount"]').val(amount);
            $('#sdp-paypal-form').submit();
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'sdp_add_inline_assets');