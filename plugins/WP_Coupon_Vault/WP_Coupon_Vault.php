<?php
/*
Plugin Name: WP Coupon Vault
Description: Create and manage exclusive coupons and deals for your audience.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Coupon_Vault.php
*/

class WPCouponVault {
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_shortcode('coupon_vault', array($this, 'display_coupons'));
    }

    public function register_post_type() {
        register_post_type('coupon', array(
            'labels' => array(
                'name' => 'Coupons',
                'singular_name' => 'Coupon'
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'custom-fields'),
            'menu_icon' => 'dashicons-tag'
        ));
    }

    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=coupon',
            'Settings',
            'Settings',
            'manage_options',
            'coupon-settings',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        echo '<div class="wrap"><h1>WP Coupon Vault Settings</h1>';
        echo '<p>Upgrade to premium for unlimited coupons, analytics, and brand management.</p>';
        echo '<a href="https://example.com/premium" target="_blank">Upgrade Now</a>';
        echo '</div>';
    }

    public function display_coupons($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts);
        $args = array(
            'post_type' => 'coupon',
            'posts_per_page' => $atts['limit'],
            'post_status' => 'publish'
        );
        $coupons = new WP_Query($args);
        $output = '<div class="coupon-vault">';
        while ($coupons->have_posts()) {
            $coupons->the_post();
            $code = get_post_meta(get_the_ID(), 'coupon_code', true);
            $url = get_post_meta(get_the_ID(), 'affiliate_url', true);
            $output .= '<div class="coupon-item">
                <h3>' . get_the_title() . '</h3>
                <p>' . get_the_content() . '</p>
                <p><strong>Code:</strong> ' . esc_html($code) . '</p>
                <a href="' . esc_url($url) . '" target="_blank">Get Deal</a>
            </div>';
        }
        $output .= '</div>';
        wp_reset_postdata();
        return $output;
    }
}

new WPCouponVault();
