/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_SmartPaywall.php
*/
<?php
/**
 * Plugin Name: WP SmartPaywall
 * Plugin URI: https://example.com/wp-smartpaywall
 * Description: A dynamic paywall plugin that intelligently unlocks premium content based on user engagement, subscription status, or micro-payments.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

// Prevent direct access
define('ABSPATH') or die('No script kiddies please!');

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'wp_smartpaywall_activate');
register_deactivation_hook(__FILE__, 'wp_smartpaywall_deactivate');

function wp_smartpaywall_activate() {
    // Add default options
    add_option('wp_smartpaywall_enabled', true);
    add_option('wp_smartpaywall_mode', 'subscription'); // subscription, engagement, micropayment
    add_option('wp_smartpaywall_threshold', 3); // e.g., number of articles read
}

function wp_smartpaywall_deactivate() {
    // Cleanup if needed
}

// Add admin menu
add_action('admin_menu', 'wp_smartpaywall_admin_menu');
function wp_smartpaywall_admin_menu() {
    add_options_page(
        'WP SmartPaywall Settings',
        'SmartPaywall',
        'manage_options',
        'wp-smartpaywall',
        'wp_smartpaywall_settings_page'
    );
}

// Settings page
function wp_smartpaywall_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    if (isset($_POST['wp_smartpaywall_submit'])) {
        update_option('wp_smartpaywall_enabled', isset($_POST['enabled']) ? 1 : 0);
        update_option('wp_smartpaywall_mode', sanitize_text_field($_POST['mode']));
        update_option('wp_smartpaywall_threshold', intval($_POST['threshold']));
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }
    $enabled = get_option('wp_smartpaywall_enabled', true);
    $mode = get_option('wp_smartpaywall_mode', 'subscription');
    $threshold = get_option('wp_smartpaywall_threshold', 3);
    ?>
    <div class="wrap">
        <h1>WP SmartPaywall Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Enable SmartPaywall</th>
                    <td><input type="checkbox" name="enabled" value="1" <?php checked($enabled, 1); ?> /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Paywall Mode</th>
                    <td>
                        <select name="mode">
                            <option value="subscription" <?php selected($mode, 'subscription'); ?>>Subscription</option>
                            <option value="engagement" <?php selected($mode, 'engagement'); ?>>Engagement</option>
                            <option value="micropayment" <?php selected($mode, 'micropayment'); ?>>Micro-payment</option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Threshold</th>
                    <td><input type="number" name="threshold" value="<?php echo esc_attr($threshold); ?>" /></td>
                </tr>
            </table>
            <?php submit_button('Save Changes', 'primary', 'wp_smartpaywall_submit'); ?>
        </form>
    </div>
    <?php
}

// Apply paywall logic
add_filter('the_content', 'wp_smartpaywall_apply_paywall');
function wp_smartpaywall_apply_paywall($content) {
    if (!get_option('wp_smartpaywall_enabled', true)) return $content;

    $mode = get_option('wp_smartpaywall_mode', 'subscription');
    $threshold = get_option('wp_smartpaywall_threshold', 3);

    // Check if user is logged in and has premium access
    if (is_user_logged_in() && user_can(get_current_user_id(), 'premium_access')) {
        return $content;
    }

    // Apply paywall logic based on mode
    switch ($mode) {
        case 'engagement':
            $read_count = get_user_meta(get_current_user_id(), 'wp_smartpaywall_read_count', true);
            if ($read_count >= $threshold) {
                return $content;
            }
            // Increment read count
            update_user_meta(get_current_user_id(), 'wp_smartpaywall_read_count', $read_count + 1);
            break;
        case 'micropayment':
            // Placeholder for micropayment logic
            // In a real plugin, integrate with a payment gateway
            return $content; // Simplified for demo
        default:
            // Subscription mode
            return '<p>This content is behind a paywall. Please subscribe to access.</p>';
    }

    return $content;
}

// Add premium access capability
add_action('init', 'wp_smartpaywall_add_capabilities');
function wp_smartpaywall_add_capabilities() {
    $role = get_role('subscriber');
    if (!$role->has_cap('premium_access')) {
        $role->add_cap('premium_access');
    }
}

// Shortcode for manual paywall
add_shortcode('smartpaywall', 'wp_smartpaywall_shortcode');
function wp_smartpaywall_shortcode($atts, $content = null) {
    if (is_user_logged_in() && user_can(get_current_user_id(), 'premium_access')) {
        return $content;
    }
    return '<p>This content is behind a paywall. Please subscribe to access.</p>';
}

// Enqueue scripts and styles
add_action('wp_enqueue_scripts', 'wp_smartpaywall_enqueue_scripts');
function wp_smartpaywall_enqueue_scripts() {
    wp_enqueue_style('wp-smartpaywall', plugins_url('style.css', __FILE__));
}

// Create style.css if needed
// This is a simplified version; in reality, you'd create a separate file
add_action('wp_head', 'wp_smartpaywall_inline_styles');
function wp_smartpaywall_inline_styles() {
    echo '<style>.wp-smartpaywall { background: #f0f0f0; padding: 20px; border: 1px solid #ccc; }</style>';
}
?>