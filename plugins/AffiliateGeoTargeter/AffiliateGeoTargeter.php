<?php
/*
Plugin Name: AffiliateGeoTargeter
Description: Display region-specific affiliate offers automatically based on visitor geolocation to boost affiliate conversions.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateGeoTargeter.php
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateGeoTargeter {
    public function __construct() {
        add_shortcode('affiliate_offers', array($this, 'render_affiliate_offers'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    // Enqueue minimal styles
    public function enqueue_styles() {
        wp_register_style('agt_style', false);
        wp_enqueue_style('agt_style');
        wp_add_inline_style('agt_style', ".agt-offer{border:1px solid #ccc;padding:10px;margin:10px 0;background:#f9f9f9;border-radius:4px;} .agt-offer a{color:#0073aa;text-decoration:none;} .agt-offer a:hover{color:#005177;}\n");
    }

    // Get visitor IP address
    private function get_visitor_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
    }

    // Get geolocation by IP using a free API
    private function get_geolocation($ip) {
        $response = wp_remote_get("https://ipapi.co/" . $ip . "/json/");
        if (is_wp_error($response)) return false;
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if (!empty($data['country_code'])) {
            return $data['country_code'];
        }
        return false;
    }

    // Holds affiliate offers data (could be replaced by DB or admin interface)
    private function get_offers() {
        return array(
            'US' => array(
                array('link' => 'https://affiliate.example.com/us-product1', 'text' => 'Special US Deal: Save 20% Today!'),
                array('link' => 'https://affiliate.example.com/us-product2', 'text' => 'Exclusive US Coupon Code!')
            ),
            'CA' => array(
                array('link' => 'https://affiliate.example.com/ca-product1', 'text' => 'Canada Exclusive Offer!'),
            ),
            'GB' => array(
                array('link' => 'https://affiliate.example.com/uk-product1', 'text' => 'UK Only Promo: Buy One Get One Free!'),
            ),
            'default' => array(
                array('link' => 'https://affiliate.example.com/global-product', 'text' => 'Global Offer: Check it Out!')
            )
        );
    }

    // Render shortcode output
    public function render_affiliate_offers() {
        $ip = $this->get_visitor_ip();
        $country = $this->get_geolocation($ip);
        $offers = $this->get_offers();

        $output = '<div class="agt-offers">';

        if ($country && isset($offers[$country])) {
            foreach ($offers[$country] as $offer) {
                $output .= '<div class="agt-offer"><a href="' . esc_url($offer['link']) . '" target="_blank" rel="nofollow noopener">' . esc_html($offer['text']) . '</a></div>';
            }
        } else {
            // Show default offers if no geo match
            foreach ($offers['default'] as $offer) {
                $output .= '<div class="agt-offer"><a href="' . esc_url($offer['link']) . '" target="_blank" rel="nofollow noopener">' . esc_html($offer['text']) . '</a></div>';
            }
        }

        $output .= '</div>';
        return $output;
    }
}

new AffiliateGeoTargeter();
