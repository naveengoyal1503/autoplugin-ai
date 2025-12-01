/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Paywall_Pro.php
*/
<?php
/**
 * Plugin Name: WP Paywall Pro
 * Description: Monetize your content with paywalls, subscriptions, and affiliate links.
 * Version: 1.0.0
 * Author: WP Paywall Team
 */

if (!defined('ABSPATH')) exit;

class WPPaywallPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('paywall', array($this, 'paywall_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
    }

    public function init() {
        if (!get_option('wppaywall_settings')) {
            add_option('wppaywall_settings', array(
                'mode' => 'subscription',
                'price' => 9.99,
                'currency' => 'USD',
                'affiliate_id' => '',
            ));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wppaywall-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_enqueue_script('wppaywall-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('wppaywall-script', 'wppaywall', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wppaywall_nonce')
        ));
    }

    public function paywall_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'type' => 'subscription',
            'price' => get_option('wppaywall_settings')['price'],
            'currency' => get_option('wppaywall_settings')['currency'],
        ), $atts);

        if (is_user_logged_in() || $this->has_paid($atts['type'])) {
            return $content;
        }

        $output = '<div class="wppaywall-container">
            <p>This content is locked. Pay ' . $atts['price'] . ' ' . $atts['currency'] . ' to unlock.</p>
            <button class="wppaywall-pay-btn" data-type="' . $atts['type'] . '" data-price="' . $atts['price'] . '" data-currency="' . $atts['currency'] . '">Pay Now</button>
        </div>';

        return $output;
    }

    public function has_paid($type) {
        // Simulate payment check
        return false; // Replace with real payment logic
    }

    public function admin_menu() {
        add_options_page(
            'WP Paywall Pro Settings',
            'Paywall Pro',
            'manage_options',
            'wppaywall-pro',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['wppaywall_save'])) {
            update_option('wppaywall_settings', array(
                'mode' => sanitize_text_field($_POST['mode']),
                'price' => floatval($_POST['price']),
                'currency' => sanitize_text_field($_POST['currency']),
                'affiliate_id' => sanitize_text_field($_POST['affiliate_id']),
            ));
            echo '<div class="updated"><p>Settings saved.</p></div>';
        }

        $settings = get_option('wppaywall_settings');
        ?>
        <div class="wrap">
            <h1>WP Paywall Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Mode</th>
                        <td>
                            <select name="mode">
                                <option value="subscription" <?php selected($settings['mode'], 'subscription'); ?>>Subscription</option>
                                <option value="one-time" <?php selected($settings['mode'], 'one-time'); ?>>One-Time</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Price</th>
                        <td><input type="number" name="price" value="<?php echo esc_attr($settings['price']); ?>" step="0.01" /></td>
                    </tr>
                    <tr>
                        <th>Currency</th>
                        <td><input type="text" name="currency" value="<?php echo esc_attr($settings['currency']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate ID</th>
                        <td><input type="text" name="affiliate_id" value="<?php echo esc_attr($settings['affiliate_id']); ?>" /></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="wppaywall_save" class="button-primary" value="Save Settings" />
                </p>
            </form>
        </div>
        <?php
    }
}

new WPPaywallPro();

// style.css
// .wppaywall-container { padding: 20px; background: #f9f9f9; border: 1px solid #ddd; }
// .wppaywall-pay-btn { background: #0073aa; color: white; border: none; padding: 10px 20px; cursor: pointer; }

// script.js
// jQuery(document).ready(function($) {
//     $('.wppaywall-pay-btn').on('click', function() {
//         alert('Payment processing...');
//     });
// });
