<?php
/*
Plugin Name: Affiliate Deal Tracker
Description: Manage and display affiliate coupon deals with tracking capabilities.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Tracker.php
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateDealTracker {
    public function __construct() {
        add_action('init', array($this, 'register_coupon_post_type'));
        add_action('add_meta_boxes', array($this, 'add_coupon_meta_boxes'));
        add_action('save_post', array($this, 'save_coupon_meta')); 
        add_shortcode('aff_deals', array($this, 'display_deals_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function register_coupon_post_type() {
        $labels = array(
            'name' => 'Affiliate Deals',
            'singular_name' => 'Affiliate Deal',
            'add_new' => 'Add New Deal',
            'add_new_item' => 'Add New Affiliate Deal',
            'edit_item' => 'Edit Affiliate Deal',
            'new_item' => 'New Affiliate Deal',
            'view_item' => 'View Affiliate Deal',
            'search_items' => 'Search Deals',
            'not_found' => 'No deals found',
            'not_found_in_trash' => 'No deals found in Trash',
            'all_items' => 'All Affiliate Deals',
            'menu_name' => 'Affiliate Deals',
            'name_admin_bar' => 'Affiliate Deal'
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'show_in_rest' => true,
            'supports' => array('title', 'editor'),
            'menu_icon' => 'dashicons-tag'
        );

        register_post_type('affiliate_deal', $args);
    }

    public function add_coupon_meta_boxes() {
        add_meta_box('deal_details', 'Deal Details', array($this, 'render_deal_meta_box'), 'affiliate_deal', 'normal', 'high');
    }

    public function render_deal_meta_box($post) {
        wp_nonce_field('save_deal_meta', 'deal_meta_nonce');
        $url = get_post_meta($post->ID, '_deal_url', true);
        $code = get_post_meta($post->ID, '_deal_code', true);
        $expiry = get_post_meta($post->ID, '_deal_expiry', true);
        $clicks = get_post_meta($post->ID, '_deal_clicks', true);
        if (!$clicks) $clicks = 0;
        echo '<p><label>Affiliate URL (required):</label><br><input type="url" name="deal_url" value="'.esc_attr($url).'" size="50" required></p>';
        echo '<p><label>Coupon Code (optional):</label><br><input type="text" name="deal_code" value="'.esc_attr($code).'" size="30"></p>';
        echo '<p><label>Expiry Date (optional):</label><br><input type="date" name="deal_expiry" value="'.esc_attr($expiry).'" /></p>';
        echo '<p><strong>Clicks:</strong> '.intval($clicks).'</p>';
    }

    public function save_coupon_meta($post_id) {
        if (!isset($_POST['deal_meta_nonce']) || !wp_verify_nonce($_POST['deal_meta_nonce'], 'save_deal_meta')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        if (isset($_POST['deal_url'])) {
            update_post_meta($post_id, '_deal_url', esc_url_raw($_POST['deal_url']));
        }
        if (isset($_POST['deal_code'])) {
            update_post_meta($post_id, '_deal_code', sanitize_text_field($_POST['deal_code']));
        }
        if (isset($_POST['deal_expiry'])) {
            update_post_meta($post_id, '_deal_expiry', sanitize_text_field($_POST['deal_expiry']));
        }
    }

    public function display_deals_shortcode($atts) {
        $atts = shortcode_atts(array('count'=>5), $atts, 'aff_deals');
        $args = array(
            'post_type' => 'affiliate_deal',
            'posts_per_page' => intval($atts['count']),
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_deal_expiry',
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE'
                ),
                array(
                    'key' => '_deal_expiry',
                    'compare' => 'NOT EXISTS'
                )
            )
        );
        $query = new WP_Query($args);
        if (!$query->have_posts()) return '<p>No deals found.</p>';

        $output = '<div class="aff-deals-list">';
        while ($query->have_posts()) {
            $query->the_post();
            $url = get_post_meta(get_the_ID(), '_deal_url', true);
            $code = get_post_meta(get_the_ID(), '_deal_code', true);
            $expiry = get_post_meta(get_the_ID(), '_deal_expiry', true);

            $title = get_the_title();
            $deal_id = get_the_ID();

            $expiry_html = $expiry ? '<small>Expires on '.esc_html($expiry).'</small>' : '';
            $code_html = $code ? '<p><strong>Coupon Code:</strong> '.esc_html($code).'</p>' : '';

            $link = esc_url(add_query_arg(array('aff_deal_click' => $deal_id), $url));

            $output .= '<div class="aff-deal-item" style="border:1px solid #ccc;padding:10px;margin-bottom:10px;">';
            $output .= '<h3><a href="'.esc_url($link).'" target="_blank" rel="nofollow noopener">'.esc_html($title).'</a></h3>';
            $output .= $code_html;
            $output .= $expiry_html;
            $output .= '</div>';
        }
        wp_reset_postdata();
        $output .= '</div>';
        return $output;
    }

    public function enqueue_scripts() {
        // Add custom styles for the deals display (optional)
        wp_register_style('aff-deals-style', false);
        wp_enqueue_style('aff-deals-style');
        wp_add_inline_style('aff-deals-style', '.aff-deal-item h3 a { color: #0073aa; text-decoration: none; } 
            .aff-deal-item h3 a:hover { text-decoration: underline; }');
    }
}

new AffiliateDealTracker();

// Track clicks and update click count
add_action('template_redirect', function() {
    if (isset($_GET['aff_deal_click'])) {
        $deal_id = intval($_GET['aff_deal_click']);
        $url = get_post_meta($deal_id, '_deal_url', true);
        if ($url) {
            $clicks = get_post_meta($deal_id, '_deal_clicks', true);
            $clicks = $clicks ? intval($clicks) : 0;
            update_post_meta($deal_id, '_deal_clicks', $clicks + 1);
            wp_redirect($url, 301);
            exit;
        }
    }
});