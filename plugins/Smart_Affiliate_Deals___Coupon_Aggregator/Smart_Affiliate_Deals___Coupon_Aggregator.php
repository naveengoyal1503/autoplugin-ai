<?php
/*
Plugin Name: Smart Affiliate Deals & Coupon Aggregator
Plugin URI: https://example.com/smart-affiliate-deals
Description: Aggregates affiliate coupons and deals from various merchants, auto-updates, and displays them to monetize your site.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Deals___Coupon_Aggregator.php
License: GPL2
Text Domain: smart-affiliate-deals
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SmartAffiliateDeals {
    private $option_name = 'sad_deals_cache';
    private $cache_time = 3600; // 1 hour cache

    public function __construct() {
        add_shortcode('sad_deals', [$this, 'render_deals_shortcode']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_post_sad_refresh_cache', [$this, 'refresh_cache']);
        add_action('wp_ajax_sad_refresh_cache', [$this, 'ajax_refresh_cache']);
        add_action('wp_ajax_nopriv_sad_refresh_cache', [$this, 'ajax_refresh_cache']);
        register_activation_hook(__FILE__, [$this, 'activate_plugin']);
    }

    public function activate_plugin(){
        $this->refresh_cache();
    }

    public function admin_menu() {
        add_menu_page(
            'Affiliate Deals',
            'Affiliate Deals',
            'manage_options',
            'smart-affiliate-deals',
            [$this, 'admin_page'],
            'dashicons-cart',
            80
        );
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Deals &amp; Coupon Aggregator</h1>
            <p>Cached deals last updated: <?php echo date('Y-m-d H:i:s', get_option($this->option_name.'_timestamp', 0)); ?></p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="sad_refresh_cache">
                <?php wp_nonce_field('sad_refresh_cache_action', 'sad_refresh_cache_nonce'); ?>
                <input type="submit" class="button button-primary" value="Refresh Deals Cache Now">
            </form>
            <p>You can also use shortcode <code>[sad_deals]</code> in your posts or pages to display the deals.</p>
        </div>
        <?php
    }

    public function ajax_refresh_cache() {
        check_ajax_referer('sad_refresh_cache_action', 'nonce');
        $this->refresh_cache();
        wp_send_json_success(['message' => 'Deals cache refreshed']);
    }

    public function refresh_cache() {
        // For demo, we'll simulate fetching from 2 mock affiliate feed APIs
        $deals = [];

        $feeds = [
            'https://example.com/api/affiliate_feed1.json',
            'https://example.com/api/affiliate_feed2.json'
        ];

        // Instead of real fetch, simulate with static data
        $deals[] = [
            'title' => '50% OFF Star Trek T-Shirts',
            'description' => 'Get half price on all Star Trek tees!',
            'affiliate_link' => 'https://affiliate.example.com/track?product=star-trek-tees',
            'expiry' => '2025-12-31'
        ];

        $deals[] = [
            'title' => '20% OFF Baby Furniture for Star Trek Fans',
            'description' => 'Special discount on baby furniture themed for Star Trek lovers',
            'affiliate_link' => 'https://affiliate.example.com/track?product=baby-furniture',
            'expiry' => '2025-11-30'
        ];

        // Remove expired deals
        $now = time();
        $valid_deals = array_filter($deals, function($d) use ($now) {
            return strtotime($d['expiry']) > $now;
        });

        // Save to option as cache
        update_option($this->option_name, $valid_deals);
        update_option($this->option_name.'_timestamp', time());
    }

    public function render_deals_shortcode($atts) {
        $deals = get_option($this->option_name, []);
        if (empty($deals)) {
            return '<p>No deals available at the moment. Please check back soon.</p>';
        }

        $output = '<div class="sad-deals-container" style="border:1px solid #ddd;padding:10px;">
        <h3>Latest Affiliate Deals & Coupons</h3>
        <ul style="list-style-type:none;padding-left:0;">';

        foreach ($deals as $deal) {
            $title = esc_html($deal['title']);
            $desc = esc_html($deal['description']);
            $link = esc_url($deal['affiliate_link']);
            $output .= "<li style='margin-bottom:10px;'>";
            $output .= "<a href='$link' target='_blank' rel='nofollow noopener' style='font-weight:bold;color:#0073aa;'>$title</a><br>";
            $output .= "<small>$desc</small>";
            $output .= "</li>";
        }

        $output .= '</ul></div>';

        return $output;
    }
}

new SmartAffiliateDeals();