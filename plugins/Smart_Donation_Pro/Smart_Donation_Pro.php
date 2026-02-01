/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Boost your WordPress site revenue with easy donation buttons, tiers, and progress bars.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartDonationPro {
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_shortcode('smart_donation', [$this, 'donation_shortcode']);
        add_action('wp_ajax_sdp_process_donation', [$this, 'process_donation']);
        add_action('wp_ajax_nopriv_sdp_process_donation', [$this, 'process_donation']);
    }

    public function init() {
        if (get_option('sdp_paypal_email')) {
            // PayPal integration ready
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp-script.js', ['jquery'], '1.0.0', true);
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'sdp-style.css', [], '1.0.0');
        wp_localize_script('sdp-script', 'sdp_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sdp_nonce')
        ]);
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts([
            'goal' => '1000',
            'tiers' => '5,10,25,50,100'
        ], $atts);

        $tiers = explode(',', $atts['tiers']);
        $current = get_option('sdp_total_donated', 0);
        $goal = floatval($atts['goal']);
        $progress = min(100, ($current / $goal) * 100);

        ob_start();
        ?>
        <div class="sdp-container">
            <h3>Support Us! <span id="sdp-current"><?php echo $this->format_currency($current); ?></span> / <?php echo $this->format_currency($goal); ?></h3>
            <div class="sdp-progress-bar">
                <div class="sdp-progress" style="width: <?php echo $progress; ?>%;"></div>
            </div>
            <div class="sdp-tiers">
                <?php foreach ($tiers as $tier): $tier = trim($tier); ?>
                    <button class="sdp-tier-btn" data-amount="<?php echo $tier; ?>"><?php echo $this->format_currency($tier); ?></button>
                <?php endforeach; ?>
            </div>
            <div id="sdp-custom-amount">
                <input type="number" id="sdp-amount" placeholder="Custom amount" min="1">
                <button id="sdp-donate-btn">Donate Now</button>
            </div>
            <div id="sdp-message"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        $amount = floatval($_POST['amount']);
        $email = sanitize_email(get_option('sdp_paypal_email'));

        if (!$email || $amount < 1) {
            wp_send_json_error('Invalid amount or setup');
        }

        // Simulate PayPal form (in Pro: full Stripe/PayPal API)
        $paypal_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=' . $email . '&amount=' . $amount . '&item_name=Donation';

        // Update total (demo: add amount)
        $current = floatval(get_option('sdp_total_donated', 0));
        update_option('sdp_total_donated', $current + $amount);

        wp_send_json_success(['redirect' => $paypal_url]);
    }

    private function format_currency($amount) {
        return '$' . number_format($amount, 0);
    }
}

new SmartDonationPro();

// Settings page
add_action('admin_menu', function() {
    add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'sdp-settings', function() {
        if (isset($_POST['sdp_paypal_email'])) {
            update_option('sdp_paypal_email', sanitize_email($_POST['sdp_paypal_email']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="sdp_paypal_email" value="<?php echo esc_attr(get_option('sdp_paypal_email')); ?>" class="regular-text"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock Stripe, analytics, unlimited goals for $49/year.</p>
        </div>
        <?php
    });
});

// Minimal JS (inline for single file)
function sdp_inline_js() {
    if (is_page() || is_single()) { // Load only where needed
        ?>
        <script>
jQuery(document).ready(function($) {
    $('.sdp-tier-btn').click(function() {
        $('#sdp-amount').val($(this).data('amount'));
    });
    $('#sdp-donate-btn').click(function() {
        var amount = parseFloat($('#sdp-amount').val());
        if (amount < 1) return;
        $('#sdp-message').html('<p>Processing...</p>');
        $.post(sdp_ajax.ajax_url, {
            action: 'sdp_process_donation',
            amount: amount,
            nonce: sdp_ajax.nonce
        }, function(res) {
            if (res.success) {
                window.location = res.data.redirect;
            } else {
                $('#sdp-message').html('<p style="color:red;">' + res.data + '</p>');
            }
        });
    });
});
        </script>
        <style>
.sdp-container { max-width: 400px; margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9; }
.sdp-progress-bar { width: 100%; height: 20px; background: #eee; border-radius: 10px; overflow: hidden; margin: 10px 0; }
.sdp-progress { height: 100%; background: #4CAF50; transition: width 0.3s; }
.sdp-tiers { display: flex; flex-wrap: wrap; gap: 10px; margin: 10px 0; }
.sdp-tier-btn { padding: 10px 15px; background: #007cba; color: white; border: none; border-radius: 5px; cursor: pointer; }
.sdp-tier-btn:hover { background: #005a87; }
#sdp-custom-amount input { width: 120px; padding: 8px; margin-right: 10px; }
#sdp-donate-btn { padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; }
        </style>
        <?php
    }
}
add_action('wp_footer', 'sdp_inline_js');