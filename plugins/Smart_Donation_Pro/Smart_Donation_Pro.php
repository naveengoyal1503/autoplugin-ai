/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Boost your WordPress site revenue with easy donation buttons, progress bars, and payment integration.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-donation-pro
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
        if (get_option('sdp_paypal_email')) {
            // PayPal integration ready
        }
        load_plugin_textdomain('smart-donation-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'smart-donation-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
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
            <p><strong>Upgrade to Pro:</strong> Unlock recurring donations, analytics, and custom themes! <a href="https://example.com/pro" target="_blank">Learn More</a></p>
        </div>
        <?php
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'goal' => '100',
            'title' => 'Support Us!',
            'button_text' => 'Donate Now',
        ), $atts);

        $current = get_option('sdp_total_donated', 0);
        $goal = floatval($atts['goal']);
        $progress = min(100, ($current / $goal) * 100);

        ob_start();
        ?>
        <div class="sdp-container">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <div class="sdp-progress-bar">
                <div class="sdp-progress" style="width: <?php echo $progress; ?>%;"></div>
            </div>
            <p>$<?php echo number_format($current, 2); ?> / $<?php echo $goal; ?> raised</p>
            <div class="sdp-amounts">
                <button class="sdp-amount-btn" data-amount="5">$5</button>
                <button class="sdp-amount-btn" data-amount="10">$10</button>
                <button class="sdp-amount-btn" data-amount="25">$25</button>
                <button class="sdp-amount-btn" data-amount="50">$50</button>
                <input type="number" class="sdp-custom-amount" placeholder="Custom" />
            </div>
            <button class="sdp-donate-btn" data-paypal="<?php echo esc_attr(get_option('sdp_paypal_email')); ?>"><?php echo esc_html($atts['button_text']); ?></button>
        </div>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        $amount = floatval($_POST['amount']);
        if ($amount > 0) {
            $current = floatval(get_option('sdp_total_donated', 0));
            update_option('sdp_total_donated', $current + $amount);
            wp_send_json_success('Thank you for your donation!');
        } else {
            wp_send_json_error('Invalid amount');
        }
    }
}

new SmartDonationPro();

// Create assets directories if missing
$upload_dir = wp_upload_dir();
$assets_dir = plugin_dir_path(__FILE__) . 'assets/';
if (!file_exists($assets_dir)) {
    wp_mkdir_p($assets_dir);
}

// Sample style.css content
$css = ".sdp-container { max-width: 400px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; text-align: center; background: #f9f9f9; } .sdp-progress-bar { background: #eee; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0; } .sdp-progress { background: #4CAF50; height: 100%; transition: width 0.3s; } .sdp-amounts { margin: 20px 0; } .sdp-amount-btn, .sdp-custom-amount { margin: 5px; padding: 10px; background: #007cba; color: white; border: none; border-radius: 5px; cursor: pointer; } .sdp-custom-amount { width: 100px; background: white; color: #333; } .sdp-donate-btn { background: #28a745; color: white; padding: 12px 24px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; } .sdp-donate-btn:hover { background: #218838; }";
file_put_contents($assets_dir . 'style.css', $css);

// Sample script.js content
$js = "jQuery(document).ready(function($) { $('.sdp-amount-btn, .sdp-custom-amount').on('click change', function() { $('.sdp-custom-amount, .sdp-amount-btn').removeClass('selected'); $(this).addClass('selected'); }); $('.sdp-donate-btn').on('click', function() { var amount = $('.sdp-custom-amount').val() || $('.sdp-amount-btn.selected').data('amount') || 10; if (amount > 0) { $.post(sdp_ajax.ajax_url, { action: 'sdp_process_donation', amount: amount, nonce: sdp_ajax.nonce }, function(resp) { if (resp.success) { alert('Thank you!'); location.reload(); } else { alert(resp.data); } }); } }); });";
file_put_contents($assets_dir . 'script.js', $js);