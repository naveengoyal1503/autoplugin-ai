/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Booster.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Booster
 * Description: Create and display affiliate-linked coupons and deals with auto-tracking to boost affiliate revenue.
 * Version: 1.0
 * Author: GeneratedAI
 * Text Domain: affiliate-coupon-booster
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateCouponBooster {
    public function __construct() {
        add_action('init', array($this, 'register_coupon_post_type'));
        add_shortcode('acb_coupons', array($this, 'render_coupons_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_acb_track_click', array($this, 'track_click')); // logged in
        add_action('wp_ajax_nopriv_acb_track_click', array($this, 'track_click')); // guests
    }

    public function register_coupon_post_type() {
        $labels = array(
            'name' => __('Coupons', 'affiliate-coupon-booster'),
            'singular_name' => __('Coupon', 'affiliate-coupon-booster'),
            'add_new_item' => __('Add New Coupon', 'affiliate-coupon-booster'),
            'edit_item' => __('Edit Coupon', 'affiliate-coupon-booster'),
            'new_item' => __('New Coupon', 'affiliate-coupon-booster'),
            'view_item' => __('View Coupon', 'affiliate-coupon-booster'),
            'search_items' => __('Search Coupons', 'affiliate-coupon-booster'),
            'not_found' => __('No coupons found', 'affiliate-coupon-booster'),
            'not_found_in_trash' => __('No coupons found in Trash', 'affiliate-coupon-booster'),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => false,
            'menu_icon' => 'dashicons-tag',
            'supports' => array('title', 'editor', 'custom-fields'),
            'capability_type' => 'post',
        );

        register_post_type('acb_coupon', $args);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('acb_script', plugin_dir_url(__FILE__). 'acb-script.js', array('jquery'), '1.0', true);
        wp_localize_script('acb_script', 'acb_ajax_obj', array('ajax_url' => admin_url('admin-ajax.php')));
        wp_enqueue_style('acb_style', plugin_dir_url(__FILE__) . 'acb-style.css');
    }

    public function render_coupons_shortcode($atts) {
        $args = array(
            'post_type' => 'acb_coupon',
            'posts_per_page' => 10,
            'post_status' => 'publish',
        );
        $coupons = new WP_Query($args);

        if (!$coupons->have_posts()) {
            return '<p>No coupons available at the moment.</p>';
        }

        $output = '<div class="acb-coupons-container">';

        while ($coupons->have_posts()) {
            $coupons->the_post();
            $affiliate_link = get_post_meta(get_the_ID(), 'affiliate_link', true);
            $coupon_code = get_post_meta(get_the_ID(), 'coupon_code', true);
            $expiry = get_post_meta(get_the_ID(), 'expiry_date', true);

            $expiry_text = $expiry ? 'Expires on ' . esc_html($expiry) : 'Valid while supplies last';

            $output .= '<div class="acb-coupon-item">';
            $output .= '<h3>' . get_the_title() . '</h3>';
            $output .= '<p>' . get_the_content() . '</p>';

            if ($coupon_code) {
                $output .= '<div class="acb-coupon-code" tabindex="0">Code: <strong>' . esc_html($coupon_code) . '</strong></div>';
            }

            if ($affiliate_link) {
                $encoded_link = esc_url($affiliate_link);
                $output .= '<a href="#" class="acb-claim-button" data-link="' . $encoded_link . '">Claim Deal</a>';
            }

            $output .= '<small class="acb-expiry">' . $expiry_text . '</small>';
            $output .= '</div>';
        }

        $output .= '</div>';
        wp_reset_postdata();
        return $output;
    }

    public function track_click() {
        if (!isset($_POST['link'])) {
            wp_send_json_error('Missing link data');
            wp_die();
        }

        $link = esc_url_raw($_POST['link']);

        // Simpler tracking: store counts in transient
        $count_key = 'acb_click_count_' . md5($link);
        $count = get_transient($count_key);
        $count = $count ? $count + 1 : 1;
        set_transient($count_key, $count, DAY_IN_SECONDS * 30);

        // Return success with redirect URL
        wp_send_json_success(array('redirect' => $link));

        wp_die();
    }
}
new AffiliateCouponBooster();

// Inline JS for claiming and tracking clicks
add_action('wp_footer', function() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('.acb-claim-button').on('click', function(e) {
            e.preventDefault();
            var link = $(this).data('link');
            var button = $(this);
            $.post(acb_ajax_obj.ajax_url, {
                action: 'acb_track_click',
                link: link
            }, function(response) {
                if (response.success) {
                    window.open(response.data.redirect, '_blank');
                    button.text('Redirecting...');
                } else {
                    alert('Failed to track coupon click.');
                }
            });
        });
    });
    </script>
    <style>
    .acb-coupons-container{display:flex;flex-wrap:wrap;gap:15px;}
    .acb-coupon-item{border:1px solid #ddd;padding:15px;width:100%;max-width:300px;box-shadow:0 0 5px rgba(0,0,0,0.05);background:#fff;border-radius:4px;}
    .acb-coupon-code{background:#f4f4f4;padding:10px;margin-top:10px;cursor:pointer;user-select:none;}
    .acb-claim-button{display:inline-block;margin-top:10px;padding:10px 15px;background:#0073aa;color:#fff;border-radius:3px;text-decoration:none;cursor:pointer;}
    .acb-claim-button:hover{background:#005177;}
    .acb-expiry{display:block;margin-top:8px;font-size:12px;color:#666;}
    </style>
    <?php
});
