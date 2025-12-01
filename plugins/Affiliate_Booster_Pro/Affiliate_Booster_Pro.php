<?php
/*
Plugin Name: Affiliate Booster Pro
Description: Manage and display affiliate coupons, discounts, and track clicks to boost affiliate revenue.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Booster_Pro.php
Text Domain: affiliate-booster-pro
*/

// Prevent direct access
if(!defined('ABSPATH')) exit;

class AffiliateBoosterPro {
    public function __construct() {
        add_action('init', array($this, 'register_coupon_post_type'));
        add_shortcode('abp_coupons', array($this, 'display_coupons_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_abp_track_click', array($this, 'track_click')); // Ajax tracking
        add_action('wp_ajax_nopriv_abp_track_click', array($this, 'track_click'));
    }

    public function register_coupon_post_type() {
        $labels = array(
            'name' => __('Coupons', 'affiliate-booster-pro'),
            'singular_name' => __('Coupon', 'affiliate-booster-pro'),
            'add_new' => __('Add New Coupon', 'affiliate-booster-pro'),
            'add_new_item' => __('Add New Coupon', 'affiliate-booster-pro'),
            'edit_item' => __('Edit Coupon', 'affiliate-booster-pro'),
            'new_item' => __('New Coupon', 'affiliate-booster-pro'),
            'view_item' => __('View Coupon', 'affiliate-booster-pro'),
            'search_items' => __('Search Coupons', 'affiliate-booster-pro'),
            'not_found' => __('No coupons found', 'affiliate-booster-pro'),
            'not_found_in_trash' => __('No coupons found in Trash', 'affiliate-booster-pro'),
            'menu_name' => __('Coupons', 'affiliate-booster-pro')
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'menu_position' => 20,
            'supports' => array('title','editor'),
            'rewrite' => array('slug' => 'coupons'),
            'show_in_rest' => true,
        );

        register_post_type('abp_coupon', $args);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('abp-main-js', plugin_dir_url(__FILE__). 'abp-main.js', array('jquery'), '1.0', true);
        wp_localize_script('abp-main-js', 'abp_ajax_obj', array('ajax_url' => admin_url('admin-ajax.php')));
        wp_enqueue_style('abp-style', plugin_dir_url(__FILE__). 'abp-style.css');
    }

    public function display_coupons_shortcode($atts) {
        $atts = shortcode_atts(array(
            'count' => 5
        ), $atts);

        $args = array(
            'post_type' => 'abp_coupon',
            'posts_per_page' => intval($atts['count']),
            'post_status' => 'publish'
        );
        $coupons = get_posts($args);

        if(!$coupons) return '<p>No coupons available.</p>';

        ob_start();
        echo '<div class="abp-coupons">';
        foreach($coupons as $coupon) {
            $link = get_post_meta($coupon->ID, '_abp_affiliate_link', true);
            $code = get_post_meta($coupon->ID, '_abp_coupon_code', true);
            $desc = $coupon->post_content ? wp_trim_words($coupon->post_content, 20, '...') : '';
            echo '<div class="abp-coupon">';
            echo '<h3>' . esc_html($coupon->post_title) . '</h3>';
            if($desc) echo '<p>' . esc_html($desc) . '</p>';
            if($code) echo '<p><strong>Coupon Code:</strong> ' . esc_html($code) . '</p>';
            if($link) {
                $url = esc_url($link);
                echo '<a href="#" class="abp-link" data-id="' . esc_attr($coupon->ID) . '" data-url="' . $url . '" target="_blank" rel="nofollow noopener">Use Coupon</a>';
            }
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    public function track_click() {
        if(empty($_POST['coupon_id']) || !is_numeric($_POST['coupon_id'])) {
            wp_send_json_error('Invalid coupon ID');
        }

        $coupon_id = intval($_POST['coupon_id']);
        $clicks = get_post_meta($coupon_id, '_abp_clicks', true);
        $clicks = $clicks ? intval($clicks) + 1 : 1;
        update_post_meta($coupon_id, '_abp_clicks', $clicks);

        if(!empty($_POST['redirect_url'])) {
            wp_send_json_success(array('redirect_url' => esc_url_raw($_POST['redirect_url'])));
        } else {
            wp_send_json_success();
        }
    }
}

new AffiliateBoosterPro();

// Inline JavaScript and CSS files content below - dummy placeholders for simplicity:
// You can alternatively add these contents as separate files in production.

add_action('wp_footer', function() {
    ?>
    <script>
    jQuery(document).ready(function($){
        $('.abp-link').on('click', function(e){
            e.preventDefault();
            var couponId = $(this).data('id');
            var redirectURL = $(this).data('url');
            $.post(
                '<?php echo admin_url('admin-ajax.php'); ?>',
                {
                    action: 'abp_track_click',
                    coupon_id: couponId,
                    redirect_url: redirectURL
                },
                function(response){
                    if(response.success && response.data.redirect_url) {
                        window.open(response.data.redirect_url, '_blank');
                    }
                }
            );
        });
    });
    </script>
    <style>
    .abp-coupons { display: flex; flex-wrap: wrap; gap: 1rem; }
    .abp-coupon { border: 1px solid #ddd; padding: 1rem; width: 100%; max-width: 300px; background: #f9f9f9; border-radius: 4px;}
    .abp-coupon h3 { margin-top: 0; }
    .abp-coupon a.abp-link { display: inline-block; margin-top: 0.5em; padding: 0.4em 0.8em; background: #0073aa; color: #fff; text-decoration: none; border-radius: 3px; }
    .abp-coupon a.abp-link:hover { background: #005177; }
    </style>
    <?php
});
