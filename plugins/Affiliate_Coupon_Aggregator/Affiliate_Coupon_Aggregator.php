<?php
/*
Plugin Name: Affiliate Coupon Aggregator
Plugin URI: https://example.com/affiliate-coupon-aggregator
Description: Aggregates affiliate coupons from multiple networks and displays them with customizable categories.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Aggregator.php
License: GPLv2 or later
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateCouponAggregator {
    private $option_name = 'aca_coupons_data';
    private $update_interval = 12 * HOUR_IN_SECONDS; // update every 12 hours

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_shortcode('affiliate_coupons', array($this, 'render_coupons_shortcode'));
        add_action('affiliate_coupon_update_event', array($this, 'update_coupons_data'));
        register_activation_hook(__FILE__, array($this, 'activation'));
        register_deactivation_hook(__FILE__, array($this, 'deactivation'));
    }

    public function activation() {
        if (!wp_next_scheduled('affiliate_coupon_update_event')) {
            wp_schedule_event(time(), 'twicedaily', 'affiliate_coupon_update_event');
        }
        $this->update_coupons_data();
    }

    public function deactivation() {
        wp_clear_scheduled_hook('affiliate_coupon_update_event');
    }

    public function add_admin_menu() {
        add_options_page('Affiliate Coupon Aggregator', 'Affiliate Coupons', 'manage_options', 'affiliate_coupon_aggregator', array($this, 'options_page'));
    }

    public function settings_init() {
        register_setting('affiliateCouponGroup', 'aca_settings');

        add_settings_section(
            'aca_section_main',
            __('Coupon Source Settings', 'affiliate-coupon-aggregator'),
            function() { echo '<p>'.__('Configure coupon source API keys and categories.', 'affiliate-coupon-aggregator').'</p>'; },
            'affiliateCouponGroup'
        );

        add_settings_field(
            'aca_affiliate_api_key',
            __('Affiliate API Key', 'affiliate-coupon-aggregator'),
            array($this, 'render_text_field'),
            'affiliateCouponGroup',
            'aca_section_main',
            array('label_for' => 'aca_affiliate_api_key', 'name' => 'affiliate_api_key')
        );

        add_settings_field(
            'aca_categories',
            __('Coupon Categories (comma separated)', 'affiliate-coupon-aggregator'),
            array($this, 'render_text_field'),
            'affiliateCouponGroup',
            'aca_section_main',
            array('label_for' => 'aca_categories', 'name' => 'categories')
        );
    }

    public function render_text_field($args) {
        $options = get_option('aca_settings');
        $value = isset($options[$args['name']]) ? esc_attr($options[$args['name']]) : '';
        echo "<input type='text' id='".esc_attr($args['label_for'])."' name='aca_settings[".esc_attr($args['name'])."]' value='$value' style='width: 50%;' />";
    }

    public function options_page() {
        echo '<form action="options.php" method="post">';
        settings_fields('affiliateCouponGroup');
        do_settings_sections('affiliateCouponGroup');
        submit_button('Save Settings');
        echo '</form>';
    }

    public function update_coupons_data() {
        $options = get_option('aca_settings');
        $api_key = isset($options['affiliate_api_key']) ? $options['affiliate_api_key'] : '';

        if (empty($api_key)) {
            // No API key, clear coupons
            update_option($this->option_name, array());
            return;
        }

        // Simulated coupon data fetching (replace with real API calls)
        $coupons = array(
            array(
                'title' => '10% Off on Electronics',
                'code' => 'ELEC10',
                'description' => 'Save 10% on all electronics items.',
                'url' => 'https://affiliatesite.com/deal1',
                'category' => 'Electronics',
                'expiry' => date('Y-m-d', strtotime('+10 days'))
            ),
            array(
                'title' => 'Free Shipping on Orders $50+',
                'code' => 'FREESHIP50',
                'description' => 'Get free shipping on orders over $50.',
                'url' => 'https://affiliatesite.com/deal2',
                'category' => 'Shipping',
                'expiry' => date('Y-m-d', strtotime('+5 days'))
            ),
            array(
                'title' => '20% Off Apparel',
                'code' => 'APPAREL20',
                'description' => '20% discount on all apparel products.',
                'url' => 'https://affiliatesite.com/deal3',
                'category' => 'Apparel',
                'expiry' => date('Y-m-d', strtotime('+15 days'))
            )
        );
        update_option($this->option_name, $coupons);
    }

    public function render_coupons_shortcode($atts) {
        $atts = shortcode_atts(array('category' => ''), $atts);
        $coupons = get_option($this->option_name, array());

        if (empty($coupons)) {
            return '<p>No coupons available at the moment. Please check back later.</p>';
        }

        $category_filter = trim(strtolower($atts['category']));

        $output = '<div class="aca-coupons">';

        $filtered = array_filter($coupons, function($coupon) use ($category_filter) {
            if (empty($category_filter)) return true;
            return strtolower($coupon['category']) == $category_filter;
        });

        if (empty($filtered)) {
            return '<p>No coupons found for this category.</p>';
        }

        foreach ($filtered as $coupon) {
            $output .= '<div class="aca-coupon" style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">';
            $output .= '<h3 style="margin:0 0 5px 0;">'.esc_html($coupon['title']).'</h3>';
            $output .= '<p>'.esc_html($coupon['description']).'</p>';
            $output .= '<p><strong>Code: </strong><code>'.esc_html($coupon['code']).'</code></p>';
            $output .= '<p><strong>Expires: </strong>'.esc_html($coupon['expiry']).'</p>';
            $output .= '<a href="'.esc_url($coupon['url']).'" target="_blank" rel="nofollow noopener" style="display:inline-block; background:#0073aa; color:#fff; padding:8px 12px; text-decoration:none; border-radius:3px;">Use Coupon</a>';
            $output .= '</div>';
        }

        $output .= '</div>';
        return $output;
    }
}

new AffiliateCouponAggregator();
