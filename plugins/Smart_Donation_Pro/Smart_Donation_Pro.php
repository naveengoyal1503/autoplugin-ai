/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Boost your WordPress site revenue with customizable donation buttons, progress bars, and PayPal payments.
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
        add_action('wp_ajax_process_donation', array($this, 'process_donation'));
        add_action('wp_ajax_nopriv_process_donation', array($this, 'process_donation'));
    }

    public function init() {
        if (get_option('smart_donation_paypal_email')) {
            wp_register_script('paypal-sdk', 'https://www.paypal.com/sdk/js?client-id=' . get_option('smart_donation_paypal_client_id', 'YOUR_SANDBOX_CLIENT_ID'), array(), null, true);
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', "
            jQuery(document).ready(function($) {
                $('.smart-donate-btn').click(function(e) {
                    e.preventDefault();
                    var amount = $(this).data('amount');
                    $('#donation-amount').val(amount);
                    $('#donation-modal').show();
                });
                $('.close-modal').click(function() {
                    $('#donation-modal').hide();
                });
                $('#process-donation').click(function() {
                    var amount = $('#donation-amount').val();
                    var name = $('#donor-name').val();
                    $.post('" . admin_url('admin-ajax.php') . "', {
                        action: 'process_donation',
                        amount: amount,
                        name: name
                    }, function(response) {
                        if (response.success) {
                            $('#paypal-button-container').html(response.data.html);
                            if (typeof paypal !== 'undefined') {
                                paypal.Buttons({
                                    createOrder: function(data, actions) {
                                        return actions.order.create({
                                            purchase_units: [{
                                                amount: { value: amount }
                                            }]
                                        });
                                    },
                                    onApprove: function(data, actions) {
                                        return actions.order.capture().then(function(details) {
                                            alert('Transaction completed by ' + details.payer.name.given_name);
                                            $('#donation-modal').hide();
                                        });
                                    }
                                }).render('#paypal-button-container');
                            }
                        }
                    });
                });
            });
        ");
        wp_enqueue_style('smart-donation-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro', 'Donation Settings', 'manage_options', 'smart-donation', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['paypal_email'])) {
            update_option('smart_donation_paypal_email', sanitize_email($_POST['paypal_email']));
            update_option('smart_donation_paypal_client_id', sanitize_text_field($_POST['paypal_client_id']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="paypal_email" value="<?php echo get_option('smart_donation_paypal_email'); ?>" /></td>
                    </tr>
                    <tr>
                        <th>PayPal Client ID (Sandbox)</th>
                        <td><input type="text" name="paypal_client_id" value="<?php echo get_option('smart_donation_paypal_client_id'); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '5',
            'title' => 'Support Us',
            'goal' => '1000',
            'current' => '250'
        ), $atts);

        ob_start();
        ?>
        <div class="smart-donation-container">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <div class="donation-progress" style="background: #f0f0f0; height: 20px; border-radius: 10px; overflow: hidden;">
                <div class="progress-bar" style="background: #007cba; height: 100%; width: <?php echo ($atts['current'] / $atts['goal']) * 100; ?>%; transition: width 0.3s;"></div>
            </div>
            <p>$<?php echo esc_html($atts['current']); ?> / $<?php echo esc_html($atts['goal']); ?> raised</p>
            <button class="smart-donate-btn button" data-amount="<?php echo esc_attr($atts['amount']); ?>">Donate $<?php echo esc_html($atts['amount']); ?></button>
        </div>
        <div id="donation-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999;">
            <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:white; padding:20px; border-radius:10px;">
                <span class="close-modal" style="float:right; cursor:pointer; font-size:24px;">&times;</span>
                <h4>Enter Donation Amount</h4>
                $<input type="number" id="donation-amount" value="<?php echo esc_attr($atts['amount']); ?>" step="0.01" min="1" />
                <p>Name: <input type="text" id="donor-name" /></p>
                <button id="process-donation" class="button button-primary">Pay with PayPal</button>
                <div id="paypal-button-container"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        $amount = floatval($_POST['amount']);
        $name = sanitize_text_field($_POST['name']);
        $paypal_email = get_option('smart_donation_paypal_email');
        if (!$paypal_email) {
            wp_send_json_error('PayPal not configured');
        }
        wp_send_json_success(array('html' => '<p>Redirecting to PayPal...</p>'));
    }
}

new SmartDonationPro();

// Inline CSS
add_action('wp_head', function() {
    echo '<style>
        .smart-donation-container { text-align: center; padding: 20px; border: 1px solid #ddd; border-radius: 10px; margin: 20px 0; }
        .smart-donate-btn { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .smart-donate-btn:hover { background: #005a87; }
    </style>';
});
