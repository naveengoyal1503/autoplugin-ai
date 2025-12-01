<?php
/*
Plugin Name: GeoAffiliate Pro
Plugin URI: https://example.com/geoaffiliate-pro
Description: Insert geo-targeted affiliate links and coupons automatically to increase affiliate revenue.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=GeoAffiliate_Pro.php
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class GeoAffiliatePro {
    private $affiliate_links = array();

    public function __construct() {
        // Define affiliate links with country codes
        $this->affiliate_links = array(
            'US' => 'https://affiliate.example.com/us-offer',
            'CA' => 'https://affiliate.example.com/ca-offer',
            'GB' => 'https://affiliate.example.com/uk-offer',
            'DE' => 'https://affiliate.example.com/de-offer',
            'FR' => 'https://affiliate.example.com/fr-offer',
            'default' => 'https://affiliate.example.com/global-offer'
        );

        add_shortcode('geo_affiliate_link', array($this, 'render_affiliate_link'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    public function enqueue_styles() {
        wp_register_style('geoaffiliate-style', plugins_url('geoaffiliate.css', __FILE__));
        wp_enqueue_style('geoaffiliate-style');
    }

    private function get_user_country() {
        // Get user IP
        $ip = $_SERVER['REMOTE_ADDR'];
        // Use external IP geolocation API (free tier example)
        $response = wp_remote_get('https://ipapi.co/' . $ip . '/json/');
        if (is_wp_error($response)) {
            return 'default';
        }
        $data = json_decode(wp_remote_retrieve_body($response));
        if (!empty($data->country)) {
            return $data->country;
        }
        return 'default';
    }

    public function render_affiliate_link($atts) {
        $atts = shortcode_atts(array(
            'text' => 'Check this deal',
            'coupon' => ''
        ), $atts, 'geo_affiliate_link');

        $country = $this->get_user_country();
        $url = isset($this->affiliate_links[$country]) ? $this->affiliate_links[$country] : $this->affiliate_links['default'];

        $coupon_html = '';
        if (!empty($atts['coupon'])) {
            $coupon_html = '<span class="geoaffiliate-coupon">Use Code: ' . esc_html($atts['coupon']) . '</span>';
        }

        $link = '<a class="geoaffiliate-link" href="' . esc_url($url) . '" target="_blank" rel="nofollow noopener">' . esc_html($atts['text']) . '</a> ' . $coupon_html;
        return $link;
    }
}

new GeoAffiliatePro();