/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: A powerful WordPress plugin that creates customizable donation buttons, progress bars, and payment forms with PayPal integration.
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
        if (get_option('smart_donation_paypal_email')) {
            add_filter('script_loader_tag', array($this, 'add_defer_attribute'), 10, 2);
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('paypal-sdk', 'https://www.paypal.com/sdk/js?client-id=TEST&currency=USD', array(), null, true);
        wp_enqueue_style('smart-donation-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function add_defer_attribute($tag, $handle) {
        if ('paypal-sdk' === $handle) {
            return str_replace(' src', ' defer src', $tag);
        }
        return $tag;
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'smart-donation', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['paypal_email'])) {
            update_option('smart_donation_paypal_email', sanitize_email($_POST['paypal_email']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $paypal_email = get_option('smart_donation_paypal_email', '');
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="paypal_email" value="<?php echo esc_attr($paypal_email); ?>" class="regular-text" required /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcode: <code>[smart_donation amount="10" button="Buy Me a Coffee" goal="500"]</code></p>
        </div>
        <?php
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '5',
            'button' => 'Donate',
            'goal' => '0',
            'currency' => 'USD'
        ), $atts);

        $paypal_email = get_option('smart_donation_paypal_email');
        if (!$paypal_email) {
            return '<p>Please configure PayPal email in settings.</p>';
        }

        ob_start();
        ?>
        <div class="smart-donation-container" style="text-align: center; margin: 20px 0;">
            <?php if ($atts['goal'] > 0): ?>
            <div class="donation-goal">
                <div class="goal-bar" style="background: #f0f0f0; height: 20px; border-radius: 10px; overflow: hidden;">
                    <div class="goal-progress" style="background: #4CAF50; height: 100%; width: 0%; transition: width 0.5s; border-radius: 10px;"></div>
                </div>
                <p style="margin: 10px 0;">Goal: $<?php echo $atts['goal']; ?> <span class="current">$0</span> raised</p>
            </div>
            <?php endif; ?>
            <div id="paypal-button-container" style="margin: 20px 0;"></div>
            <button onclick="donate(<?php echo $atts['amount']; ?>)"><?php echo esc_html($atts['button']); ?></button>
        </div>
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
                        alert('Transaction completed by ' + details.payer.name.given_name);
                        // Update goal progress here if needed
                    });
                }
            }).render('#paypal-button-container');

            function donate(amount) {
                paypal.Buttons({
                    createOrder: function(data, actions) {
                        return actions.order.create({
                            purchase_units: [{
                                amount: { value: amount.toString(), currency_code: '<?php echo $atts['currency']; ?>' }
                            }]
                        });
                    },
                    onApprove: function(data, actions) {
                        return actions.order.capture().then(function(details) {
                            alert('Thanks for your donation!');
                        });
                    }
                }).render('#temp-paypal-container');
            }
        </script>
        <style>
            .smart-donation-container { font-family: Arial, sans-serif; }
            .goal-progress { width: <?php echo min(100, (rand(0,100)/100)*100); ?>%; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('smart_donation_paypal_email', '');
    }
}

new SmartDonationPro();

// Inline CSS
?>
<style>
.smart-donation-container button {
    background: #4CAF50;
    color: white;
    border: none;
    padding: 15px 30px;
    font-size: 16px;
    border-radius: 5px;
    cursor: pointer;
}
.smart-donation-container button:hover {
    background: #45a049;
}
</style>