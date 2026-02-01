/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Boost donations with one-click PayPal, tiered levels, progress bars, and analytics.
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('smart_donation_paypal_email')) {
            add_action('wp_ajax_process_donation', array($this, 'process_donation'));
            add_action('wp_ajax_nopriv_process_donation', array($this, 'process_donation'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('paypal-sdk', 'https://www.paypal.com/sdk/js?client-id=TEST&currency=USD', array(), null, true);
        wp_enqueue_script('smart-donation-js', plugin_dir_url(__FILE__) . 'smart-donation.js', array('jquery', 'paypal-sdk'), '1.0.0', true);
        wp_enqueue_style('smart-donation-css', plugin_dir_url(__FILE__) . 'smart-donation.css', array(), '1.0.0');
        wp_localize_script('smart-donation-js', 'sd_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sd_nonce')));
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'goal' => '1000',
            'title' => 'Support Us!',
            'button_text' => 'Donate Now'
        ), $atts);

        $current = get_option('smart_donation_total', 0);
        $progress = min(100, ($current / $atts['goal']) * 100);

        ob_start();
        ?>
        <div class="smart-donation-widget">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <div class="progress-bar">
                <div class="progress" style="width: <?php echo $progress; ?>%;"></div>
            </div>
            <p>$<?php echo number_format($current); ?> raised of $<?php echo $atts['goal']; ?> goal</p>
            <div id="paypal-button-container"></div>
            <div class="donation-tiers">
                <button class="tier-btn" data-amount="5">$5 Tip</button>
                <button class="tier-btn" data-amount="10">$10 Coffee</button>
                <button class="tier-btn" data-amount="25">$25 Supporter</button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        check_ajax_referer('sd_nonce', 'nonce');
        $email = get_option('smart_donation_paypal_email');
        $amount = floatval($_POST['amount']);
        // In production, process actual PayPal webhook or IPN
        // For demo, simulate
        $total = get_option('smart_donation_total', 0) + $amount;
        update_option('smart_donation_total', $total);
        wp_send_json_success('Thank you for your donation!');
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro', 'Donation Settings', 'manage_options', 'smart-donation', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['paypal_email'])) {
            update_option('smart_donation_paypal_email', sanitize_email($_POST['paypal_email']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $email = get_option('smart_donation_paypal_email');
        echo '<div class="wrap"><h1>Smart Donation Pro Settings</h1><form method="post"><table class="form-table"><tr><th>PayPal Email</th><td><input type="email" name="paypal_email" value="' . esc_attr($email) . '" /></td></tr></table><p><input type="submit" class="button-primary" value="Save" /></p></form></div>';
    }

    public function activate() {
        update_option('smart_donation_total', 0);
    }
}

new SmartDonationPro();

// Inline CSS
add_action('wp_head', function() { ?>
<style>
.smart-donation-widget { max-width: 400px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; text-align: center; background: #f9f9f9; }
.progress-bar { background: #eee; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0; }
.progress { height: 100%; background: linear-gradient(90deg, #4CAF50, #45a049); transition: width 0.3s; }
.donation-tiers { margin-top: 15px; }
.tier-btn { background: #007cba; color: white; border: none; padding: 10px 20px; margin: 5px; border-radius: 5px; cursor: pointer; }
.tier-btn:hover { background: #005a87; }
@media (max-width: 768px) { .smart-donation-widget { margin: 10px; padding: 15px; } }
</style>
<?php });

// Inline JS
add_action('wp_footer', function() { ?>
<script>
jQuery(document).ready(function($) {
    paypal.Buttons({
        createOrder: function(data, actions) {
            return actions.order.create({
                purchase_units: [{
                    amount: { value: '0.01' } // Demo
                }]
            });
        },
        onApprove: function(data, actions) {
            return actions.order.capture().then(function(details) {
                $.post(sd_ajax.ajaxurl, {
                    action: 'process_donation',
                    amount: $('.tier-btn.active').data('amount') || 10,
                    nonce: sd_ajax.nonce
                }, function() {
                    location.reload();
                });
            });
        }
    }).render('#paypal-button-container');

    $('.tier-btn').click(function() {
        $(this).addClass('active').siblings().removeClass('active');
    });
});
</script>
<?php });