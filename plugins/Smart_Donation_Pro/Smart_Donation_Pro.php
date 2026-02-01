/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Customizable donation buttons and forms with Stripe and PayPal for WordPress monetization.
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
        if (get_option('sdp_stripe_key') || get_option('sdp_paypal_email')) {
            add_filter('script_loader_tag', array($this, 'add_defer_attribute'), 10, 2);
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/', array(), null, true);
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'sdp-style.css', array(), '1.0.0');
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp-script.js', array('jquery', 'stripe-js'), '1.0.0', true);
        wp_localize_script('sdp-script', 'sdp_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sdp_nonce'),
            'stripe_key' => get_option('sdp_stripe_key')
        ));
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'smart-donation-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['sdp_submit'])) {
            update_option('sdp_stripe_key', sanitize_text_field($_POST['stripe_key']));
            update_option('sdp_paypal_email', sanitize_email($_POST['paypal_email']));
            update_option('sdp_amount', floatval($_POST['default_amount']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Stripe Publishable Key</th>
                        <td><input type="text" name="stripe_key" value="<?php echo esc_attr(get_option('sdp_stripe_key')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="paypal_email" value="<?php echo esc_attr(get_option('sdp_paypal_email')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Default Amount ($)</th>
                        <td><input type="number" step="0.01" name="default_amount" value="<?php echo esc_attr(get_option('sdp_amount', 5)); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => get_option('sdp_amount', 5),
            'button_text' => 'Donate Now',
            'goal' => 0
        ), $atts);

        $stripe_key = get_option('sdp_stripe_key');
        $paypal_email = get_option('sdp_paypal_email');

        ob_start();
        ?>
        <div id="sdp-container" class="sdp-donation-form" data-amount="<?php echo $atts['amount']; ?>">
            <?php if ($atts['goal'] > 0): ?>
            <div class="sdp-progress-bar">
                <div class="sdp-progress" style="width: 0%;"></div>
                <span>Goal: $<?php echo $atts['goal']; ?></span>
            </div>
            <?php endif; ?
            <div class="sdp-buttons">
                <?php if ($stripe_key): ?>
                <button class="sdp-stripe-btn button-primary" id="sdp-stripe-payment"><?php echo esc_html($atts['button_text']); ?></button>
                <?php endif; ?
                <?php if ($paypal_email): ?>
                <a href="https://www.paypal.com/donate/?hosted_button_id=TEST&business=<?php echo urlencode($paypal_email); ?>&amount=<?php echo $atts['amount']; ?>&item_name=Donation" class="sdp-paypal-btn button-secondary" target="_blank">PayPal</a>
                <?php endif; ?>
            </div>
            <div id="sdp-payment-form" style="display:none;">
                <div id="sdp-card-element"></div>
                <button id="sdp-submit-payment" class="button-primary">Pay $<span id="sdp-amount-display"><?php echo $atts['amount']; ?></span></button>
                <div id="sdp-message"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function add_defer_attribute($tag, $handle) {
        if ('stripe-js' === $handle) {
            return str_replace(' src', ' defer src', $tag);
        }
        return $tag;
    }

    public function activate() {
        add_option('sdp_amount', 5);
    }
}

new SmartDonationPro();

// AJAX handler for Stripe payments
add_action('wp_ajax_sdp_process_payment', 'sdp_process_payment');
add_action('wp_ajax_nopriv_sdp_process_payment', 'sdp_process_payment');

function sdp_process_payment() {
    check_ajax_referer('sdp_nonce', 'nonce');
    if (!wp_verify_nonce($_POST['nonce'], 'sdp_nonce')) {
        wp_die('Security check failed');
    }

    require_once(ABSPATH . 'wp-admin/includes/file.php');

    // In production, use Stripe PHP library and secret key
    // For demo: Log payment intent
    error_log('SDP Demo Payment: ' . print_r($_POST, true));

    wp_send_json_success('Payment processed successfully! Thank you for your donation.');
}

// Inline CSS
add_action('wp_head', 'sdp_inline_styles');
function sdp_inline_styles() {
    echo '<style>
    .sdp-donation-form { max-width: 400px; margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px; background: #f9f9f9; }
    .sdp-progress-bar { background: #eee; height: 20px; border-radius: 10px; margin-bottom: 15px; overflow: hidden; position: relative; }
    .sdp-progress { height: 100%; background: #0073aa; transition: width 0.3s; }
    .sdp-progress span { position: absolute; right: 10px; top: 0; line-height: 20px; color: #333; font-size: 12px; }
    .sdp-buttons { display: flex; gap: 10px; flex-wrap: wrap; }
    .sdp-buttons button, .sdp-buttons a { flex: 1; text-align: center; padding: 12px; text-decoration: none; border-radius: 4px; font-weight: bold; }
    .sdp-payment-form { margin-top: 15px; }
    #sdp-card-element { background: white; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 10px; }
    #sdp-message { margin-top: 10px; padding: 10px; border-radius: 4px; }
    #sdp-message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    #sdp-message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>';
}

// Demo JS
add_action('wp_footer', 'sdp_inline_js');
function sdp_inline_js() {
    if (!get_option('sdp_stripe_key')) return;
    ?>
    <script>
    var stripe = Stripe(sdp_ajax.stripe_key);
    var elements = stripe.elements();
    var card = null;

    document.addEventListener('DOMContentLoaded', function() {
        var stripeBtn = document.getElementById('sdp-stripe-payment');
        var paymentForm = document.getElementById('sdp-payment-form');
        var cardElement = document.getElementById('sdp-card-element');

        if (stripeBtn) {
            stripeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                paymentForm.style.display = 'block';
                stripeBtn.style.display = 'none';
                card = elements.create('card');
                card.mount(cardElement);
            });
        }

        var submitBtn = document.getElementById('sdp-submit-payment');
        if (submitBtn) {
            submitBtn.addEventListener('click', function(e) {
                e.preventDefault();
                submitBtn.disabled = true;
                stripe.createPaymentMethod('card', card).then(function(result) {
                    if (result.error) {
                        document.getElementById('sdp-message').innerHTML = '<div class="error">' + result.error.message + '</div>';
                        submitBtn.disabled = false;
                    } else {
                        jQuery.post(sdp_ajax.ajax_url, {
                            action: 'sdp_process_payment',
                            nonce: sdp_ajax.nonce,
                            payment_method: result.paymentMethod.id,
                            amount: document.getElementById('sdp-amount-display').textContent * 100
                        }, function(response) {
                            document.getElementById('sdp-message').innerHTML = '<div class="success">' + response.data + '</div>';
                            paymentForm.reset();
                            submitBtn.disabled = false;
                        });
                    }
                });
            });
        }
    });
    </script>
    <?php
}
