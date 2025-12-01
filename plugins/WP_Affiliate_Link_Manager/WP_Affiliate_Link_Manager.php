<?php
/*
Plugin Name: WP Affiliate Link Manager
Description: Manage, track, and cloak affiliate links from your WordPress dashboard.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Affiliate_Link_Manager.php
*/

// Prevent direct access
define('ABSPATH') or die('No script kiddies please!');

// Register custom post type for affiliate links
function wp_affiliate_link_manager_cpt() {
    $args = array(
        'public' => true,
        'label'  => 'Affiliate Links',
        'supports' => array('title'),
        'has_archive' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-admin-links',
        'show_in_rest' => true,
    );
    register_post_type('wp_affiliate_link', $args);
}
add_action('init', 'wp_affiliate_link_manager_cpt');

// Add custom fields to affiliate link post type
function wp_affiliate_link_manager_meta_box() {
    add_meta_box(
        'wp_affiliate_link_manager_meta',
        'Affiliate Link Details',
        'wp_affiliate_link_manager_meta_callback',
        'wp_affiliate_link',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'wp_affiliate_link_manager_meta_box');

function wp_affiliate_link_manager_meta_callback($post) {
    wp_nonce_field('wp_affiliate_link_manager_meta_nonce', 'wp_affiliate_link_manager_meta_nonce');
    $url = get_post_meta($post->ID, '_affiliate_url', true);
    $cloak = get_post_meta($post->ID, '_cloak_link', true);
    $clicks = get_post_meta($post->ID, '_click_count', true);
    $clicks = $clicks ? $clicks : 0;
    ?>
    <p>
        <label for="affiliate_url">Affiliate URL:</label>
        <input type="url" name="affiliate_url" id="affiliate_url" value="<?php echo esc_url($url); ?>" style="width:100%;" />
    </p>
    <p>
        <label for="cloak_link">
            <input type="checkbox" name="cloak_link" id="cloak_link" value="1" <?php checked($cloak, 1); ?> />
            Cloak this link?
        </label>
    </p>
    <p><strong>Clicks:</strong> <?php echo $clicks; ?></p>
    <?php
}

function wp_affiliate_link_manager_save_meta($post_id) {
    if (!isset($_POST['wp_affiliate_link_manager_meta_nonce']) || !wp_verify_nonce($_POST['wp_affiliate_link_manager_meta_nonce'], 'wp_affiliate_link_manager_meta_nonce')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['affiliate_url'])) {
        update_post_meta($post_id, '_affiliate_url', sanitize_url($_POST['affiliate_url']));
    }
    if (isset($_POST['cloak_link'])) {
        update_post_meta($post_id, '_cloak_link', intval($_POST['cloak_link']));
    }
}
add_action('save_post', 'wp_affiliate_link_manager_save_meta');

// Shortcode to display affiliate link
function wp_affiliate_link_shortcode($atts) {
    $atts = shortcode_atts(array('id' => 0), $atts, 'affiliate_link');
    $post = get_post($atts['id']);
    if (!$post || $post->post_type !== 'wp_affiliate_link') return '';

    $url = get_post_meta($post->ID, '_affiliate_url', true);
    $cloak = get_post_meta($post->ID, '_cloak_link', true);
    $clicks = get_post_meta($post->ID, '_click_count', true);
    $clicks = $clicks ? $clicks : 0;

    if ($cloak) {
        $link = home_url('/go/' . $post->post_name);
    } else {
        $link = $url;
    }

    // Increment click count
    update_post_meta($post->ID, '_click_count', $clicks + 1);

    return '<a href="' . esc_url($link) . '" target="_blank" rel="nofollow">' . esc_html($post->post_title) . '</a>';
}
add_shortcode('affiliate_link', 'wp_affiliate_link_shortcode');

// Handle cloaked link redirects
function wp_affiliate_link_manager_redirect() {
    if (isset($_GET['go']) && !empty($_GET['go'])) {
        $slug = sanitize_text_field($_GET['go']);
        $post = get_page_by_path($slug, OBJECT, 'wp_affiliate_link');
        if ($post) {
            $url = get_post_meta($post->ID, '_affiliate_url', true);
            $clicks = get_post_meta($post->ID, '_click_count', true);
            $clicks = $clicks ? $clicks : 0;
            update_post_meta($post->ID, '_click_count', $clicks + 1);
            wp_redirect($url);
            exit;
        }
    }
}
add_action('init', 'wp_affiliate_link_manager_redirect');

// Add rewrite rule for cloaked links
function wp_affiliate_link_manager_rewrite() {
    add_rewrite_rule('^go/([^/]+)/?', 'index.php?go=$matches[1]', 'top');
}
add_action('init', 'wp_affiliate_link_manager_rewrite');

// Add query var
function wp_affiliate_link_manager_query_vars($vars) {
    $vars[] = 'go';
    return $vars;
}
add_filter('query_vars', 'wp_affiliate_link_manager_query_vars');

// Flush rewrite rules on activation
function wp_affiliate_link_manager_activation() {
    wp_affiliate_link_manager_cpt();
    wp_affiliate_link_manager_rewrite();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'wp_affiliate_link_manager_activation');

// Flush rewrite rules on deactivation
function wp_affiliate_link_manager_deactivation() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'wp_affiliate_link_manager_deactivation');
