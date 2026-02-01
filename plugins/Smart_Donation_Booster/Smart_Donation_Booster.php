/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Booster.php
*/
<?php
/**
 * Plugin Name: Smart Donation Booster
 * Plugin URI: https://example.com/smart-donation-booster
 * Description: Boost donations with progress bars, goals, and PayPal integration.
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
        add_action('wp_ajax_sdb_donate', array($this, 'handle_donation_ajax'));
        add_action('wp_ajax_nopriv_sdb_donate', array($this, 'handle_donation_ajax'));
    }

    public function init() {
        if (get_option('sdb_paypal_email')) {
            wp_register_script('paypal-sdk', 'https://www.paypal.com/sdk/js?client-id=' . get_option('sdb_paypal_client_id', 'YOUR_CLIENT_ID') . '&currency=USD', array(), null, true);
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdb-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sdb-frontend', 'sdb_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdb_nonce')));
        wp_enqueue_style('sdb-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Donation Booster Settings', 'Donation Booster', 'manage_options', 'sdb-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['sdb_submit'])) {
            update_option('sdb_goal_amount', sanitize_text_field($_POST['goal_amount']));
            update_option('sdb_current_amount', sanitize_text_field($_POST['current_amount']));
            update_option('sdb_paypal_email', sanitize_email($_POST['paypal_email']));
            update_option('sdb_paypal_client_id', sanitize_text_field($_POST['paypal_client_id']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Donation Booster Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Goal Amount</th>
                        <td><input type="number" name="goal_amount" value="<?php echo esc_attr(get_option('sdb_goal_amount', 1000)); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Current Amount</th>
                        <td><input type="number" name="current_amount" value="<?php echo esc_attr(get_option('sdb_current_amount', 0)); ?>" /></td>
                    </tr>
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="paypal_email" value="<?php echo esc_attr(get_option('sdb_paypal_email')); ?>" /></td>
                    </tr>
                    <tr>
                        <th>PayPal Client ID</th>
                        <td><input type="text" name="paypal_client_id" value="<?php echo esc_attr(get_option('sdb_paypal_client_id')); ?>" /> <p>Get from <a href="https://developer.paypal.com/" target="_blank">PayPal Developer</a></p></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcode: <code>[donation_goal]</code></p>
        </div>
        <?php
    }

    public function donation_goal_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 'default',
        ), $atts);

        $goal = (float) get_option('sdb_goal_amount', 1000);
        $current = (float) get_option('sdb_current_amount', 0);
        $percent = min(100, ($current / $goal) * 100);
        $paypal_email = get_option('sdb_paypal_email');

        ob_start();
        ?>
        <div class="sdb-container" id="sdb-<?php echo esc_attr($atts['id']); ?>">
            <h3>Support Our Work! <?php echo $percent; ?>% to goal</h3>
            <div class="sdb-progress-bar">
                <div class="sdb-progress" style="width: <?php echo $percent; ?>%;"></div>
            </div>
            <p>$<?php echo number_format($current); ?> / $<?php echo number_format($goal); ?> raised</p>
            <?php if ($paypal_email) : ?>
            <div id="sdb-paypal-button" data-email="<?php echo esc_attr($paypal_email); ?>"></div>
            <?php endif; ?>
            <button class="sdb-donate-btn" data-amount="5">Donate $5</button>
            <button class="sdb-donate-btn" data-amount="10">Donate $10</button>
            <button class="sdb-donate-btn" data-amount="25">Donate $25</button>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_donation_ajax() {
        check_ajax_referer('sdb_nonce', 'nonce');
        $amount = floatval($_POST['amount']);
        $current = (float) get_option('sdb_current_amount', 0);
        update_option('sdb_current_amount', $current + $amount);
        wp_send_json_success(array('new_amount' => $current + $amount));
    }
}

SmartDonationBooster::get_instance();

// Frontend CSS
/* Add to assets/frontend.css */
/* .sdb-container { text-align: center; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
.sdb-progress-bar { background: #f0f0f0; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0; }
.sdb-progress { background: #4CAF50; height: 100%; transition: width 0.3s; }
.sdb-donate-btn { background: #0073aa; color: white; border: none; padding: 10px 20px; margin: 5px; cursor: pointer; border-radius: 4px; } */

// Frontend JS
/* Add to assets/frontend.js */
/* jQuery(document).ready(function($) {
    if (typeof paypal !== 'undefined') {
        paypal.Buttons({
            createOrder: function(data, actions) {
                return actions.order.create({
                    purchase_units: [{
                        amount: { value: '10.00' }
                    }]
                });
            },
            onApprove: function(data, actions) {
                return actions.order.capture().then(function(details) {
                    alert('Transaction completed by ' + details.payer.name.given_name);
                });
            }
        }).render('#sdb-paypal-button');
    }

    $('.sdb-donate-btn').click(function() {
        $.post(sdb_ajax.ajax_url, {
            action: 'sdb_donate',
            amount: $(this).data('amount'),
            nonce: sdb_ajax.nonce
        }, function(response) {
            if (response.success) {
                location.reload();
            }
        });
    });
}); */