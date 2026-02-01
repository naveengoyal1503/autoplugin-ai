/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Create customizable donation buttons and forms to monetize your WordPress site with PayPal.
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
        add_action('admin_menu', [$this, 'admin_menu']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function init() {
        if (get_option('smart_donation_paypal_email')) {
            // Plugin is set up
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('paypal-sdk', 'https://www.paypal.com/sdk/js?client-id=TEST&currency=USD', [], null, true);
        wp_enqueue_style('smart-donation-style', plugin_dir_url(__FILE__) . 'style.css', []);
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts([
            'amount' => '10',
            'button_text' => 'Donate Now',
            'goal' => '',
            'currency' => 'USD',
            'recurring' => 'no'
        ], $atts);

        $paypal_email = get_option('smart_donation_paypal_email');
        if (!$paypal_email) {
            return '<p>Please set up PayPal email in plugin settings.</p>';
        }

        ob_start();
        ?>
        <div class="smart-donation-container">
            <?php if ($atts['goal']): ?>
                <div class="donation-goal">
                    <div class="goal-bar" style="width: 0%;"></div>
                    <span class="goal-text">$<?php echo $atts['goal']; ?> goal</span>
                </div>
            <?php endif; ?>
            <div id="paypal-button-container-<?php echo uniqid(); ?>"></div>
            <script>
                paypal.Buttons({
                    createOrder: function(data, actions) {
                        return actions.order.create({
                            purchase_units: [{
                                amount: {
                                    value: '<?php echo $atts['amount']; ?>',
                                    currency_code: '<?php echo $atts['currency']; ?>'
                                }
                            }]
                        });
                    },
                    onApprove: function(data, actions) {
                        return actions.order.capture().then(function(details) {
                            alert('Thank you ' + details.payer.name.given_name + '!');
                            // Optional: Send to thank you page
                            window.location.href = '<?php echo home_url('/thank-you/'); ?>';
                        });
                    }
                }).render('#paypal-button-container-<?php echo uniqid(); ?>');
            </script>
            <p class="donation-text"><?php echo esc_html($atts['button_text']); ?> - Supports the site!</p>
        </div>
        <style>
            .smart-donation-container { text-align: center; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9; }
            .donation-goal { margin-bottom: 20px; }
            .goal-bar { height: 20px; background: #007cba; transition: width 0.3s; border-radius: 10px; }
            .goal-text { display: block; margin-top: 5px; font-weight: bold; }
            .donation-text { font-size: 14px; color: #666; margin-top: 10px; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'smart-donation', [$this, 'settings_page']);
    }

    public function settings_page() {
        if (isset($_POST['paypal_email'])) {
            update_option('smart_donation_paypal_email', sanitize_email($_POST['paypal_email']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $email = get_option('smart_donation_paypal_email');
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="paypal_email" value="<?php echo esc_attr($email); ?>" class="regular-text" required /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[smart_donation amount="20" button_text="Buy Me Coffee" goal="500"]</code></p>
        </div>
        <?php
    }

    public function activate() {
        add_option('smart_donation_version', '1.0.0');
    }
}

new SmartDonationPro();

// Prevent direct access
if (!defined('ABSPATH')) exit();