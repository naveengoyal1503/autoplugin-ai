/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Easily add customizable donation buttons and forms to monetize your WordPress site.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

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
            // Plugin is set up
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('smart-donation', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('smart-donation', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10',
            'currency' => 'USD',
            'button_text' => 'Donate Now',
            'goal' => '1000',
            'current' => '250'
        ), $atts);

        $paypal_email = get_option('smart_donation_paypal_email', '');
        if (!$paypal_email) return '<p>Please set up PayPal email in settings.</p>';

        $paypal_url = 'https://www.paypal.com/donate?hosted_button_id=' . $this->generate_button_id($atts['amount']);

        ob_start();
        ?>
        <div class="smart-donation-container">
            <div class="donation-progress" style="background: #f0f0f0; height: 20px; border-radius: 10px; overflow: hidden;">
                <div class="progress-bar" style="background: #28a745; height: 100%; width: <?php echo ($atts['current'] / $atts['goal']) * 100; ?>%; transition: width 0.5s;"></div>
            </div>
            <p><strong>$<?php echo $atts['current']; ?> / $<?php echo $atts['goal']; ?></strong> raised</p>
            <a href="<?php echo esc_url($paypal_url); ?>" class="donation-button" target="_blank">
                <?php echo esc_html($atts['button_text']); ?> $<?php echo $atts['amount']; ?> <?php echo $atts['currency']; ?>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_button_id($amount) {
        // In pro version, integrate real PayPal button creation API
        // For demo, return a placeholder
        return md5('demo_' . $amount . get_option('smart_donation_paypal_email'));
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'smart-donation', array($this, 'settings_page'));
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
            <p>Use shortcode: <code>[smart_donation amount="20" goal="500" current="100" button_text="Support Us"]</code></p>
        </div>
        <?php
    }

    public function activate() {
        add_option('smart_donation_version', '1.0.0');
    }
}

new SmartDonationPro();

// Inline styles and scripts for single file

function smart_donation_assets() {
    ?>
    <style>
    .smart-donation-container { text-align: center; padding: 20px; max-width: 300px; margin: 0 auto; }
    .donation-button { display: inline-block; background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; }
    .donation-button:hover { background: #005a87; }
    </style>
    <script>jQuery(document).ready(function($) { $('.donation-button').on('click', function() { $(this).text('Thank you!'); }); });</script>
    <?php
}

add_action('wp_head', 'smart_donation_assets');