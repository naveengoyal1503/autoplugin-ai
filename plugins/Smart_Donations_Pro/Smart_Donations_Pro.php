/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donations_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donations Pro
 * Plugin URI: https://example.com/smart-donations-pro
 * Description: Easily add donation buttons and forms to monetize your WordPress site with one-time or recurring payments.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartDonationsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        add_action('wp_ajax_sdp_process_donation', array($this, 'process_donation'));
        add_action('wp_ajax_nopriv_sdp_process_donation', array($this, 'process_donation'));
    }

    public function init() {
        if (get_option('sdp_paypal_email')) {
            // Plugin is set up
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
            'currency' => '$'
        ), $atts);

        $paypal_email = get_option('sdp_paypal_email');
        if (!$paypal_email) {
            return '<p>Please <a href="' . admin_url('options-general.php?page=sdp-settings') . '">configure PayPal email</a> in settings.</p>';
        }

        $current = get_option('sdp_total_donated', 0);
        $progress = min(100, ($current / $atts['goal']) * 100);

        ob_start();
        ?>
        <div class="sdp-container" style="max-width: 400px; margin: 20px 0;">
            <div class="sdp-progress" style="background: #f0f0f0; height: 20px; border-radius: 10px; overflow: hidden; margin-bottom: 15px;">
                <div class="sdp-progress-bar" style="background: #28a745; height: 100%; width: <?php echo $progress; ?>%; transition: width 0.3s;"></div>
            </div>
            <p style="text-align: center; font-weight: bold;">Goal: <?php echo $atts['currency'] . $atts['goal']; ?> | Raised: <?php echo $atts['currency'] . number_format($current, 2); ?> (<?php echo $progress; ?>%)</p>
            <form class="sdp-form" method="post" action="https://www.paypal.com/cgi-bin/webscr">
                <input type="hidden" name="cmd" value="_xclick">
                <input type="hidden" name="business" value="<?php echo esc_attr($paypal_email); ?>">
                <input type="hidden" name="item_name" value="Donation to <?php echo get_bloginfo('name'); ?>">
                <input type="hidden" name="currency_code" value="USD">
                <input type="hidden" name="amount" id="sdp-amount" value="<?php echo esc_attr($atts['amount']); ?>">
                <input type="hidden" name="return" value="<?php echo esc_url(home_url()); ?>">
                <input type="submit" value="<?php echo esc_attr($atts['button_text']); ?>" class="sdp-button" style="background: #007cba; color: white; border: none; padding: 12px 24px; border-radius: 5px; cursor: pointer; width: 100%; font-size: 16px;">
            </form>
            <p style="text-align: center; margin-top: 10px; font-size: 14px;">Suggested: <button type="button" class="sdp-suggest" data-amount="5">$5</button> <button type="button" class="sdp-suggest" data-amount="10">$10</button> <button type="button" class="sdp-suggest" data-amount="25">$25</button></p>
        </div>
        <script>
        jQuery(function($) {
            $('.sdp-suggest').click(function() {
                $('#sdp-amount').val($(this).data('amount'));
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        // Simulate donation tracking (in pro version, integrate webhooks)
        $amount = sanitize_text_field($_POST['amount'] ?? 0);
        $current = (float) get_option('sdp_total_donated', 0);
        update_option('sdp_total_donated', $current + (float)$amount);
        wp_send_json_success('Thank you for your donation!');
    }
}

// Settings page
add_action('admin_menu', function() {
    add_options_page('Smart Donations Pro', 'Donations Pro', 'manage_options', 'sdp-settings', function() {
        if (isset($_POST['sdp_paypal_email'])) {
            update_option('sdp_paypal_email', sanitize_email($_POST['sdp_paypal_email']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $email = get_option('sdp_paypal_email');
        echo '<div class="wrap"><h1>Smart Donations Pro Settings</h1><form method="post"><table class="form-table"><tr><th>PayPal Email</th><td><input type="email" name="sdp_paypal_email" value="' . esc_attr($email) . '" class="regular-text" required></td></tr></table><p class="submit"><input type="submit" value="Save" class="button-primary"></p></form><p>Use shortcode: <code>[smart_donation amount="10" goal="1000" button_text="Support Us"]</code></p></div>';
    });
});

new SmartDonationsPro();

// Freemium upsell notice
add_action('admin_notices', function() {
    if (!get_option('sdp_paypal_email') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Unlock <strong>recurring donations, analytics, and custom themes</strong> with <a href="https://example.com/pro" target="_blank">Smart Donations Pro Premium</a>! Dismiss forever on setup.</p></div>';
    }
});