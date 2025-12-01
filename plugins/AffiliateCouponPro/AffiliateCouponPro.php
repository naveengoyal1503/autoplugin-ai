/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateCouponPro.php
*/
<?php
/**
 * Plugin Name: AffiliateCouponPro
 * Description: Automatically fetches affiliate coupons from multiple sources and displays dynamic deals to increase affiliate commissions.
 * Version: 1.0
 * Author: YourName
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateCouponPro {
    private $affiliate_links = [
        // Example affiliate programs with coupon RSS feeds or APIs
        // Users can add their own affiliate coupon sources here
        'ExampleAffiliate1' => 'https://example.com/coupons/feed/',
        'ExampleAffiliate2' => 'https://example2.com/api/v1/coupons.json',
    ];

    public function __construct() {
        add_shortcode('affiliate_coupons', [$this, 'display_coupons_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets() {
        wp_enqueue_style('affiliatecouponpro-style', plugin_dir_url(__FILE__) . 'style.css');
    }

    private function fetch_coupons() {
        $coupons = [];
        foreach ($this->affiliate_links as $name => $url) {
            $response = wp_remote_get($url, ['timeout' => 5]);
            if (is_wp_error($response)) continue;
            $body = wp_remote_retrieve_body($response);
            if (!$body) continue;

            // Simple parser for RSS or JSON-based coupon feeds
            if (strpos($url, '.json') !== false) {
                $data = json_decode($body, true);
                if (is_array($data) && isset($data['coupons'])) {
                    foreach ($data['coupons'] as $coupon) {
                        $coupons[] = [
                            'title' => sanitize_text_field($coupon['title']),
                            'link' => esc_url($coupon['affiliate_url']),
                            'code' => sanitize_text_field($coupon['code'] ?? ''),
                            'description' => sanitize_text_field($coupon['description'] ?? ''),
                            'source' => $name,
                        ];
                    }
                }
            } else {
                // Parsing simple RSS format
                $xml = @simplexml_load_string($body);
                if ($xml && isset($xml->channel->item)) {
                    foreach ($xml->channel->item as $item) {
                        $coupons[] = [
                            'title' => esc_html($item->title),
                            'link' => esc_url($item->link),
                            'code' => '',
                            'description' => esc_html($item->description),
                            'source' => $name,
                        ];
                    }
                }
            }
        }
        return $coupons;
    }

    public function display_coupons_shortcode() {
        $coupons = $this->fetch_coupons();
        if (empty($coupons)) {
            return '<p>No coupons available at this time. Please check back later.</p>';
        }

        shuffle($coupons); // Shuffle for variety
        $output = '<div class="affiliatecouponpro-container">';
        $max_display = 10;
        $count = 0;
        foreach ($coupons as $coupon) {
            if ($count++ >= $max_display) break;
            $code_html = $coupon['code'] ? '<span class="coupon-code">' . esc_html($coupon['code']) . '</span>' : '';
            $output .= '<div class="affiliatecouponpro-coupon">';
            $output .= '<a href="' . esc_url($coupon['link']) . '" target="_blank" rel="nofollow noopener noreferrer">';
            $output .= '<h3 class="coupon-title">' . esc_html($coupon['title']) . '</h3>' . $code_html;
            $output .= '<p class="coupon-desc">' . esc_html($coupon['description']) . '</p>';
            $output .= '<small class="coupon-source">Source: ' . esc_html($coupon['source']) . '</small>';
            $output .= '</a></div>';
        }
        $output .= '</div>';
        return $output;
    }
}

new AffiliateCouponPro();