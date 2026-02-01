/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Easily add customizable donation buttons and forms to monetize your WordPress site with PayPal.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-donation-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartDonationPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('smart-donation-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('paypal-sdk', 'https://www.paypal.com/sdk/js?client-id=TEST&currency=USD', array(), null, true);
        wp_enqueue_style('smart-donation-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10',
            'label' => 'Donate Now',
            'currency' => 'USD',
            'goal' => '1000',
            'raised' => '0'
        ), $atts);

        $progress = ($atts['raised'] / $atts['goal']) * 100;
        $paypal_client_id = get_option('sdp_paypal_client_id', 'TEST');

        ob_start();
        ?>
        <div class="smart-donation-container">
            <h3>Support Our Work</h3>
            <?php if ($atts['goal']): ?>
            <div class="donation-goal">
                <div class="progress-bar">
                    <div class="progress" style="width: <?php echo esc_attr($progress); ?>%;"></div>
                </div>
                <p>$<?php echo esc_html($atts['raised']); ?> raised of $<?php echo esc_html($atts['goal']); ?> goal</p>
            </div>
            <?php endif; ?>
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
                        alert('Thank you ' + details.payer.name.given_name + '!');
                        // Optional: Send to server
                    });
                }
            }).render('#paypal-button-container-<?php echo uniqid(); ?>');
            </script>
            <p class="donation-label"><?php echo esc_html($atts['label']); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'smart-donation-pro', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('sdp_settings', 'sdp_paypal_client_id');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('sdp_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>PayPal Client ID</th>
                        <td><input type="text" name="sdp_paypal_client_id" value="<?php echo esc_attr(get_option('sdp_paypal_client_id')); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcode: <code>[smart_donation amount="20" goal="500" raised="250"]</code></p>
        </div>
        <?php
    }

    public function activate() {
        add_option('sdp_paypal_client_id', 'TEST');
    }
}

new SmartDonationPro();

// Inline CSS

function sdp_inline_css() {
    echo '<style>
.smart-donation-container { max-width: 400px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; text-align: center; background: #f9f9f9; }
.progress-bar { background: #eee; height: 20px; border-radius: 10px; overflow: hidden; margin-bottom: 10px; }
.progress { background: #28a745; height: 100%; transition: width 0.3s; }
.donation-label { font-size: 18px; margin-top: 15px; font-weight: bold; }
.donation-goal { margin-bottom: 20px; }
    </style>';
}
add_action('wp_head', 'sdp_inline_css');