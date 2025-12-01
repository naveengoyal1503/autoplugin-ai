/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Paywall_Pro.php
*/
<?php
/**
 * Plugin Name: WP Paywall Pro
 * Description: Monetize your content with flexible paywalls, subscriptions, and one-time payments.
 * Version: 1.0
 * Author: WP Paywall Team
 */

define('WP_PAYWALL_PRO_VERSION', '1.0');
define('WP_PAYWALL_PRO_PATH', plugin_dir_path(__FILE__));
define('WP_PAYWALL_PRO_URL', plugin_dir_url(__FILE__));

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'wp_paywall_pro_activate');
register_deactivation_hook(__FILE__, 'wp_paywall_pro_deactivate');

function wp_paywall_pro_activate() {
    // Add default options
    add_option('wp_paywall_pro_enabled', true);
    add_option('wp_paywall_pro_mode', 'subscription'); // subscription, one-time, tiered
}

function wp_paywall_pro_deactivate() {
    // Clean up if needed
}

// Add admin menu
add_action('admin_menu', 'wp_paywall_pro_add_admin_menu');
function wp_paywall_pro_add_admin_menu() {
    add_options_page(
        'WP Paywall Pro Settings',
        'WP Paywall Pro',
        'manage_options',
        'wp-paywall-pro',
        'wp_paywall_pro_settings_page'
    );
}

// Settings page
function wp_paywall_pro_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    if (isset($_POST['wp_paywall_pro_submit'])) {
        update_option('wp_paywall_pro_enabled', isset($_POST['enabled']) ? 1 : 0);
        update_option('wp_paywall_pro_mode', sanitize_text_field($_POST['mode']));
        echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
    }
    $enabled = get_option('wp_paywall_pro_enabled', true);
    $mode = get_option('wp_paywall_pro_mode', 'subscription');
    ?>
    <div class="wrap">
        <h1>WP Paywall Pro Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="enabled">Enable Paywall</label></th>
                    <td><input type="checkbox" id="enabled" name="enabled" <?php checked($enabled, 1); ?> /></td>
                </tr>
                <tr>
                    <th><label for="mode">Paywall Mode</label></th>
                    <td>
                        <select id="mode" name="mode">
                            <option value="subscription" <?php selected($mode, 'subscription'); ?>>Subscription</option>
                            <option value="one-time" <?php selected($mode, 'one-time'); ?>>One-Time Payment</option>
                            <option value="tiered" <?php selected($mode, 'tiered'); ?>>Tiered Access</option>
                        </select>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Settings', 'primary', 'wp_paywall_pro_submit'); ?>
        </form>
    </div>
    <?php
}

// Apply paywall to content
add_filter('the_content', 'wp_paywall_pro_apply_paywall');
function wp_paywall_pro_apply_paywall($content) {
    if (!get_option('wp_paywall_pro_enabled', true)) {
        return $content;
    }

    $mode = get_option('wp_paywall_pro_mode', 'subscription');
    $user_has_access = wp_paywall_pro_check_access($mode);

    if ($user_has_access) {
        return $content;
    }

    $paywall_message = '<div class="wp-paywall-pro-message">
        <p>This content is locked. Please subscribe or make a payment to access.</p>
        <a href="#" class="wp-paywall-pro-pay-btn">Pay Now</a>
    </div>';

    return $paywall_message;
}

// Check if user has access
function wp_paywall_pro_check_access($mode) {
    // For demo, always return false to show paywall
    // In real plugin, check user roles, payments, subscriptions
    return false;
}

// Enqueue frontend styles
add_action('wp_enqueue_scripts', 'wp_paywall_pro_enqueue_styles');
function wp_paywall_pro_enqueue_styles() {
    wp_enqueue_style('wp-paywall-pro-style', WP_PAYWALL_PRO_URL . 'assets/style.css');
}

// Create assets directory and style.css if not exists
if (!file_exists(WP_PAYWALL_PRO_PATH . 'assets')) {
    mkdir(WP_PAYWALL_PRO_PATH . 'assets', 0755, true);
}
if (!file_exists(WP_PAYWALL_PRO_PATH . 'assets/style.css')) {
    file_put_contents(WP_PAYWALL_PRO_PATH . 'assets/style.css', ".wp-paywall-pro-message { padding: 20px; background: #f0f0f0; text-align: center; }
.wp-paywall-pro-pay-btn { display: inline-block; margin-top: 10px; padding: 10px 20px; background: #0073aa; color: white; text-decoration: none; border-radius: 4px; }" );
}

// Add shortcode for custom paywall
add_shortcode('paywall', 'wp_paywall_pro_shortcode');
function wp_paywall_pro_shortcode($atts, $content = null) {
    $mode = get_option('wp_paywall_pro_mode', 'subscription');
    $user_has_access = wp_paywall_pro_check_access($mode);

    if ($user_has_access) {
        return $content;
    }

    return '<div class="wp-paywall-pro-message">
        <p>This content is locked. Please subscribe or make a payment to access.</p>
        <a href="#" class="wp-paywall-pro-pay-btn">Pay Now</a>
    </div>';
}

// Add admin notice for premium features
add_action('admin_notices', 'wp_paywall_pro_premium_notice');
function wp_paywall_pro_premium_notice() {
    if (!current_user_can('manage_options')) {
        return;
    }
    echo '<div class="notice notice-info"><p>Upgrade to WP Paywall Pro Premium for advanced features like Stripe/PayPal integration, analytics, and more.</p></div>';
}
?>