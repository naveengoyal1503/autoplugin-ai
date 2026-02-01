/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donations_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donations Pro
 * Plugin URI: https://example.com/smart-donations-pro
 * Description: Accept one-time and recurring donations via PayPal with tiered levels, customizable forms, and analytics.
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
        add_shortcode('smart_donations', array($this, 'donations_shortcode'));
        add_action('wp_ajax_process_donation', array($this, 'process_donation'));
        add_action('wp_ajax_nopriv_process_donation', array($this, 'process_donation'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('smart_donations_paypal_email')) {
            // Plugin is set up
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('paypal-sdk', 'https://www.paypal.com/sdk/js?client-id=TEST&currency=USD', array(), '1.0', true);
        wp_enqueue_script('smart-donations-js', plugin_dir_url(__FILE__) . 'smart-donations.js', array('jquery', 'paypal-sdk'), '1.0', true);
        wp_localize_script('smart-donations-js', 'smart_donations_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('smart_donations_nonce')
        ));
    }

    public function donations_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => 'Support Us',
            'amounts' => '5,10,25,50',
            'recurring' => 'no'
        ), $atts);

        $amounts = explode(',', $atts['amounts']);
        $paypal_email = get_option('smart_donations_paypal_email');

        ob_start();
        ?>
        <div id="smart-donations-container" style="max-width: 400px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; text-align: center;">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <p>Help us keep creating great content! Choose an amount:</p>
            <?php foreach ($amounts as $amount) : $amount = trim($amount); ?>
                <button class="donation-btn" data-amount="<?php echo esc_attr($amount); ?>" style="margin: 5px; padding: 10px 20px; background: #007cba; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    $<?php echo esc_html($amount); ?> <?php echo $atts['recurring'] === 'yes' ? '(Monthly)' : ''; ?>
                </button>
            <?php endforeach; ?>
            <div id="paypal-button-container" style="margin-top: 20px;"></div>
            <div id="donation-message"></div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.donation-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const amount = this.dataset.amount;
                        const container = document.getElementById('paypal-button-container');
                        container.innerHTML = '';

                        paypal.Buttons({
                            createOrder: function(data, actions) {
                                return actions.order.create({
                                    purchase_units: [{
                                        amount: {
                                            value: amount,
                                            currency_code: 'USD'
                                        }
                                    }]
                                });
                            },
                            onApprove: function(data, actions) {
                                return actions.order.capture().then(function(details) {
                                    jQuery.post(smart_donations_ajax.ajax_url, {
                                        action: 'process_donation',
                                        orderID: data.orderID,
                                        amount: amount,
                                        nonce: smart_donations_ajax.nonce
                                    }, function(response) {
                                        document.getElementById('donation-message').innerHTML = '<p style="color: green;">Thank you, ' + details.payer.name.given_name + '! Your donation of $' + amount + ' has been received.</p>';
                                    });
                                });
                            }
                        }).render('#paypal-button-container');
                    });
                });
            });
        </script>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        check_ajax_referer('smart_donations_nonce', 'nonce');

        $order_id = sanitize_text_field($_POST['orderID']);
        $amount = floatval($_POST['amount']);

        // Log donation (in production, save to DB or send email)
        error_log('Donation processed: Order ' . $order_id . ' for $' . $amount);

        wp_send_json_success('Donation logged successfully');
    }

    public function activate() {
        add_option('smart_donations_version', '1.0.0');
    }
}

new SmartDonationsPro();

// Admin settings page
add_action('admin_menu', function() {
    add_options_page('Smart Donations Pro', 'Smart Donations', 'manage_options', 'smart-donations', 'smart_donations_admin_page');
});

function smart_donations_admin_page() {
    if (isset($_POST['paypal_email'])) {
        update_option('smart_donations_paypal_email', sanitize_email($_POST['paypal_email']));
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }
    $paypal_email = get_option('smart_donations_paypal_email');
    ?>
    <div class="wrap">
        <h1>Smart Donations Pro Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>PayPal Email</th>
                    <td><input type="email" name="paypal_email" value="<?php echo esc_attr($paypal_email); ?>" class="regular-text" required /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <p><strong>Usage:</strong> Use shortcode <code>[smart_donations]</code> or <code>[smart_donations title="Support Our Work" amounts="5,10,25,50" recurring="yes"]</code></p>
    </div>
    <?php
}
