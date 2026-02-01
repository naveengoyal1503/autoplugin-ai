/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Collect donations easily with customizable buttons, progress bars, and payment integrations.
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
        add_action('wp_ajax_smart_donation_process', array($this, 'process_donation'));
        add_action('wp_ajax_nopriv_smart_donation_process', array($this, 'process_donation'));
    }

    public function init() {
        $this->options = get_option('smart_donation_options', array(
            'paypal_email' => '',
            'amount' => '10',
            'button_text' => 'Donate Now',
            'goal_amount' => '1000',
            'current_amount' => '0',
            'currency' => 'USD',
            'thank_you_message' => 'Thank you for your donation!'
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', 'jQuery(document).ready(function($) { $(".donate-btn").click(function(e) { e.preventDefault(); $("#donation-form").slideToggle(); }); });');
        wp_enqueue_style('smart-donation-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
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
                        <td><input type="number" name="options[amount]" value="<?php echo esc_attr($options['amount']); ?>" /></td>
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
                        <th>Currency</th>
                        <td><input type="text" name="options[currency]" value="<?php echo esc_attr($options['currency']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Thank You Message</th>
                        <td><textarea name="options[thank_you_message]"><?php echo esc_textarea($options['thank_you_message']); ?></textarea></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" name="submit" class="button-primary" value="Save Settings" /></p>
            </form>
            <p><strong>Upgrade to Pro:</strong> Unlock recurring donations, Stripe, analytics, and custom themes for $29/year!</p>
        </div>
        <?php
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 'default'), $atts);
        $progress = ($this->options['current_amount'] / $this->options['goal_amount']) * 100;
        ob_start();
        ?>
        <div class="smart-donation-widget">
            <h3>Support Us!</h3>
            <div class="progress-bar">
                <div class="progress" style="width: <?php echo min(100, $progress); ?>%;"></div>
            </div>
            <p><?php echo $this->options['currency']; ?> <?php echo $this->options['current_amount']; ?> / <?php echo $this->options['goal_amount']; ?> raised</p>
            <button class="donate-btn button-primary"><?php echo $this->options['button_text']; ?></button>
            <form id="donation-form" style="display:none;">
                <input type="number" id="donation-amount" value="<?php echo $this->options['amount']; ?>" min="1" step="0.01" />
                <input type="hidden" name="action" value="smart_donation_process" />
                <?php wp_nonce_field('smart_donation_nonce'); ?>
                <button type="button" onclick="processDonation()" class="button-primary">Pay with PayPal</button>
            </form>
            <div id="donation-message"></div>
        </div>
        <script>
        function processDonation() {
            var amount = jQuery('#donation-amount').val();
            var nonce = jQuery('#smart_donation_nonce').val();
            jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                action: 'smart_donation_process',
                amount: amount,
                nonce: nonce
            }, function(response) {
                if (response.success) {
                    jQuery('#donation-message').html('<?php echo addslashes($this->options['thank_you_message']); ?>');
                    window.location.href = response.data.paypal_url;
                } else {
                    jQuery('#donation-message').html('Error: ' + response.data);
                }
            });
        }
        </script>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        check_ajax_referer('smart_donation_nonce', 'nonce');
        $amount = sanitize_text_field($_POST['amount']);
        $paypal_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=' . urlencode($this->options['paypal_email']) . '&amount=' . urlencode($amount) . '&currency_code=' . urlencode($this->options['currency']) . '&return=' . urlencode(get_site_url()) . '&notify_url=' . urlencode(get_site_url() . '/?smart_donation_ipn=1';
        // Simulate updating current amount (in pro: real IPN handling)
        $current = floatval($this->options['current_amount']) + floatval($amount);
        $this->options['current_amount'] = strval(min($current, $this->options['goal_amount']));
        update_option('smart_donation_options', $this->options);
        wp_send_json_success(array('paypal_url' => $paypal_url));
    }
}

new SmartDonationPro();

// Minimal CSS

function smart_donation_css() {
    echo '<style>
.smart-donation-widget { border: 1px solid #ddd; padding: 20px; border-radius: 5px; text-align: center; background: #f9f9f9; }
.progress-bar { background: #eee; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0; }
.progress { background: #0073aa; height: 100%; transition: width 0.3s; }
.donate-btn { background: #0073aa; color: white; border: none; padding: 10px 20px; border-radius: 3px; cursor: pointer; }
#donation-form { margin-top: 15px; }
#donation-form input { margin: 5px; padding: 8px; }
    </style>';
}
add_action('wp_head', 'smart_donation_css');