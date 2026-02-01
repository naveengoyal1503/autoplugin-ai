/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Collect donations easily with customizable buttons, progress bars, and PayPal integration.
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
        add_action('wp_ajax_sdp_process_donation', array($this, 'process_donation'));
        add_action('wp_ajax_nopriv_sdp_process_donation', array($this, 'process_donation'));
    }

    public function init() {
        if (get_option('sdp_paypal_email')) {
            // PayPal integration ready
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'sdp-style.css', array(), '1.0.0');
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10',
            'label' => 'Donate Now',
            'goal' => '1000',
            'currency' => '$',
            'paypal_email' => get_option('sdp_paypal_email', ''),
        ), $atts);

        $current = get_option('sdp_total_donations', 0);
        $progress = min(100, ($current / $atts['goal']) * 100);

        ob_start();
        ?>
        <div class="sdp-container">
            <h3>Support Us!</h3>
            <div class="sdp-progress-bar">
                <div class="sdp-progress" style="width: <?php echo $progress; ?>%;"></div>
            </div>
            <p><?php echo $atts['currency']; ?><?php echo $current; ?> / <?php echo $atts['currency']; ?><?php echo $atts['goal']; ?> raised</p>
            <form class="sdp-form" data-paypal="<?php echo esc_attr($atts['paypal_email']); ?>">
                <input type="number" name="amount" value="<?php echo $atts['amount']; ?>" min="1" step="0.01" placeholder="Amount">
                <input type="email" name="email" placeholder="Your Email (optional)">
                <button type="submit" class="sdp-button"><?php echo esc_html($atts['label']); ?></button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        $amount = sanitize_text_field($_POST['amount']);
        $email = sanitize_email($_POST['email']);

        // Simulate PayPal redirect (in pro version, integrate real PayPal API)
        $paypal_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=' . get_option('sdp_paypal_email') . '&amount=' . $amount . '&item_name=Donation';
        if ($email) $paypal_url .= '&email=' . $email;

        $current = (float) get_option('sdp_total_donations', 0);
        update_option('sdp_total_donations', $current + (float)$amount);

        wp_send_json_success(array('redirect' => $paypal_url));
    }
}

new SmartDonationPro();

// Admin settings
add_action('admin_menu', function() {
    add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'sdp-settings', 'sdp_settings_page');
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
                    <td><input type="email" name="sdp_paypal_email" value="<?php echo esc_attr($paypal_email); ?>" class="regular-text"></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <p>Use shortcode: <code>[smart_donation amount="10" label="Buy Me a Coffee" goal="1000"]</code></p>
    </div>
    <?php
}

// Minimal CSS
$css = '.sdp-container { max-width: 400px; margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; text-align: center; background: #f9f9f9; } .sdp-progress-bar { background: #eee; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0; } .sdp-progress { background: #4CAF50; height: 100%; transition: width 0.3s; } .sdp-form input { margin: 5px; padding: 8px; } .sdp-button { background: #0073aa; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; } .sdp-button:hover { background: #005a87; }';
file_put_contents(plugin_dir_path(__FILE__) . 'sdp-style.css', $css);

// Minimal JS
$js = "jQuery(document).ready(function($) { $('.sdp-form').on('submit', function(e) { e.preventDefault(); var form = $(this); $.post(sdp_ajax.ajax_url, { action: 'sdp_process_donation', amount: form.find('input[name=amount]').val(), email: form.find('input[name=email]').val(), nonce: sdp_ajax.nonce }, function(res) { if (res.success) { window.location = res.data.redirect; } }); }); });";
file_put_contents(plugin_dir_path(__FILE__) . 'sdp-script.js', $js);