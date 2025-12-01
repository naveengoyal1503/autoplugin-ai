<?php
/*
Plugin Name: AffiliateLinkOptimizer
Description: Automatically optimize and track affiliate links for higher conversions and revenue.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateLinkOptimizer.php
*/

// Prevent direct access
define('ABSPATH') or die('No script kiddies please!');

// Register settings
function aflo_register_settings() {
    register_setting('aflo_options', 'aflo_affiliate_id');
    register_setting('aflo_options', 'aflo_tracking_enabled');
}
add_action('admin_init', 'aflo_register_settings');

// Add menu
function aflo_add_menu() {
    add_options_page('Affiliate Link Optimizer', 'Affiliate Optimizer', 'manage_options', 'affiliate-link-optimizer', 'aflo_options_page');
}
add_action('admin_menu', 'aflo_add_menu');

// Options page
function aflo_options_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    ?>
    <div class="wrap">
        <h1>Affiliate Link Optimizer</h1>
        <form method="post" action="options.php">
            <?php settings_fields('aflo_options'); ?>
            <?php do_settings_sections('aflo_options'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Affiliate ID</th>
                    <td><input type="text" name="aflo_affiliate_id" value="<?php echo esc_attr(get_option('aflo_affiliate_id')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Enable Tracking</th>
                    <td><input type="checkbox" name="aflo_tracking_enabled" value="1" <?php checked(1, get_option('aflo_tracking_enabled'), true); ?> /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Process content and optimize affiliate links
function aflo_optimize_links($content) {
    $affiliate_id = get_option('aflo_affiliate_id');
    $tracking_enabled = get_option('aflo_tracking_enabled');

    if (empty($affiliate_id)) return $content;

    // Example: Replace affiliate links with optimized ones
    $pattern = '/(https?:\/\/[^\s]+\?)([^\s]*)/i';
    $replacement = '$1$2&ref=' . $affiliate_id;
    if ($tracking_enabled) {
        $replacement .= '&utm_source=affiliate&utm_medium=link';
    }
    $content = preg_replace($pattern, $replacement, $content);

    return $content;
}
add_filter('the_content', 'aflo_optimize_links');

// Shortcode for manual link optimization
function aflo_optimize_shortcode($atts, $content = null) {
    $affiliate_id = get_option('aflo_affiliate_id');
    $tracking_enabled = get_option('aflo_tracking_enabled');

    if (empty($affiliate_id) || empty($content)) return $content;

    $url = $content;
    $separator = strpos($url, '?') !== false ? '&' : '?';
    $url .= $separator . 'ref=' . $affiliate_id;
    if ($tracking_enabled) {
        $url .= '&utm_source=affiliate&utm_medium=shortcode';
    }
    return '<a href="' . esc_url($url) . '" target="_blank">' . $content . '</a>';
}
add_shortcode('aflo', 'aflo_optimize_shortcode');

// Add admin notice if affiliate ID is not set
function aflo_admin_notice() {
    if (!get_option('aflo_affiliate_id')) {
        echo '<div class="notice notice-warning is-dismissible"><p>Affiliate Link Optimizer: Please set your affiliate ID in the plugin settings.</p></div>';
    }
}
add_action('admin_notices', 'aflo_admin_notice');

// Enqueue admin styles
function aflo_admin_styles() {
    wp_enqueue_style('aflo-admin-style', plugins_url('admin.css', __FILE__));
}
add_action('admin_enqueue_scripts', 'aflo_admin_styles');

// Create admin CSS
function aflo_create_admin_css() {
    $css = "
        .aflo-admin-container { padding: 20px; }
        .aflo-admin-container h1 { margin-bottom: 20px; }
    ";
    file_put_contents(plugin_dir_path(__FILE__) . 'admin.css', $css);
}
aflo_create_admin_css();

// Activation hook
function aflo_activate() {
    // Do activation tasks
}
register_activation_hook(__FILE__, 'aflo_activate');

// Deactivation hook
function aflo_deactivate() {
    // Do deactivation tasks
}
register_deactivation_hook(__FILE__, 'aflo_deactivate');

?>