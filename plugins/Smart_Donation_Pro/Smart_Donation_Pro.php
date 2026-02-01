/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Create customizable donation buttons and forms with PayPal integration for easy monetization.
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
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sdp_paypal_email')) {
            add_filter('script_loader_tag', array($this, 'add_defer_attribute'), 10, 2);
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('paypal-sdk', 'https://www.paypal.com/sdk/js?client-id=TEST&currency=USD', array(), null, true);
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function add_defer_attribute($tag, $handle) {
        if ('paypal-sdk' === $handle) {
            return str_replace(' src', ' defer src', $tag);
        }
        return $tag;
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
            <p>Use shortcode: <code>[smart_donation amount="10" label="Buy Me a Coffee"]</code></p>
        </div>
        <?php
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '5',
            'label' => 'Donate',
            'button_text' => 'Donate Now',
            'currency' => 'USD'
        ), $atts);

        $paypal_email = get_option('sdp_paypal_email');
        if (!$paypal_email) {
            return '<p>Please configure PayPal email in settings.</p>';
        }

        ob_start();
        ?>
        <div class="sdp-container" style="text-align: center; padding: 20px; border: 1px solid #ddd; border-radius: 10px; max-width: 300px; margin: 20px auto;">
            <h3><?php echo esc_html($atts['label']); ?></h3>
            <div id="paypal-button-container-<?php echo uniqid(); ?>"></div>
            <script>
                paypal.Buttons({
                    createOrder: function(data, actions) {
                        return actions.order.create({
                            purchase_units: [{
                                amount: {
                                    value: '<?php echo esc_attr($atts['amount']); ?>',
                                    currency_code: '<?php echo esc_attr($atts['currency']); ?>'
                                }
                            }]
                        });
                    },
                    onApprove: function(data, actions) {
                        return actions.order.capture().then(function(details) {
                            alert('Thank you for your donation, ' + details.payer.name.given_name + '!');
                        });
                    }
                }).render('#paypal-button-container-<?php echo uniqid(); ?>');
            </script>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('sdp_paypal_email', '');
    }
}

new SmartDonationPro();

// Inline style for simplicity (self-contained)
function sdp_add_inline_style() {
    echo '<style>
        .sdp-container { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); font-family: Arial, sans-serif; }
        .sdp-container h3 { color: #333; margin-bottom: 15px; }
    </style>';
}
add_action('wp_head', 'sdp_add_inline_style');