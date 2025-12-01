/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Booster.php
*/
<?php
/**
 * Plugin Name: Affiliate Deal Booster
 * Plugin URI: https://example.com/affiliate-deal-booster
 * Description: Dynamically display geo-targeted affiliate deals and coupons to boost conversions.
 * Version: 1.0
 * Author: YourName
 * License: GPL2
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AffiliateDealBooster {
    public function __construct() {
        add_shortcode('affiliate_deals', array($this, 'render_deals'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('adb-style', plugin_dir_url(__FILE__) . 'style.css');
    }

    // Simple geo-location via free API to get country code
    private function get_visitor_country() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (empty($ip)) {
            return 'US'; // Default if IP not found
        }
        $response = @wp_remote_get("https://ipapi.co/{$ip}/country/");
        if (is_wp_error($response)) {
            return 'US';
        }
        $country = trim(wp_remote_retrieve_body($response));
        if (strlen($country) !== 2) {
            return 'US';
        }
        return $country;
    }

    // Sample affiliate deals array - normally fetched from APIs or database
    private function get_all_deals() {
        return array(
            array(
                'title' => '50% Off on Tech Gadgets',
                'network' => 'Amazon',
                'country' => 'US',
                'link' => 'https://amazon.com/deal-tech-50-off?tag=affiliateID',
                'code' => 'TECH50',
                'description' => 'Huge discount on select tech gadgets.',
            ),
            array(
                'title' => '30% Discount on Fashion',
                'network' => 'ShareASale',
                'country' => 'UK',
                'link' => 'https://shareasale.com/deal-fashion-30?affid=affiliateID',
                'code' => 'FASHION30',
                'description' => 'Save on latest fashion brands.',
            ),
            array(
                'title' => 'Buy 1 Get 1 Free Coffee',
                'network' => 'CJ Affiliate',
                'country' => 'US',
                'link' => 'https://cj.com/deal-coffee-bogo?sid=affiliateID',
                'code' => '',
                'description' => 'Special BOGO coffee offer.',
            ),
            array(
                'title' => '25% off Home Decor',
                'network' => 'Amazon',
                'country' => 'CA',
                'link' => 'https://amazon.ca/deal-home-25?tag=affiliateID',
                'code' => 'HOME25',
                'description' => 'Refresh your home with discounted decor.',
            )
        );
    }

    // Render deals shortcode
    public function render_deals($atts) {
        $country = $this->get_visitor_country();
        $deals = $this->get_all_deals();
        $filtered = array_filter($deals, function ($deal) use ($country) {
            return strtoupper($deal['country']) === strtoupper($country);
        });

        if (empty($filtered)) {
            $filtered = $deals; // Fallback: show all deals if none match
        }

        ob_start();
        echo '<div class="adb-deal-container">';
        foreach ($filtered as $deal) {
            echo '<div class="adb-deal-card">';
            echo '<h3>' . esc_html($deal['title']) . '</h3>';
            echo '<p>' . esc_html($deal['description']) . '</p>';
            if (!empty($deal['code'])) {
                echo '<p><strong>Coupon Code:</strong> <span class="adb-coupon-code">' . esc_html($deal['code']) . '</span></p>';
            }
            echo '<p><a href="' . esc_url($deal['link']) . '" target="_blank" rel="nofollow noopener" class="adb-deal-link">Grab Deal</a></p>';
            echo '</div>';
        }
        echo '</div>';

        return ob_get_clean();
    }
}

new AffiliateDealBooster();
