/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Easily add customizable donation buttons, progress bars, and forms to monetize your WordPress site.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartDonationPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        add_shortcode('smart_donation_progress', array($this, 'progress_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('smart-donation-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('smart-donation-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_enqueue($hook) {
        if ($hook === 'settings_page_smart-donation') {
            wp_enqueue_style('smart-donation-admin', plugin_dir_url(__FILE__) . 'admin-style.css', array(), '1.0.0');
        }
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro', 'Smart Donation', 'manage_options', 'smart-donation', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('sdp_paypal_email', sanitize_email($_POST['paypal_email']));
            update_option('sdp_goal_amount', floatval($_POST['goal_amount']));
            update_option('sdp_current_amount', floatval($_POST['current_amount']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $paypal_email = get_option('sdp_paypal_email', '');
        $goal_amount = get_option('sdp_goal_amount', 0);
        $current_amount = get_option('sdp_current_amount', 0);
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="paypal_email" value="<?php echo esc_attr($paypal_email); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Goal Amount</th>
                        <td><input type="number" step="0.01" name="goal_amount" value="<?php echo esc_attr($goal_amount); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Current Amount (Manual Update)</th>
                        <td><input type="number" step="0.01" name="current_amount" value="<?php echo esc_attr($current_amount); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcodes: <code>[smart_donation]</code> for button, <code>[smart_donation_progress]</code> for progress bar.</p>
        </div>
        <?php
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10',
            'label' => 'Donate Now',
            'button_text' => 'Support Us'
        ), $atts);

        $paypal_email = get_option('sdp_paypal_email', '');
        if (empty($paypal_email)) {
            return '<p>Please set your PayPal email in settings.</p>';
        }

        $donate_url = 'https://www.paypal.com/donate?hosted_button_id=TEST&amount=' . $atts['amount'];

        ob_start();
        ?>
        <div class="smart-donation">
            <p><?php echo esc_html($atts['label']); ?></p>
            <a href="<?php echo esc_url($donate_url); ?>" class="smart-donate-btn" target="_blank">
                <?php echo esc_html($atts['button_text']); ?> (<?php echo esc_html($atts['amount']); ?>)
            </a>
        </div>
        <style>
        .smart-donate-btn { background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; }
        .smart-donate-btn:hover { background: #005a87; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function progress_shortcode($atts) {
        $goal = get_option('sdp_goal_amount', 100);
        $current = get_option('sdp_current_amount', 0);
        $percent = $goal > 0 ? min(100, ($current / $goal) * 100) : 0;

        ob_start();
        ?>
        <div class="smart-donation-progress">
            <p>Raised: $<?php echo number_format($current, 2); ?> of $<?php echo number_format($goal, 2); ?> (<?php echo round($percent); ?>%)</p>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo $percent; ?>%;"></div>
            </div>
        </div>
        <style>
        .progress-bar { background: #eee; height: 20px; border-radius: 10px; overflow: hidden; }
        .progress-fill { background: #007cba; height: 100%; transition: width 0.3s; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('sdp_paypal_email', '');
        add_option('sdp_goal_amount', 1000);
        add_option('sdp_current_amount', 0);
    }
}

new SmartDonationPro();

// Inline styles and scripts (self-contained)
function sdp_add_inline_styles() {
    echo '<style>
    .smart-donation { text-align: center; margin: 20px 0; }
    </style>';
}
add_action('wp_head', 'sdp_add_inline_styles');