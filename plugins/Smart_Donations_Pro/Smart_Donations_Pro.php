/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donations_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donations Pro
 * Plugin URI: https://example.com/smart-donations-pro
 * Description: Easily add customizable donation buttons, progress bars, goals, and PayPal integration to monetize your WordPress site.
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        add_action('wp_ajax_process_donation', array($this, 'process_donation'));
        add_action('wp_ajax_nopriv_process_donation', array($this, 'process_donation'));
    }

    public function init() {
        $this->options = get_option('smart_donations_options', array(
            'paypal_email' => '',
            'goal_amount' => 1000,
            'current_amount' => 0,
            'button_text' => 'Donate Now',
            'thank_you_message' => 'Thank you for your donation!'
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('paypal-sdk', 'https://www.paypal.com/sdk/js?client-id=YOUR_PAYPAL_CLIENT_ID&currency=USD', array(), null, true);
        wp_enqueue_script('smart-donations', plugin_dir_url(__FILE__) . 'smart-donations.js', array('jquery', 'paypal-sdk'), '1.0.0', true);
        wp_localize_script('smart-donations', 'smartDonations', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'paypal_email' => $this->options['paypal_email'],
            'nonce' => wp_create_nonce('smart_donations_nonce')
        ));
    }

    public function admin_menu() {
        add_options_page('Smart Donations Pro', 'Donations', 'manage_options', 'smart-donations', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            $this->options = array(
                'paypal_email' => sanitize_email($_POST['paypal_email']),
                'goal_amount' => floatval($_POST['goal_amount']),
                'current_amount' => floatval($_POST['current_amount']),
                'button_text' => sanitize_text_field($_POST['button_text']),
                'thank_you_message' => sanitize_text_field($_POST['thank_you_message'])
            );
            update_option('smart_donations_options', $this->options);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Donations Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="paypal_email" value="<?php echo esc_attr($this->options['paypal_email']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Goal Amount</th>
                        <td><input type="number" name="goal_amount" value="<?php echo esc_attr($this->options['goal_amount']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Current Amount</th>
                        <td><input type="number" name="current_amount" value="<?php echo esc_attr($this->options['current_amount']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Button Text</th>
                        <td><input type="text" name="button_text" value="<?php echo esc_attr($this->options['button_text']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Thank You Message</th>
                        <td><input type="text" name="thank_you_message" value="<?php echo esc_attr($this->options['thank_you_message']); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10',
            'show_goal' => 'true'
        ), $atts);

        $progress = ($this->options['current_amount'] / $this->options['goal_amount']) * 100;
        $html = '<div class="smart-donation-container">';
        if ($atts['show_goal'] === 'true') {
            $html .= '<div class="donation-goal">';
            $html .= '<p>Goal: $' . $this->options['goal_amount'] . ' | Raised: $' . $this->options['current_amount'] . '</p>';
            $html .= '<div class="progress-bar"><div class="progress" style="width: ' . min($progress, 100) . '%;"></div></div>';
            $html .= '</div>';
        }
        $html .= '<div id="paypal-button-container-' . uniqid() . '"></div>';
        $html .= '<button class="donation-btn">' . $this->options['button_text'] . ' ($' . $atts['amount'] . ')</button>';
        $html .= '</div>';
        $html .= '<script>renderPayPalButton("paypal-button-container-' . uniqid() . '", ' . $atts['amount'] . ');</script>';
        return $html;
    }

    public function process_donation() {
        check_ajax_referer('smart_donations_nonce', 'nonce');
        $amount = floatval($_POST['amount']);
        $this->options['current_amount'] += $amount;
        update_option('smart_donations_options', $this->options);
        wp_send_json_success($this->options['thank_you_message']);
    }
}

new SmartDonationsPro();

// Sample JS (inlined for single file)
function renderPayPalButton(containerId, amount) {
    paypal.Buttons({
        createOrder: function(data, actions) {
            return actions.order.create({
                purchase_units: [{
                    amount: { value: amount }
                }]
            });
        },
        onApprove: function(data, actions) {
            return actions.order.capture().then(function(details) {
                jQuery.post(smartDonations.ajax_url, {
                    action: 'process_donation',
                    amount: amount,
                    nonce: smartDonations.nonce
                }, function(response) {
                    alert('Transaction completed! ' + response.data);
                });
            });
        }
    }).render('#' + containerId);
}

// CSS
add_action('wp_head', function() {
    echo '<style>
    .smart-donation-container { text-align: center; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
    .progress-bar { background: #f0f0f0; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0; }
    .progress { background: #4CAF50; height: 100%; transition: width 0.3s; }
    .donation-btn { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
    .donation-btn:hover { background: #005a87; }
    </style>';
});

// Note: Replace YOUR_PAYPAL_CLIENT_ID with your actual PayPal client ID. JS is simplified; enhance for production.