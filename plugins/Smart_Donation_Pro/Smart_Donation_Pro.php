/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Create customizable donation buttons and progress bars for easy site monetization.
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
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('smart_donation_paypal_email')) {
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'settings_link'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-donation-js', plugin_dir_url(__FILE__) . 'smart-donation.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('smart-donation-css', plugin_dir_url(__FILE__) . 'smart-donation.css', array(), '1.0.0');
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10',
            'currency' => 'USD',
            'button_text' => 'Donate Now',
            'goal' => '1000',
            'progress' => '50',
        ), $atts);

        $paypal_email = get_option('smart_donation_paypal_email', '');
        if (!$paypal_email) {
            return '<p>Please configure PayPal email in settings.</p>';
        }

        $paypal_url = 'https://www.paypal.com/donate?hosted_button_id=' . $this->generate_button_id($atts['amount']);

        ob_start();
        ?>
        <div class="smart-donation-container">
            <div class="donation-progress" style="width: <?php echo esc_attr($atts['progress']); ?>%;"></div>
            <p>Goal: $<?php echo esc_html($atts['goal']); ?> (Progress: <?php echo esc_html($atts['progress']); ?>%)</p>
            <a href="<?php echo esc_url($paypal_url); ?}" class="donation-button" target="_blank">
                <?php echo esc_html($atts['button_text']); ?> $<?php echo esc_html($atts['amount']); ?> <?php echo esc_html($atts['currency']); ?>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_button_id($amount) {
        // In pro version, integrate real PayPal button creation API
        // For demo, return placeholder
        return md5($amount . 'demo');
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro', 'Donation Settings', 'manage_options', 'smart-donation', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['paypal_email'])) {
            update_option('smart_donation_paypal_email', sanitize_email($_POST['paypal_email']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $email = get_option('smart_donation_paypal_email', '');
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="paypal_email" value="<?php echo esc_attr($email); ?>" class="regular-text" required /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Usage: <code>[smart_donation amount="20" goal="500" progress="40" button_text="Support Us"]</code></p>
            <p><strong>Upgrade to Pro</strong> for recurring donations, Stripe, analytics, and more!</p>
        </div>
        <?php
    }

    public function settings_link($links) {
        $settings_link = '<a href="options-general.php?page=smart-donation">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public function activate() {
        add_option('smart_donation_version', '1.0.0');
    }
}

new SmartDonationPro();

// Inline CSS and JS for self-contained plugin
/*
<style>
.smart-donation-container { text-align: center; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9; }
.donation-progress { height: 20px; background: #4CAF50; transition: width 0.3s; border-radius: 10px; }
.donation-button { display: inline-block; background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; }
.donation-button:hover { background: #005a87; }
</style>
*/

/*
<script>
jQuery(document).ready(function($) {
    $('.donation-button').on('click', function() {
        // Optional: Track clicks with analytics in pro
        console.log('Donation button clicked');
    });
});
</script>
*/