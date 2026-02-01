/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donations_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donations Pro
 * Plugin URI: https://example.com/smart-donations-pro
 * Description: Collect donations easily with customizable buttons, progress bars, and PayPal integration.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartDonationsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        add_action('wp_ajax_smart_donation_process', array($this, 'process_donation'));
        add_action('wp_ajax_nopriv_smart_donation_process', array($this, 'process_donation'));
    }

    public function init() {
        if (get_option('smart_donations_paypal_email')) {
            add_filter('script_loader_tag', array($this, 'add_defer_attribute'), 10, 2);
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', 'jQuery(document).ready(function($) { $(".smart-donate-btn").click(function(e) { e.preventDefault(); var amount = $(this).data("amount"); $("#donation-amount").val(amount); $("#donation-form").submit(); }); });');
        wp_enqueue_style('smart-donations-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '5',
            'label' => 'Donate',
            'goal' => '1000',
            'current' => '250',
            'paypal_email' => get_option('smart_donations_paypal_email'),
        ), $atts);

        $progress = ($atts['current'] / $atts['goal']) * 100;
        $html = '<div class="smart-donation-container">';
        $html .= '<div class="donation-progress" style="background: #f0f0f0; height: 20px; border-radius: 10px; overflow: hidden;"><div class="progress-bar" style="background: #4CAF50; height: 100%; width: ' . $progress . '%; transition: width 0.3s;"></div></div>';
        $html .= '<p><strong>$' . $atts['current'] . '</strong> raised of <strong>$' . $atts['goal'] . '</strong> goal</p>';
        $html .= '<form id="donation-form" method="post" action="https://www.paypal.com/cgi-bin/webscr">';
        $html .= '<input type="hidden" name="cmd" value="_s-xclick">';
        $html .= '<input type="hidden" name="hosted_button_id" value="' . $this->get_paypal_button_id() . '">';
        $html .= '<input type="hidden" name="custom" value="' . get_the_ID() . '">';
        $html .= '<input type="hidden" name="amount" id="donation-amount" value="' . $atts['amount'] . '">';
        $html .= '<input type="hidden" name="business" value="' . esc_attr($atts['paypal_email']) . '">';
        $html .= '<button type="submit" class="smart-donate-btn" data-amount="' . $atts['amount'] . '">' . esc_html($atts['label']) . ' $' . $atts['amount'] . '</button>';
        $html .= '</form></div>';
        return $html;
    }

    private function get_paypal_button_id() {
        return 'TESTBUTTON123'; // Replace with actual hosted button ID
    }

    public function process_donation() {
        // Log donation attempt
        error_log('Smart Donation: ' . print_r($_POST, true));
        wp_die('Donation processed via PayPal. Thank you!');
    }

    public function add_defer_attribute($tag, $handle) {
        return str_replace(' src=', ' defer src=', $tag);
    }
}

new SmartDonationsPro();

// Admin settings
add_action('admin_menu', function() {
    add_options_page('Smart Donations', 'Smart Donations', 'manage_options', 'smart-donations', 'smart_donations_settings_page');
});

function smart_donations_settings_page() {
    if (isset($_POST['paypal_email'])) {
        update_option('smart_donations_paypal_email', sanitize_email($_POST['paypal_email']));
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }
    $paypal_email = get_option('smart_donations_paypal_email');
    echo '<div class="wrap"><h1>Smart Donations Settings</h1><form method="post"><table class="form-table">';
    echo '<tr><th>PayPal Email</th><td><input type="email" name="paypal_email" value="' . esc_attr($paypal_email) . '" class="regular-text"></td></tr>';
    echo '</table><p><a href="https://www.paypal.com/buttons/" target="_blank">Create PayPal Button</a> and use its ID.</p>';
    echo '<p class="submit"><input type="submit" class="button-primary" value="Save"></p></form></div>';
}

// Inline CSS
add_action('wp_head', function() {
    echo '<style>.smart-donation-container { max-width: 300px; margin: 20px 0; text-align: center; } .smart-donate-btn { background: #4CAF50; color: white; border: none; padding: 12px 24px; font-size: 16px; border-radius: 5px; cursor: pointer; } .smart-donate-btn:hover { background: #45a049; }</style>';
});