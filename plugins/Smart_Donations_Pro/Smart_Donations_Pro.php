/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donations_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donations Pro
 * Plugin URI: https://example.com/smart-donations-pro
 * Description: Add customizable donation buttons and forms to monetize your WordPress site easily.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartDonationsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        add_action('wp_ajax_process_donation', array($this, 'process_donation'));
        add_action('wp_ajax_nopriv_process_donation', array($this, 'process_donation'));
    }

    public function init() {
        if (get_option('smart_donations_paypal_email')) {
            // Plugin is configured
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('paypal-sdk', 'https://www.paypal.com/sdk/js?client-id=TEST&currency=USD', array(), null, true);
        wp_enqueue_script('smart-donations-js', plugin_dir_url(__FILE__) . 'smart-donations.js', array('jquery', 'paypal-sdk'), '1.0.0', true);
        wp_localize_script('smart-donations-js', 'smartDonations', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('smart_donations_nonce')
        ));
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10',
            'label' => 'Donate Now',
            'goal' => '500',
            'current' => '250',
            'currency' => 'USD'
        ), $atts);

        $paypal_email = get_option('smart_donations_paypal_email', '');
        if (!$paypal_email) {
            return '<p>Please configure PayPal email in settings.</p>';
        }

        ob_start();
        ?>
        <div class="smart-donation-container">
            <h3>Support Us!</h3>
            <div class="donation-goal">
                <div style="background: #e0e0e0; border-radius: 10px; height: 20px;">
                    <div style="background: #4CAF50; height: 20px; border-radius: 10px; width: <?php echo ($atts['current'] / $atts['goal']) * 100; ?>%;"></div>
                </div>
                <p><?php echo $atts['currency']; ?> <?php echo $atts['current']; ?> / <?php echo $atts['goal']; ?> raised</p>
            </div>
            <div id="paypal-button-container-<?php echo uniqid(); ?>"></div>
            <form id="custom-donation-<?php echo uniqid(); ?>" style="display:none;">
                <input type="number" id="custom-amount" placeholder="Custom amount" step="1" min="1">
                <button type="button" class="donate-btn">Donate</button>
            </form>
        </div>
        <script>
            paypal.Buttons({
                createOrder: function(data, actions) {
                    return actions.order.create({
                        purchase_units: [{
                            amount: {
                                value: '<?php echo $atts['amount']; ?>'
                            }
                        }]
                    });
                },
                onApprove: function(data, actions) {
                    return actions.order.capture().then(function(details) {
                        jQuery.post(smartDonations.ajaxurl, {
                            action: 'process_donation',
                            orderID: data.orderID,
                            nonce: smartDonations.nonce
                        });
                        alert('Thank you ' + details.payer.name.given_name + '!');
                    });
                }
            }).render('#paypal-button-container-<?php echo uniqid(); ?>');
        </script>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        check_ajax_referer('smart_donations_nonce', 'nonce');
        $order_id = sanitize_text_field($_POST['orderID']);
        // Log donation
        error_log('Smart Donation: Order ' . $order_id);
        wp_send_json_success('Donation processed');
    }
}

// Admin settings
if (is_admin()) {
    add_action('admin_menu', function() {
        add_options_page('Smart Donations', 'Smart Donations', 'manage_options', 'smart-donations', 'smart_donations_admin_page');
    });

    function smart_donations_admin_page() {
        if (isset($_POST['paypal_email'])) {
            update_option('smart_donations_paypal_email', sanitize_email($_POST['paypal_email']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $paypal_email = get_option('smart_donations_paypal_email', '');
        ?>
        <div class="wrap">
            <h1>Smart Donations Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="paypal_email" value="<?php echo esc_attr($paypal_email); ?>" class="regular-text" required></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Shortcode:</strong> <code>[smart_donation amount="10" goal="500" current="250"]</code></p>
        </div>
        <?php
    }
}

new SmartDonationsPro();

// Add CSS
add_action('wp_head', function() {
    echo '<style>
        .smart-donation-container { text-align: center; padding: 20px; border: 1px solid #ddd; border-radius: 10px; background: #f9f9f9; }
        .donate-btn { background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .donate-btn:hover { background: #005a87; }
    </style>';
});