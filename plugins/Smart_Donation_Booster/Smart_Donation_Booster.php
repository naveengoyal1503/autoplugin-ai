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
        add_action('wp_ajax_sdb_donate', array($this, 'ajax_donate'));
        add_action('wp_ajax_nopriv_sdb_donate', array($this, 'ajax_donate'));
    }

    public function init() {
        if (get_option('sdb_goal_amount') === false) {
            update_option('sdb_goal_amount', 1000);
        }
        if (get_option('sdb_current_amount') === false) {
            update_option('sdb_current_amount', 0);
        }
        if (get_option('sdb_paypal_email') === false) {
            update_option('sdb_paypal_email', get_option('admin_email'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdb-script', plugin_dir_url(__FILE__) . 'sdb.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdb-style', plugin_dir_url(__FILE__) . 'sdb.css', array(), '1.0.0');
        wp_localize_script('sdb-script', 'sdb_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdb_nonce')));
    }

    public function admin_menu() {
        add_options_page('Donation Booster Settings', 'Donation Booster', 'manage_options', 'sdb-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['sdb_submit'])) {
            update_option('sdb_goal_amount', sanitize_text_field($_POST['goal_amount']));
            update_option('sdb_current_amount', sanitize_text_field($_POST['current_amount']));
            update_option('sdb_paypal_email', sanitize_email($_POST['paypal_email']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $goal = get_option('sdb_goal_amount', 1000);
        $current = get_option('sdb_current_amount', 0);
        $paypal = get_option('sdb_paypal_email', '');
        ?>
        <div class="wrap">
            <h1>Smart Donation Booster Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Goal Amount</th>
                        <td><input type="number" name="goal_amount" value="<?php echo esc_attr($goal); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Current Amount</th>
                        <td><input type="number" name="current_amount" value="<?php echo esc_attr($current); ?>" /></td>
                    </tr>
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="paypal_email" value="<?php echo esc_attr($paypal); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Pro:</strong> Unlock unlimited goals, analytics, and more for $29/year! <a href="https://example.com/pro" target="_blank">Get Pro</a></p>
            <p>Use shortcode: <code>[donation_goal]</code></p>
        </div>
        <?php
    }

    public function donation_goal_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 'default'), $atts);
        $goal = get_option('sdb_goal_amount', 1000);
        $current = get_option('sdb_current_amount', 0);
        $paypal = get_option('sdb_paypal_email', '');
        $percent = min(100, ($current / $goal) * 100);

        ob_start();
        ?>
        <div class="sdb-container" data-goal="<?php echo $goal; ?>" data-current="<?php echo $current; ?>">
            <h3>Support Our Goal: $<?php echo number_format($goal); ?>!</h3>
            <div class="sdb-progress-bar">
                <div class="sdb-progress" style="width: <?php echo $percent; ?>%;"></div>
            </div>
            <p>Current: $<span class="sdb-current"><?php echo number_format($current); ?></span> / $<?php echo number_format($goal); ?> (<?php echo round($percent); ?>%)</p>
            <?php if ($paypal): ?>
            <form action="https://www.paypal.com/donate" method="post" target="_top">
                <input type="hidden" name="hosted_button_id" value="YOUR_BUTTON_ID">
                <input type="hidden" name="amount" value="10">
                <input type="hidden" name="business" value="<?php echo esc_attr($paypal); ?>">
                <input type="hidden" name="item_name" value="Donation to Site">
                <input type="submit" value="Donate $10" class="sdb-donate-btn">
            </form>
            <?php endif; ?>
            <button class="sdb-simulate-donate">Simulate $10 Donation (Admin)</button>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_donate() {
        check_ajax_referer('sdb_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 10;
        $current = get_option('sdb_current_amount', 0) + $amount;
        $goal = get_option('sdb_goal_amount', 1000);
        if ($current > $goal) $current = $goal;
        update_option('sdb_current_amount', $current);
        wp_send_json_success(array('current' => $current, 'goal' => $goal));
    }
}

SmartDonationBooster::get_instance();

/* Inline CSS and JS for self-contained plugin */
function sdb_inline_assets() {
    ?>
    <style>
    .sdb-container { max-width: 400px; margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9; }
    .sdb-progress-bar { width: 100%; height: 20px; background: #eee; border-radius: 10px; overflow: hidden; margin: 10px 0; }
    .sdb-progress { height: 100%; background: linear-gradient(90deg, #4CAF50, #45a049); transition: width 0.5s ease; }
    .sdb-donate-btn { background: #007cba; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
    .sdb-donate-btn:hover { background: #005a87; }
    .sdb-simulate-donate { background: #ff9800; color: white; border: none; padding: 8px 16px; border-radius: 5px; cursor: pointer; margin-top: 10px; display: none; }
    .sdb-simulate-donate.visible { display: block; }
    </style>
    <script>
    jQuery(document).ready(function($) {
        $('.sdb-simulate-donate').click(function() {
            var $container = $(this).closest('.sdb-container');
            $.post(sdb_ajax.ajax_url, {
                action: 'sdb_donate',
                nonce: sdb_ajax.nonce,
                amount: 10
            }, function(response) {
                if (response.success) {
                    var percent = (response.data.current / response.data.goal) * 100;
                    $container.find('.sdb-progress').css('width', percent + '%');
                    $container.find('.sdb-current').text(response.data.current.toLocaleString());
                }
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'sdb_inline_assets');