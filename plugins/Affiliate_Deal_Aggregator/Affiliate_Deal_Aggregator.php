/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Aggregator.php
*/
<?php
/**
 * Plugin Name: Affiliate Deal Aggregator
 * Description: Aggregates affiliate coupons and deals dynamically and displays them with affiliate tracking links.
 * Version: 1.0
 * Author: WP Monetize
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AffiliateDealAggregator {
    private $offers = [];
    private $transient_key = 'ada_affiliate_deals';
    private $affiliate_param = 'ref=wpdeals';

    public function __construct() {
        add_shortcode('affiliate_deals', [$this, 'shortcode_display_deals']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('init', [$this, 'maybe_refresh_cache']);
    }

    // Enqueue minimal CSS
    public function enqueue_scripts() {
        wp_enqueue_style('ada-style', plugin_dir_url(__FILE__) . 'style.css');
    }

    // Fetch deals from simulated affiliate APIs or static JSON
    private function fetch_deals() {
        // For demonstration, hardcoded sample deals from different affiliate networks
        $sample_deals = [
            [
                'title' => '50% off Stylish Sneakers',
                'link' => 'https://affiliate.vendor1.com/product1',
                'description' => 'Limited time 50% discount on top sneaker brand.',
                'expires' => '2026-01-15',
                'affiliate_code' => 'vendor1'
            ],
            [
                'title' => '30% off Home Office Chairs',
                'link' => 'https://affiliate.vendor2.com/chairs',
                'description' => 'Ergonomic chairs at 30% discount.',
                'expires' => '2025-12-31',
                'affiliate_code' => 'vendor2'
            ],
            [
                'title' => '20% cashback on Electronics',
                'link' => 'https://affiliate.vendor3.com/electronics',
                'description' => 'Get 20% cashback when shopping electronics.',
                'expires' => '2026-02-10',
                'affiliate_code' => 'vendor3'
            ]
        ];
        return $sample_deals;
    }

    // Add affiliate tracking param to URLs
    private function add_affiliate_param($url, $affiliate_code) {
        $param = 'ref=' . urlencode($affiliate_code . '_wpdeals');
        if (strpos($url, '?') !== false) {
            return $url . '&' . $param;
        } else {
            return $url . '?' . $param;
        }
    }

    // Cache and serve offers
    public function maybe_refresh_cache() {
        $cached = get_transient($this->transient_key);
        if ($cached === false) {
            $deals = $this->fetch_deals();
            set_transient($this->transient_key, $deals, DAY_IN_SECONDS);
        }
    }

    // Shortcode handler to render deals
    public function shortcode_display_deals($atts) {
        $deals = get_transient($this->transient_key);
        if (!$deals) {
            $deals = $this->fetch_deals();
            set_transient($this->transient_key, $deals, DAY_IN_SECONDS);
        }
        $output = '<div class="ada-deals-container">';
        $output .= '<h2>Exclusive Affiliate Deals</h2>';
        if (empty($deals)) {
            $output .= '<p>No current deals available. Check back later.</p>';
        } else {
            $output .= '<ul class="ada-deals-list">';
            foreach ($deals as $deal) {
                $url = esc_url($this->add_affiliate_param($deal['link'], $deal['affiliate_code']));
                $title = esc_html($deal['title']);
                $desc = esc_html($deal['description']);
                $expires = esc_html($deal['expires']);
                $output .= "<li class='ada-deal-item'>";
                $output .= "<a href='$url' target='_blank' rel='nofollow noopener'>$title</a>";
                $output .= "<p>$desc</p>";
                $output .= "<small>Expires: $expires</small>";
                $output .= "</li>";
            }
            $output .= '</ul>';
        }
        $output .= '</div>';
        return $output;
    }
}

new AffiliateDealAggregator();