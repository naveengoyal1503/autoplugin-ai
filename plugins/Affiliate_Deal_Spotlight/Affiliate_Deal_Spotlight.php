<?php
/*
Plugin Name: Affiliate Deal Spotlight
Description: Aggregates and displays geo-targeted affiliate coupon deals with tracking to boost commissions.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Spotlight.php
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateDealSpotlight {

    private $version = '1.0';
    private $plugin_slug = 'affiliate-deal-spotlight';
    private $cookie_name = 'ads_geo';

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_deals', array($this, 'deals_shortcode'));
        add_action('wp_ajax_ads_track_click', array($this, 'track_click')); // Logged-in
        add_action('wp_ajax_nopriv_ads_track_click', array($this, 'track_click'));// Guests
        add_action('init', array($this, 'set_geo_cookie'));
    }

    // Enqueue necessary JS and CSS
    public function enqueue_scripts() {
        wp_enqueue_style($this->plugin_slug . '-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_enqueue_script($this->plugin_slug . '-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), $this->version, true);
        wp_localize_script($this->plugin_slug . '-script', 'adsAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'cookie' => $this->cookie_name
        ));
    }

    // Detect visitor country using simple GeoIP via API and set a cookie
    public function set_geo_cookie() {
        if (!isset($_COOKIE[$this->cookie_name])) {
            $ip = $_SERVER['REMOTE_ADDR'];
            $country = $this->get_country_by_ip($ip);
            if (!$country) $country = 'us';
            setcookie($this->cookie_name, $country, time()+3600*24*7, COOKIEPATH, COOKIE_DOMAIN);
            $_COOKIE[$this->cookie_name] = $country;
        }
    }

    private function get_country_by_ip($ip) {
        // Use a free geoip API to get country code (2-letter)
        $response = wp_remote_get('https://ipapi.co/' . $ip . '/country/');
        if (is_wp_error($response)) return false;
        $code = wp_remote_retrieve_body($response);
        if ($code && preg_match('/^[a-z]{2}$/i', $code)) return strtolower($code);
        return false;
    }

    // Shortcode handler to output deals
    public function deals_shortcode($atts) {
        // Map sample affiliate deals with countries
        $deals = $this->get_sample_deals();
        $user_country = isset($_COOKIE[$this->cookie_name]) ? sanitize_text_field($_COOKIE[$this->cookie_name]) : 'us';

        $output = '<div class="ads-deal-spotlight">';
        $count = 0;

        foreach ($deals as $deal) {
            if (in_array($user_country, $deal['countries']) || in_array('all', $deal['countries'])) {
                $count++;
                // Each deal block
                $output .= '<div class="ads-deal">';
                $output .= '<h3 class="ads-deal-title">'.esc_html($deal['title']).'</h3>';
                $output .= '<p class="ads-deal-desc">'.esc_html($deal['description']).'</p>';
                $output .= '<a href="#" class="ads-deal-link" data-affurl="'.esc_url($deal['affiliate_url']).'" data-dealid="'.esc_attr($deal['id']).'" target="_blank" rel="nofollow noopener">Grab Deal</a>';
                $output .= '</div>';
            }
        }
        if ($count === 0) {
            $output .= '<p>No deals available for your location at the moment. Check back soon!</p>';
        }
        $output .= '</div>';

        return $output;
    }

    // Sample deals data - in practice, this would be dynamic or from external API
    private function get_sample_deals() {
        return array(
            array(
                'id' => 1,
                'title' => '50% Off Electronics - US & CA Only',
                'description' => 'Save half price on select electronics brands. Limited time offer!',
                'affiliate_url' => 'https://affiliate-network.com/deal1?ref=pluginuser',
                'countries' => array('us','ca')
            ),
            array(
                'id' => 2,
                'title' => '30% Discount on Fashion Apparel - Global',
                'description' => 'Exclusive 30% off for our visitors worldwide on top fashion stores.',
                'affiliate_url' => 'https://affiliate-network.com/deal2?ref=pluginuser',
                'countries' => array('all')
            ),
            array(
                'id' => 3,
                'title' => 'Free Shipping on Orders Over $50 - UK',
                'description' => 'Enjoy free shipping on any purchase above $50 in the UK.',
                'affiliate_url' => 'https://affiliate-network.com/deal3?ref=pluginuser',
                'countries' => array('gb')
            )
        );
    }

    // AJAX handler to track clicks
    public function track_click() {
        $deal_id = isset($_POST['deal_id']) ? intval($_POST['deal_id']) : 0;
        if ($deal_id > 0) {
            $count_key = 'ads_deal_clicks_' . $deal_id;
            $current = (int) get_option($count_key, 0);
            update_option($count_key, $current + 1);
            wp_send_json_success(array('clicked' => true));
        } else {
            wp_send_json_error(array('error' => 'Invalid deal id'));
        }
        wp_die();
    }

}

new AffiliateDealSpotlight();

// Inline simple CSS for demo
add_action('wp_head', function(){
    echo '<style>.ads-deal-spotlight{border:1px solid #ccc;padding:15px;background:#f9f9f9;max-width:500px;margin:15px auto;font-family:Arial,sans-serif;}.ads-deal{border-bottom:1px solid #ddd;padding:10px 0;}.ads-deal:last-child{border:none;}.ads-deal-title{margin:0 0 5px;color:#0073aa;}.ads-deal-desc{margin:0 0 10px;color:#555;}.ads-deal-link{background:#0073aa;color:#fff;padding:7px 12px;text-decoration:none;border-radius:3px;transition:background 0.3s;} .ads-deal-link:hover{background:#005177;}</style>';
});

// Inline JS for click tracking
add_action('wp_footer', function(){
    ?>
    <script>
    jQuery(document).ready(function($){
        $('.ads-deal-link').on('click', function(e){
            var link = $(this);
            var dealID = link.data('dealid');
            var affURL = link.data('affurl');
            // Send click data
            $.post(adsAjax.ajaxurl, {
                action: 'ads_track_click',
                deal_id: dealID
            });
            // Redirect to affiliate URL
            window.open(affURL, '_blank');
            e.preventDefault();
        });
    });
    </script>
    <?php
});