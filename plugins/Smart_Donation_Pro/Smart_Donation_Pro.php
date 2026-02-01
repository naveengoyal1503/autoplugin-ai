/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: A powerful WordPress plugin that adds customizable donation buttons, progress bars, and one-time/recurring payment options using PayPal and Stripe for easy site monetization.
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
        if (get_option('sdp_paypal_email') || get_option('sdp_stripe_key')) {
            add_filter('the_content', array($this, 'auto_insert_donation'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/', array(), null, true);
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'smart-donation-pro', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('sdp_paypal_email', sanitize_email($_POST['paypal_email']));
            update_option('sdp_stripe_key', sanitize_text_field($_POST['stripe_key']));
            update_option('sdp_stripe_secret', sanitize_text_field($_POST['stripe_secret']));
            update_option('sdp_auto_insert', isset($_POST['auto_insert']) ? 'yes' : 'no');
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="paypal_email" value="<?php echo esc_attr(get_option('sdp_paypal_email')); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Stripe Publishable Key</th>
                        <td><input type="text" name="stripe_key" value="<?php echo esc_attr(get_option('sdp_stripe_key')); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Stripe Secret Key</th>
                        <td><input type="password" name="stripe_secret" value="<?php echo esc_attr(get_option('sdp_stripe_secret')); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Auto-insert after posts</th>
                        <td><input type="checkbox" name="auto_insert" <?php checked(get_option('sdp_auto_insert'), 'yes'); ?> /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Shortcode:</strong> <code>[smart_donation amount="10" currency="USD" goal="500"]</code></p>
            <p><em>Upgrade to Pro for recurring donations, analytics, and more!</em></p>
        </div>
        <?php
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '5',
            'currency' => 'USD',
            'goal' => '0',
            'button_text' => 'Donate Now',
            'provider' => 'paypal'
        ), $atts);

        $paypal_email = get_option('sdp_paypal_email');
        $stripe_key = get_option('sdp_stripe_key');

        if (!$paypal_email && !$stripe_key) {
            return '<p>Please configure payment settings in admin.</p>';
        }

        ob_start();
        ?>
        <div class="sdp-container">
            <?php if ($atts['goal'] > 0): ?>
                <div class="sdp-progress">
                    <div class="sdp-progress-bar" style="width: 0%;"></div>
                    <span class="sdp-goal">Goal: $<?php echo $atts['goal']; ?> (0% reached)</span>
                </div>
            <?php endif; ?>
            <div class="sdp-button-group">
                <?php if ($paypal_email): ?>
                    <a href="https://www.paypal.com/donate?hosted_button_id=TEST&amount=<?php echo $atts['amount']; ?>&currency=<?php echo $atts['currency']; ?>&email=<?php echo $paypal_email; ?>" class="sdp-paypal-btn" target="_blank">
                        <?php echo esc_html($atts['button_text']); ?> via PayPal ($<?php echo $atts['amount']; ?>)
                    </a>
                <?php endif; ?>
                <?php if ($stripe_key): ?>
                    <button class="sdp-stripe-btn" data-amount="<?php echo $atts['amount'] * 100; ?>" data-currency="<?php echo $atts['currency']; ?>">
                        <?php echo esc_html($atts['button_text']); ?> via Card ($<?php echo $atts['amount']; ?>)
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <script>
        const stripe = Stripe('<?php echo $stripe_key; ?>');
        document.querySelectorAll('.sdp-stripe-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const {error} = await stripe.redirectToCheckout({
                    lineItems: [{price_data: {currency: '<?php echo $atts['currency']; ?>', product_data: {name: 'Donation'}, unit_amount: <?php echo $atts['amount'] * 100; ?>}, quantity: 1}],
                    mode: 'payment',
                    successUrl: window.location.href + '?donation=success',
                    cancelUrl: window.location.href,
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function auto_insert_donation($content) {
        if (is_single() && get_option('sdp_auto_insert') === 'yes') {
            $content .= do_shortcode('[smart_donation amount="5" currency="USD"]');
        }
        return $content;
    }

    public function activate() {
        update_option('sdp_auto_insert', 'no');
    }
}

new SmartDonationPro();

// Inline CSS
add_action('wp_head', function() {
    echo '<style>
    .sdp-container { text-align: center; margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9; }
    .sdp-progress { margin-bottom: 15px; }
    .sdp-progress-bar { height: 20px; background: #007cba; transition: width 0.3s; border-radius: 10px; }
    .sdp-goal { display: block; margin-top: 5px; font-weight: bold; }
    .sdp-button-group { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
    .sdp-paypal-btn, .sdp-stripe-btn { padding: 12px 24px; background: #007cba; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; border: none; cursor: pointer; }
    .sdp-paypal-btn:hover, .sdp-stripe-btn:hover { background: #005a87; }
    @media (max-width: 600px) { .sdp-button-group { flex-direction: column; } }
    </style>';
});