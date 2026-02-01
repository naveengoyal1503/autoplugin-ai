/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Booster.php
*/
<?php
/**
 * Plugin Name: Smart Donation Booster
 * Plugin URI: https://example.com/smart-donation-booster
 * Description: Boost donations with smart popups, progress bars, and PayPal integration.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartDonationBooster {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_sdb_donate', array($this, 'handle_donation'));
        add_shortcode('sdb_donation', array($this, 'donation_shortcode'));
    }

    public function init() {
        if (get_option('sdb_enabled')) {
            add_action('wp_footer', array($this, 'render_popup'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', $this->get_js());
        wp_add_inline_style('wp-block-library', $this->get_css());
    }

    private function get_css() {
        return '
#donation-popup {
    display: none;
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    z-index: 9999;
    max-width: 300px;
    text-align: center;
}
#donation-popup.show { display: block; animation: slideIn 0.5s; }
@keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }
.sdb-progress { background: rgba(255,255,255,0.3); height: 8px; border-radius: 4px; margin: 10px 0; overflow: hidden; }
.sdb-progress-fill { height: 100%; background: #4CAF50; transition: width 0.3s; }
.sdb-amounts button { background: rgba(255,255,255,0.2); border: none; color: white; padding: 10px 15px; margin: 5px; border-radius: 5px; cursor: pointer; }
.sdb-amounts button:hover { background: rgba(255,255,255,0.4); }
#close-donation { position: absolute; top: 5px; right: 10px; font-size: 20px; cursor: pointer; }';
    }

    private function get_js() {
        $goal = get_option('sdb_goal', 1000);
        $current = get_option('sdb_current', 0);
        $progress = min(100, ($current / $goal) * 100);
        return "
(jQuery)(function($) {
    setTimeout(function() { $('#donation-popup').addClass('show'); }, 10000);
    $('.sdb-close').click(function() { $('#donation-popup').removeClass('show'); });
    $('.sdb-amount').click(function() {
        var amount = $(this).data('amount');
        $('#sdb_amount').val(amount);
        $('#donation-form').submit();
    });
    $('.sdb-progress-fill').css('width', '{$progress}%');
});";
    }

    public function render_popup() {
        $title = get_option('sdb_title', 'Support Us!');
        $message = get_option('sdb_message', 'Help us reach our goal of $1000. Your donation makes a difference!');
        $amounts = get_option('sdb_amounts', '5,10,20,50');
        $amount_array = explode(',', $amounts);
        echo '<div id="donation-popup">
            <span class="sdb-close">&times;</span>
            <h3>' . esc_html($title) . '</h3>
            <p>' . esc_html($message) . '</p>
            <div class="sdb-progress"><div class="sdb-progress-fill"></div></div>
            <div class="sdb-amounts">';
        foreach ($amount_array as $amt) {
            echo '<button class="sdb-amount" data-amount="' . trim($amt) . '">' . trim($amt) . '</button>';
        }
        echo '</div>
            <form id="donation-form" action="https://www.paypal.com/donate" method="post" target="_blank">
                <input type="hidden" name="business" value="' . get_option('sdb_paypal_email') . '">
                <input type="hidden" name="item_name" value="Support ' . get_bloginfo('name') . '">
                <input type="number" id="sdb_amount" name="amount" min="1" step="0.01" style="width:100%; margin:10px 0; padding:8px; border-radius:5px; border:none;">
                <input type="hidden" name="currency_code" value="USD">
                <input type="submit" value="Donate Now" style="background:#4CAF50; color:white; border:none; padding:12px; width:100%; border-radius:5px; cursor:pointer;">
            </form>
        </div>';
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array('amount' => '10'), $atts);
        return '<div style="text-align:center; padding:20px; background:#f9f9f9; border-radius:10px;">
            <form action="https://www.paypal.com/donate" method="post" target="_blank">
                <input type="hidden" name="business" value="' . get_option('sdb_paypal_email') . '">
                <input type="hidden" name="amount" value="' . $atts['amount'] . '">
                <input type="submit" value="Buy Me a Coffee ($' . $atts['amount'] . ')" style="background:#FF6B35; color:white; border:none; padding:15px 30px; border-radius:25px; cursor:pointer; font-size:16px;">
            </form>
        </div>';
    }

    public function admin_menu() {
        add_options_page('Donation Booster', 'Donation Booster', 'manage_options', 'sdb-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('sdb_enabled', isset($_POST['sdb_enabled']));
            update_option('sdb_title', sanitize_text_field($_POST['sdb_title']));
            update_option('sdb_message', sanitize_textarea_field($_POST['sdb_message']));
            update_option('sdb_goal', intval($_POST['sdb_goal']));
            update_option('sdb_current', intval($_POST['sdb_current']));
            update_option('sdb_amounts', sanitize_text_field($_POST['sdb_amounts']));
            update_option('sdb_paypal_email', sanitize_email($_POST['sdb_paypal_email']));
            echo '<div class="updated"><p>Settings saved!</p></div>';
        }
        $enabled = get_option('sdb_enabled', true);
        ?>
        <div class="wrap">
            <h1>Smart Donation Booster Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr><th>Enable Popup</th><td><input type="checkbox" name="sdb_enabled" <?php checked($enabled); ?>></td></tr>
                    <tr><th>Title</th><td><input type="text" name="sdb_title" value="<?php echo esc_attr(get_option('sdb_title', 'Support Us!')); ?>" class="regular-text"></td></tr>
                    <tr><th>Message</th><td><textarea name="sdb_message" class="large-text"><?php echo esc_textarea(get_option('sdb_message', 'Help us reach our goal!')); ?></textarea></td></tr>
                    <tr><th>Goal Amount</th><td><input type="number" name="sdb_goal" value="<?php echo esc_attr(get_option('sdb_goal', 1000)); ?>"></td></tr>
                    <tr><th>Current Amount</th><td><input type="number" name="sdb_current" value="<?php echo esc_attr(get_option('sdb_current', 0)); ?>"> (Update manually)</td></tr>
                    <tr><th>Quick Amounts</th><td><input type="text" name="sdb_amounts" value="<?php echo esc_attr(get_option('sdb_amounts', '5,10,20,50')); ?>"> (comma-separated)</td></tr>
                    <tr><th>PayPal Email</th><td><input type="email" name="sdb_paypal_email" value="<?php echo esc_attr(get_option('sdb_paypal_email')); ?>" class="regular-text"></td></tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcode <code>[sdb_donation amount="10"]</code> for static buttons.</p>
            <p><strong>Pro Upgrade:</strong> A/B testing, geo-targeting, unlimited goals. <a href="#pro">Get Pro</a></p>
        </div>
        <?php
    }

    public function handle_donation() {
        // AJAX handler for tracking donations (pro feature placeholder)
        wp_die();
    }
}

new SmartDonationBooster();

// Pro upsell notice
add_action('admin_notices', function() {
    if (!get_option('sdb_pro')) {
        echo '<div class="notice notice-info"><p>Unlock <strong>Smart Donation Booster Pro</strong>: Advanced targeting & analytics for $29/year! <a href="#pro">Upgrade Now</a></p></div>';
    }
});