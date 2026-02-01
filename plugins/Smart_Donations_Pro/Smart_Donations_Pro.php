/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donations_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donations Pro
 * Plugin URI: https://example.com/smart-donations-pro
 * Description: Add customizable donation buttons and fundraising goals to monetize your WordPress site.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartDonationsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        add_shortcode('smart_goal', array($this, 'goal_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('smart_donations_enabled') !== 'yes') {
            update_option('smart_donations_enabled', 'yes');
            update_option('smart_donation_amount', '5,10,25,50');
            update_option('smart_donation_text', 'Support this site!');
            update_option('smart_paypal_email', '');
            update_option('smart_goal_amount', '1000');
            update_option('smart_goal_current', '0');
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-donations', plugin_dir_url(__FILE__) . 'smart-donations.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('smart-donations', plugin_dir_url(__FILE__) . 'smart-donations.css', array(), '1.0.0');
        wp_localize_script('smart-donations', 'smartDonations', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('smart_donations')
        ));
    }

    public function admin_menu() {
        add_options_page('Smart Donations', 'Smart Donations', 'manage_options', 'smart-donations', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('smart_paypal_email', sanitize_email($_POST['paypal_email']));
            update_option('smart_donation_amount', sanitize_text_field($_POST['amounts']));
            update_option('smart_donation_text', sanitize_text_field($_POST['text']));
            update_option('smart_goal_amount', intval($_POST['goal_amount']));
            update_option('smart_goal_current', intval($_POST['goal_current']));
            echo '<div class="updated"><p>Settings saved!</p></div>';
        }
        $paypal = get_option('smart_paypal_email');
        $amounts = get_option('smart_donation_amount');
        $text = get_option('smart_donation_text');
        $goal_amount = get_option('smart_goal_amount');
        $goal_current = get_option('smart_goal_current');
        ?>
        <div class="wrap">
            <h1>Smart Donations Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="paypal_email" value="<?php echo esc_attr($paypal); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Donation Amounts (comma-separated)</th>
                        <td><input type="text" name="amounts" value="<?php echo esc_attr($amounts); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Button Text</th>
                        <td><input type="text" name="text" value="<?php echo esc_attr($text); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Goal Amount</th>
                        <td><input type="number" name="goal_amount" value="<?php echo esc_attr($goal_amount); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Current Raised</th>
                        <td><input type="number" name="goal_current" value="<?php echo esc_attr($goal_current); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array('amount' => ''), $atts);
        $amounts = explode(',', get_option('smart_donation_amount'));
        $text = get_option('smart_donation_text');
        $paypal = get_option('smart_paypal_email');
        if (empty($paypal)) return '<p>Please set PayPal email in settings.</p>';

        $options = '';
        foreach ($amounts as $amt) {
            $options .= '<option value="' . esc_attr(trim($amt)) . '">' . esc_html(trim($amt)) . '</option>';
        }

        ob_start();
        ?>
        <div class="smart-donation">
            <p><?php echo esc_html($text); ?></p>
            <select id="smart-amount-<?php echo uniqid(); ?>">
                <?php echo $options; ?>
            </select>
            <input type="submit" class="button" value="Donate via PayPal" onclick="window.open('https://www.paypal.com/donate/?hosted_button_id=GENERIC&amount=' + jQuery('#smart-amount-<?php echo uniqid(); ?>').val() + '&email=<?php echo urlencode($paypal); ?>', '_blank'); return false;" />
        </div>
        <?php
        return ob_get_clean();
    }

    public function goal_shortcode($atts) {
        $goal_amount = get_option('smart_goal_amount');
        $goal_current = get_option('smart_goal_current');
        $percent = $goal_amount > 0 ? min(100, ($goal_current / $goal_amount) * 100) : 0;

        ob_start();
        ?>
        <div class="smart-goal">
            <p>Fundraising Goal: $<span id="current"><?php echo number_format($goal_current); ?></span> / $<span><?php echo number_format($goal_amount); ?></span></p>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo $percent; ?>%;"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new SmartDonationsPro();

// Inline CSS
add_action('wp_head', function() {
    echo '<style>
    .smart-donation { text-align: center; padding: 20px; border: 1px solid #ddd; border-radius: 5px; background: #f9f9f9; }
    .smart-donation select, .smart-donation input.button { margin: 5px; padding: 10px; }
    .smart-goal { text-align: center; padding: 20px; }
    .progress-bar { width: 100%; height: 20px; background: #eee; border-radius: 10px; overflow: hidden; margin: 10px 0; }
    .progress-fill { height: 100%; background: #4CAF50; transition: width 0.3s; }
    </style>';
});

// Inline JS
add_action('wp_footer', function() {
    echo '<script>jQuery(document).ready(function($) { /* Basic functionality */ });</script>';
});