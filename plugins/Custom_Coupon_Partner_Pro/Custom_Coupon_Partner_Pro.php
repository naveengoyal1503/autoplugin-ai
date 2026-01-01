/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Custom_Coupon_Partner_Pro.php
*/
<?php
/**
 * Plugin Name: Custom Coupon Partner Pro
 * Plugin URI: https://example.com/coupon-partner-pro
 * Description: Generate personalized affiliate coupons, track performance, and boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: coupon-partner-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CPP_VERSION', '1.0.0');
define('CPP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CPP_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Pro check (simulate with option for demo)
function cpp_is_pro() {
    return get_option('cpp_pro_activated', false);
}

// Admin menu
add_action('admin_menu', 'cpp_admin_menu');
function cpp_admin_menu() {
    add_menu_page(
        'Coupon Partner Pro',
        'Coupons',
        'manage_options',
        'coupon-partner-pro',
        'cpp_admin_page',
        'dashicons-tickets',
        30
    );
}

function cpp_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Handle form submission
    if (isset($_POST['cpp_submit']) && check_admin_referer('cpp_save')) {
        $coupons = isset($_POST['coupons']) ? sanitize_textarea_field($_POST['coupons']) : '';
        update_option('cpp_coupons', $coupons);
        echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
    }
    
    $coupons = get_option('cpp_coupons', "Code: SAVE10\nBrand: Example Store\nAffiliate Link: https://example.com/ref=yourid\nDescription: 10% off first purchase");
    
    ?>
    <div class="wrap">
        <h1>Custom Coupon Partner Pro</h1>
        <?php if (!cpp_is_pro()): ?>
        <div class="notice notice-info"><p><strong>Pro Features Unlocked!</strong> Analytics, unlimited coupons, custom codes. <a href="#pro">Upgrade Now</a></p></div>
        <?php endif; ?>
        <form method="post">
            <?php wp_nonce_field('cpp_save'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">Coupons (one per line: Code|Brand|Link|Description)</th>
                    <td><textarea name="coupons" rows="10" cols="50" class="large-text"><?php echo esc_textarea($coupons); ?></textarea></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        
        <h2>Shortcode Usage</h2>
        <p>Use <code>[cpp_coupons]</code> to display coupons on any page/post.</p>
        
        <?php if (!cpp_is_pro()): ?>
        <div id="pro" class="card">
            <h3>Go Pro - $49/year</h3>
            <ul>
                <li>Unlimited coupons</li>
                <li>Click tracking dashboard</li>
                <li>Custom branding</li>
                <li>Export analytics</li>
            </ul>
            <p><a href="https://example.com/pro" class="button button-primary">Upgrade Now</a></p>
        </div>
        <?php endif; 
        
        // Display stats if pro
        if (cpp_is_pro()) {
            echo '<h2>Analytics</h2><p>Total clicks: ' . get_option('cpp_total_clicks', 0) . '</p>';
        }
        ?>
    </div>
    <?php
}

// Shortcode to display coupons
add_shortcode('cpp_coupons', 'cpp_coupons_shortcode');
function cpp_coupons_shortcode($atts) {
    $coupons_text = get_option('cpp_coupons', '');
    if (empty($coupons_text)) {
        return '<p>No coupons configured. <a href="' . admin_url('admin.php?page=coupon-partner-pro') . '">Set up now</a>.</p>';
    }
    
    $lines = explode("\n", $coupons_text);
    $output = '<div class="cpp-coupons">';
    foreach ($lines as $line) {
        $parts = array_map('trim', explode('|', $line));
        if (count($parts) >= 3) {
            $code = $parts;
            $brand = $parts[1];
            $link = $parts[2];
            $desc = isset($parts[3]) ? $parts[3] : '';
            
            $track_link = add_query_arg('cpp_ref', 'tracked', $link);
            
            $output .= '<div class="cpp-coupon">';
            $output .= '<h4>' . esc_html($brand) . '</h4>';
            $output .= '<strong>Code: ' . esc_html($code) . '</strong>';
            if ($desc) $output .= '<p>' . esc_html($desc) . '</p>';
            $output .= '<a href="' . esc_url($track_link) . '" class="button" target="_blank">Shop Now & Apply</a>';
            $output .= '</div>';
        }
    }
    $output .= '</div>';
    return $output;
}

// Track clicks
add_action('init', 'cpp_track_click');
function cpp_track_click() {
    if (isset($_GET['cpp_ref']) && cpp_is_pro()) {
        $clicks = get_option('cpp_total_clicks', 0) + 1;
        update_option('cpp_total_clicks', $clicks);
        // In pro version, log more details
    }
}

// Enqueue styles
add_action('wp_enqueue_scripts', 'cpp_styles');
add_action('admin_enqueue_scripts', 'cpp_styles');
function cpp_styles($hook) {
    if ($hook === 'settings_page_coupon-partner-pro' || has_shortcode(get_post()->post_content ?? '', 'cpp_coupons')) {
        wp_enqueue_style('cpp-style', CPP_PLUGIN_URL . 'style.css', array(), CPP_VERSION);
    }
}

// Create assets dir and basic CSS
add_action('register_activation_hook', 'cpp_create_assets');
function cpp_create_assets() {
    $css_dir = CPP_PLUGIN_PATH . 'assets/';
    if (!file_exists($css_dir)) {
        mkdir($css_dir, 0755, true);
    }
    $css_content = ".cpp-coupons { display: flex; flex-wrap: wrap; gap: 20px; } .cpp-coupon { border: 1px solid #ddd; padding: 20px; border-radius: 8px; flex: 1 1 300px; background: #f9f9f9; } .cpp-coupon h4 { margin: 0 0 10px; color: #333; } .cpp-coupon .button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }";
    file_put_contents($css_dir . 'style.css', $css_content);
}

// Freemium upsell notice
add_action('admin_notices', 'cpp_pro_notice');
function cpp_pro_notice() {
    if (!cpp_is_pro() && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Unlock <strong>Custom Coupon Partner Pro</strong> for unlimited coupons and analytics! <a href="' . admin_url('admin.php?page=coupon-partner-pro') . '#pro' . '">Upgrade Now</a></p></div>';
    }
}

// Plugin row meta
add_filter('plugin_row_meta', 'cpp_plugin_links', 10, 2);
function cpp_plugin_links($links, $file) {
    if ($file === plugin_basename(__FILE__)) {
        $links[] = '<a href="https://example.com/pro">Go Pro</a>';
        $links[] = '<a href="https://example.com/docs">Docs</a>';
    }
    return $links;
}

?>