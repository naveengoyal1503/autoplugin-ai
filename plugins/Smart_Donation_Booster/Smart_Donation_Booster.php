/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Booster.php
*/
<?php
/**
 * Plugin Name: Smart Donation Booster
 * Plugin URI: https://example.com/smart-donation-booster
 * Description: Boost your WordPress site revenue with smart, customizable donation prompts that appear at optimal times to maximize contributions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartDonationBooster {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_sdb_donate', array($this, 'handle_donation'));
        add_action('wp_ajax_nopriv_sdb_donate', array($this, 'handle_donation'));
        add_shortcode('sdb_donation_button', array($this, 'donation_button_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (!get_option('sdb_enabled')) {
            update_option('sdb_enabled', '1');
            update_option('sdb_amount', '5');
            update_option('sdb_message', 'Support this site with a donation!');
            update_option('sdb_trigger_scroll', '50');
        }
    }

    public function enqueue_scripts() {
        if (!is_admin() && get_option('sdb_enabled')) {
            wp_enqueue_script('sdb-script', plugin_dir_url(__FILE__) . 'sdb-script.js', array('jquery'), '1.0.0', true);
            wp_enqueue_style('sdb-style', plugin_dir_url(__FILE__) . 'sdb-style.css', array(), '1.0.0');
            wp_localize_script('sdb-script', 'sdb_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sdb_nonce'),
                'amount' => get_option('sdb_amount'),
                'message' => get_option('sdb_message'),
                'paypal' => get_option('sdb_paypal_email'),
                'trigger_scroll' => get_option('sdb_trigger_scroll', 50)
            ));
        }
    }

    public function donation_button_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => get_option('sdb_amount'),
            'label' => 'Donate Now'
        ), $atts);
        ob_start();
        ?>
        <div id="sdb-donation-modal" style="display:none;">
            <div class="sdb-overlay"></div>
            <div class="sdb-modal-content">
                <span class="sdb-close">&times;</span>
                <h3><?php echo esc_html(get_option('sdb_message', 'Support us!')); ?></h3>
                <p>Amount: $<span id="sdb-amount"><?php echo esc_html($atts['amount']); ?></span></p>
                <input type="number" id="sdb-custom-amount" min="1" step="0.01" placeholder="Custom amount">
                <div id="sdb-paypal-button"></div>
                <p>Thank you for your support!</p>
            </div>
        </div>
        <button class="sdb-button" onclick="sdbShowModal()" style="background:#007cba;color:white;padding:10px 20px;border:none;cursor:pointer;"><?php echo esc_html($atts['label']); ?></button>
        <script src="https://www.paypal.com/sdk/js?client-id=TEST&currency=USD"></script>
        <script>
        paypal.Buttons({
            createOrder: function(data, actions) {
                return actions.order.create({
                    purchase_units: [{
                        amount: {
                            value: document.getElementById('sdb-custom-amount').value || <?php echo esc_html($atts['amount']); ?>
                        }
                    }]
                });
            },
            onApprove: function(data, actions) {
                return actions.order.capture().then(function(details) {
                    alert('Transaction completed by ' + details.payer.name.given_name);
                    // Log donation here
                });
            }
        }).render('#sdb-paypal-button');
        </script>
        <?php
        return ob_get_clean();
    }

    public function handle_donation() {
        check_ajax_referer('sdb_nonce', 'nonce');
        $amount = sanitize_text_field($_POST['amount']);
        $email = sanitize_email(get_option('sdb_paypal_email'));
        // In pro version: Integrate with PayPal/Stripe API, log donation
        wp_die(json_encode(array('success' => true, 'message' => 'Donation processed!')));
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

// Admin settings page
add_action('admin_menu', function() {
    add_options_page('Smart Donation Booster', 'Donation Booster', 'manage_options', 'sdb-settings', function() {
        if (isset($_POST['sdb_submit'])) {
            update_option('sdb_enabled', sanitize_text_field($_POST['sdb_enabled']));
            update_option('sdb_amount', sanitize_text_field($_POST['sdb_amount']));
            update_option('sdb_message', sanitize_textarea_field($_POST['sdb_message']));
            update_option('sdb_paypal_email', sanitize_email($_POST['sdb_paypal_email']));
            update_option('sdb_trigger_scroll', intval($_POST['sdb_trigger_scroll']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Donation Booster Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Enable Plugin</th>
                        <td><input type="checkbox" name="sdb_enabled" value="1" <?php checked(get_option('sdb_enabled')); ?>></td>
                    </tr>
                    <tr>
                        <th>Default Amount ($)</th>
                        <td><input type="number" name="sdb_amount" value="<?php echo esc_attr(get_option('sdb_amount', '5')); ?>" step="0.01"></td>
                    </tr>
                    <tr>
                        <th>Donation Message</th>
                        <td><textarea name="sdb_message"><?php echo esc_textarea(get_option('sdb_message', 'Support this site!')); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="sdb_paypal_email" value="<?php echo esc_attr(get_option('sdb_paypal_email')); ?>"></td>
                    </tr>
                    <tr>
                        <th>Trigger on Scroll (%)</th>
                        <td><input type="number" name="sdb_trigger_scroll" value="<?php echo esc_attr(get_option('sdb_trigger_scroll', 50)); ?>" min="0" max="100"> % of page</td>
                    </tr>
                </table>
                <p><strong>Upgrade to Pro for recurring donations, analytics, A/B testing, and more! <a href="#pro">Get Pro</a></strong></p>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    });
});

SmartDonationBooster::get_instance();

// Inline JS and CSS for simplicity (self-contained)
function sdb_inline_assets() {
    if (get_option('sdb_enabled')) {
        ?>
        <style>
        #sdb-donation-modal { position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; }
        .sdb-overlay { position: absolute; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .sdb-modal-content { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 8px; max-width: 400px; }
        .sdb-close { float: right; font-size: 28px; cursor: pointer; }
        .sdb-button { position: fixed; bottom: 20px; right: 20px; z-index: 9998; background: #28a745; color: white; padding: 15px 25px; border: none; border-radius: 50px; cursor: pointer; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .sdb-button:hover { background: #218838; }
        </style>
        <script>jQuery(document).ready(function($) { var shown = false; $(window).scroll(function() { if (!shown && $(window).scrollTop() > $(document).height() * (<?php echo get_option('sdb_trigger_scroll',50)/100; ?>) ) { $('.sdb-button').fadeIn(); shown=true; } }); function sdbShowModal() { $('#sdb-donation-modal').fadeIn(); } $('.sdb-close, .sdb-overlay').click(function(){ $('#sdb-donation-modal').fadeOut(); }); }); </script>
        <?php
    }
}
add_action('wp_footer', 'sdb_inline_assets');