<?php
/*
Plugin Name: WP Smart Deals
Plugin URI: https://example.com/wp-smart-deals
Description: Automatically aggregate and display exclusive coupons, deals, and discount codes tailored to your niche for affiliate monetization.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Smart_Deals.php
License: GPL2
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class WPSmartDeals {
    private $option_name = 'wpsd_deals_cache';
    private $cache_time_option = 'wpsd_cache_time';
    private $cache_duration = 3600; // 1 hour

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_shortcode('wpsmartdeals', array($this, 'deal_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Auto refresh deals cache every hour
        if (!wp_next_scheduled('wpsd_update_deals')) {
            wp_schedule_event(time(), 'hourly', 'wpsd_update_deals');
        }
        add_action('wpsd_update_deals', array($this, 'fetch_and_cache_deals'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wpsd-style', plugins_url('style.css', __FILE__));
    }

    public function add_admin_menu() {
        add_menu_page('WP Smart Deals', 'WP Smart Deals', 'manage_options', 'wpsmartdeals', array($this, 'admin_page'), 'dashicons-tag', 80);
    }

    public function settings_init() {
        register_setting('wpsd_options', 'wpsd_deal_source');

        add_settings_section(
            'wpsd_section_deal_source',
            __('Deal Aggregation Settings', 'wpsd'),
            null,
            'wpsd_options'
        );

        add_settings_field(
            'wpsd_deal_source',
            __('Deal Source API URL', 'wpsd'),
            array($this, 'deal_source_render'),
            'wpsd_options',
            'wpsd_section_deal_source'
        );
    }

    public function deal_source_render() {
        $value = get_option('wpsd_deal_source', 'https://api.example.com/deals?category=tech&limit=10');
        echo "<input type='text' name='wpsd_deal_source' value='" . esc_attr($value) . "' size='50' />";
        echo "<p class='description'>Enter a JSON deals API endpoint. The API must return deals in JSON format with keys: title, url, discount, expiry.</p>";
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>WP Smart Deals Settings</h1>
            <form action='options.php' method='post'>
                <?php
                settings_fields('wpsd_options');
                do_settings_sections('wpsd_options');
                submit_button();
                ?>
            </form>
            <h2>Current Cached Deals</h2>
            <?php
            $deals = get_option($this->option_name);
            if (empty($deals)) {
                echo '<p>No deals cached yet. They will appear here after the first scheduled fetch.</p>';
            } else {
                echo '<ul>';
                foreach ($deals as $deal) {
                    echo '<li><strong>' . esc_html($deal['title']) . '</strong> &mdash; <a href="' . esc_url($deal['url']) . '" target="_blank" rel="nofollow noopener">Get Deal</a> (Discount: ' . esc_html($deal['discount']) . ')';
                    if (!empty($deal['expiry'])) {
                        echo ' &mdash; Expires: ' . esc_html($deal['expiry']);
                    }
                    echo '</li>';
                }
                echo '</ul>';
            }
            ?>
        </div>
        <?php
    }

    public function fetch_and_cache_deals() {
        $api_url = get_option('wpsd_deal_source', 'https://api.example.com/deals?category=tech&limit=10');
        if (empty($api_url)) return;

        $response = wp_remote_get($api_url, array('timeout' => 10));
        if (is_wp_error($response)) return;

        $body = wp_remote_retrieve_body($response);
        $deals = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($deals)) {
            return;
        }

        // Validate each deal
        $valid_deals = array();
        foreach ($deals as $deal) {
            if (!empty($deal['title']) && !empty($deal['url']) && !empty($deal['discount'])) {
                $valid_deals[] = array(
                    'title' => sanitize_text_field($deal['title']),
                    'url' => esc_url_raw($deal['url']),
                    'discount' => sanitize_text_field($deal['discount']),
                    'expiry' => !empty($deal['expiry']) ? sanitize_text_field($deal['expiry']) : '',
                );
            }
        }

        if (!empty($valid_deals)) {
            update_option($this->option_name, $valid_deals);
            update_option($this->cache_time_option, time());
        }
    }

    public function deal_shortcode($atts) {
        $deals = get_option($this->option_name);
        if (empty($deals) || !is_array($deals)) return '<p>No deals available at the moment. Please check back later.</p>';

        ob_start();
        echo '<div class="wpsd-deals-list">';
        foreach ($deals as $deal) {
            echo '<div class="wpsd-deal-item">';
            echo '<a href="' . esc_url($deal['url']) . '" target="_blank" rel="nofollow noopener">' . esc_html($deal['title']) . '</a><br/>';
            echo '<span class="wpsd-discount">Discount: ' . esc_html($deal['discount']) . '</span><br/>';
            if (!empty($deal['expiry'])) {
                echo '<small class="wpsd-expiry">Expires: ' . esc_html($deal['expiry']) . '</small>';
            }
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }
}

new WPSmartDeals();
