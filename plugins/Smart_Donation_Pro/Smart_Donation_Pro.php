/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Boost your WordPress site revenue with easy donation buttons, progress bars, and payment integration.
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
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        add_action('wp_ajax_sdp_process_donation', array($this, 'process_donation'));
        add_action('wp_ajax_nopriv_sdp_process_donation', array($this, 'process_donation'));
    }

    public function init() {
        if (get_option('sdp_paypal_email')) {
            // PayPal integration ready
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'sdp-style.css', array(), '1.0.0');
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10',
            'button_text' => 'Donate Now',
            'goal' => '1000',
            'current' => '250',
            'paypal_email' => get_option('sdp_paypal_email', ''),
        ), $atts);

        $progress = ($atts['current'] / $atts['goal']) * 100;

        ob_start();
        ?>
        <div class="sdp-container">
            <div class="sdp-progress-bar">
                <div class="sdp-progress" style="width: <?php echo esc_attr($progress); ?>%;"></div>
            </div>
            <p class="sdp-goal">Goal: $<?php echo esc_html($atts['goal']); ?> | Raised: $<?php echo esc_html($atts['current']); ?></p>
            <form class="sdp-form" data-paypal="<?php echo esc_attr($atts['paypal_email']); ?>">
                <input type="number" name="amount" value="<?php echo esc_attr($atts['amount']); ?>" min="1" step="0.01" placeholder="Enter amount">
                <button type="submit" class="sdp-button"><?php echo esc_html($atts['button_text']); ?></button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        $amount = sanitize_text_field($_POST['amount']);
        $paypal_email = get_option('sdp_paypal_email');

        if (!$paypal_email) {
            wp_die('PayPal email not configured.');
        }

        $paypal_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=' . urlencode($paypal_email) . '&amount=' . urlencode($amount) . '&item_name=Donation&currency_code=USD&return=' . urlencode(get_site_url()) . '&cancel_return=' . urlencode(get_site_url());

        wp_redirect($paypal_url);
        exit;
    }
}

new SmartDonationPro();

// Admin settings
add_action('admin_menu', function() {
    add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'sdp-settings', 'sdp_settings_page');
});

function sdp_settings_page() {
    if (isset($_POST['sdp_paypal_email'])) {
        update_option('sdp_paypal_email', sanitize_email($_POST['sdp_paypal_email']));
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }
    $paypal_email = get_option('sdp_paypal_email', '');
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
        <p>Use shortcode: <code>[smart_donation amount="10" goal="1000" current="250"]</code></p>
    </div>
    <?php
}

// Inline styles and scripts for single file

function sdp_add_inline_styles() {
    ?>
    <style>
    .sdp-container { max-width: 400px; margin: 20px 0; text-align: center; }
    .sdp-progress-bar { background: #f0f0f0; height: 20px; border-radius: 10px; overflow: hidden; margin-bottom: 10px; }
    .sdp-progress { background: #28a745; height: 100%; transition: width 0.3s; }
    .sdp-goal { font-weight: bold; margin-bottom: 15px; }
    .sdp-form input { padding: 10px; margin-right: 10px; border: 1px solid #ddd; border-radius: 5px; }
    .sdp-button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
    .sdp-button:hover { background: #005a87; }
    </style>
    <?php
}
add_action('wp_head', 'sdp_add_inline_styles');

function sdp_add_inline_scripts() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.sdp-form').on('submit', function(e) {
            e.preventDefault();
            var amount = $(this).find('input[name="amount"]').val();
            var paypal = $(this).data('paypal');
            if (amount && paypal) {
                var url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=' + encodeURIComponent(paypal) + '&amount=' + encodeURIComponent(amount) + '&item_name=Donation&currency_code=USD&return=' + encodeURIComponent(window.location.href) + '&cancel_return=' + encodeURIComponent(window.location.href);
                window.location.href = url;
            }
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'sdp_add_inline_scripts');