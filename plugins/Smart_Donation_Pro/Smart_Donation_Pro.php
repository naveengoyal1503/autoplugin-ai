/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Boost donations with customizable forms, progress bars, goals, and analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-donation-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartDonationPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_sdp_donate', array($this, 'handle_donation'));
        add_action('wp_ajax_nopriv_sdp_donate', array($this, 'handle_donation'));
        add_shortcode('sdp_donation_form', array($this, 'donation_form_shortcode'));
        add_shortcode('sdp_progress_bar', array($this, 'progress_bar_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sdp_goal') === false) {
            update_option('sdp_goal', 1000);
        }
        if (get_option('sdp_raised') === false) {
            update_option('sdp_raised', 0);
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'sdp-style.css', array(), '1.0.0');
    }

    public function donation_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'goal' => get_option('sdp_goal'),
            'raised' => get_option('sdp_raised'),
            'button_text' => 'Donate Now',
            'amounts' => '5,10,25,50,100'
        ), $atts);

        $amounts = explode(',', $atts['amounts']);
        $output = '<div class="sdp-form-wrapper">';
        $output .= '<form id="sdp-donate-form" method="post">';
        $output .= '<p>Help us reach our goal of $' . number_format($atts['goal']) . '!</p>';
        $output .= '<div class="sdp-amounts">';
        foreach ($amounts as $amount) {
            $output .= '<label><input type="radio" name="amount" value="' . trim($amount) . '" required> $' . trim($amount) . '</label> ';
        }
        $output .= '<input type="number" name="custom_amount" placeholder="Custom amount" min="1"></div>';
        $output .= '<input type="email" name="email" placeholder="Your email" required>';
        $output .= '<button type="submit">' . esc_html($atts['button_text']) . '</button>';
        $output .= '</form>';
        $output .= $this->progress_bar_shortcode(array('goal' => $atts['goal'], 'raised' => $atts['raised']));
        $output .= '</div>';
        return $output;
    }

    public function progress_bar_shortcode($atts) {
        $atts = shortcode_atts(array(
            'goal' => get_option('sdp_goal'),
            'raised' => get_option('sdp_raised')
        ), $atts);
        $percent = ($atts['raised'] / $atts['goal']) * 100;
        return '<div class="sdp-progress-wrapper"><div class="sdp-progress-bar"><div class="sdp-progress-fill" style="width: ' . $percent . '%;"></div></div><span>$' . number_format($atts['raised']) . ' / $' . number_format($atts['goal']) . ' (' . round($percent) . '%)</span></div>';
    }

    public function handle_donation() {
        $amount = sanitize_text_field($_POST['amount'] ?? $_POST['custom_amount']);
        $email = sanitize_email($_POST['email']);
        if (!$amount || !$email || !is_email($email)) {
            wp_die('Invalid input');
        }
        $raised = get_option('sdp_raised', 0) + floatval($amount);
        update_option('sdp_raised', $raised);
        // In pro version, integrate Stripe/PayPal
        wp_mail(get_option('admin_email'), 'New Donation', "Donation of \$$amount from $email");
        wp_send_json_success('Thank you for your donation!');
    }

    public function activate() {
        update_option('sdp_goal', 1000);
        update_option('sdp_raised', 0);
    }
}

new SmartDonationPro();

// Freemium notice
add_action('admin_notices', function() {
    if (!get_option('sdp_pro_activated')) {
        echo '<div class="notice notice-info"><p><strong>Smart Donation Pro:</strong> Unlock recurring donations, analytics, and more with <a href="https://example.com/pro">Pro version</a> ($49/year)!</p></div>';
    }
});

// Minimal CSS and JS (base64 or inline for single file)
function sdp_inline_assets() {
    ?>
    <style>
    .sdp-form-wrapper { max-width: 400px; margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
    .sdp-amounts { margin: 10px 0; }
    .sdp-amounts label { margin-right: 10px; }
    .sdp-form-wrapper input, .sdp-form-wrapper button { width: 100%; margin: 5px 0; padding: 10px; }
    .sdp-form-wrapper button { background: #0073aa; color: white; border: none; cursor: pointer; }
    .sdp-progress-wrapper { margin-top: 20px; }
    .sdp-progress-bar { height: 20px; background: #f0f0f0; border-radius: 10px; overflow: hidden; }
    .sdp-progress-fill { height: 100%; background: #0073aa; transition: width 0.3s; }
    </style>
    <script>
    jQuery(document).ready(function($) {
        $('#sdp-donate-form').on('submit', function(e) {
            e.preventDefault();
            var formData = $(this).serialize();
            $.post(sdp_ajax.ajaxurl, {action: 'sdp_donate', ...Object.fromEntries(new FormData(this))}, function(res) {
                if (res.success) {
                    alert('Thank you!');
                    location.reload();
                }
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'sdp_inline_assets');