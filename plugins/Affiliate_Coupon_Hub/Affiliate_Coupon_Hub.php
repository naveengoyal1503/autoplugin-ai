/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Hub.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Hub
 * Description: Easily create a coupon hub with affiliate links to increase your affiliate marketing revenue.
 * Version: 1.0
 * Author: YourName
 * Text Domain: affiliate-coupon-hub
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateCouponHub {
    private static $instance = null;
    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    private function __construct() {
        add_action('init', array($this, 'register_coupon_post_type'));
        add_action('add_meta_boxes', array($this, 'add_coupon_meta_box'));
        add_action('save_post', array($this, 'save_coupon_meta')); 
        add_shortcode('affiliate_coupons', array($this, 'render_coupon_list'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function register_coupon_post_type() {
        $labels = array(
            'name' => __('Coupons', 'affiliate-coupon-hub'),
            'singular_name' => __('Coupon', 'affiliate-coupon-hub'),
            'add_new' => __('Add New Coupon', 'affiliate-coupon-hub'),
            'add_new_item' => __('Add New Coupon', 'affiliate-coupon-hub'),
            'edit_item' => __('Edit Coupon', 'affiliate-coupon-hub'),
            'new_item' => __('New Coupon', 'affiliate-coupon-hub'),
            'view_item' => __('View Coupon', 'affiliate-coupon-hub'),
            'search_items' => __('Search Coupons', 'affiliate-coupon-hub'),
            'not_found' => __('No Coupons found', 'affiliate-coupon-hub'),
            'not_found_in_trash' => __('No Coupons found in Trash', 'affiliate-coupon-hub'),
        );
        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'coupons'),
            'supports' => array('title', 'editor'),
            'menu_position' => 20,
            'menu_icon' => 'dashicons-tickets-alt',
        );
        register_post_type('affiliate_coupon', $args);
    }

    public function add_coupon_meta_box() {
        add_meta_box(
            'coupon_details_meta_box',
            __('Coupon Details', 'affiliate-coupon-hub'),
            array($this, 'render_coupon_meta_box'),
            'affiliate_coupon',
            'normal',
            'high'
        );
    }

    public function render_coupon_meta_box($post) {
        wp_nonce_field('save_coupon_meta', 'coupon_meta_nonce');
        $affiliate_link = get_post_meta($post->ID, '_affiliate_link', true);
        $expiration = get_post_meta($post->ID, '_expiration_date', true);
        $coupon_code = get_post_meta($post->ID, '_coupon_code', true);
        echo '<p><label for="affiliate_link">' . __('Affiliate URL:', 'affiliate-coupon-hub') . '</label><br>';
        echo '<input type="url" id="affiliate_link" name="affiliate_link" value="' . esc_attr($affiliate_link) . '" size="50" required></p>';
        echo '<p><label for="coupon_code">' . __('Coupon Code:', 'affiliate-coupon-hub') . '</label><br>';
        echo '<input type="text" id="coupon_code" name="coupon_code" value="' . esc_attr($coupon_code) . '" size="30"></p>';
        echo '<p><label for="expiration_date">' . __('Expiration Date:', 'affiliate-coupon-hub') . '</label><br>';
        echo '<input type="date" id="expiration_date" name="expiration_date" value="' . esc_attr($expiration) . '"></p>';
    }

    public function save_coupon_meta($post_id) {
        if (!isset($_POST['coupon_meta_nonce']) || !wp_verify_nonce($_POST['coupon_meta_nonce'], 'save_coupon_meta')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        if (isset($_POST['affiliate_link'])) {
            update_post_meta($post_id, '_affiliate_link', esc_url_raw($_POST['affiliate_link']));
        }
        if (isset($_POST['expiration_date'])) {
            update_post_meta($post_id, '_expiration_date', sanitize_text_field($_POST['expiration_date']));
        }
        if (isset($_POST['coupon_code'])) {
            update_post_meta($post_id, '_coupon_code', sanitize_text_field($_POST['coupon_code']));
        }
    }

    public function render_coupon_list($atts) {
        $atts = shortcode_atts(array(
            'count' => 10
        ), $atts, 'affiliate_coupons');

        $query = new WP_Query(array(
            'post_type' => 'affiliate_coupon',
            'posts_per_page' => intval($atts['count']),
            'meta_query' => array(
                array(
                    'key' => '_expiration_date',
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE'
                )
            ),
            'orderby' => 'date',
            'order' => 'DESC',
        ));

        if (!$query->have_posts()) {
            return '<p>' . __('No active coupons found.', 'affiliate-coupon-hub') . '</p>';
        }

        ob_start();
        echo '<div class="affiliate-coupon-hub">';
        while ($query->have_posts()) {
            $query->the_post();
            $affiliate_link = esc_url(get_post_meta(get_the_ID(), '_affiliate_link', true));
            $coupon_code = esc_html(get_post_meta(get_the_ID(), '_coupon_code', true));
            $expiration = esc_html(get_post_meta(get_the_ID(), '_expiration_date', true));
            echo '<div class="coupon-item" style="border:1px solid #ccc; padding:10px;margin-bottom:10px;">';
            echo '<h3><a href="' . $affiliate_link . '" target="_blank" rel="nofollow noopener">' . get_the_title() . '</a></h3>';
            if ($coupon_code) {
                echo '<p><strong>' . __('Code:', 'affiliate-coupon-hub') . '</strong> ' . $coupon_code . '</p>';
            }
            echo '<div>' . get_the_content() . '</div>';
            if ($expiration) {
                $date = date_i18n(get_option('date_format'), strtotime($expiration));
                echo '<p><small>' . sprintf(__('Expires on %s', 'affiliate-coupon-hub'), $date) . '</small></p>';
            }
            echo '<p><a class="use-coupon-btn" href="' . $affiliate_link . '" target="_blank" rel="nofollow noopener">' . __('Use Coupon', 'affiliate-coupon-hub') . '</a></p>';
            echo '</div>';
        }
        echo '</div>';
        wp_reset_postdata();
        return ob_get_clean();
    }

    public function enqueue_scripts() {
        wp_register_style('affiliate-coupon-hub-style', false);
        wp_enqueue_style('affiliate-coupon-hub-style');
        $custom_css = ".affiliate-coupon-hub{max-width:600px;margin:0 auto;font-family:Arial,sans-serif;}.coupon-item h3 a{color:#2a7ae2;text-decoration:none;}.use-coupon-btn{background:#2a7ae2;color:#fff;padding:8px 15px;text-decoration:none;border-radius:4px;display:inline-block;margin-top:8px;}";
        wp_add_inline_style('affiliate-coupon-hub-style', $custom_css);
    }
}

AffiliateCouponHub::get_instance();
