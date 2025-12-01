/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Manager.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Manager
 * Description: Display exclusive affiliate coupons with tracking links to boost affiliate sales.
 * Version: 1.0
 * Author: OpenAI
 */

// Register custom post type for coupons
add_action('init', 'acm_register_coupon_cpt');
function acm_register_coupon_cpt() {
    $labels = array(
        'name' => 'Coupons',
        'singular_name' => 'Coupon',
        'add_new_item' => 'Add New Coupon',
        'edit_item' => 'Edit Coupon',
        'new_item' => 'New Coupon',
        'view_item' => 'View Coupon',
        'search_items' => 'Search Coupons',
        'not_found' => 'No coupons found',
    );
    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => false,
        'show_in_menu' => true,
        'supports' => array('title', 'editor', 'custom-fields'),
        'menu_icon' => 'dashicons-tickets-alt',
    );
    register_post_type('acm_coupon', $args);
}

// Add metabox for coupon settings (e.g. affiliate URL, coupon code, expiry)
add_action('add_meta_boxes', 'acm_add_coupon_metabox');
function acm_add_coupon_metabox() {
    add_meta_box('acm_coupon_details', 'Coupon Details', 'acm_coupon_metabox_callback', 'acm_coupon', 'normal', 'high');
}
function acm_coupon_metabox_callback($post) {
    wp_nonce_field('acm_save_coupon_details', 'acm_coupon_nonce');

    $affiliate_url = get_post_meta($post->ID, '_acm_affiliate_url', true);
    $coupon_code = get_post_meta($post->ID, '_acm_coupon_code', true);
    $expiry_date = get_post_meta($post->ID, '_acm_expiry_date', true);

    echo '<p><label>Affiliate Link URL:</label><br/><input type="url" name="acm_affiliate_url" value="' . esc_attr($affiliate_url) . '" style="width:100%;" required></p>';
    echo '<p><label>Coupon Code:</label><br/><input type="text" name="acm_coupon_code" value="' . esc_attr($coupon_code) . '" style="width:100%;" required></p>';
    echo '<p><label>Expiry Date (optional):</label><br/><input type="date" name="acm_expiry_date" value="' . esc_attr($expiry_date) . '" style="width:100%;"></p>';
}

// Save coupon metadata
add_action('save_post_acm_coupon', 'acm_save_coupon_details');
function acm_save_coupon_details($post_id) {
    if (!isset($_POST['acm_coupon_nonce']) || !wp_verify_nonce($_POST['acm_coupon_nonce'], 'acm_save_coupon_details')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['acm_affiliate_url'])) {
        update_post_meta($post_id, '_acm_affiliate_url', esc_url_raw($_POST['acm_affiliate_url']));
    }
    if (isset($_POST['acm_coupon_code'])) {
        update_post_meta($post_id, '_acm_coupon_code', sanitize_text_field($_POST['acm_coupon_code']));
    }
    if (isset($_POST['acm_expiry_date'])) {
        update_post_meta($post_id, '_acm_expiry_date', sanitize_text_field($_POST['acm_expiry_date']));
    }
}

// Shortcode to display active coupons
add_shortcode('acm_coupons', 'acm_display_coupons_shortcode');
function acm_display_coupons_shortcode() {
    $args = array(
        'post_type' => 'acm_coupon',
        'posts_per_page' => -1,
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => '_acm_expiry_date',
                'value' => date('Y-m-d'),
                'compare' => '>=',
                'type' => 'DATE'
            ),
            array(
                'key' => '_acm_expiry_date',
                'compare' => 'NOT EXISTS'
            )
        ),
        'orderby' => 'date',
        'order' => 'DESC'
    );
    $coupons = new WP_Query($args);
    if (!$coupons->have_posts()) {
        return '<p>No coupons available at this time.</p>';
    }

    $content = '<div class="acm-coupons">';
    while ($coupons->have_posts()) {
        $coupons->the_post();
        $affiliate_url = get_post_meta(get_the_ID(), '_acm_affiliate_url', true);
        $coupon_code = get_post_meta(get_the_ID(), '_acm_coupon_code', true);

        $content .= '<div class="acm-coupon" style="border:1px solid #ddd;padding:15px;margin-bottom:10px;">';
        $content .= '<h3>' . esc_html(get_the_title()) . '</h3>';
        $content .= '<p>' . wp_kses_post(get_the_content()) . '</p>';
        $content .= '<p><strong>Coupon Code:</strong> <code>' . esc_html($coupon_code) . '</code></p>';
        $content .= '<p><a href="' . esc_url($affiliate_url) . '" target="_blank" rel="noopener noreferrer" style="background:#0073aa;color:#fff;padding:8px 12px;text-decoration:none;border-radius:3px;">Use Coupon</a></p>';
        $content .= '</div>';
    }
    wp_reset_postdata();
    $content .= '</div>';

    return $content;
}

// Enqueue minimal styles
add_action('wp_enqueue_scripts', 'acm_enqueue_styles');
function acm_enqueue_styles() {
    wp_register_style('acm-styles', false);
    wp_enqueue_style('acm-styles');
    wp_add_inline_style('acm-styles', ".acm-coupon code {background: #f4f4f4; padding: 2px 6px; border-radius: 2px; font-family: monospace;}");
}
