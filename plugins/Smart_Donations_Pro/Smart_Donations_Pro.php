/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donations_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donations Pro
 * Plugin URI: https://example.com/smart-donations-pro
 * Description: Add customizable donation buttons, progress bars, and payment options to monetize your site.
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
            'button_text' => 'Donate Now',
            'tiers' => array(
                array('amount' => '5', 'label' => 'Coffee'),
                array('amount' => '10', 'label' => 'Lunch'),
                array('amount' => '25', 'label' => 'Dinner'),
                array('amount' => 'custom', 'label' => 'Custom')
            ),
            'goal_amount' => '1000',
            'current_amount' => '0',
            'thank_you_message' => 'Thank you for your generous donation!'
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('paypal-sdk', 'https://www.paypal.com/sdk/js?client-id=YOUR_PAYPAL_CLIENT_ID&currency=USD', array(), '1.0', true);
        wp_enqueue_script('smart-donations-js', plugin_dir_url(__FILE__) . 'smart-donations.js', array('jquery', 'paypal-sdk'), '1.0', true);
        wp_localize_script('smart-donations-js', 'smartDonations', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('smart_donations_nonce'),
            'options' => $this->options
        ));
        wp_enqueue_style('smart-donations-css', plugin_dir_url(__FILE__) . 'smart-donations.css', array(), '1.0');
    }

    public function admin_menu() {
        add_options_page('Smart Donations Pro', 'Donations', 'manage_options', 'smart-donations', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('smart_donations_options', $_POST['options']);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $options = $this->options;
        ?>
        <div class="wrap">
            <h1>Smart Donations Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="options[paypal_email]" value="<?php echo esc_attr($options['paypal_email']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Button Text</th>
                        <td><input type="text" name="options[button_text]" value="<?php echo esc_attr($options['button_text']); ?>" /></td>
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
        $atts = shortcode_atts(array('id' => 'default'), $atts);
        ob_start();
        ?>
        <div class="smart-donation-container" data-id="<?php echo esc_attr($atts['id']); ?>">
            <div class="donation-goal">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo ($this->options['current_amount'] / $this->options['goal_amount']) * 100; ?>%;"></div>
                </div>
                <span class="goal-text">$<?php echo $this->options['current_amount']; ?> / $<?php echo $this->options['goal_amount']; ?> raised</span>
            </div>
            <div class="donation-tiers">
                <?php foreach ($this->options['tiers'] as $tier): ?>
                    <button class="tier-button" data-amount="<?php echo esc_attr($tier['amount']); ?>"><?php echo esc_html($tier['label']); ?> ($<?php echo $tier['amount']; ?>)</button>
                <?php endforeach; ?>
                <input type="number" class="custom-amount" placeholder="Custom amount" />
                <button class="donate-button" id="donateBtn"><?php echo esc_html($this->options['button_text']); ?></button>
            </div>
            <div id="paypal-button-container"></div>
            <div class="thank-you" style="display:none;"><?php echo esc_html($this->options['thank_you_message']); ?></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        check_ajax_referer('smart_donations_nonce', 'nonce');
        // Simulate donation processing - in pro version, integrate full PayPal IPN
        $amount = sanitize_text_field($_POST['amount']);
        $new_amount = $this->options['current_amount'] + $amount;
        $this->options['current_amount'] = min($new_amount, $this->options['goal_amount']);
        update_option('smart_donations_options', $this->options);
        wp_send_json_success('Donation recorded!');
    }
}

new SmartDonationsPro();

// Inline CSS
add_action('wp_head', function() {
    echo '<style>
        .smart-donation-container { max-width: 400px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .progress-bar { height: 20px; background: #f0f0f0; border-radius: 10px; overflow: hidden; margin-bottom: 10px; }
        .progress-fill { height: 100%; background: #4CAF50; transition: width 0.3s; }
        .goal-text { display: block; text-align: center; font-weight: bold; }
        .donation-tiers { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; margin-bottom: 20px; }
        .tier-button, .donate-button { padding: 10px 15px; background: #007cba; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .tier-button:hover, .donate-button:hover { background: #005a87; }
        .custom-amount { padding: 10px; border: 1px solid #ddd; border-radius: 5px; width: 100px; }
        #paypal-button-container { text-align: center; }
        .thank-you { text-align: center; color: #4CAF50; font-weight: bold; }
    </style>';
});

// Note: Replace YOUR_PAYPAL_CLIENT_ID with actual sandbox/live client ID. JS file would handle PayPal buttons and AJAX.