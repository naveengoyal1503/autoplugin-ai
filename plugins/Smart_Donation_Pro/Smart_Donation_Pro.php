/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Add customizable donation buttons, progress bars, and goal tracking to encourage donations with PayPal integration.
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
        add_shortcode('smart_donation_goal', array($this, 'goal_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sdp_paypal_email')) {
            add_filter('script_loader_tag', array($this, 'defer_paypal_script'), 10, 3);
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('paypal-sdk', 'https://www.paypal.com/sdk/js?client-id=TEST&currency=USD', array(), null, true);
        wp_enqueue_style('sdp-styles', plugin_dir_url(__FILE__) . 'sdp-styles.css', array(), '1.0.0');
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro', 'Donation Settings', 'manage_options', 'smart-donation-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['sdp_paypal_email'])) {
            update_option('sdp_paypal_email', sanitize_email($_POST['sdp_paypal_email']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $paypal_email = get_option('sdp_paypal_email', '');
        echo '<div class="wrap"><h1>Smart Donation Pro Settings</h1><form method="post"><table class="form-table"><tr><th>PayPal Email</th><td><input type="email" name="sdp_paypal_email" value="' . esc_attr($paypal_email) . '" class="regular-text" required /></td></tr></table><p class="submit"><input type="submit" class="button-primary" value="Save Settings" /></p></form><p>Use shortcodes: <code>[smart_donation]</code> or <code>[smart_donation_goal goal="500" raised="120"]</code></p></div>';
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array('amount' => '10', 'label' => 'Donate Now'), $atts);
        $paypal_email = get_option('sdp_paypal_email');
        if (!$paypal_email) return '<p>Please configure PayPal email in settings.</p>';
        ob_start();
        ?>
        <div class="sdp-donation">
            <button class="sdp-donate-btn" data-amount="<?php echo esc_attr($atts['amount']); ?>"><?php echo esc_html($atts['label']); ?></button>
            <div id="sdp-paypal-button"></div>
        </div>
        <script>
        paypal.Buttons({
            createOrder: function(data, actions) {
                return actions.order.create({
                    purchase_units: [{
                        amount: { value: '<?php echo esc_js($atts['amount']); ?>' }
                    }]
                });
            },
            onApprove: function(data, actions) {
                return actions.order.capture().then(function(details) {
                    alert('Donation completed by ' + details.payer.name.given_name);
                    jQuery.post(sdp_ajax.ajax_url, {action: 'sdp_log_donation', amount: '<?php echo esc_js($atts['amount']); ?>', nonce: sdp_ajax.nonce});
                });
            }
        }).render('#sdp-paypal-button');
        </script>
        <?php
        return ob_get_clean();
    }

    public function goal_shortcode($atts) {
        $atts = shortcode_atts(array('goal' => '1000', 'raised' => '0', 'label' => 'Help us reach our goal!'), $atts);
        $percent = ($atts['raised'] / $atts['goal']) * 100;
        ob_start();
        ?>
        <div class="sdp-goal">
            <h3><?php echo esc_html($atts['label']); ?></h3>
            <div class="sdp-progress-bar"><div class="sdp-progress-fill" style="width: <?php echo $percent; ?>%;"></div></div>
            <p>$<?php echo number_format($atts['raised']); ?> / $<?php echo number_format($atts['goal']); ?> (<?php echo round($percent); ?>%)</p>
            [smart_donation]
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('sdp_paypal_email', '');
    }

    public function defer_paypal_script($tag, $handle, $src) {
        if ('paypal-sdk' === $handle) {
            return str_replace(' src', ' defer src', $tag);
        }
        return $tag;
    }
}

new SmartDonationPro();

add_action('wp_ajax_sdp_log_donation', function() {
    check_ajax_referer('sdp_nonce', 'nonce');
    // Log donation (pro feature placeholder)
    wp_die();
});

/* CSS */
function sdp_add_styles() {
    echo '<style>
    .sdp-donation { text-align: center; margin: 20px 0; }
    .sdp-donate-btn { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
    .sdp-donate-btn:hover { background: #005a87; }
    .sdp-goal { background: #f9f9f9; padding: 20px; border-radius: 10px; margin: 20px 0; }
    .sdp-progress-bar { background: #ddd; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0; }
    .sdp-progress-fill { background: #28a745; height: 100%; transition: width 0.3s; }
    #sdp-paypal-button { margin-top: 10px; }
    </style>';
}
add_action('wp_head', 'sdp_add_styles');

/* JS Placeholder */
function sdp_add_script() {
    echo '<script>jQuery(document).ready(function($) { /* Pro analytics here */ });</script>';
}
add_action('wp_footer', 'sdp_add_script');