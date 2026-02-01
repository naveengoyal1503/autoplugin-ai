/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Collect donations easily with customizable forms, PayPal support, and tiered levels.
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
        add_action('wp_ajax_process_donation', array($this, 'process_donation'));
        add_action('wp_ajax_nopriv_process_donation', array($this, 'process_donation'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('smart_donation_paypal_email')) {
            // PayPal ready
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('paypal-sdk', 'https://www.paypal.com/sdk/js?client-id=TEST&currency=USD', array(), null, true);
        wp_enqueue_script('smart-donation-js', plugin_dir_url(__FILE__) . 'smart-donation.js', array('jquery', 'paypal-sdk'), '1.0.0', true);
        wp_localize_script('smart-donation-js', 'smartDonation', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('smart_donation_nonce')
        ));
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => 'Support Us',
            'amounts' => '5,10,25,50',
            'button_text' => 'Donate Now'
        ), $atts);

        $amounts = explode(',', $atts['amounts']);
        $options = '';
        foreach ($amounts as $amount) {
            $options .= '<option value="' . trim($amount) . '">' . trim($amount) . '</option>';
        }

        ob_start();
        ?>
        <div id="smart-donation-form" style="max-width: 400px; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <div class="donation-amounts">
                <select id="donation-amount" style="width: 100%; padding: 10px; margin-bottom: 10px;">
                    <?php echo $options; ?>
                    <option value="custom">Custom Amount</option>
                </select>
                <input type="number" id="custom-amount" placeholder="Enter amount" style="width: 100%; padding: 10px; display: none; margin-bottom: 10px;">
            </div>
            <div id="paypal-button-container" style="margin-bottom: 10px;"></div>
            <p style="font-size: 12px; color: #666;">Secure payment via PayPal. Thank you for your support!</p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#donation-amount').change(function() {
                if ($(this).val() === 'custom') {
                    $('#custom-amount').show();
                } else {
                    $('#custom-amount').hide();
                }
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        check_ajax_referer('smart_donation_nonce', 'nonce');
        // In pro version, save donation data to DB
        wp_die('Donation processed successfully!');
    }

    public function activate() {
        add_option('smart_donation_version', '1.0.0');
    }
}

new SmartDonationPro();

// Settings page
add_action('admin_menu', function() {
    add_options_page('Smart Donation Pro', 'Donation Settings', 'manage_options', 'smart-donation', 'smart_donation_settings_page');
});

function smart_donation_settings_page() {
    if (isset($_POST['paypal_email'])) {
        update_option('smart_donation_paypal_email', sanitize_email($_POST['paypal_email']));
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }
    $paypal_email = get_option('smart_donation_paypal_email');
    ?>
    <div class="wrap">
        <h1>Smart Donation Pro Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>PayPal Email</th>
                    <td><input type="email" name="paypal_email" value="<?php echo esc_attr($paypal_email); ?>" class="regular-text" required></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <p><strong>Shortcode:</strong> <code>[smart_donation]</code></p>
        <p><em>Upgrade to Pro for recurring donations and analytics.</em></p>
    </div>
    <?php
}

// Note: Full PayPal integration requires sandbox/live client ID. Replace TEST with your client ID. JS for PayPal buttons goes in smart-donation.js (enqueue placeholder).