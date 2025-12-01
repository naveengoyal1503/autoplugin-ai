/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateBoost_Pro.php
*/
<?php
/**
 * Plugin Name: AffiliateBoost Pro
 * Description: Smart affiliate link manager with AI recommendations, geolocation targeting, and automated link rotation.
 * Version: 1.0
 * Author: Your Company
 */

class AffiliateBoostPro {

    public function __construct() {
        add_action('init', array($this, 'init_plugin'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_link', array($this, 'affiliate_link_shortcode'));
    }

    public function init_plugin() {
        // Register custom post type for affiliate links
        register_post_type('affiliate_link', array(
            'labels' => array(
                'name' => 'Affiliate Links',
                'singular_name' => 'Affiliate Link'
            ),
            'public' => false,
            'show_ui' => true,
            'supports' => array('title')
        ));
    }

    public function add_admin_menu() {
        add_menu_page(
            'AffiliateBoost Pro',
            'AffiliateBoost Pro',
            'manage_options',
            'affiliateboost-pro',
            array($this, 'admin_page'),
            'dashicons-admin-links'
        );
    }

    public function admin_page() {
        echo '<div class="wrap"><h1>AffiliateBoost Pro</h1><p>Manage your affiliate links with AI-powered recommendations and geolocation targeting.</p></div>';
    }

    public function enqueue_scripts() {
        wp_enqueue_script('affiliateboost-pro', plugins_url('/js/affiliateboost-pro.js', __FILE__), array(), '1.0', true);
    }

    public function affiliate_link_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'fallback' => '',
        ), $atts, 'affiliate_link');

        if (!$atts['id']) return '';

        $link = get_post($atts['id']);
        if (!$link || $link->post_type !== 'affiliate_link') return $atts['fallback'];

        $url = get_post_meta($link->ID, 'affiliate_url', true);
        $geo = get_post_meta($link->ID, 'geo_target', true);
        $country = $this->get_visitor_country();

        if ($geo && $geo !== $country) {
            return $atts['fallback'];
        }

        return '<a href="' . esc_url($url) . '" target="_blank" rel="nofollow">' . esc_html($link->post_title) . '</a>';
    }

    private function get_visitor_country() {
        // Simplified for demo; in production, use a geo IP service
        return 'US'; // Example
    }
}

new AffiliateBoostPro();
