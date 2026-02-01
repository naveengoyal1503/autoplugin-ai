/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donations_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donations Pro
 * Plugin URI: https://example.com/smart-donations-pro
 * Description: Boost your WordPress site revenue with easy-to-use donation buttons, progress bars, and payment options.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
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
        if (get_option('smart_donations_paypal_email')) {
            // PayPal integration ready
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-donations', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('smart-donations', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
        wp_localize_script('smart-donations', 'smartDonations', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('donation_nonce')
        ));
    }

    public function admin_menu() {
        add_options_page('Smart Donations Pro', 'Donations', 'manage_options', 'smart-donations', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['paypal_email'])) {
            update_option('smart_donations_paypal_email', sanitize_email($_POST['paypal_email']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $paypal_email = get_option('smart_donations_paypal_email', '');
        ?>
        <div class="wrap">
            <h1>Smart Donations Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="paypal_email" value="<?php echo esc_attr($paypal_email); ?>" /></td>
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
            'label' => 'Donate Now',
            'goal' => '1000',
            'currency' => '$'
        ), $atts);

        $paypal_email = get_option('smart_donations_paypal_email');
        if (!$paypal_email) {
            return '<p>Please configure PayPal email in settings.</p>';
        }

        $progress = min(100, (get_option('smart_donations_total', 0) / $atts['goal']) * 100);
        ob_start();
        ?>
        <div class="smart-donation-container">
            <h3>Support Us! <?php echo $atts['currency']; echo number_format($atts['amount']); ?> Donation</h3>
            <div class="donation-progress">
                <div class="progress-bar" style="width: <?php echo $progress; ?>%;"></div>
            </div>
            <p>Goal: <?php echo $atts['currency']; echo $atts['goal']; ?> (<?php echo $progress; ?>% reached)</p>
            <form class="donation-form" data-paypal="<?php echo esc_attr($paypal_email); ?>">
                <input type="number" name="amount" value="<?php echo $atts['amount']; ?>" min="1" step="0.01" />
                <input type="hidden" name="currency" value="<?php echo esc_attr($atts['currency']); ?>" />
                <button type="submit" class="donate-button"><?php echo esc_html($atts['label']); ?></button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        check_ajax_referer('donation_nonce', 'nonce');
        $amount = floatval($_POST['amount']);
        $paypal_email = get_option('smart_donations_paypal_email');
        $paypal_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=' . urlencode($paypal_email) .
                      '&amount=' . $amount . '&currency_code=USD&item_name=Donation';
        wp_die(json_encode(array('success' => true, 'url' => $paypal_url)));
    }
}

new SmartDonationsPro();

// Create assets directories and files on activation
register_activation_hook(__FILE__, function() {
    $upload_dir = plugin_dir_path(__FILE__) . 'assets';
    if (!file_exists($upload_dir)) {
        wp_mkdir_p($upload_dir);
    }
    // Minimal JS
    $js = "jQuery(document).ready(function($) {
        $('.donation-form').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            $.post(smartDonations.ajaxurl, {
                action: 'process_donation',
                amount: form.find('input[name=amount]').val(),
                nonce: smartDonations.nonce
            }, function(res) {
                if (res.success) window.location = res.data.url;
            });
        });
    });";
    file_put_contents($upload_dir . '/script.js', $js);
    // Minimal CSS
    $css = ".smart-donation-container { border: 1px solid #ddd; padding: 20px; margin: 20px 0; background: #f9f9f9; }
    .progress-bar { height: 20px; background: #0073aa; transition: width 0.3s; }
    .donation-progress { background: #eee; height: 20px; margin: 10px 0; }
    .donate-button { background: #0073aa; color: white; border: none; padding: 10px 20px; cursor: pointer; }
    .donate-button:hover { background: #005a87; }";
    file_put_contents($upload_dir . '/style.css', $css);
});