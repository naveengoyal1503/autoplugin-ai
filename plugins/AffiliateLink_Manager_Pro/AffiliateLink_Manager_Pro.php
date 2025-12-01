<?php
/*
Plugin Name: AffiliateLink Manager Pro
Description: Manage, track, and optimize affiliate links with advanced analytics and automated link cloaking.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateLink_Manager_Pro.php
*/

define('ALMP_VERSION', '1.0');
define('ALMP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ALMP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Register custom post type for affiliate links
function almp_register_affiliate_link_post_type() {
    $args = array(
        'public' => false,
        'show_ui' => true,
        'label' => 'Affiliate Links',
        'supports' => array('title'),
        'menu_icon' => 'dashicons-admin-links',
        'show_in_menu' => 'edit.php?post_type=affiliate-link',
    );
    register_post_type('affiliate-link', $args);
}
add_action('init', 'almp_register_affiliate_link_post_type');

// Add custom columns to the affiliate link list
function almp_affiliate_link_columns($columns) {
    $columns['link'] = 'Affiliate Link';
    $columns['clicks'] = 'Clicks';
    $columns['conversions'] = 'Conversions';
    return $columns;
}
add_filter('manage_affiliate-link_posts_columns', 'almp_affiliate_link_columns');

// Populate custom columns
function almp_affiliate_link_column_content($column, $post_id) {
    switch ($column) {
        case 'link':
            echo '<input type="text" value="' . esc_url(get_post_meta($post_id, '_affiliate_url', true)) . '" readonly style="width:100%" onclick="this.select()">';
            break;
        case 'clicks':
            echo (int) get_post_meta($post_id, '_clicks', true);
            break;
        case 'conversions':
            echo (int) get_post_meta($post_id, '_conversions', true);
            break;
    }
}
add_action('manage_affiliate-link_posts_custom_column', 'almp_affiliate_link_column_content', 10, 2);

// Add meta box for affiliate link URL
function almp_add_affiliate_link_meta_box() {
    add_meta_box(
        'almp_affiliate_link_meta',
        'Affiliate Link Details',
        'almp_affiliate_link_meta_box_callback',
        'affiliate-link',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'almp_add_affiliate_link_meta_box');

function almp_affiliate_link_meta_box_callback($post) {
    wp_nonce_field('almp_save_affiliate_link_meta', 'almp_affiliate_link_nonce');
    $affiliate_url = get_post_meta($post->ID, '_affiliate_url', true);
    $cloaked = get_post_meta($post->ID, '_cloaked', true);
    ?>
    <p>
        <label for="almp_affiliate_url">Affiliate URL:</label>
        <input type="url" id="almp_affiliate_url" name="almp_affiliate_url" value="<?php echo esc_url($affiliate_url); ?>" style="width:100%">
    </p>
    <p>
        <label for="almp_cloaked">Cloak Link:</label>
        <input type="checkbox" id="almp_cloaked" name="almp_cloaked" value="1" <?php checked($cloaked, 1); ?>>
    </p>
    <?php
}

function almp_save_affiliate_link_meta($post_id) {
    if (!isset($_POST['almp_affiliate_link_nonce']) || !wp_verify_nonce($_POST['almp_affiliate_link_nonce'], 'almp_save_affiliate_link_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (isset($_POST['almp_affiliate_url'])) {
        update_post_meta($post_id, '_affiliate_url', sanitize_url($_POST['almp_affiliate_url']));
    }
    if (isset($_POST['almp_cloaked'])) {
        update_post_meta($post_id, '_cloaked', 1);
    } else {
        update_post_meta($post_id, '_cloaked', 0);
    }
}
add_action('save_post_affiliate-link', 'almp_save_affiliate_link_meta');

// Shortcode to display cloaked affiliate link
function almp_affiliate_link_shortcode($atts) {
    $atts = shortcode_atts(array(
        'id' => 0,
    ), $atts, 'affiliate_link');
    $post = get_post($atts['id']);
    if (!$post || $post->post_type !== 'affiliate-link') {
        return '';
    }
    $url = get_post_meta($post->ID, '_affiliate_url', true);
    $cloaked = get_post_meta($post->ID, '_cloaked', true);
    if ($cloaked) {
        $url = home_url('/go/' . $post->post_name);
    }
    // Increment click count
    $clicks = (int) get_post_meta($post->ID, '_clicks', true);
    update_post_meta($post->ID, '_clicks', $clicks + 1);
    return '<a href="' . esc_url($url) . '" target="_blank">' . esc_html($post->post_title) . '</a>';
}
add_shortcode('affiliate_link', 'almp_affiliate_link_shortcode');

// Handle cloaked link redirects
function almp_handle_cloaked_link() {
    if (isset($_GET['go']) && !empty($_GET['go'])) {
        $slug = sanitize_text_field($_GET['go']);
        $post = get_page_by_path($slug, OBJECT, 'affiliate-link');
        if ($post && get_post_meta($post->ID, '_cloaked', true)) {
            $url = get_post_meta($post->ID, '_affiliate_url', true);
            // Increment click count
            $clicks = (int) get_post_meta($post->ID, '_clicks', true);
            update_post_meta($post->ID, '_clicks', $clicks + 1);
            wp_redirect(esc_url($url));
            exit;
        }
    }
}
add_action('init', 'almp_handle_cloaked_link');

// Admin menu page for analytics
function almp_add_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=affiliate-link',
        'Analytics',
        'Analytics',
        'manage_options',
        'almp-analytics',
        'almp_analytics_page'
    );
}
add_action('admin_menu', 'almp_add_admin_menu');

function almp_analytics_page() {
    $links = get_posts(array(
        'post_type' => 'affiliate-link',
        'numberposts' => -1,
    ));
    echo '<div class="wrap"><h1>Affiliate Link Analytics</h1><table class="widefat fixed"><thead><tr><th>Title</th><th>Clicks</th><th>Conversions</th></tr></thead><tbody>';
    foreach ($links as $link) {
        $clicks = (int) get_post_meta($link->ID, '_clicks', true);
        $conversions = (int) get_post_meta($link->ID, '_conversions', true);
        echo '<tr><td>' . esc_html($link->post_title) . '</td><td>' . $clicks . '</td><td>' . $conversions . '</td></tr>';
    }
    echo '</tbody></table></div>';
}

// Add conversion tracking (example: via query param)
function almp_track_conversion() {
    if (isset($_GET['conversion']) && !empty($_GET['conversion'])) {
        $slug = sanitize_text_field($_GET['conversion']);
        $post = get_page_by_path($slug, OBJECT, 'affiliate-link');
        if ($post) {
            $conversions = (int) get_post_meta($post->ID, '_conversions', true);
            update_post_meta($post->ID, '_conversions', $conversions + 1);
        }
    }
}
add_action('init', 'almp_track_conversion');

// Enqueue admin styles
function almp_admin_styles() {
    wp_enqueue_style('almp-admin-style', ALMP_PLUGIN_URL . 'admin.css');
}
add_action('admin_enqueue_scripts', 'almp_admin_styles');

// Create admin.css file if not exists
function almp_create_admin_css() {
    $css = "table.fixed th, table.fixed td { padding: 8px; text-align: left; }";
    file_put_contents(ALMP_PLUGIN_DIR . 'admin.css', $css);
}
register_activation_hook(__FILE__, 'almp_create_admin_css');

// Plugin activation hook
function almp_activate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'almp_activate');

// Plugin deactivation hook
function almp_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'almp_deactivate');
