/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donations_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donations Pro
 * Plugin URI: https://example.com/smart-donations-pro
 * Description: Boost your WordPress site revenue with easy PayPal donations, progress bars, and analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-donations-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartDonationsPro {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        add_shortcode('smart_donation_goal', array($this, 'goal_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('smart-donations-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (get_option('smart_donations_paypal_email')) {
            add_action('wp_ajax_smart_donation_process', array($this, 'process_donation'));
            add_action('wp_ajax_nopriv_smart_donation_process', array($this, 'process_donation'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('paypal-sdk', 'https://www.paypal.com/sdk/js?client-id=' . get_option('smart_donations_paypal_client_id', 'YOUR_SANDBOX_CLIENT_ID') . '&currency=USD', array(), '1.0', true);
        wp_enqueue_script('smart-donations-js', plugin_dir_url(__FILE__) . 'assets/smart-donations.js', array('jquery'), '1.0', true);
        wp_enqueue_style('smart-donations-css', plugin_dir_url(__FILE__) . 'assets/smart-donations.css', array(), '1.0');
        wp_localize_script('smart-donations-js', 'smartDonations', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('smart_donations_nonce')
        ));
    }

    public function admin_menu() {
        add_options_page('Smart Donations Pro', 'Smart Donations', 'manage_options', 'smart-donations', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['smart_donations_submit'])) {
            update_option('smart_donations_paypal_email', sanitize_email($_POST['paypal_email']));
            update_option('smart_donations_paypal_client_id', sanitize_text_field($_POST['paypal_client_id']));
            update_option('smart_donations_goal_amount', floatval($_POST['goal_amount']));
            update_option('smart_donations_current_amount', floatval($_POST['current_amount']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Donations Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="paypal_email" value="<?php echo esc_attr(get_option('smart_donations_paypal_email')); ?>" required /></td>
                    </tr>
                    <tr>
                        <th>PayPal Client ID (Sandbox for testing)</th>
                        <td><input type="text" name="paypal_client_id" value="<?php echo esc_attr(get_option('smart_donations_paypal_client_id')); ?>" required /></td>
                    </tr>
                    <tr>
                        <th>Goal Amount ($)</th>
                        <td><input type="number" step="0.01" name="goal_amount" value="<?php echo esc_attr(get_option('smart_donations_goal_amount', 1000)); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Current Amount ($)</th>
                        <td><input type="number" step="0.01" name="current_amount" value="<?php echo esc_attr(get_option('smart_donations_current_amount', 0)); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Shortcodes</h2>
            <p><code>[smart_donation]</code> - Add donation button</p>
            <p><code>[smart_donation_goal]</code> - Show progress bar</p>
            <p><strong>Upgrade to Pro</strong> for analytics, custom themes, and more! <a href="https://example.com/pro">Get Pro</a></p>
        </div>
        <?php
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array('amount' => '10'), $atts);
        ob_start();
        ?>
        <div id="smart-donation-form" data-amount="<?php echo esc_attr($atts['amount']); ?>">
            <div id="paypal-button-container"></div>
            <p><small>Anonymous donations supported. Thank you!</small></p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function goal_shortcode() {
        $goal = get_option('smart_donations_goal_amount', 1000);
        $current = get_option('smart_donations_current_amount', 0);
        $percent = min(100, ($current / $goal) * 100);
        ob_start();
        ?>
        <div class="donation-goal">
            <p>Raised: $<span id="current-amount"><?php echo number_format($current, 2); ?></span> / $<span><?php echo number_format($goal, 2); ?></span></p>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo $percent; ?>%;"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        if (!wp_verify_nonce($_POST['nonce'], 'smart_donations_nonce')) {
            wp_die('Security check failed');
        }
        $amount = floatval($_POST['amount']);
        $current = get_option('smart_donations_current_amount', 0);
        update_option('smart_donations_current_amount', $current + $amount);
        wp_send_json_success('Donation recorded!');
    }

    public function activate() {
        add_option('smart_donations_current_amount', 0);
    }
}

SmartDonationsPro::get_instance();

// Create assets directories on activation
register_activation_hook(__FILE__, function() {
    $assets_dir = plugin_dir_path(__FILE__) . 'assets/';
    if (!file_exists($assets_dir)) {
        wp_mkdir_p($assets_dir);
    }
    // Minimal CSS
    $css = ".donation-goal { margin: 20px 0; } .progress-bar { background: #eee; height: 20px; border-radius: 10px; overflow: hidden; } .progress-fill { height: 100%; background: #4CAF50; transition: width 0.3s; } #smart-donation-form { text-align: center; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }";
    file_put_contents($assets_dir . 'smart-donations.css', $css);
    // Minimal JS
    $js = "jQuery(document).ready(function($) { paypal.Buttons({ createOrder: function(data, actions) { return actions.order.create({ purchase_units: [{ amount: { value: $('#smart-donation-form').data('amount') } }] }); }, onApprove: function(data, actions) { return actions.order.capture().then(function(details) { $.post(smartDonations.ajaxurl, { action: 'smart_donation_process', amount: $('#smart-donation-form').data('amount'), nonce: smartDonations.nonce }, function() { location.reload(); }); }); } }).render('#paypal-button-container'); });";
    file_put_contents($assets_dir . 'smart-donations.js', $js);
});
?>