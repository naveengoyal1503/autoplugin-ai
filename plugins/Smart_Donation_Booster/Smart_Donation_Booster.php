/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Booster.php
*/
<?php
/**
 * Plugin Name: Smart Donation Booster
 * Plugin URI: https://example.com/smart-donation-booster
 * Description: Boost donations with progress bars, goals, and PayPal buttons.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-donation-booster
 */

if (!defined('ABSPATH')) exit;

class SmartDonationBooster {
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
        add_shortcode('sdb_donation', array($this, 'donation_shortcode'));
        add_action('wp_ajax_sdb_donate', array($this, 'handle_donation'));
        add_action('wp_ajax_nopriv_sdb_donate', array($this, 'handle_donation'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('smart-donation-booster', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdb-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdb-style', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
        wp_localize_script('sdb-script', 'sdb_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdb_nonce')));
    }

    public function admin_menu() {
        add_options_page('Donation Booster', 'Donation Booster', 'manage_options', 'sdb-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('sdb_paypal_email', sanitize_email($_POST['paypal_email']));
            update_option('sdb_goal_amount', floatval($_POST['goal_amount']));
            update_option('sdb_current_amount', floatval($_POST['current_amount']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $paypal_email = get_option('sdb_paypal_email', '');
        $goal = get_option('sdb_goal_amount', 1000);
        $current = get_option('sdb_current_amount', 0);
        ?>
        <div class="wrap">
            <h1>Smart Donation Booster Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="paypal_email" value="<?php echo esc_attr($paypal_email); ?>" class="regular-text" required /></td>
                    </tr>
                    <tr>
                        <th>Goal Amount</th>
                        <td><input type="number" name="goal_amount" value="<?php echo esc_attr($goal); ?>" step="0.01" /></td>
                    </tr>
                    <tr>
                        <th>Current Amount</th>
                        <td><input type="number" name="current_amount" value="<?php echo esc_attr($current); ?>" step="0.01" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcode: <code>[sdb_donation]</code></p>
        </div>
        <?php
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array('title' => 'Support Us!', 'button_text' => 'Donate Now'), $atts);
        $paypal_email = get_option('sdb_paypal_email');
        $goal = get_option('sdb_goal_amount', 1000);
        $current = get_option('sdb_current_amount', 0);
        $progress = min(100, ($current / $goal) * 100);

        if (!$paypal_email) {
            return '<p>Please set your PayPal email in settings.</p>';
        }

        ob_start();
        ?>
        <div class="sdb-container">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <div class="sdb-progress-bar">
                <div class="sdb-progress" style="width: <?php echo $progress; ?>%;"></div>
            </div>
            <p>$<?php echo number_format($current, 2); ?> / $<?php echo number_format($goal, 2); ?> raised</p>
            <form class="sdb-donate-form" method="post" action="https://www.paypal.com/donate">
                <input type="hidden" name="business" value="<?php echo esc_attr($paypal_email); ?>">
                <input type="hidden" name="item_name" value="Support via Smart Donation Booster">
                <input type="hidden" name="currency_code" value="USD">
                <input type="number" name="amount" placeholder="Enter amount" step="0.01" min="1" required>
                <button type="submit" name="submit"><?php echo esc_html($atts['button_text']); ?></button>
            </form>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('.sdb-donate-form').on('submit', function(e) {
                var amount = $(this).find('input[name="amount"]').val();
                if (!amount || amount < 1) {
                    alert('Please enter a valid amount.');
                    e.preventDefault();
                    return false;
                }
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function handle_donation() {
        check_ajax_referer('sdb_nonce', 'nonce');
        $amount = floatval($_POST['amount']);
        $current = get_option('sdb_current_amount', 0);
        update_option('sdb_current_amount', $current + $amount);
        wp_send_json_success('Thank you for your donation!');
    }

    public function activate() {
        if (!get_option('sdb_paypal_email')) {
            update_option('sdb_goal_amount', 1000);
            update_option('sdb_current_amount', 0);
        }
    }
}

SmartDonationBooster::get_instance();

// Pro upsell notice
function sdb_pro_notice() {
    if (current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Smart Donation Booster Pro</strong> for unlimited goals, analytics & more! <a href="https://example.com/pro" target="_blank">Get Pro</a></p></div>';
    }
}
add_action('admin_notices', 'sdb_pro_notice');

// Create assets directories on activation
register_activation_hook(__FILE__, function() {
    $upload_dir = wp_upload_dir();
    $assets_dir = plugin_dir_path(__FILE__) . 'assets';
    if (!file_exists($assets_dir)) {
        wp_mkdir_p($assets_dir);
    }
    // Minimal CSS
    file_put_contents($assets_dir . '/style.css', ".sdb-container { max-width: 400px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; text-align: center; background: #f9f9f9; } .sdb-progress-bar { background: #eee; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0; } .sdb-progress { height: 100%; background: linear-gradient(90deg, #4CAF50, #45a049); transition: width 0.3s; } .sdb-donate-form input[type=\"number\"] { width: 100px; padding: 8px; margin: 10px; } .sdb-donate-form button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; } .sdb-donate-form button:hover { background: #005a87; }");
    // Minimal JS
    file_put_contents($assets_dir . '/script.js', "jQuery(document).ready(function($) { console.log('SDB loaded'); });");
});