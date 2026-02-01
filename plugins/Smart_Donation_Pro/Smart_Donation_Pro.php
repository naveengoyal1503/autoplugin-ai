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

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartDonationPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        add_action('wp_ajax_sdp_process_donation', array($this, 'process_donation'));
        add_action('wp_ajax_nopriv_sdp_process_donation', array($this, 'process_donation'));
    }

    public function init() {
        if (get_option('sdp_paypal_email')) {
            // Plugin is set up
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10',
            'label' => 'Donate Now',
            'goal' => '500',
            'currency' => '$',
            'paypal_email' => get_option('sdp_paypal_email', ''),
        ), $atts);

        $current = get_option('sdp_total_donated', 0);
        $progress = min(100, ($current / $atts['goal']) * 100);

        ob_start();
        ?>
        <div class="sdp-container" style="max-width: 400px; margin: 20px 0;">
            <div class="sdp-progress" style="background: #f0f0f0; border-radius: 10px; height: 20px; margin-bottom: 15px;">
                <div class="sdp-progress-bar" style="background: #4CAF50; height: 100%; width: <?php echo $progress; ?>%; border-radius: 10px; transition: width 0.3s;"></div>
            </div>
            <p><strong>Goal: <?php echo $atts['currency']; ?><?php echo $atts['goal']; ?></strong> | Raised: <?php echo $atts['currency']; ?><?php echo number_format($current, 0); ?> (<?php echo round($progress); ?>%)</p>
            <input type="number" id="sdp-amount" value="<?php echo $atts['amount']; ?>" min="1" step="1" style="width: 100px; padding: 8px; margin-right: 10px;">
            <button id="sdp-donate-btn" class="sdp-button" style="background: #007cba; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 5px;" data-paypal="<?php echo $atts['paypal_email']; ?>"><?php echo esc_html($atts['label']); ?></button>
            <p id="sdp-message" style="margin-top: 10px; display: none;"></p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        $amount = floatval($_POST['amount']);
        $paypal_email = sanitize_email($_POST['paypal_email']);

        if ($amount < 1 || empty($paypal_email)) {
            wp_send_json_error('Invalid amount or email.');
            return;
        }

        // Simulate donation (in pro version, integrate Stripe/PayPal API)
        $current = floatval(get_option('sdp_total_donated', 0));
        update_option('sdp_total_donated', $current + $amount);

        // PayPal button URL
        $paypal_url = 'https://www.paypal.com/donate?hosted_button_id=TEST&amount=' . $amount . '&currency_code=USD&item_name=Donation&business=' . urlencode($paypal_email);

        wp_send_json_success(array('redirect' => $paypal_url, 'message' => 'Thank you for your donation!'));
    }
}

new SmartDonationPro();

// Admin settings
add_action('admin_menu', function() {
    add_options_page('Smart Donation Pro', 'Smart Donation', 'manage_options', 'sdp-settings', 'sdp_settings_page');
});

function sdp_settings_page() {
    if (isset($_POST['sdp_paypal_email'])) {
        update_option('sdp_paypal_email', sanitize_email($_POST['sdp_paypal_email']));
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }
    $paypal_email = get_option('sdp_paypal_email', '');
    ?>
    <div class="wrap">
        <h1>Smart Donation Pro Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>PayPal Email</th>
                    <td><input type="email" name="sdp_paypal_email" value="<?php echo esc_attr($paypal_email); ?>" class="regular-text" required /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <p>Use shortcode: <code>[smart_donation amount="10" label="Buy Me a Coffee" goal="500"]</code></p>
    </div>
    <?php
}

// Reset total donated
add_action('admin_init', function() {
    if (isset($_GET['sdp_reset']) && current_user_can('manage_options')) {
        update_option('sdp_total_donated', 0);
    }
});

?>