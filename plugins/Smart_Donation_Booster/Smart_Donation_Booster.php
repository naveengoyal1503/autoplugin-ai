/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Booster.php
*/
<?php
/**
 * Plugin Name: Smart Donation Booster
 * Plugin URI: https://example.com/smart-donation-booster
 * Description: Boost revenue with customizable donation buttons, progress bars, and analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartDonationBooster {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('sdb_donate', array($this, 'donate_shortcode'));
        add_action('wp_ajax_sdb_donate', array($this, 'ajax_donate'));
        add_action('wp_ajax_nopriv_sdb_donate', array($this, 'ajax_donate'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sdb_paypal_email')) {
            // Ready
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdb-js', plugin_dir_url(__FILE__) . 'sdb.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdb-css', plugin_dir_url(__FILE__) . 'sdb.css', array(), '1.0.0');
        wp_localize_script('sdb-js', 'sdb_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Smart Donation Booster', 'Donation Booster', 'manage_options', 'sdb-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['sdb_save'])) {
            update_option('sdb_paypal_email', sanitize_email($_POST['paypal_email']));
            update_option('sdb_button_text', sanitize_text_field($_POST['button_text']));
            update_option('sdb_goal_amount', floatval($_POST['goal_amount']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $paypal_email = get_option('sdb_paypal_email', '');
        $button_text = get_option('sdb_button_text', 'Donate Now');
        $goal_amount = get_option('sdb_goal_amount', 1000);
        $current_amount = get_option('sdb_current_amount', 0);
        include plugin_dir_path(__FILE__) . 'settings.html';
    }

    public function donate_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10',
            'currency' => 'USD'
        ), $atts);

        $paypal_email = get_option('sdb_paypal_email');
        if (!$paypal_email) return '<p>Please configure PayPal email in settings.</p>';

        $paypal_url = 'https://www.paypal.com/donate?hosted_button_id=TEST&amount=' . $atts['amount'] . '&currency=' . $atts['currency'] . '&email=' . $paypal_email;

        ob_start();
        ?>
        <div class="sdb-donate-widget">
            <h3><?php echo esc_html(get_option('sdb_button_text', 'Support Us')); ?></h3>
            <?php if ($goal = get_option('sdb_goal_amount')): $progress = min(100, (get_option('sdb_current_amount', 0) / $goal) * 100); ?>
            <div class="sdb-progress-bar">
                <div class="sdb-progress" style="width: <?php echo $progress; ?>%;"></div>
            </div>
            <p>$<?php echo number_format(get_option('sdb_current_amount', 0)); ?> / $<?php echo $goal; ?> raised</p>
            <?php endif; ?>
            <a href="<?php echo esc_url($paypal_url); ?>" class="sdb-donate-btn" target="_blank">$<?php echo $atts['amount']; ?> - <?php echo esc_html(get_option('sdb_button_text', 'Donate Now')); ?></a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_donate() {
        if (!wp_verify_nonce($_POST['nonce'], 'sdb_nonce')) {
            wp_die('Security check failed');
        }
        $amount = floatval($_POST['amount']);
        $current = get_option('sdb_current_amount', 0);
        update_option('sdb_current_amount', $current + $amount);
        wp_send_json_success(array('new_amount' => $current + $amount));
    }

    public function activate() {
        if (!get_option('sdb_paypal_email')) {
            update_option('sdb_button_text', 'Buy Me a Coffee');
        }
    }
}

SmartDonationBooster::get_instance();

/* Sample CSS - Save as sdb.css in plugin folder */
/* .sdb-donate-widget { border: 1px solid #ddd; padding: 20px; border-radius: 8px; text-align: center; background: #f9f9f9; } .sdb-progress-bar { height: 20px; background: #eee; border-radius: 10px; overflow: hidden; margin: 10px 0; } .sdb-progress { height: 100%; background: #28a745; transition: width 0.3s; } .sdb-donate-btn { display: inline-block; padding: 12px 24px; background: #007cba; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; } */

/* Sample JS - Save as sdb.js */
/* jQuery(document).ready(function($) { $('.sdb-donate-btn').click(function(e) { e.preventDefault(); var amount = prompt('Enter donation amount:'); if (amount) { $.post(sdb_ajax.ajaxurl, { action: 'sdb_donate', amount: amount, nonce: 'test_nonce' }, function(res) { if (res.success) alert('Thank you! Total now: $' + res.data.new_amount); }); } }); }); */