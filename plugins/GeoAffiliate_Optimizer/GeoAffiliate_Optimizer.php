/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=GeoAffiliate_Optimizer.php
*/
<?php
/**
 * Plugin Name: GeoAffiliate Optimizer
 * Description: Show geolocation-targeted affiliate offers and coupons automatically.
 * Version: 1.0
 * Author: OpenAI
 * License: GPL2
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class GeoAffiliateOptimizer {
    private $supported_countries;
    private $cookie_name = 'geoaffiliate_country';

    public function __construct() {
        $this->supported_countries = array(
            'US' => array('affiliate_url' => 'https://affiliate.example.com/us-deal', 'coupon' => 'US10OFF'),
            'GB' => array('affiliate_url' => 'https://affiliate.example.com/uk-deal', 'coupon' => 'UK10OFF'),
            'CA' => array('affiliate_url' => 'https://affiliate.example.com/ca-deal', 'coupon' => 'CA10OFF'),
            'AU' => array('affiliate_url' => 'https://affiliate.example.com/au-deal', 'coupon' => 'AU10OFF'),
            'default' => array('affiliate_url' => 'https://affiliate.example.com/global-deal', 'coupon' => 'GLOBAL10')
        );

        add_shortcode('geo_affiliate_offer', array($this, 'display_affiliate_offer'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('init', array($this, 'detect_geolocation'));
    }

    public function enqueue_styles() {
        wp_register_style('geoaffiliate-style', plugins_url('style.css', __FILE__));
        wp_enqueue_style('geoaffiliate-style');
    }

    // Detect visitor country via IP (using freegeoip.app)
    public function detect_geolocation() {
        if (!isset($_COOKIE[$this->cookie_name])) {
            $ip = $this->get_ip_address();
            $country_code = $this->get_country_from_ip($ip);
            if (!$country_code || !array_key_exists($country_code, $this->supported_countries)) {
                $country_code = 'default';
            }
            setcookie($this->cookie_name, $country_code, time() + DAY_IN_SECONDS * 7, COOKIEPATH, COOKIE_DOMAIN);
            $_COOKIE[$this->cookie_name] = $country_code;
        }
    }

    private function get_ip_address() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return sanitize_text_field(trim($ip_list));
        } else {
            return sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }
    }

    private function get_country_from_ip($ip) {
        $response = wp_remote_get('https://freegeoip.app/json/' . $ip, array('timeout' => 2));
        if (is_wp_error($response)) return false;
        $body = wp_remote_retrieve_body($response);
        if (!$body) return false;
        $data = json_decode($body, true);
        if (isset($data['country_code'])) {
            return $data['country_code'];
        }
        return false;
    }

    public function display_affiliate_offer($atts) {
        $country = isset($_COOKIE[$this->cookie_name]) ? $_COOKIE[$this->cookie_name] : 'default';
        $data = $this->supported_countries[$country];

        $offer_html = '<div class="geoaffiliate-offer">';
        $offer_html .= '<h3>Special Offer For You</h3>';
        $offer_html .= '<p>Use coupon <strong>' . esc_html($data['coupon']) . '</strong> at checkout!</p>';
        $offer_html .= '<a class="geoaffiliate-button" href="' . esc_url($data['affiliate_url']) . '" target="_blank" rel="nofollow noopener">Get Your Deal Now</a>';
        $offer_html .= '</div>';

        return $offer_html;
    }
}

new GeoAffiliateOptimizer();
