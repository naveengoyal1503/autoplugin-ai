/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: WP Coupon Vault
 * Description: Manage and display exclusive coupons and deals on your WordPress site.
 * Version: 1.0
 * Author: Cozmo Labs
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPCouponVault {

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('coupon_vault', array($this, 'shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
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

    public function admin_menu() {
        add_submenu_page(
            'edit.php?post_type=coupon',
            'Settings',
            'Settings',
            'manage_options',
            'coupon_vault_settings',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        echo '<div class="wrap"><h2>Coupon Vault Settings</h2><p>Configure your coupon display options here.</p></div>';
    }

    public function shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 10,
            'category' => ''
        ), $atts);

        $args = array(
            'post_type' => 'coupon',
            'posts_per_page' => $atts['limit'],
            'tax_query' => !empty($atts['category']) ? array(
                array(
                    'taxonomy' => 'category',
                    'field' => 'slug',
                    'terms' => $atts['category']
                )
            ) : array()
        );

        $coupons = new WP_Query($args);
        $output = '<div class="coupon-vault">';
        while ($coupons->have_posts()) {
            $coupons->the_post();
            $code = get_post_meta(get_the_ID(), '_coupon_code', true);
            $url = get_post_meta(get_the_ID(), '_coupon_url', true);
            $output .= '<div class="coupon-item">
                <h3>' . get_the_title() . '</h3>
                <p>' . get_the_content() . '</p>
                <p><strong>Code:</strong> ' . $code . '</p>
                <a href="' . $url . '" target="_blank" class="coupon-link">Get Deal</a>
            </div>';
        }
        $output .= '</div>';
        wp_reset_postdata();
        return $output;
    }

    public function enqueue_styles() {
        wp_enqueue_style('coupon-vault-style', plugin_dir_url(__FILE__) . 'style.css');
    }
}

new WPCouponVault();
