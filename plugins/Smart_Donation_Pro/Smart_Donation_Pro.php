/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Easily add donation buttons, progress bars, and payment options to monetize your WordPress site.
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
        if (get_option('sdp_paypal_email')) {
            add_filter('widget_text', 'do_shortcode');
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', '
            jQuery(document).ready(function($) {
                $(".donation-btn").click(function(e) {
                    e.preventDefault();
                    var amount = $("#donation-amount").val();
                    if (amount > 0) {
                        var paypalUrl = "https://www.paypal.com/donate?hosted_button_id=" + $("#paypal-button-id").val() + "&amount=" + amount;
                        window.open(paypalUrl, "_blank");
                    } else {
                        alert("Please enter a donation amount.");
                    }
                });
            });
        ');
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'smart-donation-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['sdp_submit'])) {
            update_option('sdp_paypal_email', sanitize_email($_POST['paypal_email']));
            update_option('sdp_paypal_button_id', sanitize_text_field($_POST['paypal_button_id']));
            update_option('sdp_goal_amount', floatval($_POST['goal_amount']));
            update_option('sdp_current_amount', floatval($_POST['current_amount']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $paypal_email = get_option('sdp_paypal_email', '');
        $paypal_button_id = get_option('sdp_paypal_button_id', '');
        $goal_amount = get_option('sdp_goal_amount', 1000);
        $current_amount = get_option('sdp_current_amount', 0);
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="paypal_email" value="<?php echo esc_attr($paypal_email); ?>" class="regular-text" required /></td>
                    </tr>
                    <tr>
                        <th>PayPal Button ID</th>
                        <td><input type="text" name="paypal_button_id" value="<?php echo esc_attr($paypal_button_id); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Goal Amount</th>
                        <td><input type="number" step="0.01" name="goal_amount" value="<?php echo esc_attr($goal_amount); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Current Amount Raised</th>
                        <td><input type="number" step="0.01" name="current_amount" value="<?php echo esc_attr($current_amount); ?>" /></td>
                    </tr>
                </table>
                <p>Use shortcode <code>[smart_donation]</code> to display the donation form.</p>
                <?php submit_button(); ?>
            </form>
            <h2>Upgrade to Pro</h2>
            <p>Unlock recurring donations, analytics, and custom themes for $29/year!</p>
            <a href="https://example.com/pro" class="button button-primary">Get Pro Now</a>
        </div>
        <?php
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'goal' => get_option('sdp_goal_amount', 1000),
            'current' => get_option('sdp_current_amount', 0)
        ), $atts);

        $progress = ($atts['current'] / $atts['goal']) * 100;
        $paypal_email = get_option('sdp_paypal_email', '');
        $paypal_button_id = get_option('sdp_paypal_button_id', '');

        ob_start();
        ?>
        <div class="smart-donation-container">
            <h3>Support Us! Donate Today</h3>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo min(100, $progress); ?>%;"></div>
            </div>
            <p><?php echo $atts['current']; ?> / <?php echo $atts['goal']; ?> raised (<?php echo round($progress); ?>%)</p>
            <div class="donation-form">
                <input type="number" id="donation-amount" placeholder="$10" step="1" min="1" />
                <input type="hidden" id="paypal-button-id" value="<?php echo esc_attr($paypal_button_id); ?>" />
                <button class="donation-btn button-primary">Donate via PayPal</button>
            </div>
            <?php if ($paypal_email): ?>
            <p><small>PayPal: <?php echo esc_html($paypal_email); ?></small></p>
            <?php endif; ?>
        </div>
        <style>
        .smart-donation-container { max-width: 400px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; text-align: center; }
        .progress-bar { background: #f0f0f0; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0; }
        .progress-fill { height: 100%; background: #0073aa; transition: width 0.3s; }
        .donation-form input { padding: 10px; margin: 10px; width: 100px; }
        .donation-btn { padding: 10px 20px; background: #0073aa; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .donation-btn:hover { background: #005a87; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('sdp_goal_amount', 1000);
    }
}

new SmartDonationPro();

// Prevent direct access
if (!isset($content_width)) $content_width = 600;
?>