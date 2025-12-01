/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Paywall_Pro.php
*/
<?php
/**
 * Plugin Name: WP Paywall Pro
 * Plugin URI: https://example.com/wp-paywall-pro
 * Description: Monetize your WordPress content with paywalls, subscriptions, and affiliate tracking.
 * Version: 1.0
 * Author: Cozmo Labs
 * Author URI: https://cozmoslabs.com
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
    add_option('wp_paywall_pro_price', 5);
    add_option('wp_paywall_pro_currency', 'USD');
}

function wp_paywall_pro_deactivate() {
    // Cleanup if needed
}

// Add settings page
add_action('admin_menu', 'wp_paywall_pro_add_admin_menu');
function wp_paywall_pro_add_admin_menu() {
    add_options_page(
        'WP Paywall Pro Settings',
        'Paywall Pro',
        'manage_options',
        'wp-paywall-pro',
        'wp_paywall_pro_settings_page'
    );
}

function wp_paywall_pro_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
    ?>
    <div class="wrap">
        <h1>WP Paywall Pro Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('wp_paywall_pro_options'); ?>
            <?php do_settings_sections('wp_paywall_pro_options'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Enable Paywall</th>
                    <td><input type="checkbox" name="wp_paywall_pro_enabled" value="1" <?php checked(1, get_option('wp_paywall_pro_enabled')); ?> /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Price per access ($)</th>
                    <td><input type="number" step="0.01" name="wp_paywall_pro_price" value="<?php echo esc_attr(get_option('wp_paywall_pro_price')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Currency</th>
                    <td><input type="text" name="wp_paywall_pro_currency" value="<?php echo esc_attr(get_option('wp_paywall_pro_currency')); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', 'wp_paywall_pro_settings_init');
function wp_paywall_pro_settings_init() {
    register_setting('wp_paywall_pro_options', 'wp_paywall_pro_enabled');
    register_setting('wp_paywall_pro_options', 'wp_paywall_pro_price');
    register_setting('wp_paywall_pro_options', 'wp_paywall_pro_currency');
}

// Apply paywall to posts
add_filter('the_content', 'wp_paywall_pro_apply_paywall');
function wp_paywall_pro_apply_paywall($content) {
    if (!get_option('wp_paywall_pro_enabled') || !is_single()) {
        return $content;
    }

    $price = get_option('wp_paywall_pro_price', 5);
    $currency = get_option('wp_paywall_pro_currency', 'USD');

    // Check if user has paid
    $has_paid = get_post_meta(get_the_ID(), '_wp_paywall_pro_paid', true);
    if ($has_paid) {
        return $content;
    }

    $paywall = '<div class="wp-paywall-pro">
        <p>This content is locked. Pay $' . $price . ' ' . $currency . ' to unlock.</p>
        <form method="post" action="">
            <input type="hidden" name="wp_paywall_pro_post_id" value="' . get_the_ID() . '" />
            <button type="submit" name="wp_paywall_pro_pay">Pay Now</button>
        </form>
    </div>';

    if (isset($_POST['wp_paywall_pro_pay']) && $_POST['wp_paywall_pro_post_id'] == get_the_ID()) {
        // Simulate payment (in real plugin, integrate with Stripe/PayPal)
        update_post_meta(get_the_ID(), '_wp_paywall_pro_paid', true);
        return $content;
    }

    return $paywall;
}

// Add affiliate tracking
add_action('wp_head', 'wp_paywall_pro_affiliate_tracking');
function wp_paywall_pro_affiliate_tracking() {
    if (isset($_GET['affiliate_id'])) {
        setcookie('wp_paywall_pro_affiliate', sanitize_text_field($_GET['affiliate_id']), time() + (86400 * 30), '/');
    }
}

// Add shortcode for custom paywall
add_shortcode('paywall', 'wp_paywall_pro_shortcode');
function wp_paywall_pro_shortcode($atts, $content = null) {
    $atts = shortcode_atts(array(
        'price' => get_option('wp_paywall_pro_price', 5),
        'currency' => get_option('wp_paywall_pro_currency', 'USD')
    ), $atts);

    if (get_post_meta(get_the_ID(), '_wp_paywall_pro_paid', true)) {
        return $content;
    }

    $paywall = '<div class="wp-paywall-pro">
        <p>Unlock this content for $' . $atts['price'] . ' ' . $atts['currency'] . '</p>
        <form method="post" action="">
            <input type="hidden" name="wp_paywall_pro_shortcode_id" value="' . get_the_ID() . '" />
            <button type="submit" name="wp_paywall_pro_pay_shortcode">Pay Now</button>
        </form>
    </div>';

    if (isset($_POST['wp_paywall_pro_pay_shortcode']) && $_POST['wp_paywall_pro_shortcode_id'] == get_the_ID()) {
        update_post_meta(get_the_ID(), '_wp_paywall_pro_paid', true);
        return $content;
    }

    return $paywall;
}

// Enqueue styles
add_action('wp_enqueue_scripts', 'wp_paywall_pro_enqueue_styles');
function wp_paywall_pro_enqueue_styles() {
    wp_enqueue_style('wp-paywall-pro', WP_PAYWALL_PRO_URL . 'assets/css/style.css');
}

// Create assets directory and style.css if not exists
if (!file_exists(WP_PAYWALL_PRO_PATH . 'assets/css/style.css')) {
    wp_mkdir_p(WP_PAYWALL_PRO_PATH . 'assets/css');
    file_put_contents(WP_PAYWALL_PRO_PATH . 'assets/css/style.css', ".wp-paywall-pro { padding: 20px; background: #f9f9f9; border: 1px solid #ddd; text-align: center; }");
}

// Add affiliate stats page
add_action('admin_menu', 'wp_paywall_pro_affiliate_stats_menu');
function wp_paywall_pro_affiliate_stats_menu() {
    add_submenu_page(
        'wp-paywall-pro',
        'Affiliate Stats',
        'Affiliate Stats',
        'manage_options',
        'wp-paywall-pro-affiliate-stats',
        'wp_paywall_pro_affiliate_stats_page'
    );
}

function wp_paywall_pro_affiliate_stats_page() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
    echo '<div class="wrap"><h1>Affiliate Stats</h1><p>Track affiliate referrals and earnings here.</p></div>';
}
?>