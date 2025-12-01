<?php
/*
Plugin Name: Affiliate Coupon & Deal Booster
Plugin URI: https://example.com/affiliate-coupon-booster
Description: Manage affiliate coupons and deals with ease and boost your affiliate revenue.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon___Deal_Booster.php
License: GPL2
*/

if ( ! defined( 'ABSPATH' ) ) { exit; }

class AffiliateCouponBooster {

    public function __construct() {
        add_action( 'init', array( $this, 'register_coupon_post_type' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_coupon_metabox' ) );
        add_action( 'save_post', array( $this, 'save_coupon_meta' ) );
        add_shortcode( 'affiliate_coupons', array( $this, 'coupons_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
    }

    public function register_coupon_post_type() {
        $labels = array(
            'name' => 'Coupons',
            'singular_name' => 'Coupon',
            'add_new' => 'Add New Coupon',
            'add_new_item' => 'Add New Coupon',
            'edit_item' => 'Edit Coupon',
            'new_item' => 'New Coupon',
            'view_item' => 'View Coupon',
            'search_items' => 'Search Coupons',
            'not_found' => 'No coupons found',
            'not_found_in_trash' => 'No coupons found in Trash',
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor'),
            'menu_icon' => 'dashicons-tickets-alt',
        );

        register_post_type( 'aff_coupon', $args );
    }

    public function add_coupon_metabox() {
        add_meta_box(
            'coupon_details',
            'Coupon Details',
            array( $this, 'render_coupon_metabox' ),
            'aff_coupon',
            'normal',
            'high'
        );
    }

    public function render_coupon_metabox( $post ) {
        wp_nonce_field( 'save_coupon_meta', 'coupon_meta_nonce' );

        $affiliate_url = get_post_meta( $post->ID, '_affiliate_url', true );
        $coupon_code = get_post_meta( $post->ID, '_coupon_code', true );
        $expiry_date = get_post_meta( $post->ID, '_expiry_date', true );

        echo '<p><label for="affiliate_url">Affiliate URL:</label><br>';
        echo '<input type="url" id="affiliate_url" name="affiliate_url" value="' . esc_attr( $affiliate_url ) . '" style="width:100%;" required></p>';

        echo '<p><label for="coupon_code">Coupon Code (optional):</label><br>';
        echo '<input type="text" id="coupon_code" name="coupon_code" value="' . esc_attr( $coupon_code ) . '" style="width:100%;"></p>';

        echo '<p><label for="expiry_date">Expiry Date (optional):</label><br>';
        echo '<input type="date" id="expiry_date" name="expiry_date" value="' . esc_attr( $expiry_date ) . '"></p>';
    }

    public function save_coupon_meta( $post_id ) {
        if ( ! isset( $_POST['coupon_meta_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['coupon_meta_nonce'], 'save_coupon_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( isset( $_POST['affiliate_url'] ) ) {
            update_post_meta( $post_id, '_affiliate_url', esc_url_raw( $_POST['affiliate_url'] ) );
        }

        if ( isset( $_POST['coupon_code'] ) ) {
            update_post_meta( $post_id, '_coupon_code', sanitize_text_field( $_POST['coupon_code'] ) );
        }

        if ( isset( $_POST['expiry_date'] ) ) {
            update_post_meta( $post_id, '_expiry_date', sanitize_text_field( $_POST['expiry_date'] ) );
        }
    }

    public function coupons_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'limit' => 10
        ), $atts, 'affiliate_coupons' );

        $today = date( 'Y-m-d' );

        $args = array(
            'post_type' => 'aff_coupon',
            'posts_per_page' => intval( $atts['limit'] ),
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_expiry_date',
                    'value' => $today,
                    'compare' => '>=',
                    'type' => 'DATE'
                ),
                array(
                    'key' => '_expiry_date',
                    'compare' => 'NOT EXISTS'
                )
            ),
            'orderby' => 'date',
            'order' => 'DESC'
        );

        $coupons_query = new WP_Query( $args );

        if ( ! $coupons_query->have_posts() ) {
            return '<p>No valid coupons available at the moment.</p>';
        }

        $output = '<div class="affiliate-coupons-list" style="border:1px solid #ccc;padding:1em;border-radius:5px;">';

        while ( $coupons_query->have_posts() ) {
            $coupons_query->the_post();
            $affiliate_url = esc_url( get_post_meta( get_the_ID(), '_affiliate_url', true ) );
            $coupon_code = esc_html( get_post_meta( get_the_ID(), '_coupon_code', true ) );

            $button_text = $coupon_code ? 'Use Coupon: ' . $coupon_code : 'Grab Deal';

            $output .= '<div class="single-coupon" style="margin-bottom:1em;">';
            $output .= '<h3 style="margin:0 0 .5em 0;">' . get_the_title() . '</h3>';
            $output .= '<div>' . wpautop( get_the_content() ) . '</div>';
            $output .= '<a href="' . $affiliate_url . '" target="_blank" rel="nofollow noopener noreferrer" style="display:inline-block;margin-top:.5em;padding:.5em 1em;background:#0073aa;color:#fff;text-decoration:none;border-radius:3px;">' . $button_text . '</a>';
            $output .= '</div>';
        }

        $output .= '</div>';

        wp_reset_postdata();

        return $output;
    }

    public function enqueue_styles() {
        wp_enqueue_style( 'affiliate-coupon-booster-style', plugin_dir_url( __FILE__ ) . 'style.css', array(), '1.0' );
    }

}

new AffiliateCouponBooster();
