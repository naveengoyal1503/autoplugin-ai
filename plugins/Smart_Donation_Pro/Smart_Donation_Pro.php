/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Create customizable donation buttons and forms to monetize your WordPress site.
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
        add_action('wp_ajax_sdp_process_donation', array($this, 'process_donation'));
        add_action('wp_ajax_nopriv_sdp_process_donation', array($this, 'process_donation'));
    }

    public function init() {
        $this->options = get_option('smart_donation_options', array(
            'paypal_email' => '',
            'default_amount' => '10',
            'currency' => 'USD',
            'button_text' => 'Donate Now',
            'thank_you_message' => 'Thank you for your donation!'
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', 'jQuery(document).ready(function($) { $(".sdp-donate-btn").click(function(e) { e.preventDefault(); var amount = $("#sdp-amount").val(); if(amount <= 0) { alert("Please enter a valid amount"); return; } var form = $("<form method=\"post\" action=\"https://www.paypal.com/cgi-bin/webscr\"> <input type=\"hidden\" name=\"cmd\" value=\"_xclick\" /> <input type=\"hidden\" name=\"business\" value=\"" + sdp_vars.paypal_email + "\" /> <input type=\"hidden\" name=\"amount\" value=\"" + amount + "\" /> <input type=\"hidden\" name=\"currency_code\" value=\"" + sdp_vars.currency + "\" /> <input type=\"hidden\" name=\"item_name\" value=\"Donation\" /> </form>"); form.submit(); }); });');
        wp_localize_script('jquery', 'sdp_vars', array(
            'paypal_email' => $this->options['paypal_email'],
            'currency' => $this->options['currency']
        ));
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro', 'Donation Settings', 'manage_options', 'smart-donation', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('smart_donation_options', $_POST['options']);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $options = $this->options;
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="options[paypal_email]" value="<?php echo esc_attr($options['paypal_email']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Default Amount</th>
                        <td><input type="number" step="0.01" name="options[default_amount]" value="<?php echo esc_attr($options['default_amount']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Currency</th>
                        <td><input type="text" name="options[currency]" value="<?php echo esc_attr($options['currency']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Button Text</th>
                        <td><input type="text" name="options[button_text]" value="<?php echo esc_attr($options['button_text']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Thank You Message</th>
                        <td><textarea name="options[thank_you_message]"><?php echo esc_textarea($options['thank_you_message']); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => $this->options['default_amount'],
            'text' => $this->options['button_text']
        ), $atts);

        ob_start();
        ?>
        <div class="smart-donation-widget" style="text-align: center; padding: 20px; border: 1px solid #ddd; border-radius: 5px; background: #f9f9f9;">
            <p>Support our content! <strong>Buy us a coffee ☕</strong></p>
            $<input type="number" id="sdp-amount" value="<?php echo esc_attr($atts['amount']); ?>" step="0.01" style="width: 80px;" min="1" />
            <br><br>
            <button class="sdp-donate-btn button button-primary" style="padding: 10px 20px; font-size: 16px;"><?php echo esc_html($atts['text']); ?></button>
        </div>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        // Additional server-side validation or logging can be added here
        wp_die();
    }
}

new SmartDonationPro();