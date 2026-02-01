/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Easily add customizable donation buttons and forms to monetize your WordPress site with PayPal.
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
        $this->options = get_option('sdp_options', array(
            'paypal_email' => '',
            'button_text' => 'Donate Now',
            'goal_amount' => 1000,
            'current_amount' => 0,
            'currency' => 'USD',
            'show_progress' => 'yes'
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'sdp-style.css', array(), '1.0.0');
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'sdp-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['sdp_save'])) {
            update_option('sdp_options', $_POST['sdp_options']);
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
                        <td><input type="email" name="sdp_options[paypal_email]" value="<?php echo esc_attr($options['paypal_email']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Button Text</th>
                        <td><input type="text" name="sdp_options[button_text]" value="<?php echo esc_attr($options['button_text']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Goal Amount</th>
                        <td><input type="number" name="sdp_options[goal_amount]" value="<?php echo esc_attr($options['goal_amount']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Current Amount</th>
                        <td><input type="number" name="sdp_options[current_amount]" value="<?php echo esc_attr($options['current_amount']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Currency</th>
                        <td><input type="text" name="sdp_options[currency]" value="<?php echo esc_attr($options['currency']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Show Progress Bar</th>
                        <td><input type="checkbox" name="sdp_options[show_progress]" value="yes" <?php checked($options['show_progress'], 'yes'); ?> /></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" name="sdp_save" class="button-primary" value="Save Settings" /></p>
            </form>
            <p><strong>Upgrade to Pro:</strong> Unlock recurring donations, analytics, and custom themes for $29/year!</p>
        </div>
        <?php
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array('amount' => '10'), $atts);
        ob_start();
        ?>
        <div class="sdp-container">
            <?php if ($this->options['show_progress'] === 'yes') : ?>
            <div class="sdp-progress">
                <div class="sdp-progress-bar" style="width: <?php echo ($this->options['current_amount'] / $this->options['goal_amount']) * 100; ?>%;"></div>
            </div>
            <p><?php echo $this->options['currency']; ?> <?php echo $this->options['current_amount']; ?> / <?php echo $this->options['currency']; ?> <?php echo $this->options['goal_amount']; ?> raised</p>
            <?php endif; ?>
            <form class="sdp-form" method="post" action="https://www.paypal.com/cgi-bin/webscr">
                <input type="hidden" name="cmd" value="_s-xclick">
                <input type="hidden" name="hosted_button_id" value="">
                <input type="hidden" name="business" value="<?php echo esc_attr($this->options['paypal_email']); ?>">
                <input type="hidden" name="item_name" value="Donation to <?php echo get_bloginfo('name'); ?>">
                <input type="number" name="amount" placeholder="Enter amount" step="0.01" required>
                <input type="hidden" name="currency_code" value="<?php echo esc_attr($this->options['currency']); ?>">
                <input type="submit" name="sdp_submit" class="sdp-button" value="<?php echo esc_attr($this->options['button_text']); ?>">
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        // Simulate donation processing (in Pro version, integrate full PayPal IPN)
        $amount = floatval($_POST['amount']);
        $options = $this->options;
        $options['current_amount'] += $amount;
        update_option('sdp_options', $options);
        wp_send_json_success('Thank you for your donation!');
    }
}

new SmartDonationPro();

// Inline styles and scripts for self-contained plugin
?>
<style>
.sdp-container { max-width: 300px; margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; text-align: center; background: #f9f9f9; }
.sdp-progress { height: 20px; background: #eee; border-radius: 10px; overflow: hidden; margin-bottom: 10px; }
.sdp-progress-bar { height: 100%; background: #4CAF50; transition: width 0.3s ease; }
.sdp-form input[type="number"] { width: 100px; padding: 8px; margin: 10px 0; }
.sdp-button { background: #007cba; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
.sdp-button:hover { background: #005a87; }
</style>
<script>
jQuery(document).ready(function($) {
    $('.sdp-button').on('click', function(e) {
        var amount = $(this).closest('form').find('input[name="amount"]').val();
        if (amount) {
            // For demo, simulate AJAX
            $.post(sdp_ajax.ajax_url, {action: 'sdp_process_donation', amount: amount}, function(res) {
                alert(res.data);
            });
        }
    });
});
</script>