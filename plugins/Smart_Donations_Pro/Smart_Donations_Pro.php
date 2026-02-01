/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donations_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donations Pro
 * Plugin URI: https://example.com/smart-donations-pro
 * Description: Add customizable donation buttons and progress bars to monetize your WordPress site easily.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartDonationsPro {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        add_shortcode('smart_goal', array($this, 'goal_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-donations-js', plugin_dir_url(__FILE__) . 'smart-donations.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('smart-donations-css', plugin_dir_url(__FILE__) . 'smart-donations.css', array(), '1.0.0');
        wp_localize_script('smart-donations-js', 'smartDonations', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('smart_donations')
        ));
    }

    public function admin_menu() {
        add_options_page('Smart Donations Pro', 'Smart Donations', 'manage_options', 'smart-donations', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('smart_donations_settings', 'smart_donations_options');
        add_settings_section('main_section', 'Main Settings', null, 'smart-donations');
        add_settings_field('paypal_email', 'PayPal Email', array($this, 'paypal_field'), 'smart-donations', 'main_section');
        add_settings_field('goal_amount', 'Fundraising Goal ($)', array($this, 'goal_field'), 'smart-donations', 'main_section');
        add_settings_field('button_text', 'Button Text', array($this, 'button_text_field'), 'smart-donations', 'main_section');
    }

    public function paypal_field() {
        $options = get_option('smart_donations_options');
        echo '<input type="email" name="smart_donations_options[paypal_email]" value="' . esc_attr($options['paypal_email'] ?? '') . '" />';
    }

    public function goal_field() {
        $options = get_option('smart_donations_options');
        echo '<input type="number" name="smart_donations_options[goal_amount]" value="' . esc_attr($options['goal_amount'] ?? '1000') . '" step="0.01" />';
    }

    public function button_text_field() {
        $options = get_option('smart_donations_options');
        echo '<input type="text" name="smart_donations_options[button_text]" value="' . esc_attr($options['button_text'] ?? 'Donate Now') . '" />';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Donations Pro Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('smart_donations_settings');
                do_settings_sections('smart-donations');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function donation_shortcode($atts) {
        $options = get_option('smart_donations_options');
        $paypal_email = $options['paypal_email'] ?? '';
        $button_text = $options['button_text'] ?? 'Donate Now';
        if (empty($paypal_email)) return '<p>Please set up PayPal email in settings.</p>';

        $amounts = array(5, 10, 25, 50, 'custom');
        $output = '<div class="smart-donation-container">';
        foreach ($amounts as $amount) {
            if ($amount === 'custom') {
                $output .= '<input type="number" id="custom-amount" placeholder="Custom" min="1" step="0.01">';
            } else {
                $output .= '<button class="donation-btn" data-amount="' . $amount . '">' . $button_text . ' $' . $amount . '</button> ';
            }
        }
        $output .= '<form id="paypal-form" action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
            <input type="hidden" name="cmd" value="_s-xclick">
            <input type="hidden" name="hosted_button_id" value="">
            <input type="hidden" name="business" value="' . esc_attr($paypal_email) . '">
            <input type="hidden" name="item_name" value="Donation">
            <input type="hidden" name="amount" value="">
            <input type="hidden" name="currency_code" value="USD">
            <input type="submit" value="Pay with PayPal" id="paypal-submit">
        </form></div>';
        return $output;
    }

    public function goal_shortcode($atts) {
        $options = get_option('smart_donations_options');
        $goal = floatval($options['goal_amount'] ?? 1000);
        $donated = get_option('smart_donations_total', 0);
        $percent = min(100, ($donated / $goal) * 100);
        return '<div class="smart-goal-container">
            <p>Goal: $' . number_format($goal) . ' | Raised: $' . number_format($donated) . ' (' . $percent . '%)</p>
            <div class="goal-progress" style="width: 100%; height: 20px; background: #ddd;">
                <div class="progress-bar" style="width: ' . $percent . '%; height: 100%; background: #4CAF50;"></div>
            </div>
        </div>';
    }

    public function activate() {
        add_option('smart_donations_options', array('goal_amount' => 1000));
    }
}

SmartDonationsPro::get_instance();

// AJAX for updating goal (demo - in pro version connect to Stripe/PayPal webhooks)
add_action('wp_ajax_update_donation', 'handle_donation_update');
add_action('wp_ajax_nopriv_update_donation', 'handle_donation_update');
function handle_donation_update() {
    check_ajax_referer('smart_donations', 'nonce');
    $amount = floatval($_POST['amount']);
    $total = get_option('smart_donations_total', 0) + $amount;
    update_option('smart_donations_total', $total);
    wp_send_json_success(array('total' => $total));
}

// Inline CSS and JS for self-contained plugin
add_action('wp_head', 'smart_donations_styles');
function smart_donations_styles() {
    echo '<style>
        .smart-donation-container { text-align: center; margin: 20px 0; }
        .donation-btn { background: #007cba; color: white; border: none; padding: 10px 20px; margin: 5px; cursor: pointer; border-radius: 5px; }
        .donation-btn:hover { background: #005a87; }
        #paypal-submit { background: #ffc439; color: #000; padding: 12px 24px; font-size: 16px; }
        .smart-goal-container { margin: 20px 0; }
        .goal-progress { position: relative; }
    </style>';
    echo '<script>jQuery(document).ready(function($) {
        $(".donation-btn").click(function() {
            var amount = $(this).data("amount");
            if (amount === "custom") amount = $("#custom-amount").val();
            $("input[name=amount]").val(amount);
            $("#paypal-form").submit();
            // Simulate AJAX update
            $.post(smartDonations.ajax_url, {action: "update_donation", amount: amount, nonce: smartDonations.nonce}, function(res) {
                location.reload();
            });
        });
    });</script>';
}
?>