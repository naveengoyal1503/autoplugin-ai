/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Collect donations easily with customizable buttons, progress bars, and PayPal/Stripe support.
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
            add_filter('script_loader_tag', array($this, 'add_defer_attribute'), 10, 2);
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'sdp-style.css', array(), '1.0.0');
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'smart-donation-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['sdp_save'])) {
            update_option('sdp_paypal_email', sanitize_email($_POST['paypal_email']));
            update_option('sdp_stripe_key', sanitize_text_field($_POST['stripe_key']));
            update_option('sdp_goal_amount', floatval($_POST['goal_amount']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $paypal_email = get_option('sdp_paypal_email', '');
        $stripe_key = get_option('sdp_stripe_key', '');
        $goal_amount = get_option('sdp_goal_amount', 1000);
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="paypal_email" value="<?php echo esc_attr($paypal_email); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Stripe Publishable Key</th>
                        <td><input type="text" name="stripe_key" value="<?php echo esc_attr($stripe_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Donation Goal ($)</th>
                        <td><input type="number" name="goal_amount" value="<?php echo esc_attr($goal_amount); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button('Save Settings', 'primary', 'sdp_save'); ?>
            </form>
            <p><strong>Upgrade to Pro:</strong> Unlock recurring donations, analytics, and more for $29/year!</p>
        </div>
        <?php
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10',
            'label' => 'Donate Now',
            'goal' => true
        ), $atts);

        $paypal_email = get_option('sdp_paypal_email');
        $goal_amount = get_option('sdp_goal_amount', 1000);
        $donated = get_option('sdp_total_donated', 0);
        $progress = min(100, ($donated / $goal_amount) * 100);

        ob_start();
        ?>
        <div class="sdp-container">
            <?php if ($atts['goal'] && $goal_amount > 0): ?>
            <div class="sdp-goal">
                <p>Goal: $<span id="sdp-goal"><?php echo number_format($goal_amount); ?></span> | Raised: $<span id="sdp-raised"><?php echo number_format($donated); ?></span></p>
                <div class="sdp-progress-bar">
                    <div class="sdp-progress" style="width: <?php echo $progress; ?>%;"></div>
                </div>
            </div>
            <?php endif; ?>
            <div class="sdp-button-group">
                <input type="number" id="sdp-amount" value="<?php echo esc_attr($atts['amount']); ?>" min="1" step="0.01" />
                <button id="sdp-paypal" class="sdp-btn paypal"><?php echo esc_html($atts['label']); ?> via PayPal</button>
                <div id="sdp-stripe" class="sdp-stripe">or Pay with Card</div>
            </div>
            <p class="sdp-message" style="display:none;"></p>
        </div>
        <script src="https://www.paypal.com/sdk/js?client-id=<?php echo esc_js($paypal_email); ?>&currency=USD"></script>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        $amount = floatval($_POST['amount']);
        $email = sanitize_email($_POST['email']);
        $total_donated = get_option('sdp_total_donated', 0) + $amount;
        update_option('sdp_total_donated', $total_donated);
        wp_send_json_success('Thank you for your $' . number_format($amount, 2) . ' donation!');
    }

    public function add_defer_attribute($tag, $handle) {
        return str_replace(' src=', ' defer src=', $tag);
    }
}

new SmartDonationPro();

// Enqueue JS and CSS inline for single file
add_action('wp_head', function() {
    echo '<style>
.sdp-container { max-width: 400px; margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9; }
.sdp-goal { margin-bottom: 20px; }
.sdp-progress-bar { height: 20px; background: #eee; border-radius: 10px; overflow: hidden; }
.sdp-progress { height: 100%; background: #28a745; transition: width 0.3s; }
#sdp-amount { width: 100px; padding: 8px; margin-right: 10px; }
.sdp-btn { background: #007cba; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
.sdp-btn:hover { background: #005a87; }
.sdp-message { margin-top: 10px; padding: 10px; border-radius: 5px; }
.sdp-message.success { background: #d4edda; color: #155724; }
    </style>';
    echo '<script>
jQuery(document).ready(function($) {
    $("#sdp-paypal").click(function(e) {
        e.preventDefault();
        var amount = $("#sdp-amount").val();
        paypal.Buttons({
            createOrder: function(data, actions) {
                return actions.order.create({
                    purchase_units: [{ amount: { value: amount } }]
                });
            },
            onApprove: function(data, actions) {
                return actions.order.capture().then(function(details) {
                    $.post(sdp_ajax.ajaxurl, {
                        action: "sdp_process_donation",
                        amount: amount,
                        email: details.payer.email_address,
                        nonce: sdp_ajax.nonce
                    }, function(res) {
                        $(".sdp-message").text(res.data).addClass("success").show();
                        location.reload();
                    });
                });
            }
        }).render("#sdp-paypal");
    });
});
    </script>';
});