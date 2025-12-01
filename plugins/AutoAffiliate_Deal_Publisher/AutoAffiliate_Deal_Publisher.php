/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AutoAffiliate_Deal_Publisher.php
*/
<?php
/**
 * Plugin Name: AutoAffiliate Deal Publisher
 * Description: Automatically fetches and displays affiliate deals and coupons with dynamic updating and tracking.
 * Version: 1.0
 * Author: AutoDev
 */

if (!defined('ABSPATH')) { exit; }

class AutoAffiliateDealPublisher {
    private $option_name = 'aafdp_deals_cache';
    private $api_url = 'https://example-deals-api.com/v1/deals'; // Placeholder API for demo

    public function __construct() {
        add_shortcode('aafdp_deals', [$this, 'render_deals']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'settings_init']);
        add_action('aafdp_fetch_deals_hook', [$this, 'fetch_and_cache_deals']);
        if (!wp_next_scheduled('aafdp_fetch_deals_hook')) {
            wp_schedule_event(time(), 'hourly', 'aafdp_fetch_deals_hook');
        }
    }

    public function admin_menu() {
        add_options_page('AutoAffiliate Deals', 'AutoAffiliate Deals', 'manage_options', 'aafdp-settings', [$this, 'settings_page']);
    }

    public function settings_init() {
        register_setting('aafdp_settings', 'aafdp_settings_options');
        add_settings_section('aafdp_section_api', 'API Settings', null, 'aafdp_settings');
        add_settings_field(
            'aafdp_api_key',
            'API Key',
            [$this, 'render_api_key_field'],
            'aafdp_settings',
            'aafdp_section_api'
        );
    }

    public function render_api_key_field() {
        $options = get_option('aafdp_settings_options');
        $api_key = isset($options['api_key']) ? esc_attr($options['api_key']) : '';
        echo "<input type='text' name='aafdp_settings_options[api_key]' value='$api_key' style='width:300px;' />";
    }

    public function settings_page() {
        ?>
        <div class='wrap'>
            <h1>AutoAffiliate Deal Publisher Settings</h1>
            <form method='post' action='options.php'>
                <?php
                settings_fields('aafdp_settings');
                do_settings_sections('aafdp_settings');
                submit_button();
                ?>
            </form>
            <p>Use shortcode <code>[aafdp_deals]</code> to display the latest affiliate deals anywhere on your site.</p>
        </div>
        <?php
    }

    public function fetch_and_cache_deals() {
        $options = get_option('aafdp_settings_options');
        $api_key = isset($options['api_key']) ? $options['api_key'] : '';
        if (empty($api_key)) {
            return;
        }

        $request = wp_remote_get($this->api_url . '?api_key=' . urlencode($api_key));

        if (is_wp_error($request)) {
            return;
        }

        $body = wp_remote_retrieve_body($request);
        $deals = json_decode($body, true);
        if (!$deals || !is_array($deals)) {
            return;
        }

        // Filter and store only necessary deal info, add tracking parameters
        $processed_deals = [];
        foreach ($deals as $deal) {
            if (empty($deal['title']) || empty($deal['affiliate_link'])) {
                continue;
            }
            $processed_deals[] = [
                'title' => sanitize_text_field($deal['title']),
                'description' => sanitize_textarea_field($deal['description'] ?? ''),
                'affiliate_link' => esc_url_raw($deal['affiliate_link']),
                'coupon' => sanitize_text_field($deal['coupon'] ?? ''),
                'expiry' => sanitize_text_field($deal['expiry'] ?? ''),
            ];
        }

        update_option($this->option_name, $processed_deals);
    }

    public function render_deals() {
        $deals = get_option($this->option_name, []);
        if (empty($deals)) {
            return '<p>No affiliate deals available currently.</p>';
        }

        $output = '<div class="aafdp-deals-list" style="border:1px solid #ddd;padding:10px;">';
        foreach ($deals as $deal) {
            $title = esc_html($deal['title']);
            $desc = esc_html($deal['description']);
            $link = esc_url($deal['affiliate_link']);
            $coupon = esc_html($deal['coupon']);
            $expiry = esc_html($deal['expiry']);

            $output .= '<div class="aafdp-deal" style="margin-bottom:15px;">
                <strong>' . $title . '</strong><br />
                <span style="font-style:italic;color:#555;">' . $desc . '</span><br />';
            if ($coupon) {
                $output .= '<span style="color:#d35400;font-weight:bold;">Coupon: ' . $coupon . '</span><br />';
            }
            if ($expiry) {
                $output .= '<small>Expires: ' . $expiry . '</small><br />';
            }
            $output .= '<a href="' . $link . '" target="_blank" rel="nofollow noopener noreferrer" style="color:#2980b9;">Grab Deal</a>
            </div>';
        }
        $output .= '</div>';
        return $output;
    }
}

new AutoAffiliateDealPublisher();
