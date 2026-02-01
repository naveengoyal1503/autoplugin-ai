/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Cloaker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Cloaker Pro
 * Plugin URI: https://example.com/smart-affiliate-cloaker
 * Description: Cloak affiliate links, track clicks, and optimize conversions with A/B testing.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-cloaker
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define plugin constants
define('SAC_VERSION', '1.0.0');
define('SAC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SAC_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Pro check (simulate with option; in real, check license)
function sac_is_pro() {
    return get_option('sac_pro_active', false);
}

// Activation hook
register_activation_hook(__FILE__, 'sac_activate');
function sac_activate() {
    add_option('sac_links', []);
    add_option('sac_pro_active', false);
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'sac_deactivate');
function sac_deactivate() {
    flush_rewrite_rules();
}

// Init hook
add_action('init', 'sac_init');
function sac_init() {
    sac_register_post_types();
    sac_add_rewrite_rules();
}

// Custom post type for links
function sac_register_post_types() {
    register_post_type('sac_link', [
        'labels' => [
            'name' => 'Affiliate Links',
            'singular_name' => 'Affiliate Link',
        ],
        'public' => true,
        'publicly_queryable' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => ['slug' => 'go'],
        'capability_type' => 'post',
        'supports' => ['title'],
        'menu_icon' => 'dashicons-admin-links',
    ]);
}

// Rewrite rules
add_action('init', 'sac_add_rewrite_rules');
function sac_add_rewrite_rules() {
    add_rewrite_rule('^go/([^/]+)/?', 'index.php?sac_link=$matches[1]&sac_redirect=1', 'top');
}

// Query vars
add_filter('query_vars', 'sac_query_vars');
function sac_query_vars($vars) {
    $vars[] = 'sac_redirect';
    return $vars;
}

// Template redirect for cloaked links
add_action('template_redirect', 'sac_handle_redirect');
function sac_handle_redirect() {
    if (get_query_var('sac_redirect')) {
        $slug = get_query_var('sac_link');
        $link = get_page_by_path($slug, OBJECT, 'sac_link');
        if ($link) {
            sac_track_click($link->ID);
            $url = get_post_meta($link->ID, 'sac_target_url', true);
            $ab_test = get_post_meta($link->ID, 'sac_ab_variants', true);
            if (sac_is_pro() && $ab_test && is_array($ab_test) && count($ab_test) > 1) {
                $variant = sac_get_ab_variant($ab_test);
                $url = $variant['url'];
            }
            wp_redirect($url, 301);
            exit;
        }
    }
}

// Track clicks
function sac_track_click($link_id) {
    $clicks = get_post_meta($link_id, 'sac_clicks', true);
    if (!is_array($clicks)) $clicks = [];
    $date = date('Y-m-d');
    $clicks[$date] = isset($clicks[$date]) ? $clicks[$date] + 1 : 1;
    update_post_meta($link_id, 'sac_clicks', $clicks);

    // Log IP for basic analytics
global $wpdb;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $wpdb->insert($wpdb->prefix . 'sac_logs', [
        'link_id' => $link_id,
        'ip' => $ip,
        'timestamp' => current_time('mysql'),
    ]);
}

// Install DB table
register_activation_hook(__FILE__, 'sac_install_table');
function sac_install_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'sac_logs';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        link_id bigint(20) NOT NULL,
        ip varchar(45) NOT NULL,
        timestamp datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Admin menu
add_action('admin_menu', 'sac_admin_menu');
function sac_admin_menu() {
    add_submenu_page('edit.php?post_type=sac_link', 'Analytics', 'Analytics', 'manage_options', 'sac-analytics', 'sac_analytics_page');
    add_submenu_page('edit.php?post_type=sac_link', 'Upgrade to Pro', 'Go Pro', 'manage_options', 'sac-pro', 'sac_pro_page');
}

// Analytics page
function sac_analytics_page() {
    if (!current_user_can('manage_options')) return;
    $links = get_posts(['post_type' => 'sac_link', 'numberposts' => -1]);
    echo '<div class="wrap"><h1>Affiliate Analytics</h1>';
    if (sac_is_pro()) {
        echo '<p><strong>Pro Analytics Active!</strong></p>';
    } else {
        echo '<p><a href="' . admin_url('admin.php?page=sac-pro') . '" class="button button-primary">Upgrade to Pro for Advanced Analytics & A/B Testing</a></p>';
    }
    echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Link</th><th>Total Clicks</th><th>Last 7 Days</th></tr></thead><tbody>';
    foreach ($links as $link) {
        $clicks = get_post_meta($link->ID, 'sac_clicks', true);
        $total = array_sum($clicks ?? []);
        $recent = 0;
        foreach ($clicks ?? [] as $date => $count) {
            if (strtotime($date) > strtotime('-7 days')) $recent += $count;
        }
        echo '<tr><td>' . get_the_title($link->ID) . '</td><td>' . $total . '</td><td>' . $recent . '</td></tr>';
    }
    echo '</tbody></table></div>';
}

// Pro upgrade page
function sac_pro_page() {
    echo '<div class="wrap"><h1>Upgrade to Smart Affiliate Link Cloaker Pro</h1><p>Unlock:</p><ul><li>Unlimited cloaked links</li><li>A/B testing</li><li>Advanced analytics</li><li>White-label reports</li><li>Priority support</li></ul><p><a href="https://example.com/pro" target="_blank" class="button button-primary">Get Pro Now - $9.99/mo</a></p></div>';
}

// Metaboxes
add_action('add_meta_boxes', 'sac_add_meta_boxes');
function sac_add_meta_boxes() {
    add_meta_box('sac_link_details', 'Link Settings', 'sac_link_meta_box', 'sac_link');
}

function sac_link_meta_box($post) {
    wp_nonce_field('sac_save_meta', 'sac_meta_nonce');
    $url = get_post_meta($post->ID, 'sac_target_url', true);
    $variants = get_post_meta($post->ID, 'sac_ab_variants', true);
    echo '<p><label>Target URL: <input type="url" name="sac_target_url" value="' . esc_attr($url) . '" style="width:100%;" required></label></p>';
    if (sac_is_pro()) {
        echo '<p><label>A/B Variants (JSON: [{"name":"A","url":"https://"}, {"name":"B","url":"https://"}]): <textarea name="sac_ab_variants" style="width:100%;height:100px;">' . esc_textarea($variants) . '</textarea></label></p>';
    } else {
        echo '<p><strong>Pro Feature:</strong> A/B Testing available in Pro version. <a href="' . admin_url('admin.php?page=sac-pro') . '">Upgrade Now</a></p>';
    }
}

// Save meta
add_action('save_post', 'sac_save_meta');
function sac_save_meta($post_id) {
    if (!isset($_POST['sac_meta_nonce']) || !wp_verify_nonce($_POST['sac_meta_nonce'], 'sac_save_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    update_post_meta($post_id, 'sac_target_url', sanitize_url($_POST['sac_target_url'] ?? ''));
    if (sac_is_pro() && isset($_POST['sac_ab_variants'])) {
        $variants = json_decode(stripslashes($_POST['sac_ab_variants']), true);
        if (json_last_error() === JSON_ERROR_NONE) {
            update_post_meta($post_id, 'sac_ab_variants', $variants);
        }
    }
}

// A/B variant selection
function sac_get_ab_variant($variants) {
    $total_weights = 0;
    foreach ($variants as $v) {
        $total_weights += $v['weight'] ?? 1;
    }
    $rand = mt_rand(1, $total_weights);
    $current = 0;
    foreach ($variants as $v) {
        $current += $v['weight'] ?? 1;
        if ($rand <= $current) return $v;
    }
    return $variants;
}

// Shortcode [sac id="123"]
add_shortcode('sac', 'sac_shortcode');
function sac_shortcode($atts) {
    $atts = shortcode_atts(['id' => 0], $atts);
    $post = get_post($atts['id']);
    if (!$post || $post->post_type !== 'sac_link') return '';
    $slug = $post->post_name;
    return '<a href="' . home_url('/go/' . $slug . '/') . '" target="_blank" rel="nofollow">' . get_the_title($post->ID) . '</a>';
}

// Admin JS
add_action('admin_enqueue_scripts', 'sac_admin_scripts');
function sac_admin_scripts($hook) {
    if (strpos($hook, 'sac_link') === false) return;
    wp_enqueue_script('sac-admin', SAC_PLUGIN_URL . 'admin.js', ['jquery'], SAC_VERSION, true);
}

// Pro activation (admin ajax or settings)
add_action('wp_ajax_sac_activate_pro', 'sac_activate_pro');
function sac_activate_pro() {
    if (!current_user_can('manage_options')) wp_die();
    update_option('sac_pro_active', true);
    wp_redirect(admin_url('edit.php?post_type=sac_link'));
    exit;
}

// Freemium notice
add_action('admin_notices', 'sac_freemium_notice');
function sac_freemium_notice() {
    if (!sac_is_pro() && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Unlock <strong>Smart Affiliate Link Cloaker Pro</strong> for A/B testing and more! <a href="' . admin_url('admin.php?page=sac-pro') . '">Upgrade Now</a></p></div>';
    }
}

// Widget for sidebar links
class SAC_Link_Widget extends WP_Widget {
    function __construct() {
        parent::__construct('sac_link_widget', 'Affiliate Link Cloaker');
    }
    public function widget($args, $instance) {
        $title = apply_filters('widget_title', $instance['title']);
        $link_id = $instance['link_id'];
        echo $args['before_widget'];
        if (!empty($title)) echo $args['before_title'] . $title . $args['after_title'];
        echo do_shortcode('[sac id="' . $link_id . '"]');
        echo $args['after_widget'];
    }
    public function form($instance) {
        $title = $instance['title'] ?? 'Click Here';
        $link_id = $instance['link_id'] ?? '';
        echo '<p><label>Title: <input class="widefat" name="' . $this->get_field_name('title') . '" type="text" value="' . esc_attr($title) . '"></label></p>';
        echo '<p><label>Link ID: <input class="widefat" name="' . $this->get_field_name('link_id') . '" type="number" value="' . esc_attr($link_id) . '"></label></p>';
    }
    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = sanitize_text_field($new_instance['title']);
        $instance['link_id'] = intval($new_instance['link_id']);
        return $instance;
    }
}

add_action('widgets_init', function() {
    register_widget('SAC_Link_Widget');
});