<?php
/*
Plugin Name: AffiliateCoupon Connector
Plugin URI: https://example.com/affiliatecouponconnector
Description: Automatically imports and displays affiliate coupons aggregated from multiple programs tailored to your site's niche.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateCoupon_Connector.php
License: GPL2
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateCouponConnector {
    private $options;

    public function __construct() {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
        add_shortcode('affiliate_coupons', array($this, 'render_coupons_shortcode'));
        add_action('affiliatecoupon_sync_hook', array($this, 'sync_coupons'));

        // Schedule a daily event to sync coupons
        if (!wp_next_scheduled('affiliatecoupon_sync_hook')) {
            wp_schedule_event(time(), 'daily', 'affiliatecoupon_sync_hook');
        }
    }

    public function add_plugin_page() {
        add_options_page(
            'AffiliateCoupon Connector Settings',
            'AffiliateCoupon Connector',
            'manage_options',
            'affiliate-coupon-connector',
            array($this, 'create_admin_page')
        );
    }

    public function create_admin_page() {
        $this->options = get_option('affiliatecoupon_options');
        ?>
        <div class="wrap">
            <h2>AffiliateCoupon Connector Settings</h2>
            <form method="post" action="options.php">
            <?php
                settings_fields('affiliatecoupon_option_group');
                do_settings_sections('affiliate-coupon-connector');
                submit_button();
            ?>
            </form>
            <h3>Shortcode Usage</h3>
            <p>Use <code>[affiliate_coupons]</code> in posts or pages to display coupons.</p>
        </div>
        <?php
    }

    public function page_init() {
        register_setting('affiliatecoupon_option_group', 'affiliatecoupon_options', array($this, 'sanitize'));

        add_settings_section(
            'setting_section_id',
            'Settings',
            null,
            'affiliate-coupon-connector'
        );

        add_settings_field(
            'affiliate_programs',
            'Affiliate Program APIs (JSON URLs)',
            array($this, 'affiliate_programs_callback'),
            'affiliate-coupon-connector',
            'setting_section_id'
        );

        add_settings_field(
            'display_count',
            'Number of Coupons to Display',
            array($this, 'display_count_callback'),
            'affiliate-coupon-connector',
            'setting_section_id'
        );
    }

    public function sanitize($input) {
        $new_input = array();
        if (isset($input['affiliate_programs'])) {
            // Sanitize and allow one JSON URL per line
            $lines = explode('\n', $input['affiliate_programs']);
            $clean_lines = array();
            foreach ($lines as $line) {
                $line = trim($line);
                if (filter_var($line, FILTER_VALIDATE_URL)) {
                    $clean_lines[] = esc_url_raw($line);
                }
            }
            $new_input['affiliate_programs'] = implode('\n', $clean_lines);
        }
        if (isset($input['display_count'])) {
            $new_input['display_count'] = absint($input['display_count']);
            if ($new_input['display_count'] < 1) {
                $new_input['display_count'] = 5;
            }
        }
        return $new_input;
    }

    public function affiliate_programs_callback() {
        printf(
            '<textarea id="affiliate_programs" name="affiliatecoupon_options[affiliate_programs]" rows="5" cols="50" placeholder="Enter one JSON API URL per line">%s</textarea>',
            isset($this->options['affiliate_programs']) ? esc_textarea($this->options['affiliate_programs']) : ''
        );
        echo '<p class="description">Enter one affiliate program coupon JSON API URL per line.</p>';
    }

    public function display_count_callback() {
        printf(
            '<input type="number" id="display_count" name="affiliatecoupon_options[display_count]" value="%s" min="1" max="50" />',
            isset($this->options['display_count']) ? esc_attr($this->options['display_count']) : '5'
        );
    }

    // Fetch coupons and store in wp_options transient
    public function sync_coupons() {
        if (!current_user_can('manage_options')) return;

        $this->options = get_option('affiliatecoupon_options');
        if (empty($this->options['affiliate_programs'])) return;

        $apis = explode("\n", $this->options['affiliate_programs']);
        $coupons = array();

        foreach ($apis as $api_url) {
            $api_url = trim($api_url);
            if (!$api_url) continue;

            $response = wp_remote_get($api_url, array('timeout' => 10));
            if (is_wp_error($response)) continue;

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            if (!$data || !is_array($data)) continue;

            // Expecting coupons array in $data['coupons'] or just $data
            if (isset($data['coupons']) && is_array($data['coupons'])) {
                $coupons = array_merge($coupons, $data['coupons']);
            } elseif (isset($data)) {
                $coupons = array_merge($coupons, $data);
            }
        }

        // Basic filtering: unique by title and active coupons
        $unique = array();
        $final_coupons = array();
        foreach ($coupons as $coupon) {
            if (empty($coupon['title']) || empty($coupon['code'])) continue;
            $key = md5(strtolower($coupon['title']));
            if (!isset($unique[$key])) {
                $unique[$key] = true;
                // Add defaults for missing fields
                $coupon['description'] = isset($coupon['description']) ? sanitize_text_field($coupon['description']) : '';
                $coupon['link'] = isset($coupon['link']) ? esc_url_raw($coupon['link']) : '#';
                $coupon['code'] = sanitize_text_field($coupon['code']);
                $final_coupons[] = $coupon;
            }
        }

        set_transient('affiliatecoupon_cached_coupons', $final_coupons, 12 * HOUR_IN_SECONDS);
    }

    public function render_coupons_shortcode($atts) {
        $atts = shortcode_atts(array('count' => 5), $atts, 'affiliate_coupons');
        $count = absint($atts['count']);

        $coupons = get_transient('affiliatecoupon_cached_coupons');
        if (!$coupons) {
            $this->sync_coupons();
            $coupons = get_transient('affiliatecoupon_cached_coupons');
            if (!$coupons) {
                return '<p>No coupons available at this time.</p>';
            }
        }

        if ($count > count($coupons)) {
            $count = count($coupons);
        }

        $output = '<div class="affiliatecoupon-container" style="border:1px solid #ccc;padding:10px;max-width:400px;">
        <h3>Latest Deals & Coupons</h3><ul style="list-style:none;padding:0;">';

        for ($i = 0; $i < $count; $i++) {
            $c = $coupons[$i];
            $title = esc_html($c['title']);
            $desc = !empty($c['description']) ? esc_html($c['description']) : '';
            $code = esc_html($c['code']);
            $link = esc_url($c['link']);
            $output .= '<li style="margin-bottom:15px;">
                <strong><a href="'. $link .'" target="_blank" rel="nofollow noopener">'. $title .'</a></strong><br />
                <em>'. $desc .'</em><br />
                <code style="background:#eee;padding:2px 6px;border-radius:3px;">'. $code .'</code>
            </li>';
        }

        $output .= '</ul></div>';
        return $output;
    }
}

// Initialize plugin
new AffiliateCouponConnector();
