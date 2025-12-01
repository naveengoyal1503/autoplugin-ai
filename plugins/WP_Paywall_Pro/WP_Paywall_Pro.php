/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Paywall_Pro.php
*/
<?php
/**
 * Plugin Name: WP Paywall Pro
 * Plugin URI: https://example.com/wp-paywall-pro
 * Description: Monetize your content with flexible paywalls and payment options.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPPaywallPro {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('paywall', array($this, 'paywall_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function init() {
        register_post_status('paywalled', array(
            'label' => __('Paywalled', 'wp-paywall-pro'),
            'public' => false,
            'exclude_from_search' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Paywalled (%s)', 'Paywalled (%s)', 'wp-paywall-pro')
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wp-paywall-pro', plugin_dir_url(__FILE__) . 'css/paywall.css');
        wp_enqueue_script('wp-paywall-pro', plugin_dir_url(__FILE__) . 'js/paywall.js', array('jquery'), '1.0.0', true);
    }

    public function paywall_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'price' => 5,
            'currency' => 'USD',
            'access_days' => 7
        ), $atts);

        if (is_user_logged_in() || $this->has_paid_access()) {
            return $content;
        }

        $paywall_html = '<div class="wp-paywall-pro">
            <p>This content is locked. Pay ' . $atts['price'] . ' ' . $atts['currency'] . ' for ' . $atts['access_days'] . ' days of access.</p>
            <button class="wp-paywall-pro-pay">Pay Now</button>
            <div class="wp-paywall-pro-payment-form" style="display:none;">
                <form method="post">
                    <input type="hidden" name="wp_paywall_pro_price" value="' . $atts['price'] . '">
                    <input type="hidden" name="wp_paywall_pro_currency" value="' . $atts['currency'] . '">
                    <input type="hidden" name="wp_paywall_pro_access_days" value="' . $atts['access_days'] . '">
                    <input type="text" name="wp_paywall_pro_email" placeholder="Your Email" required>
                    <button type="submit" name="wp_paywall_pro_submit">Complete Payment</button>
                </form>
            </div>
        </div>';

        if (isset($_POST['wp_paywall_pro_submit'])) {
            $email = sanitize_email($_POST['wp_paywall_pro_email']);
            $price = floatval($_POST['wp_paywall_pro_price']);
            $currency = sanitize_text_field($_POST['wp_paywall_pro_currency']);
            $access_days = intval($_POST['wp_paywall_pro_access_days']);

            // Simulate payment processing (in real plugin, integrate with Stripe/PayPal)
            update_user_meta(get_current_user_id(), 'wp_paywall_pro_paid', time() + ($access_days * DAY_IN_SECONDS));
            wp_redirect(get_permalink());
            exit;
        }

        return $paywall_html;
    }

    public function has_paid_access() {
        $paid_until = get_user_meta(get_current_user_id(), 'wp_paywall_pro_paid', true);
        return $paid_until && $paid_until > time();
    }

    public function admin_menu() {
        add_options_page(
            'WP Paywall Pro Settings',
            'WP Paywall Pro',
            'manage_options',
            'wp-paywall-pro',
            array($this, 'settings_page')
        );
    }

    public function settings_init() {
        register_setting('wp-paywall-pro', 'wp_paywall_pro_settings');
        add_settings_section(
            'wp_paywall_pro_section',
            'Settings',
            null,
            'wp-paywall-pro'
        );
        add_settings_field(
            'wp_paywall_pro_payment_gateway',
            'Payment Gateway',
            array($this, 'payment_gateway_render'),
            'wp-paywall-pro',
            'wp_paywall_pro_section'
        );
    }

    public function payment_gateway_render() {
        $options = get_option('wp_paywall_pro_settings');
        echo '<input type="text" name="wp_paywall_pro_settings[wp_paywall_pro_payment_gateway]" value="' . (isset($options['wp_paywall_pro_payment_gateway']) ? esc_attr($options['wp_paywall_pro_payment_gateway']) : '') . '" placeholder="Stripe/PayPal API Key">';
    }

    public function settings_page() {
        ?>
        <form action='options.php' method='post'>
            <h2>WP Paywall Pro Settings</h2>
            <?php
            settings_fields('wp-paywall-pro');
            do_settings_sections('wp-paywall-pro');
            submit_button();
            ?>
        </form>
        <?php
    }
}

new WPPaywallPro();
