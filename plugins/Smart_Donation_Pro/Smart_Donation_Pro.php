/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Boost donations with smart, customizable buttons and progress bars using PayPal.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-donation-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartDonationPro {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('smart-donation-pro');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('smart-donation-css', plugin_dir_url(__FILE__) . 'smart-donation.css', array(), '1.0.0');
        wp_enqueue_script('smart-donation-js', plugin_dir_url(__FILE__) . 'smart-donation.js', array('jquery'), '1.0.0', true);
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'goal' => '100',
            'current' => '0',
            'button_text' => 'Donate Now',
            'paypal_email' => get_option('smart_donation_paypal_email', ''),
            'currency' => 'USD',
            'amount' => '5'
        ), $atts);

        if (empty($atts['paypal_email'])) {
            return '<p>PayPal email not configured. Please set it in plugin settings.</p>';
        }

        $progress = ($atts['current'] / $atts['goal']) * 100;
        $paypal_url = 'https://www.paypal.com/donate?hosted_button_id=' . $this->get_paypal_button_id() . '&amount=' . $atts['amount'] . '&currency=' . $atts['currency'];

        ob_start();
        ?>
        <div class="smart-donation-widget">
            <div class="donation-progress">
                <div class="progress-bar" style="width: <?php echo esc_attr($progress); ?>%;"></div>
            </div>
            <p class="goal-text">Goal: $<?php echo esc_html($atts['goal']); ?> | Raised: $<?php echo esc_html($atts['current']); ?></p>
            <a href="<?php echo esc_url($paypal_url); ?>" class="donation-button" target="_blank">
                <?php echo esc_html($atts['button_text']); ?>
            </a>
            <p class="pro-upsell">Upgrade to Pro for Stripe, recurring donations & analytics!</p>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_paypal_button_id() {
        return get_option('smart_donation_paypal_button_id', 'YOUR_BUTTON_ID');
    }

    public function admin_menu() {
        add_options_page(
            'Smart Donation Pro Settings',
            'Donation Pro',
            'manage_options',
            'smart-donation-pro',
            array($this, 'settings_page')
        );
    }

    public function admin_init() {
        register_setting('smart_donation_group', 'smart_donation_paypal_email');
        register_setting('smart_donation_group', 'smart_donation_paypal_button_id');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('smart_donation_group'); ?>
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="smart_donation_paypal_email" value="<?php echo esc_attr(get_option('smart_donation_paypal_email')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>PayPal Button ID</th>
                        <td><input type="text" name="smart_donation_paypal_button_id" value="<?php echo esc_attr(get_option('smart_donation_paypal_button_id')); ?>" class="regular-text" /> <p class="description">Create a PayPal donate button and paste the button ID here.</p></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Features:</strong> Stripe support, progress tracking, custom themes. <a href="https://example.com/pro" target="_blank">Upgrade Now ($29/year)</a></p>
        </div>
        <?php
    }

    public function activate() {
        add_option('smart_donation_paypal_email', '');
        add_option('smart_donation_paypal_button_id', '');
    }
}

SmartDonationPro::get_instance();

// Inline CSS
add_action('wp_head', function() {
    echo '<style>
.smart-donation-widget { max-width: 300px; margin: 20px 0; text-align: center; }
.donation-progress { background: #f0f0f0; height: 20px; border-radius: 10px; overflow: hidden; margin-bottom: 10px; }
.progress-bar { height: 100%; background: #28a745; transition: width 0.3s; }
.goal-text { font-size: 14px; margin: 10px 0; }
.donation-button { display: inline-block; background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; }
.donation-button:hover { background: #005a87; }
.pro-upsell { font-size: 12px; color: #666; margin-top: 10px; }
    </style>';
});

// Inline JS
add_action('wp_footer', function() {
    echo '<script>
jQuery(document).ready(function($) {
    $(".donation-button").on("click", function() {
        // Optional: Track clicks with analytics (Pro feature)
        console.log("Donation button clicked");
    });
});
</script>';
});