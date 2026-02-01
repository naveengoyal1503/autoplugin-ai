/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Booster.php
*/
<?php
/**
 * Plugin Name: Smart Donation Booster
 * Plugin URI: https://example.com/smart-donation-booster
 * Description: Boost donations with progress bars, PayPal buttons, and goal tracking.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
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
        add_shortcode('donation_goal', array($this, 'donation_goal_shortcode'));
        add_action('wp_ajax_sdb_donate', array($this, 'handle_donation'));
        add_action('wp_ajax_nopriv_sdb_donate', array($this, 'handle_donation'));
    }

    public function init() {
        if (get_option('sdb_goal') === false) {
            add_option('sdb_goal', 1000);
            add_option('sdb_current', 0);
            add_option('sdb_currency', 'USD');
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdb-script', plugin_dir_url(__FILE__) . 'sdb-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sdb-script', 'sdb_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdb_nonce')));
        wp_enqueue_style('sdb-style', plugin_dir_url(__FILE__) . 'sdb-style.css', array(), '1.0.0');
    }

    public function donation_goal_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 'default'
        ), $atts);

        $goal = get_option('sdb_goal', 1000);
        $current = get_option('sdb_current', 0);
        $currency = get_option('sdb_currency', 'USD');
        $percent = min(100, ($current / $goal) * 100);

        ob_start();
        ?>
        <div class="sdb-container">
            <h3>Support Our Work!</h3>
            <div class="sdb-progress" style="width: 100%; height: 20px; background: #f0f0f0; border-radius: 10px; overflow: hidden;">
                <div class="sdb-progress-bar" style="height: 100%; background: #4CAF50; width: <?php echo $percent; ?>%; transition: width 0.5s;"></div>
            </div>
            <p><strong><?php echo $currency . number_format($current); ?></strong> raised of <?php echo $currency . number_format($goal); ?> goal</p>
            <button id="sdb-donate-btn" class="sdb-donate-btn">Donate Now</button>
            <div id="sdb-paypal" style="display:none;">
                <div class="sdb-paypal-button" data-amount="10"></div>
                <small>Or enter custom amount: $<input type="number" id="sdb-custom-amount" min="1" max="1000" value="10">
                <button id="sdb-paypal-btn">Pay with PayPal</button>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#sdb-donate-btn').click(function() {
                $('#sdb-paypal').toggle();
            });
            $('#sdb-paypal-btn').click(function() {
                var amount = $('#sdb-custom-amount').val();
                window.location.href = 'https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=YOUR_PAYPAL_EMAIL&amount=' + amount + '&item_name=Donation';
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function handle_donation() {
        check_ajax_referer('sdb_nonce', 'nonce');
        $amount = floatval($_POST['amount']);
        $current = get_option('sdb_current', 0);
        update_option('sdb_current', $current + $amount);
        wp_send_json_success('Thank you for your donation!');
    }
}

// Admin settings page
if (is_admin()) {
    add_action('admin_menu', function() {
        add_options_page('Donation Booster', 'Donation Booster', 'manage_options', 'sdb-settings', 'sdb_settings_page');
    });
}

function sdb_settings_page() {
    if (isset($_POST['sdb_goal'])) {
        update_option('sdb_goal', floatval($_POST['sdb_goal']));
        update_option('sdb_current', floatval($_POST['sdb_current']));
        update_option('sdb_currency', sanitize_text_field($_POST['sdb_currency']));
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }
    $goal = get_option('sdb_goal', 1000);
    $current = get_option('sdb_current', 0);
    $currency = get_option('sdb_currency', 'USD');
    ?>
    <div class="wrap">
        <h1>Smart Donation Booster Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>Goal Amount</th>
                    <td><input type="number" name="sdb_goal" value="<?php echo $goal; ?>" /></td>
                </tr>
                <tr>
                    <th>Current Amount</th>
                    <td><input type="number" name="sdb_current" value="<?php echo $current; ?>" /></td>
                </tr>
                <tr>
                    <th>Currency</th>
                    <td>
                        <select name="sdb_currency">
                            <option value="USD" <?php selected($currency, 'USD'); ?>>USD</option>
                            <option value="EUR" <?php selected($currency, 'EUR'); ?>>EUR</option>
                            <option value="GBP" <?php selected($currency, 'GBP'); ?>>GBP</option>
                        </select>
                    </td>
                </tr>
            </table>
            <p class="submit"><input type="submit" class="button-primary" value="Save Settings" /></p>
        </form>
        <p>Use shortcode: <code>[donation_goal]</code></p>
        <p><strong>Pro Upgrade:</strong> Multiple goals, analytics, custom themes - <a href="https://example.com/pro">Get Pro</a></p>
    </div>
    <?php
}

SmartDonationBooster::get_instance();

// Inline styles and JS for self-contained
add_action('wp_head', function() {
    echo '<style>
    .sdb-container { max-width: 400px; margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 10px; text-align: center; background: #f9f9f9; }
    .sdb-donate-btn { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
    .sdb-donate-btn:hover { background: #005a87; }
    </style>';
    echo '<script>jQuery(document).ready(function($){ /* Additional JS if needed */ });</script>';
});

// Replace YOUR_PAYPAL_EMAIL with actual email in production
?>