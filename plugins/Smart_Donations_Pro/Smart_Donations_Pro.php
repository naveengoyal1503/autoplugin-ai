/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donations_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donations Pro
 * Plugin URI: https://example.com/smart-donations-pro
 * Description: A lightweight WordPress plugin that adds customizable donation buttons and forms with PayPal integration, goal tracking, and thank-you pages to easily monetize your site via user donations.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-donations-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartDonationsPro {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('smart_donation_form', array($this, 'donation_shortcode'));
        add_shortcode('smart_donation_button', array($this, 'donation_button_shortcode'));
        add_action('wp_ajax_process_donation', array($this, 'process_donation'));
        add_action('wp_ajax_nopriv_process_donation', array($this, 'process_donation'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->options = get_option('smart_donations_options', array(
            'paypal_email' => '',
            'donation_goal' => 1000,
            'currency' => 'USD',
            'button_text' => 'Donate Now',
            'thank_you_message' => 'Thank you for your donation!',
            'premium_upsell' => true
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('paypal-sdk', 'https://www.paypal.com/sdk/js?client-id=YOUR_PAYPAL_CLIENT_ID&currency=' . $this->options['currency'], array(), null, true);
        wp_enqueue_script('smart-donations', plugin_dir_url(__FILE__) . 'smart-donations.js', array('jquery', 'paypal-sdk'), '1.0.0', true);
        wp_localize_script('smart-donations', 'smartDonations', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'paypal_email' => $this->options['paypal_email'],
            'goal' => $this->options['donation_goal'],
            'current' => get_option('smart_donations_total', 0)
        ));
    }

    public function admin_menu() {
        add_options_page('Smart Donations Pro', 'Donations', 'manage_options', 'smart-donations', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('smart_donations_options', $_POST['options']);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Donations Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="options[paypal_email]" value="<?php echo esc_attr($this->options['paypal_email']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Donation Goal ($)</th>
                        <td><input type="number" name="options[donation_goal]" value="<?php echo esc_attr($this->options['donation_goal']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Currency</th>
                        <td><input type="text" name="options[currency]" value="<?php echo esc_attr($this->options['currency']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Button Text</th>
                        <td><input type="text" name="options[button_text]" value="<?php echo esc_attr($this->options['button_text']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Thank You Message</th>
                        <td><textarea name="options[thank_you_message]"><?php echo esc_textarea($this->options['thank_you_message']); ?></textarea></td>
                    </tr>
                </table>
                <?php if ($this->options['premium_upsell']) { ?>
                <p><strong>Upgrade to Pro for recurring donations and analytics!</strong> <a href="https://example.com/pro" target="_blank">Get Pro</a></p>
                <?php } ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array('amount' => '10'), $atts);
        ob_start();
        ?>
        <div id="smart-donation-form" data-amount="<?php echo esc_attr($atts['amount']); ?>">
            <div id="paypal-button-container"></div>
            <div class="donation-goal">
                <p>Goal: $<span id="goal"><?php echo $this->options['donation_goal']; ?></span> | Raised: $<span id="current"><?php echo get_option('smart_donations_total', 0); ?></span></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function donation_button_shortcode($atts) {
        $atts = shortcode_atts(array(), $atts);
        return '<a href="#donate" class="smart-donate-btn">' . esc_html($this->options['button_text']) . '</a>';
    }

    public function process_donation() {
        $amount = sanitize_text_field($_POST['amount']);
        $total = get_option('smart_donations_total', 0) + (float)$amount;
        update_option('smart_donations_total', $total);
        wp_send_json_success('Donation recorded!');
    }

    public function activate() {
        add_option('smart_donations_total', 0);
    }
}

new SmartDonationsPro();

// Note: Replace YOUR_PAYPAL_CLIENT_ID with your actual PayPal client ID. JS file needed for full functionality: smart-donations.js with PayPal buttons and AJAX.