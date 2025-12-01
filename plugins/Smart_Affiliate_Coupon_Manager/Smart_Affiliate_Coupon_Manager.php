/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupon_Manager.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupon Manager
 * Description: Automatically displays geo-targeted affiliate coupons and deals to increase conversions.
 * Version: 1.0
 * Author: GeneratedAI
 */

if (!defined('ABSPATH')) exit;

class SmartAffiliateCouponManager {
    private $coupons;

    public function __construct() {
        add_shortcode('smart_affiliate_coupons', [$this, 'render_coupons']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        // Load coupons from DB or default
        $this->coupons = get_option('sacm_coupons', $this->default_coupons());
    }

    public function enqueue_assets() {
        wp_enqueue_style('sacm-style', plugin_dir_url(__FILE__) . 'style.css');
    }

    private function default_coupons() {
        return [
            [
                'title' => '10% Off Electronics',
                'code' => 'ELEC10',
                'url' => 'https://affiliate.example.com/electronics?ref=smartaff',
                'countries' => ['US', 'CA'],
                'description' => 'Save 10% on all electronics purchases.',
                'expiry' => ''
            ],
            [
                'title' => '15% Off Fashion',
                'code' => 'FASH15',
                'url' => 'https://affiliate.example.com/fashion?ref=smartaff',
                'countries' => ['GB', 'AU'],
                'description' => 'Get 15% off on fashion items.',
                'expiry' => ''
            ]
        ];
    }

    private function get_user_country() {
        $ip = $_SERVER['REMOTE_ADDR'];
        // Simple geo IP using free service
        $response = wp_remote_get('https://ipapi.co/' . $ip . '/country/');
        if (is_wp_error($response)) {
            return '';
        }
        $country = trim(wp_remote_retrieve_body($response));
        return strtoupper($country);
    }

    public function render_coupons($atts) {
        $country = $this->get_user_country();
        $output = '<div class="sacm-coupons">';
        $matches = 0;
        foreach ($this->coupons as $coupon) {
            if (empty($coupon['countries']) || in_array($country, $coupon['countries'])) {
                $matches++;
                $output .= '<div class="sacm-coupon">';
                $output .= '<h3>' . esc_html($coupon['title']) . '</h3>';
                $output .= '<p>' . esc_html($coupon['description']) . '</p>';
                if (!empty($coupon['code'])) {
                    $output .= '<p><strong>Use Code:</strong> <span class="sacm-code" onclick="navigator.clipboard.writeText(\'' . esc_attr($coupon['code']) . '\')" style="cursor:pointer;">' . esc_html($coupon['code']) . '</span></p>';
                }
                $output .= '<p><a href="' . esc_url($coupon['url']) . '" target="_blank" rel="nofollow noopener">Shop Now</a></p>';
                $output .= '</div>';
            }
        }
        if ($matches === 0) {
            $output .= '<p>No coupons available for your region. Please check back later.</p>';
        }
        $output .= '</div>';
        return $output;
    }
}

new SmartAffiliateCouponManager();
