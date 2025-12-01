<?php
/*
Plugin Name: WP Coupon Vault
Description: Create, manage, and display exclusive coupon codes from brands.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Coupon_Vault.php
*/

class WPCouponVault {
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('coupon_vault', array($this, 'shortcode'));
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
            'show_in_rest' => true
        ));
    }

    public function admin_menu() {
        add_submenu_page(
            'edit.php?post_type=coupon',
            'Settings',
            'Settings',
            'manage_options',
            'coupon-vault-settings',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        echo '<div class="wrap"><h1>Coupon Vault Settings</h1><p>Configure your coupon display settings here.</p></div>';
    }

    public function shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 5
        ), $atts, 'coupon_vault');

        $args = array(
            'post_type' => 'coupon',
            'posts_per_page' => $atts['limit']
        );
        $coupons = new WP_Query($args);

        $output = '<div class="coupon-vault">';
        while ($coupons->have_posts()) {
            $coupons->the_post();
            $code = get_post_meta(get_the_ID(), 'coupon_code', true);
            $expiry = get_post_meta(get_the_ID(), 'coupon_expiry', true);
            $output .= '<div class="coupon-item">
                <h3>' . get_the_title() . '</h3>
                <p><strong>Code:</strong> ' . $code . '</p>
                <p><strong>Expires:</strong> ' . $expiry . '</p>
                <p>' . get_the_content() . '</p>
            </div>';
        }
        $output .= '</div>';
        wp_reset_postdata();
        return $output;
    }
}

new WPCouponVault();
