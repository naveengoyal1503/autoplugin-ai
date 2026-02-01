/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Easily add donation buttons and fundraising goals to monetize your WordPress site.
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
        // Freemium check: Premium features gated
        if (!get_option('sdp_premium_key')) {
            // Free version limits
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10',
            'currency' => 'USD',
            'goal' => '1000',
            'button_text' => 'Donate Now',
            'goal_text' => 'Help us reach our goal!'
        ), $atts);

        $progress = min(100, (get_option('sdp_current_amount', 0) / $atts['goal']) * 100);

        ob_start();
        ?>
        <div class="sdp-container">
            <?php if ($atts['goal']): ?>
            <div class="sdp-goal">
                <p><?php echo esc_html($atts['goal_text']); ?></p>
                <div class="sdp-progress-bar">
                    <div class="sdp-progress" style="width: <?php echo $progress; ?>%;"></div>
                </div>
                <p><?php echo get_option('sdp_current_amount', 0); ?> / <?php echo $atts['goal']; ?> <?php echo $atts['currency']; ?></p>
            <?php endif; ?>
            <form class="sdp-form" method="post" action="https://www.paypal.com/cgi-bin/webscr">
                <input type="hidden" name="cmd" value="_xclick">
                <input type="hidden" name="business" value="<?php echo get_option('sdp_paypal_email'); ?>">
                <input type="hidden" name="item_name" value="Donation to <?php echo get_bloginfo('name'); ?>">
                <input type="hidden" name="amount" value="<?php echo $atts['amount']; ?>">
                <input type="hidden" name="currency_code" value="<?php echo $atts['currency']; ?>">
                <input type="hidden" name="return" value="<?php echo home_url(); ?>">
                <button type="submit" class="sdp-button"><?php echo esc_html($atts['button_text']); ?></button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        // Simulate donation processing (in premium: integrate Stripe webhook)
        $current = get_option('sdp_current_amount', 0);
        $donation = sanitize_text_field($_POST['amount'] ?? 0);
        update_option('sdp_current_amount', $current + (float)$donation);
        wp_send_json_success('Thank you for your donation!');
    }
}

// Admin settings
if (is_admin()) {
    add_action('admin_menu', function() {
        add_options_page('Smart Donation Pro', 'SDP Settings', 'manage_options', 'sdp-settings', 'sdp_settings_page');
    });
}

function sdp_settings_page() {
    if (isset($_POST['sdp_paypal_email'])) {
        update_option('sdp_paypal_email', sanitize_email($_POST['sdp_paypal_email']));
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>Smart Donation Pro Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>PayPal Email</th>
                    <td><input type="email" name="sdp_paypal_email" value="<?php echo get_option('sdp_paypal_email'); ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th>Upgrade to Premium</th>
                    <td><a href="https://example.com/premium" class="button button-primary">Get Premium ($29/year)</a> - Stripe, Recurring, Analytics</td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

new SmartDonationPro();

// Create assets directories on activation
register_activation_hook(__FILE__, function() {
    $upload_dir = plugin_dir_path(__FILE__) . 'assets';
    if (!file_exists($upload_dir)) {
        wp_mkdir_p($upload_dir);
    }
    // Default CSS
    file_put_contents($upload_dir . '/style.css', ".sdp-container { max-width: 400px; margin: 20px auto; text-align: center; } .sdp-progress-bar { background: #eee; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0; } .sdp-progress { background: #4CAF50; height: 100%; transition: width 0.3s; } .sdp-button { background: #007cba; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; } .sdp-button:hover { background: #005a87; } .sdp-form input { margin: 5px; }");
    // Default JS
    file_put_contents($upload_dir . '/script.js', "jQuery(document).ready(function($) { $('.sdp-button').on('click', function(e) { /* Premium analytics */ }); });");
});
?>