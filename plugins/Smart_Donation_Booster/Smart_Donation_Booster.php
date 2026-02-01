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
        add_shortcode('donation_goal', array($this, 'donation_goal_shortcode'));
        add_action('wp_ajax_sdb_donate', array($this, 'handle_donation'));
        add_action('wp_ajax_nopriv_sdb_donate', array($this, 'handle_donation'));
    }

    public function init() {
        if (get_option('sdb_goal_amount')) {
            // Auto-check progress every 6 hours
            if (!get_transient('sdb_progress_checked')) {
                $this->update_progress();
                set_transient('sdb_progress_checked', true, 6 * HOUR_IN_SECONDS);
            }
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdb-script', plugin_dir_url(__FILE__) . 'sdb-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdb-style', plugin_dir_url(__FILE__) . 'sdb-style.css', array(), '1.0.0');
        wp_localize_script('sdb-script', 'sdb_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdb_nonce')));
    }

    public function admin_menu() {
        add_options_page('Donation Booster Settings', 'Donation Booster', 'manage_options', 'sdb-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['sdb_submit'])) {
            update_option('sdb_goal_amount', sanitize_text_field($_POST['goal_amount']));
            update_option('sdb_current_amount', floatval($_POST['current_amount']));
            update_option('sdb_paypal_email', sanitize_email($_POST['paypal_email']));
            update_option('sdb_goal_text', sanitize_text_field($_POST['goal_text']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $goal = get_option('sdb_goal_amount', 1000);
        $current = get_option('sdb_current_amount', 0);
        $paypal = get_option('sdb_paypal_email', '');
        $text = get_option('sdb_goal_text', 'Help us reach our goal!');
        ?>
        <div class="wrap">
            <h1>Smart Donation Booster Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Goal Amount</th>
                        <td><input type="number" name="goal_amount" value="<?php echo $goal; ?>" step="0.01"></td>
                    </tr>
                    <tr>
                        <th>Current Amount</th>
                        <td><input type="number" name="current_amount" value="<?php echo $current; ?>" step="0.01"></td>
                    </tr>
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="paypal_email" value="<?php echo $paypal; ?>"></td>
                    </tr>
                    <tr>
                        <th>Goal Text</th>
                        <td><input type="text" name="goal_text" value="<?php echo $text; ?>"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcode: <code>[donation_goal]</code></p>
        </div>
        <?php
    }

    public function donation_goal_shortcode($atts) {
        $goal = floatval(get_option('sdb_goal_amount', 1000));
        $current = floatval(get_option('sdb_current_amount', 0));
        $percent = $goal > 0 ? min(100, ($current / $goal) * 100) : 0;
        $paypal = get_option('sdb_paypal_email', '');
        $text = get_option('sdb_goal_text', 'Help us reach our goal!');

        ob_start();
        ?>
        <div id="sdb-container" class="sdb-progress-container">
            <h3><?php echo esc_html($text); ?></h3>
            <div class="sdb-progress-bar">
                <div class="sdb-progress-fill" style="width: <?php echo $percent; ?>%;"></div>
            </div>
            <p class="sdb-amount">$<?php echo number_format($current, 2); ?> / $<?php echo number_format($goal, 2); ?> (<?php echo round($percent); ?>%)</p>
            <?php if ($paypal): ?>
            <div class="sdb-donate-btn">
                <a href="https://www.paypal.com/donate?hosted_button_id=SIMULATED&email=<?php echo urlencode($paypal); ?>" target="_blank" class="button button-large">Donate Now</a>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_donation() {
        check_ajax_referer('sdb_nonce', 'nonce');
        $amount = floatval($_POST['amount']);
        if ($amount > 0) {
            $current = get_option('sdb_current_amount', 0) + $amount;
            update_option('sdb_current_amount', $current);
            $this->update_progress();
            wp_send_json_success(array('new_amount' => $current));
        }
        wp_send_json_error();
    }

    private function update_progress() {
        // Simulate progress update from PayPal IPN or manual
        // In premium version, integrate real PayPal webhook
    }
}

SmartDonationBooster::get_instance();

// Inline styles and scripts for self-contained plugin
function sdb_inline_assets() {
    ?>
    <style>
    #sdb-container { max-width: 500px; margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9; }
    .sdb-progress-bar { width: 100%; height: 20px; background: #eee; border-radius: 10px; overflow: hidden; margin: 10px 0; }
    .sdb-progress-fill { height: 100%; background: linear-gradient(90deg, #4CAF50, #45a049); transition: width 0.5s ease; }
    .sdb-amount { font-size: 18px; font-weight: bold; color: #333; }
    .sdb-donate-btn { margin-top: 15px; text-align: center; }
    .sdb-donate-btn .button { background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; }
    .sdb-donate-btn .button:hover { background: #005a87; }
    </style>
    <script>
    jQuery(document).ready(function($) {
        $('.sdb-donate-btn').on('click', '.donate-input', function(e) {
            e.preventDefault();
            var amount = $(this).siblings('input').val();
            $.post(sdb_ajax.ajax_url, {
                action: 'sdb_donate',
                nonce: sdb_ajax.nonce,
                amount: amount
            }, function(res) {
                if (res.success) {
                    location.reload();
                }
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'sdb_inline_assets');