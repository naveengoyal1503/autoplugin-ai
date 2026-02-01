/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Add customizable donation buttons and forms to monetize your WordPress site with Stripe and PayPal.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
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
        wp_enqueue_script('stripe', 'https://js.stripe.com/v3/', array(), '3.0', true);
        wp_enqueue_style('smart-donation', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0');
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro', 'Donation Settings', 'manage_options', 'smart-donation', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('sdp_stripe_key', sanitize_text_field($_POST['stripe_key']));
            update_option('sdp_stripe_secret', sanitize_text_field($_POST['stripe_secret']));
            update_option('sdp_paypal_email', sanitize_email($_POST['paypal_email']));
            update_option('sdp_donation_amount', sanitize_text_field($_POST['donation_amount']));
            echo '<div class="updated"><p>Settings saved!</p></div>';
        }
        $stripe_key = get_option('sdp_stripe_key', '');
        $stripe_secret = get_option('sdp_stripe_secret', '');
        $paypal_email = get_option('sdp_paypal_email', '');
        $donation_amount = get_option('sdp_donation_amount', '10');
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Stripe Publishable Key</th>
                        <td><input type="text" name="stripe_key" value="<?php echo esc_attr($stripe_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Stripe Secret Key</th>
                        <td><input type="password" name="stripe_secret" value="<?php echo esc_attr($stripe_secret); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="paypal_email" value="<?php echo esc_attr($paypal_email); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Default Donation Amount ($)</th>
                        <td><input type="number" name="donation_amount" value="<?php echo esc_attr($donation_amount); ?>" step="0.01" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => get_option('sdp_donation_amount', '10'),
            'button_text' => 'Donate Now',
            'goal' => '500',
            'current' => '250'
        ), $atts);

        $stripe_key = get_option('sdp_stripe_key', '');
        $paypal_email = get_option('sdp_paypal_email', '');

        ob_start();
        ?>
        <div id="smart-donation" class="sdp-container">
            <div class="sdp-progress" style="background: #f0f0f0; height: 20px; border-radius: 10px; overflow: hidden;">
                <div class="sdp-progress-bar" style="background: #007cba; height: 100%; width: <?php echo ($atts['current'] / $atts['goal']) * 100; ?>%; transition: width 0.3s;"></div>
            </div>
            <p><strong>$<?php echo esc_html($atts['current']); ?> / $<?php echo esc_html($atts['goal']); ?> raised</strong></p>
            <div class="sdp-buttons">
                <?php if ($stripe_key): ?>
                <button id="sdp-stripe-btn" class="sdp-btn sdp-stripe" data-amount="<?php echo $atts['amount'] * 100; ?>"><?php echo esc_html($atts['button_text']); ?> (Stripe)</button>
                <?php endif; ?>
                <?php if ($paypal_email): ?>
                <a href="https://www.paypal.com/donate?hosted_button_id=TEST&business=<?php echo urlencode($paypal_email); ?>&amount=<?php echo $atts['amount']; ?>" class="sdp-btn sdp-paypal" target="_blank"><?php echo esc_html($atts['button_text']); ?> (PayPal)</a>
                <?php endif; ?>
            </div>
            <div id="sdp-payment-form" style="display: none;">
                <div id="card-element"></div>
                <button id="sdp-submit-payment">Pay $<?php echo $atts['amount']; ?></button>
                <div id="sdp-payment-message"></div>
            </div>
        </div>
        <script>
        var stripe = Stripe('<?php echo esc_js($stripe_key); ?>');
        var elements = stripe.elements();
        var card = elements.create('card');
        card.mount('#card-element');

        document.getElementById('sdp-stripe-btn').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('sdp-payment-form').style.display = 'block';
        });

        document.getElementById('sdp-submit-payment').addEventListener('click', function() {
            stripe.createToken(card).then(function(result) {
                if (result.error) {
                    document.getElementById('sdp-payment-message').textContent = result.error.message;
                } else {
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'action=sdp_process_payment&token=' + result.token.id + '&amount=<?php echo $atts['amount'] * 100; ?>'
                    }).then(response => response.json()).then(data => {
                        if (data.success) {
                            document.getElementById('sdp-payment-message').textContent = 'Payment successful!';
                        } else {
                            document.getElementById('sdp-payment-message').textContent = data.message;
                        }
                    });
                }
            });
        });
        </script>
        <style>
        .sdp-container { max-width: 400px; margin: 20px auto; text-align: center; padding: 20px; border: 1px solid #ddd; border-radius: 10px; }
        .sdp-btn { background: #007cba; color: white; padding: 10px 20px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .sdp-btn:hover { background: #005a87; }
        #sdp-payment-message { margin-top: 10px; color: green; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('sdp_donation_amount', '10');
    }
}

new SmartDonationPro();

add_action('wp_ajax_sdp_process_payment', function() {
    $stripe_secret = get_option('sdp_stripe_secret');
    if (!$stripe_secret) {
        wp_die(json_encode(array('success' => false, 'message' => 'Stripe not configured')));
    }
    \Stripe\Stripe::setApiKey($stripe_secret);
    try {
        \Stripe\Charge::create(array(
            'amount' => intval($_POST['amount']),
            'currency' => 'usd',
            'source' => $_POST['token'],
            'description' => 'Donation via Smart Donation Pro'
        ));
        wp_die(json_encode(array('success' => true)));
    } catch (Exception $e) {
        wp_die(json_encode(array('success' => false, 'message' => $e->getMessage())));
    }
});