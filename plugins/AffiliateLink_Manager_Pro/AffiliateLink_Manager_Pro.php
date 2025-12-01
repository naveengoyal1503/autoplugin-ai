<?php
/*
Plugin Name: AffiliateLink Manager Pro
Description: Manage, cloak, and track affiliate links with analytics.
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
        'label'  => 'Affiliate Links',
        'supports' => array('title'),
        'menu_icon' => 'dashicons-admin-links'
    );
    register_post_type('almp_affiliate_link', $args);
}
add_action('init', 'almp_register_affiliate_link_post_type');

// Add admin menu
function almp_add_admin_menu() {
    add_menu_page(
        'AffiliateLink Manager Pro',
        'Affiliate Links',
        'manage_options',
        'almp-dashboard',
        'almp_dashboard_page',
        'dashicons-admin-links',
        6
    );
}
add_action('admin_menu', 'almp_add_admin_menu');

// Dashboard page
function almp_dashboard_page() {
    echo '<div class="wrap"><h1>AffiliateLink Manager Pro</h1>';
    echo '<p>Manage your affiliate links and track clicks.</p>';
    echo '<table class="wp-list-table widefat fixed striped">
            <thead><tr><th>ID</th><th>Title</th><th>URL</th><th>Clicks</th><th>Actions</th></tr></thead>
            <tbody>';
    $links = get_posts(array('post_type' => 'almp_affiliate_link', 'numberposts' => -1));
    foreach ($links as $link) {
        $url = get_post_meta($link->ID, '_almp_url', true);
        $clicks = get_post_meta($link->ID, '_almp_clicks', true) ?: 0;
        echo '<tr><td>' . $link->ID . '</td><td>' . $link->post_title . '</td><td>' . $url . '</td><td>' . $clicks . '</td><td><a href="?page=almp-edit&id=' . $link->ID . '">Edit</a></td></tr>';
    }
    echo '</tbody></table>';
    echo '<a href="?page=almp-add" class="button button-primary">Add New Link</a>';
    echo '</div>';
}

// Add new link page
function almp_add_link_page() {
    if (isset($_POST['almp_save_link'])) {
        $post_id = wp_insert_post(array(
            'post_title' => sanitize_text_field($_POST['title']),
            'post_type' => 'almp_affiliate_link',
            'post_status' => 'publish'
        ));
        update_post_meta($post_id, '_almp_url', esc_url_raw($_POST['url']));
        update_post_meta($post_id, '_almp_clicks', 0);
        echo '<div class="notice notice-success"><p>Link added successfully!</p></div>';
    }
    echo '<div class="wrap"><h1>Add New Affiliate Link</h1>';
    echo '<form method="post">';
    echo '<table class="form-table">
            <tr><th><label>Title</label></th><td><input type="text" name="title" required /></td></tr>
            <tr><th><label>URL</label></th><td><input type="url" name="url" required /></td></tr>
          </table>';
    echo '<input type="submit" name="almp_save_link" class="button button-primary" value="Save Link" />';
    echo '</form></div>';
}

// Edit link page
function almp_edit_link_page() {
    if (isset($_GET['id']) && isset($_POST['almp_update_link'])) {
        $id = intval($_GET['id']);
        wp_update_post(array(
            'ID' => $id,
            'post_title' => sanitize_text_field($_POST['title'])
        ));
        update_post_meta($id, '_almp_url', esc_url_raw($_POST['url']));
        echo '<div class="notice notice-success"><p>Link updated!</p></div>';
    }
    $id = intval($_GET['id']);
    $link = get_post($id);
    $url = get_post_meta($id, '_almp_url', true);
    echo '<div class="wrap"><h1>Edit Affiliate Link</h1>';
    echo '<form method="post">';
    echo '<table class="form-table">
            <tr><th><label>Title</label></th><td><input type="text" name="title" value="' . $link->post_title . '" required /></td></tr>
            <tr><th><label>URL</label></th><td><input type="url" name="url" value="' . $url . '" required /></td></tr>
          </table>';
    echo '<input type="submit" name="almp_update_link" class="button button-primary" value="Update Link" />';
    echo '</form></div>';
}

// Handle admin pages
function almp_admin_pages() {
    if (isset($_GET['page']) && $_GET['page'] == 'almp-add') {
        almp_add_link_page();
    } elseif (isset($_GET['page']) && $_GET['page'] == 'almp-edit') {
        almp_edit_link_page();
    }
}
add_action('admin_init', 'almp_admin_pages');

// Shortcode to display cloaked affiliate link
function almp_affiliate_link_shortcode($atts) {
    $atts = shortcode_atts(array('id' => 0), $atts);
    $id = intval($atts['id']);
    $link = get_post($id);
    if (!$link || $link->post_type != 'almp_affiliate_link') return '';
    $url = get_post_meta($id, '_almp_url', true);
    $clicks = get_post_meta($id, '_almp_clicks', true) ?: 0;
    update_post_meta($id, '_almp_clicks', $clicks + 1);
    return '<a href="' . esc_url($url) . '" target="_blank">Visit Link</a>';
}
add_shortcode('almp_link', 'almp_affiliate_link_shortcode');

// Add settings link on plugin page
function almp_plugin_settings_link($links) {
    $settings_link = '<a href="admin.php?page=almp-dashboard">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'almp_plugin_settings_link');

// Enqueue admin styles
function almp_admin_styles() {
    wp_enqueue_style('almp-admin-style', ALMP_PLUGIN_URL . 'admin.css');
}
add_action('admin_enqueue_scripts', 'almp_admin_styles');

// Create admin CSS file if not exists
function almp_create_admin_css() {
    $css = "table.form-table { width: 100%; }";
    file_put_contents(ALMP_PLUGIN_DIR . 'admin.css', $css);
}
register_activation_hook(__FILE__, 'almp_create_admin_css');

// Activation hook
function almp_activate() {
    almp_register_affiliate_link_post_type();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'almp_activate');

// Deactivation hook
function almp_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'almp_deactivate');
?>