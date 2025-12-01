/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Plugin URI: https://example.com/wp-revenue-booster
 * Description: Automatically optimizes ad placements, affiliate links, and upsell offers to maximize site revenue.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

define('WP_REVENUE_BOOSTER_VERSION', '1.0');
define('WP_REVENUE_BOOSTER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_REVENUE_BOOSTER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Add admin menu
add_action('admin_menu', 'wp_revenue_booster_menu');
function wp_revenue_booster_menu() {
    add_menu_page(
        'WP Revenue Booster',
        'Revenue Booster',
        'manage_options',
        'wp-revenue-booster',
        'wp_revenue_booster_settings_page',
        'dashicons-chart-line',
        80
    );
}

// Settings page
function wp_revenue_booster_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    if (isset($_POST['wp_revenue_booster_save'])) {
        update_option('wp_revenue_booster_ad_placement', sanitize_text_field($_POST['ad_placement']));
        update_option('wp_revenue_booster_affiliate_links', sanitize_text_field($_POST['affiliate_links']));
        update_option('wp_revenue_booster_upsell_offers', sanitize_text_field($_POST['upsell_offers']));
        echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
    }
    $ad_placement = get_option('wp_revenue_booster_ad_placement', 'auto');
    $affiliate_links = get_option('wp_revenue_booster_affiliate_links', 'auto');
    $upsell_offers = get_option('wp_revenue_booster_upsell_offers', 'auto');
    ?>
    <div class="wrap">
        <h1>WP Revenue Booster</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row">Ad Placement</th>
                    <td>
                        <select name="ad_placement">
                            <option value="auto" <?php selected($ad_placement, 'auto'); ?>>Auto Optimize</option>
                            <option value="manual" <?php selected($ad_placement, 'manual'); ?>>Manual</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Affiliate Links</th>
                    <td>
                        <select name="affiliate_links">
                            <option value="auto" <?php selected($affiliate_links, 'auto'); ?>>Auto Optimize</option>
                            <option value="manual" <?php selected($affiliate_links, 'manual'); ?>>Manual</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Upsell Offers</th>
                    <td>
                        <select name="upsell_offers">
                            <option value="auto" <?php selected($upsell_offers, 'auto'); ?>>Auto Optimize</option>
                            <option value="manual" <?php selected($upsell_offers, 'manual'); ?>>Manual</option>
                        </select>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Settings', 'primary', 'wp_revenue_booster_save'); ?>
        </form>
    </div>
    <?php
}

// Auto-optimize ad placement
add_filter('the_content', 'wp_revenue_booster_auto_ad_placement');
function wp_revenue_booster_auto_ad_placement($content) {
    if (get_option('wp_revenue_booster_ad_placement') === 'auto') {
        $ad_code = '<div class="wp-revenue-booster-ad">Your Ad Here</div>';
        $paragraph_count = substr_count($content, '</p>');
        $insert_after = max(1, intval($paragraph_count / 3));
        $content = preg_replace('/(<\/p>)/', '$1' . $ad_code, $content, $insert_after);
    }
    return $content;
}

// Auto-optimize affiliate links
add_filter('the_content', 'wp_revenue_booster_auto_affiliate_links');
function wp_revenue_booster_auto_affiliate_links($content) {
    if (get_option('wp_revenue_booster_affiliate_links') === 'auto') {
        $affiliate_link = '<a href="https://example.com/affiliate" target="_blank">Affiliate Product</a>';
        $content .= '<p>Check out this ' . $affiliate_link . '!</p>';
    }
    return $content;
}

// Auto-optimize upsell offers
add_filter('the_content', 'wp_revenue_booster_auto_upsell_offers');
function wp_revenue_booster_auto_upsell_offers($content) {
    if (get_option('wp_revenue_booster_upsell_offers') === 'auto') {
        $upsell_offer = '<div class="wp-revenue-booster-upsell">Upgrade to premium for more features!</div>';
        $content .= $upsell_offer;
    }
    return $content;
}

// Enqueue admin styles
add_action('admin_enqueue_scripts', 'wp_revenue_booster_admin_styles');
function wp_revenue_booster_admin_styles($hook) {
    if ('toplevel_page_wp-revenue-booster' !== $hook) {
        return;
    }
    wp_enqueue_style('wp-revenue-booster-admin', WP_REVENUE_BOOSTER_PLUGIN_URL . 'admin.css');
}

// Enqueue frontend styles
add_action('wp_enqueue_scripts', 'wp_revenue_booster_frontend_styles');
function wp_revenue_booster_frontend_styles() {
    wp_enqueue_style('wp-revenue-booster-frontend', WP_REVENUE_BOOSTER_PLUGIN_URL . 'frontend.css');
}

// Create CSS files if not exist
register_activation_hook(__FILE__, 'wp_revenue_booster_create_css');
function wp_revenue_booster_create_css() {
    $admin_css = "div.wp-revenue-booster-ad { background: #f0f0f0; padding: 10px; margin: 10px 0; }\n";
    $frontend_css = "div.wp-revenue-booster-upsell { background: #e0ffe0; padding: 15px; margin: 15px 0; border: 1px solid #00aa00; }\n";
    file_put_contents(WP_REVENUE_BOOSTER_PLUGIN_DIR . 'admin.css', $admin_css);
    file_put_contents(WP_REVENUE_BOOSTER_PLUGIN_DIR . 'frontend.css', $frontend_css);
}

// Add premium upsell notice in admin
add_action('admin_notices', 'wp_revenue_booster_premium_notice');
function wp_revenue_booster_premium_notice() {
    if (current_user_can('manage_options') && !get_option('wp_revenue_booster_premium_active')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>WP Revenue Booster Premium</strong> for advanced optimization, A/B testing, and detailed analytics. <a href="https://example.com/premium" target="_blank">Learn more</a></p></div>';
    }
}
?>