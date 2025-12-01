/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: WP Coupon Vault
 * Plugin URI: https://example.com/wp-coupon-vault
 * Description: Create and manage exclusive coupons and deals for affiliate products.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Register Custom Post Type: Coupon
function wcv_register_coupon_post_type() {
    $labels = array(
        'name'                  => _x('Coupons', 'Post type general name', 'wp-coupon-vault'),
        'singular_name'         => _x('Coupon', 'Post type singular name', 'wp-coupon-vault'),
        'menu_name'             => _x('Coupons', 'Admin Menu text', 'wp-coupon-vault'),
        'name_admin_bar'        => _x('Coupon', 'Add New on Toolbar', 'wp-coupon-vault'),
        'add_new'               => __('Add New', 'wp-coupon-vault'),
        'add_new_item'          => __('Add New Coupon', 'wp-coupon-vault'),
        'new_item'              => __('New Coupon', 'wp-coupon-vault'),
        'edit_item'             => __('Edit Coupon', 'wp-coupon-vault'),
        'view_item'             => __('View Coupon', 'wp-coupon-vault'),
        'all_items'             => __('All Coupons', 'wp-coupon-vault'),
        'search_items'          => __('Search Coupons', 'wp-coupon-vault'),
        'not_found'             => __('No coupons found.', 'wp-coupon-vault'),
        'not_found_in_trash'    => __('No coupons found in Trash.', 'wp-coupon-vault'),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'coupon'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 20,
        'supports'           => array('title', 'editor', 'thumbnail', 'excerpt'),
        'menu_icon'          => 'dashicons-tag',
    );

    register_post_type('wcv_coupon', $args);
}
add_action('init', 'wcv_register_coupon_post_type');

// Add Custom Meta Boxes
function wcv_add_coupon_meta_boxes() {
    add_meta_box('wcv_coupon_details', 'Coupon Details', 'wcv_coupon_details_callback', 'wcv_coupon', 'normal', 'high');
}
add_action('add_meta_boxes', 'wcv_add_coupon_meta_boxes');

function wcv_coupon_details_callback($post) {
    wp_nonce_field('wcv_save_coupon_details', 'wcv_coupon_nonce');
    $code = get_post_meta($post->ID, '_wcv_coupon_code', true);
    $url = get_post_meta($post->ID, '_wcv_coupon_url', true);
    $expiry = get_post_meta($post->ID, '_wcv_coupon_expiry', true);
    $store = get_post_meta($post->ID, '_wcv_coupon_store', true);
    ?>
    <p>
        <label for="wcv_coupon_code">Coupon Code:</label>
        <input type="text" id="wcv_coupon_code" name="wcv_coupon_code" value="<?php echo esc_attr($code); ?>" class="widefat">
    </p>
    <p>
        <label for="wcv_coupon_url">Affiliate URL:</label>
        <input type="url" id="wcv_coupon_url" name="wcv_coupon_url" value="<?php echo esc_attr($url); ?>" class="widefat">
    </p>
    <p>
        <label for="wcv_coupon_expiry">Expiry Date:</label>
        <input type="date" id="wcv_coupon_expiry" name="wcv_coupon_expiry" value="<?php echo esc_attr($expiry); ?>" class="widefat">
    </p>
    <p>
        <label for="wcv_coupon_store">Store Name:</label>
        <input type="text" id="wcv_coupon_store" name="wcv_coupon_store" value="<?php echo esc_attr($store); ?>" class="widefat">
    </p>
    <?php
}

