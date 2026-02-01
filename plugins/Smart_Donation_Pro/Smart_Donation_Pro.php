/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Boost your WordPress site revenue with smart, contextual donation prompts and one-time tips.
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
        add_action('wp_footer', array($this, 'render_donation_prompt'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sdp_enabled') !== 'yes') return;
        $this->schedule_cron();
    }

    public function enqueue_scripts() {
        if (get_option('sdp_enabled') !== 'yes') return;
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'sdp.css', array(), '1.0.0');
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function render_donation_prompt() {
        if (get_option('sdp_enabled') !== 'yes') return;
        $scroll_percent = get_option('sdp_scroll_percent', 70);
        $delay = get_option('sdp_delay', 30);
        $amounts = get_option('sdp_amounts', '5,10,20,50');
        $message = get_option('sdp_message', 'Enjoying the content? Support us with a quick tip!');
        $paypal_email = get_option('sdp_paypal_email');
        if (!$paypal_email) return;
        ?>
        <div id="sdp-prompt" class="sdp-modal" style="display:none;">
            <div class="sdp-overlay"></div>
            <div class="sdp-content">
                <button class="sdp-close">&times;</button>
                <h3><?php echo esc_html($message); ?></h3>
                <div class="sdp-amounts">
                    <?php foreach (explode(',', $amounts) as $amount): ?>
                        <button class="sdp-amount-btn" data-amount="<?php echo trim($amount); ?>"><?php echo '$' . trim($amount); ?></button>
                    <?php endforeach; ?>
                </div>
                <form id="sdp-paypal-form" action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
                    <input type="hidden" name="cmd" value="_xclick">
                    <input type="hidden" name="business" value="<?php echo esc_attr($paypal_email); ?>">
                    <input type="hidden" name="item_name" value="Tip via Smart Donation Pro">
                    <input type="hidden" name="amount" id="sdp-amount" value="">
                    <input type="hidden" name="currency_code" value="USD">
                    <input type="submit" value="Donate via PayPal" class="sdp-donate-btn">
                </form>
                <button id="sdp-no-thanks">No thanks</button>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            var shown = localStorage.getItem('sdp_shown');
            if (shown) return;
            setTimeout(function() {
                var scroll = $(window).scrollTop() / ($(document).height() - $(window).height()) * 100;
                if (scroll > <?php echo $scroll_percent; ?>) {
                    $('#sdp-prompt').fadeIn();
                    localStorage.setItem('sdp_shown', '1');
                }
            }, <?php echo $delay; ?> * 1000);
            $('.sdp-close, #sdp-no-thanks').click(function() {
                $('#sdp-prompt').fadeOut();
            });
            $('.sdp-amount-btn').click(function() {
                $('#sdp-amount').val($(this).data('amount'));
            });
        });
        </script>
        <style>
        #sdp-prompt { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999; }
        .sdp-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .sdp-content { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; max-width: 400px; }
        .sdp-close { position: absolute; top: 10px; right: 15px; background: none; border: none; font-size: 24px; cursor: pointer; }
        .sdp-amounts button { margin: 5px; padding: 10px 20px; background: #007cba; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .sdp-donate-btn { width: 100%; padding: 12px; background: #ffc439; color: #000; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; margin-top: 10px; }
        #sdp-no-thanks { width: 100%; padding: 10px; background: none; border: 1px solid #ccc; border-radius: 5px; cursor: pointer; margin-top: 10px; }
        </style>
        <?php
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'sdp-settings', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('sdp_options', 'sdp_enabled');
        register_setting('sdp_options', 'sdp_paypal_email');
        register_setting('sdp_options', 'sdp_message');
        register_setting('sdp_options', 'sdp_amounts');
        register_setting('sdp_options', 'sdp_scroll_percent');
        register_setting('sdp_options', 'sdp_delay');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('sdp_options'); ?>
                <table class="form-table">
                    <tr><th>Enable Plugin</th><td><input type="checkbox" name="sdp_enabled" value="yes" <?php checked(get_option('sdp_enabled'), 'yes'); ?>></td></tr>
                    <tr><th>PayPal Email</th><td><input type="email" name="sdp_paypal_email" value="<?php echo esc_attr(get_option('sdp_paypal_email')); ?>" class="regular-text"></td></tr>
                    <tr><th>Donation Message</th><td><input type="text" name="sdp_message" value="<?php echo esc_attr(get_option('sdp_message', 'Enjoying the content? Support us with a quick tip!')); ?>" class="regular-text"></td></tr>
                    <tr><th>Suggested Amounts</th><td><input type="text" name="sdp_amounts" value="<?php echo esc_attr(get_option('sdp_amounts', '5,10,20,50')); ?>" class="regular-text"> (comma-separated)</td></tr>
                    <tr><th>Show After Scroll %</th><td><input type="number" name="sdp_scroll_percent" value="<?php echo esc_attr(get_option('sdp_scroll_percent', 70)); ?>" min="0" max="100"> %</td></tr>
                    <tr><th>Delay (seconds)</th><td><input type="number" name="sdp_delay" value="<?php echo esc_attr(get_option('sdp_delay', 30)); ?>" min="0"></td></tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Pro:</strong> Advanced targeting, analytics, custom designs, and more. <a href="https://example.com/pro">Get Pro</a></p>
        </div>
        <?php
    }

    public function activate() {
        update_option('sdp_enabled', 'no');
    }

    private function schedule_cron() {
        if (!wp_next_scheduled('sdp_daily_event')) {
            wp_schedule_event(time(), 'daily', 'sdp_daily_event');
        }
    }
}

new SmartDonationPro();

// Clear cron on deactivation
register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('sdp_daily_event');
});
