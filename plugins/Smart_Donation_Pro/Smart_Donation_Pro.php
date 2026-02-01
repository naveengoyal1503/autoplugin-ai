/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Add customizable donation buttons, progress bars, and goal tracking to encourage donations.
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
        add_shortcode('smart_donation_goal', array($this, 'goal_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->options = get_option('smart_donation_options', array(
            'paypal_email' => '',
            'goal_amount' => 1000,
            'current_amount' => 0,
            'button_text' => 'Donate Now',
            'currency' => 'USD',
            'show_goal' => true
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-donation-js', plugin_dir_url(__FILE__) . 'smart-donation.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('smart-donation-css', plugin_dir_url(__FILE__) . 'smart-donation.css', array(), '1.0.0');
        wp_localize_script('smart-donation-js', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'smart-donation', array($this, 'admin_page'));
    }

    public function admin_page() {
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
                        <th>Goal Amount</th>
                        <td><input type="number" name="options[goal_amount]" value="<?php echo esc_attr($options['goal_amount']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Current Amount</th>
                        <td><input type="number" name="options[current_amount]" value="<?php echo esc_attr($options['current_amount']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Button Text</th>
                        <td><input type="text" name="options[button_text]" value="<?php echo esc_attr($options['button_text']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Currency</th>
                        <td><input type="text" name="options[currency]" value="<?php echo esc_attr($options['currency']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Show Goal</th>
                        <td><input type="checkbox" name="options[show_goal]" <?php checked($options['show_goal']); ?> value="1" /></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" name="submit" class="button-primary" value="Save Changes" /></p>
            </form>
            <p><strong>Upgrade to Pro:</strong> Unlock recurring donations, Stripe, analytics! <a href="#">Buy Now</a></p>
        </div>
        <?php
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array('amount' => '10'), $atts);
        $options = $this->options;
        $paypal_url = 'https://www.paypal.com/donate?hosted_button_id=TEST&amount=' . $atts['amount'];
        ob_start();
        ?>
        <div class="sdp-donation">
            <form action="https://www.paypal.com/donate" method="post" target="_top">
                <input type="hidden" name="hosted_button_id" value="TEST" />
                <input type="hidden" name="amount" value="<?php echo esc_attr($atts['amount']); ?>" />
                <input type="hidden" name="currency_code" value="<?php echo esc_attr($options['currency']); ?>" />
                <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" name="submit" alt="PayPal - The safer, easier way to pay online!" />
                <p><?php echo esc_html($options['button_text']); ?> $<?php echo esc_attr($atts['amount']); ?></p>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function goal_shortcode($atts) {
        $options = $this->options;
        $progress = ($options['current_amount'] / $options['goal_amount']) * 100;
        ob_start();
        ?>
        <div class="sdp-goal">
            <h3>Donation Goal: $<?php echo number_format($options['goal_amount'], 0); ?> <?php echo esc_html($options['currency']); ?></h3>
            <p>Raised: $<?php echo number_format($options['current_amount'], 0); ?></p>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo esc_attr($progress); ?>%;"></div>
            </div>
            <p><?php echo number_format($progress, 1); ?>% achieved</p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('smart_donation_options', array(
            'paypal_email' => '',
            'goal_amount' => 1000,
            'current_amount' => 0,
            'button_text' => 'Donate Now',
            'currency' => 'USD',
            'show_goal' => true
        ));
    }
}

new SmartDonationPro();

// AJAX for updating donation amount (demo)
add_action('wp_ajax_update_donation', 'sdp_update_donation');
function sdp_update_donation() {
    check_ajax_referer('sdp_nonce', 'nonce');
    $amount = floatval($_POST['amount']);
    $options = get_option('smart_donation_options');
    $options['current_amount'] += $amount;
    update_option('smart_donation_options', $options);
    wp_send_json_success(array('current' => $options['current_amount']));
}

// Inline CSS and JS for self-contained
add_action('wp_head', 'sdp_inline_styles');
function sdp_inline_styles() {
    echo '<style>
        .sdp-donation { text-align: center; padding: 20px; border: 1px solid #ddd; border-radius: 5px; background: #f9f9f9; }
        .sdp-goal { text-align: center; padding: 20px; }
        .progress-bar { background: #eee; height: 20px; border-radius: 10px; overflow: hidden; }
        .progress-fill { height: 100%; background: #4CAF50; transition: width 0.3s; }
    </style>';
}

add_action('wp_footer', 'sdp_inline_js');
function sdp_inline_js() {
    echo '<script>
        jQuery(document).ready(function($) {
            $(".sdp-donation form").on("submit", function() {
                // Simulate donation update
                $.post(sdp_ajax.ajax_url, {
                    action: "update_donation",
                    amount: 10,
                    nonce: sdp_ajax.nonce
                });
            });
        });
    </script>';
}
