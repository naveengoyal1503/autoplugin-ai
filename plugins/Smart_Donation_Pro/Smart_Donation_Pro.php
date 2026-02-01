/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Boost donations with customizable forms, progress bars, tiered rewards, and analytics.
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
        add_action('wp_ajax_sdp_process_donation', array($this, 'process_donation'));
        add_action('wp_ajax_nopriv_sdp_process_donation', array($this, 'process_donation'));
        add_shortcode('smart_donation_form', array($this, 'donation_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
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
            'goal' => '1000',
            'title' => 'Support Us!',
            'button_text' => 'Donate Now',
            'tiers' => '5,10,25,50,100'
        ), $atts);

        $tiers = explode(',', $atts['tiers']);
        $progress = $this->get_donation_progress($atts['goal']);

        ob_start();
        ?>
        <div class="sdp-container">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <div class="sdp-progress-bar">
                <div class="sdp-progress" style="width: <?php echo $progress; ?>%;"></div>
            </div>
            <p class="sdp-goal">Goal: $<?php echo esc_html($atts['goal']); ?> (Progress: <?php echo $progress; ?>%)</p>
            <form id="sdp-form" class="sdp-form">
                <select name="amount" id="sdp-amount">
                    <?php foreach ($tiers as $tier): ?>
                        <option value="<?php echo esc_attr(trim($tier)); ?>">$<?php echo esc_html(trim($tier)); ?></option>
                    <?php endforeach; ?>
                    <option value="custom">Custom Amount</option>
                </select>
                <input type="number" id="sdp-custom-amount" style="display:none;" placeholder="Enter amount">
                <input type="email" name="email" placeholder="Your email (optional)" required>
                <button type="submit" class="sdp-button"><?php echo esc_html($atts['button_text']); ?></button>
                <div id="sdp-message"></div>
            </form>
            <p class="sdp-thanks">Thank you for your support!</p>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_donation_progress($goal) {
        $total = get_option('sdp_total_donations', 0);
        return min(100, ($total / $goal) * 100);
    }

    public function process_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        $amount = sanitize_text_field($_POST['amount']);
        $email = sanitize_email($_POST['email']);

        if (empty($amount) || $amount <= 0) {
            wp_send_json_error('Invalid amount');
        }

        $paypal_email = get_option('sdp_paypal_email');
        if (!$paypal_email) {
            wp_send_json_error('PayPal not configured');
        }

        // Simulate PayPal form (in pro version, use API)
        $paypal_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=' . urlencode($paypal_email) . '&amount=' . urlencode($amount) . '&item_name=Donation&currency_code=USD&return=' . urlencode(get_site_url() . '/?donation=success');

        // Update total (demo, use DB in pro)
        $total = get_option('sdp_total_donations', 0) + (float)$amount;
        update_option('sdp_total_donations', $total);

        if ($email) {
            $this->send_thank_you_email($email, $amount);
        }

        wp_send_json_success(array('redirect' => $paypal_url));
    }

    private function send_thank_you_email($email, $amount) {
        $subject = 'Thank you for your donation!';
        $message = "Thank you for your generous donation of \$$amount!";
        wp_mail($email, $subject, $message);
    }

    public function activate() {
        add_option('sdp_total_donations', 0);
    }
}

new SmartDonationPro();

// Admin settings page
function sdp_admin_menu() {
    add_options_page('Smart Donation Pro Settings', 'Donation Pro', 'manage_options', 'sdp-settings', 'sdp_settings_page');
}
add_action('admin_menu', 'sdp_admin_menu');

function sdp_settings_page() {
    if (isset($_POST['sdp_paypal_email'])) {
        update_option('sdp_paypal_email', sanitize_email($_POST['sdp_paypal_email']));
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }
    $paypal_email = get_option('sdp_paypal_email');
    ?>
    <div class="wrap">
        <h1>Smart Donation Pro Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>PayPal Email</th>
                    <td><input type="email" name="sdp_paypal_email" value="<?php echo esc_attr($paypal_email); ?>" class="regular-text" required></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <p>Total Donations: $<?php echo get_option('sdp_total_donations', 0); ?></p>
    </div>
    <?php
}

// Inline CSS and JS for single file
/*
<style>
.sdp-container { max-width: 400px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9; }
.sdp-progress-bar { width: 100%; height: 20px; background: #eee; border-radius: 10px; overflow: hidden; margin: 10px 0; }
.sdp-progress { height: 100%; background: linear-gradient(90deg, #4CAF50, #45a049); transition: width 0.3s; }
.sdp-form { display: flex; flex-direction: column; gap: 10px; }
.sdp-form input, select, button { padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
.sdp-button { background: #007cba; color: white; border: none; cursor: pointer; font-weight: bold; }
.sdp-button:hover { background: #005a87; }
#sdp-message { margin-top: 10px; padding: 10px; border-radius: 4px; }
#sdp-message.success { background: #d4edda; color: #155724; }
#sdp-message.error { background: #f8d7da; color: #721c24; }
</style>
*/

<script type="text/javascript">
jQuery(document).ready(function($) {
    $('#sdp-amount').change(function() {
        if ($(this).val() === 'custom') {
            $('#sdp-custom-amount').show();
        } else {
            $('#sdp-custom-amount').hide();
        }
    });

    $('#sdp-form').submit(function(e) {
        e.preventDefault();
        var amount = $('#sdp-amount').val() === 'custom' ? $('#sdp-custom-amount').val() : $('#sdp-amount').val();
        var email = $('input[name="email"]').val();

        $.post(sdp_ajax.ajax_url, {
            action: 'sdp_process_donation',
            amount: amount,
            email: email,
            nonce: sdp_ajax.nonce
        }, function(response) {
            if (response.success) {
                window.location.href = response.data.redirect;
            } else {
                $('#sdp-message').html('<div class="error">' + response.data + '</div>').show();
            }
        });
    });
});
</script>