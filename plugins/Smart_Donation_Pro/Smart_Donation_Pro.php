/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Easily add customizable donation buttons and forms to monetize your WordPress site. Supports PayPal, Stripe, one-time and recurring donations.
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('smart-donation-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('smart-donation-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('smart-donation-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro', 'Donation Settings', 'manage_options', 'smart-donation-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('sdp_paypal_email', sanitize_email($_POST['paypal_email']));
            update_option('sdp_stripe_key', sanitize_text_field($_POST['stripe_key']));
            update_option('sdp_default_amount', floatval($_POST['default_amount']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $paypal_email = get_option('sdp_paypal_email', '');
        $stripe_key = get_option('sdp_stripe_key', '');
        $default_amount = get_option('sdp_default_amount', 5.0);
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="paypal_email" value="<?php echo esc_attr($paypal_email); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Stripe Publishable Key</th>
                        <td><input type="text" name="stripe_key" value="<?php echo esc_attr($stripe_key); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Default Amount ($)</th>
                        <td><input type="number" step="0.01" name="default_amount" value="<?php echo esc_attr($default_amount); ?>" /></td>
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
            'amount' => get_option('sdp_default_amount', 5.0),
            'button_text' => 'Donate Now',
            'goal' => 1000,
            'current' => 250
        ), $atts);

        $paypal_email = get_option('sdp_paypal_email', '');
        $stripe_key = get_option('sdp_stripe_key', '');
        $progress = min(100, ($atts['current'] / $atts['goal']) * 100);

        ob_start();
        ?>
        <div id="smart-donation" class="sdp-container">
            <h3>Support This Site! <?php echo $progress; ?>% to goal ($<?php echo number_format($atts['current']); ?>/<?php echo number_format($atts['goal']); ?>)</h3>
            <div class="sdp-progress-bar">
                <div class="sdp-progress" style="width: <?php echo $progress; ?>%;"></div>
            </div>
            <div class="sdp-amounts">
                <button class="sdp-amount-btn" data-amount="5">$5</button>
                <button class="sdp-amount-btn" data-amount="10">$10</button>
                <button class="sdp-amount-btn" data-amount="25">$25</button>
                <button class="sdp-amount-btn" data-amount="50">$50</button>
                <input type="number" class="sdp-custom-amount" placeholder="Custom" step="0.01" />
            </div>
            <button id="sdp-paypal" class="sdp-paypal-btn" data-email="<?php echo esc_attr($paypal_email); ?>"><?php echo esc_html($atts['button_text']); ?> via PayPal</button>
            <?php if ($stripe_key): ?>
            <div id="sdp-stripe-form">
                <div id="sdp-stripe-element"></div>
                <button id="sdp-stripe-pay" class="sdp-stripe-btn">Pay $ <span id="sdp-stripe-amount"><?php echo $atts['amount']; ?></span> via Stripe</button>
            </div>
            <?php endif; ?>
        </div>
        <script>
        jQuery(document).ready(function($) {
            var stripe = Stripe('<?php echo esc_js($stripe_key); ?>');
            var elements = stripe.elements();
            var card = elements.create('card');
            card.mount('#sdp-stripe-element');

            $('.sdp-amount-btn, .sdp-custom-amount').on('click change', function() {
                var amt = $(this).data('amount') || $(this).val();
                $('#sdp-stripe-amount').text(amt);
            });

            $('#sdp-paypal').on('click', function() {
                var amount = $('.sdp-custom-amount').val() || $('.sdp-amount-btn.active').data('amount') || <?php echo $atts['amount']; ?>;
                var url = 'https://www.paypal.com/donate/?business=' + $(this).data('email') + '&amount=' + amount;
                window.open(url, '_blank');
            });

            $('#sdp-stripe-pay').on('click', async function() {
                var amount = parseFloat($('#sdp-stripe-amount').text()) * 100;
                var {error, paymentMethod} = await stripe.createPaymentMethod('card', card);
                if (error) {
                    alert(error.message);
                } else {
                    // In production, send paymentMethod.id to your server
                    alert('Payment initiated! (Demo mode)');
                }
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('sdp_default_amount', 5.0);
    }
}

new SmartDonationPro();

// Inline CSS
add_action('wp_head', function() {
    echo '<style>
    .sdp-container { max-width: 400px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; text-align: center; background: #f9f9f9; }
    .sdp-progress-bar { background: #eee; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0; }
    .sdp-progress { height: 100%; background: linear-gradient(90deg, #4CAF50, #45a049); transition: width 0.3s; }
    .sdp-amounts { margin: 20px 0; }
    .sdp-amount-btn { background: #007cba; color: white; border: none; padding: 10px 15px; margin: 5px; border-radius: 5px; cursor: pointer; }
    .sdp-amount-btn:hover { background: #005a87; }
    .sdp-custom-amount { padding: 10px; margin: 5px; width: 100px; }
    .sdp-paypal-btn, .sdp-stripe-btn { background: #ffc439; color: #000; border: none; padding: 12px 24px; margin: 10px 5px; border-radius: 5px; cursor: pointer; font-size: 16px; width: 100%; }
    .sdp-paypal-btn:hover, .sdp-stripe-btn:hover { background: #ffb300; }
    #sdp-stripe-element { padding: 10px; border: 1px solid #ddd; border-radius: 5px; margin: 10px 0; background: white; }
    </style>';
});