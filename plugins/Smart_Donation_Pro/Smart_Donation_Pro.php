/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Create customizable donation forms with tiers, PayPal integration, progress bars, and analytics.
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
        if (get_option('sdp_paypal_email')) {
            // PayPal integration ready
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'sdp-style.css', array(), '1.0.0');
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'smart-donation-pro', array($this, 'admin_page'));
    }

    public function admin_page() {
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
                        <td><input type="email" name="sdp_paypal_email" value="<?php echo esc_attr($paypal_email); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcode: <code>[smart_donation]</code></p>
        </div>
        <?php
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'goal' => '1000',
            'title' => 'Support Us!',
            'tiers' => '5,10,25,50,100'
        ), $atts);

        $tiers = explode(',', $atts['tiers']);
        $raised = get_option('sdp_raised', 0);
        $goal = floatval($atts['goal']);
        $progress = min(100, ($raised / $goal) * 100);

        ob_start();
        ?>
        <div id="sdp-container" data-goal="<?php echo $goal; ?>">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <div class="sdp-progress-bar">
                <div class="sdp-progress" style="width: <?php echo $progress; ?>%;"></div>
            </div>
            <p>$<?php echo number_format($raised); ?> raised of $<?php echo number_format($goal); ?> goal</p>
            <div class="sdp-tiers">
                <?php foreach ($tiers as $tier): $tier = trim($tier); ?>
                    <button class="sdp-tier" data-amount="<?php echo $tier; ?>">$<?php echo $tier; ?></button>
                <?php endforeach; ?>
            </div>
            <div class="sdp-custom">
                <input type="number" id="sdp-amount" placeholder="Custom amount" min="1" />
                <button id="sdp-donate">Donate Now</button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        $amount = floatval($_POST['amount']);
        if ($amount < 1) {
            wp_die('Invalid amount');
        }

        $paypal_email = get_option('sdp_paypal_email');
        if (!$paypal_email) {
            wp_die('PayPal not configured');
        }

        // In production, use PayPal API or redirect to PayPal
        // For demo: Simulate success and update raised
        $raised = get_option('sdp_raised', 0) + $amount;
        update_option('sdp_raised', $raised);

        // PayPal redirect URL (replace with your PayPal button link in pro version)
        $paypal_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=' . urlencode($paypal_email) . '&amount=' . $amount . '&item_name=Donation';
        wp_send_json_success(array('redirect' => $paypal_url));
    }
}

new SmartDonationPro();

// Inline styles
add_action('wp_head', function() { ?>
<style>
#sdp-container { max-width: 400px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; text-align: center; background: #f9f9f9; }
.sdp-progress-bar { width: 100%; height: 20px; background: #eee; border-radius: 10px; overflow: hidden; margin: 10px 0; }
.sdp-progress { height: 100%; background: #4CAF50; transition: width 0.3s; }
.sdp-tiers { margin: 20px 0; }
.sdp-tier { margin: 5px; padding: 10px 20px; background: #0073aa; color: white; border: none; border-radius: 5px; cursor: pointer; }
.sdp-tier:hover { background: #005a87; }
.sdp-custom input { padding: 10px; margin: 10px; width: 120px; }
#sdp-donate { padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; }
#sdp-donate:hover { background: #218838; }
</style>
<?php });

// JavaScript
add_action('wp_footer', function() { ?>
<script>
jQuery(document).ready(function($) {
    $('.sdp-tier').click(function() {
        $('#sdp-amount').val($(this).data('amount'));
    });
    $('#sdp-donate').click(function() {
        var amount = $('#sdp-amount').val();
        if (amount < 1) return alert('Enter amount >= $1');
        $.post(sdp_ajax.ajax_url, {
            action: 'sdp_process_donation',
            amount: amount,
            nonce: sdp_ajax.nonce
        }, function(res) {
            if (res.success) {
                window.location = res.data.redirect;
            }
        });
    });
});
</script>
<?php });