<?php
/*
Plugin Name: GeoPromoAffiliate
Description: Automatically inserts cloaked affiliate links with geolocation targeting and scheduled promotions.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=GeoPromoAffiliate.php
*/

if (!defined('ABSPATH')) exit;

class GeoPromoAffiliate {
    private $geo_api = 'https://ipapi.co/json';
    private $user_country = '';
    private $affiliate_links = array();

    public function __construct() {
        add_shortcode('geo_affiliate_link', array($this, 'geo_affiliate_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('init', array($this, 'maybe_replace_links'));
        $this->load_affiliate_links();
        $this->detect_user_country();
    }

    private function detect_user_country() {
        if (isset($_COOKIE['gpa_user_country'])) {
            $this->user_country = sanitize_text_field($_COOKIE['gpa_user_country']);
            return;
        }
        $resp = wp_remote_get($this->geo_api, array('timeout' => 2));
        if (!is_wp_error($resp)) {
            $data = json_decode(wp_remote_retrieve_body($resp), true);
            if (!empty($data['country_code'])) {
                $this->user_country = $data['country_code'];
                setcookie('gpa_user_country', $this->user_country, time()+3600*24*7, COOKIEPATH, COOKIE_DOMAIN);
            }
        }
    }

    private function load_affiliate_links() {
        // For demo, hardcoded set. In premium, this would be admin-configurable.
        $this->affiliate_links = array(
            array(
                'id' => 'prod1',
                'url' => 'https://affiliate.example.com/product1?ref=geo',
                'countries' => array('US', 'CA'),
                'start_date' => '2025-12-01',
                'end_date' => '2025-12-31',
                'title' => 'Product 1 Special Offer'
            ),
            array(
                'id' => 'prod2',
                'url' => 'https://affiliate.example.com/product2?ref=geo',
                'countries' => array('GB', 'IE'),
                'start_date' => '2025-11-25',
                'end_date' => '2025-12-15',
                'title' => 'Product 2 Holiday Sale'
            ),
            array(
                'id' => 'prod3',
                'url' => 'https://affiliate.example.com/product3?ref=geo',
                'countries' => array(), // default catch all
                'start_date' => '2025-12-01',
                'end_date' => '2026-01-01',
                'title' => 'Product 3 Year End Deal'
            ),
        );
    }

    public function geo_affiliate_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        if (empty($atts['id'])) return '';

        $link = $this->get_affiliate_link($atts['id']);
        if (!$link) return $content ? $content : '';

        $title = $link['title'];
        $url = $this->cloak_url($link['url']);

        $anchor_text = $content ? $content : $title;

        return '<a href="' . esc_url($url) . '" target="_blank" rel="nofollow noopener noreferrer">' . esc_html($anchor_text) . '</a>';
    }

    private function get_affiliate_link($id) {
        $now = current_time('Y-m-d');
        foreach ($this->affiliate_links as $link) {
            if ($link['id'] === $id) {
                // Check date
                if ($now >= $link['start_date'] && $now <= $link['end_date']) {
                    // Check country
                    if (empty($link['countries']) || in_array($this->user_country, $link['countries'])) {
                        return $link;
                    }
                }
            }
        }
        return false;
    }

    private function cloak_url($url) {
        // Simple cloaking: redirect through plugin endpoint
        return home_url('/gpa-redirect/?url=' . urlencode($url));
    }

    public function maybe_replace_links() {
        if (isset($_GET['gpa-redirect'])) {
            $url = esc_url_raw($_GET['url']);
            if (!empty($url)) {
                // Track click if needed here (extension)
                wp_redirect($url);
                exit;
            }
        }
    }

    public function enqueue_scripts() {
        // Optionally enqueue scripts for future, e.g., analytics
    }
}

new GeoPromoAffiliate();