function wcv_save_coupon_details($post_id) {
    if (!isset($_POST['wcv_coupon_nonce']) || !wp_verify_nonce($_POST['wcv_coupon_nonce'], 'wcv_save_coupon_details')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['wcv_coupon_code'])) {
        update_post_meta($post_id, '_wcv_coupon_code', sanitize_text_field($_POST['wcv_coupon_code']));
    }
    if (isset($_POST['wcv_coupon_url'])) {
        update_post_meta($post_id, '_wcv_coupon_url', esc_url_raw($_POST['wcv_coupon_url']));
    }
    if (isset($_POST['wcv_coupon_expiry'])) {
        update_post_meta($post_id, '_wcv_coupon_expiry', sanitize_text_field($_POST['wcv_coupon_expiry']));
    }
    if (isset($_POST['wcv_coupon_store'])) {
        update_post_meta($post_id, '_wcv_coupon_store', sanitize_text_field($_POST['wcv_coupon_store']));
    }
}
add_action('save_post', 'wcv_save_coupon_details');

// Shortcode to Display Coupons
function wcv_display_coupons_shortcode($atts) {
    $atts = shortcode_atts(array(
        'limit' => 10,
        'store' => '',
    ), $atts, 'wcv_coupons');

    $args = array(
        'post_type'      => 'wcv_coupon',
        'posts_per_page' => $atts['limit'],
        'post_status'    => 'publish',
    );

    if (!empty($atts['store'])) {
        $args['meta_query'] = array(
            array(
                'key'     => '_wcv_coupon_store',
                'value'   => $atts['store'],
                'compare' => 'LIKE',
            ),
        );
    }

    $coupons = new WP_Query($args);
    $output = '<div class="wcv-coupons-grid">';

    if ($coupons->have_posts()) {
        while ($coupons->have_posts()) {
            $coupons->the_post();
            $code = get_post_meta(get_the_ID(), '_wcv_coupon_code', true);
            $url = get_post_meta(get_the_ID(), '_wcv_coupon_url', true);
            $expiry = get_post_meta(get_the_ID(), '_wcv_coupon_expiry', true);
            $store = get_post_meta(get_the_ID(), '_wcv_coupon_store', true);
            $output .= '<div class="wcv-coupon">
                <h3>' . get_the_title() . '</h3>
                <p><strong>Store:</strong> ' . esc_html($store) . '</p>
                <p><strong>Coupon Code:</strong> <span class="wcv-code">' . esc_html($code) . '</span></p>
                <p><strong>Expires:</strong> ' . esc_html($expiry) . '</p>
                <a href="' . esc_url($url) . '" target="_blank" class="button">Get Deal</a>
            </div>';
        }
        wp_reset_postdata();
    } else {
        $output .= '<p>No coupons found.</p>';
    }

    $output .= '</div>';
    return $output;
}
add_shortcode('wcv_coupons', 'wcv_display_coupons_shortcode');

// Enqueue Styles
function wcv_enqueue_styles() {
    wp_enqueue_style('wcv-styles', plugin_dir_url(__FILE__) . 'css/wcv-styles.css', array(), '1.0');
}
add_action('wp_enqueue_scripts', 'wcv_enqueue_styles');

// Create CSS directory and file if not exists
function wcv_create_css_file() {
    $upload_dir = wp_upload_dir();
    $css_dir = $upload_dir['basedir'] . '/wcv-css';
    $css_file = $css_dir . '/wcv-styles.css';

    if (!file_exists($css_dir)) {
        wp_mkdir_p($css_dir);
    }

    if (!file_exists($css_file)) {
        $css = ".wcv-coupons-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
.wcv-coupon { border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
.wcv-coupon .button { display: inline-block; margin-top: 10px; padding: 8px 16px; background: #0073aa; color: #fff; text-decoration: none; border-radius: 3px; }
.wcv-coupon .button:hover { background: #005a87; }";
        file_put_contents($css_file, $css);
    }
}
add_action('init', 'wcv_create_css_file');

// Admin Menu Page for Settings (Placeholder for future premium features)
function wcv_admin_menu() {
    add_options_page('WP Coupon Vault Settings', 'Coupon Vault', 'manage_options', 'wcv-settings', 'wcv_settings_page');
}
add_action('admin_menu', 'wcv_admin_menu');

function wcv_settings_page() {
    echo '<div class="wrap"><h1>WP Coupon Vault Settings</h1><p>Settings page for premium features (analytics, bulk import, etc.).</p></div>';
}
?>