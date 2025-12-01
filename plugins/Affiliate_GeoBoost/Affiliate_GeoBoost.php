/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_GeoBoost.php
*/
<?php
/**
 * Plugin Name: Affiliate GeoBoost
 * Description: Automatically inserts geo-targeted affiliate links and coupons to boost conversion rates.
 * Version: 1.0
 * Author: YourName
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Affiliate_GeoBoost {

    private $affiliate_links = [
        // country_code => [link, coupon_code]
        'US' => ['https://affiliatesite.com/us-offer', 'US10OFF'],
        'CA' => ['https://affiliatesite.com/ca-offer', 'CA15OFF'],
        'GB' => ['https://affiliatesite.com/uk-offer', 'UK5OFF'],
        'IN' => ['https://affiliatesite.com/in-offer', 'IN20OFF'],
        // default fallback
        'default' => ['https://affiliatesite.com/global-offer', 'GLOBAL10']
    ];

    public function __construct() {
        add_filter('the_content', [$this, 'insert_affiliate_links']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
    }

    public function enqueue_styles() {
        wp_register_style('affgeo-style', false);
        wp_enqueue_style('affgeo-style');
        $custom_css = ".affiliate-geoboost { background: #f9f9f9; border: 1px solid #ddd; padding: 15px; margin: 20px 0; text-align:center; font-family: Arial,sans-serif; } .affiliate-geoboost a { color: #0073aa; text-decoration: none; font-weight: bold; } .affiliate-geoboost a:hover { text-decoration: underline; }";
        wp_add_inline_style('affgeo-style', $custom_css);
    }

    private function get_visitor_country() {
        if (isset($_SERVER['HTTP_CF_IPCOUNTRY'])) {
            return strtoupper($_SERVER['HTTP_CF_IPCOUNTRY']); // Cloudflare header
        }
        // fallback - very basic IP lookup
        $ip = $_SERVER['REMOTE_ADDR'];
        $response = wp_remote_get("https://ipapi.co/{$ip}/country/");
        if (is_wp_error($response)) return 'default';
        $country = trim(wp_remote_retrieve_body($response));
        return $country ? strtoupper($country) : 'default';
    }

    public function insert_affiliate_links($content) {
        if (!is_singular('post') && !is_page()) return $content;
        $country = $this->get_visitor_country();
        if (!array_key_exists($country, $this->affiliate_links)) {
            $country = 'default';
        }
        $link_data = $this->affiliate_links[$country];
        $affiliate_html = '<div class="affiliate-geoboost">';
        $affiliate_html .= 'Exclusive offer for your region! Use coupon <strong>' . esc_html($link_data[1]) . '</strong> and shop <a href="' . esc_url($link_data) . '" target="_blank" rel="nofollow noopener">here</a>.';
        $affiliate_html .= '</div>';
        return $content . $affiliate_html;
    }

}

new Affiliate_GeoBoost();
