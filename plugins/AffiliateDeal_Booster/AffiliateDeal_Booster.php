/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateDeal_Booster.php
*/
<?php
/**
 * Plugin Name: AffiliateDeal Booster
 * Description: Automatically fetch and display affiliate deals, coupons, and flash sales to increase commissions.
 * Version: 1.0
 * Author: OpenAI Assistant
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) { exit; } // Exit if accessed directly

class AffiliateDealBooster {
    private $plugin_slug = 'affiliate-deal-booster';
    private $option_name = 'adb_deals_cache';

    public function __construct() {
        add_shortcode('affiliate_deals', array($this, 'render_deals'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('adb_cron_fetch_deals', array($this, 'fetch_and_cache_deals'));

        // Schedule cron if not scheduled
        if (!wp_next_scheduled('adb_cron_fetch_deals')) {
            wp_schedule_event(time(), 'hourly', 'adb_cron_fetch_deals');
        }
    }

    public function admin_menu() {
        add_options_page('AffiliateDeal Booster Settings', 'AffiliateDeal Booster', 'manage_options', $this->plugin_slug, array($this, 'settings_page'));
    }

    public function settings_init() {
        register_setting($this->plugin_slug, 'adb_settings');

        add_settings_section('adb_section_main', 'API & Settings', null, $this->plugin_slug);

        add_settings_field(
            'adb_field_affiliate_networks',
            'Affiliate Network API Endpoints (JSON URLs)',
            array($this, 'field_affiliate_networks_render'),
            $this->plugin_slug,
            'adb_section_main'
        );
    }

    public function field_affiliate_networks_render() {
        $options = get_option('adb_settings');
        $value = isset($options['affiliate_networks']) ? esc_textarea($options['affiliate_networks']) : '';
        echo '<textarea name="adb_settings[affiliate_networks]" rows="5" style="width:100%;" placeholder="Enter one API JSON URL per line">' . $value . '</textarea>';
        echo '<p class="description">Enter one URL per line. Each URL should return deals in JSON format with fields: title, link, discount, expire_date.</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>AffiliateDeal Booster Settings</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields($this->plugin_slug);
                do_settings_sections($this->plugin_slug);
                submit_button();
                ?>
            </form>
            <h2>Manual Fetch</h2>
            <form method="post">
                <input type="hidden" name="adb_fetch_now" value="1">
                <?php submit_button('Fetch Deals Now'); ?>
            </form>
        </div>
        <?php

        if (!empty($_POST['adb_fetch_now']) && current_user_can('manage_options')) {
            $this->fetch_and_cache_deals(true);
            echo '<div class="updated notice"><p>Deals fetched successfully.</p></div>';
        }
    }

    public function fetch_and_cache_deals($manual = false) {
        if (!$manual && !wp_doing_cron()) {
            return;
        }

        $options = get_option('adb_settings');
        if (empty($options['affiliate_networks'])) {
            return;
        }

        $urls = array_filter(array_map('trim', explode("\n", $options['affiliate_networks'])));
        $deals = [];

        foreach ($urls as $url) {
            $response = wp_remote_get($url, ['timeout' => 10]);
            if (is_wp_error($response)) {
                continue;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                continue;
            }

            foreach ($data as $deal) {
                if (!empty($deal['title']) && !empty($deal['link'])) {
                    $deals[] = [
                        'title' => sanitize_text_field($deal['title']),
                        'link' => esc_url_raw($deal['link']),
                        'discount' => !empty($deal['discount']) ? sanitize_text_field($deal['discount']) : '',
                        'expire_date' => !empty($deal['expire_date']) ? sanitize_text_field($deal['expire_date']) : '',
                    ];
                }
            }
        }

        update_option($this->option_name, $deals);
    }

    public function render_deals() {
        $deals = get_option($this->option_name, []);
        if (empty($deals)) {
            return '<p>No affiliate deals available at the moment.</p>';
        }

        $output = '<ul class="adb-deal-list" style="list-style:none;padding-left:0;">';

        $now = current_time('Y-m-d');
        foreach ($deals as $deal) {
            // Skip expired deals if expire_date is set
            if (!empty($deal['expire_date']) && $deal['expire_date'] < $now) {
                continue;
            }
            $title = esc_html($deal['title']);
            $link = esc_url($deal['link']);
            $discount = !empty($deal['discount']) ? esc_html($deal['discount']) : '';
            $expiry = !empty($deal['expire_date']) ? esc_html($deal['expire_date']) : '';

            $output .= '<li style="margin-bottom:1em;">';
            $output .= '<a href="' . $link . '" target="_blank" rel="nofollow noopener noreferrer" style="font-weight:bold;color:#0073aa;">' . $title . '</a>';
            if ($discount) {
                $output .= ' - <span style="color:#d54e21;">' . $discount . '</span>';
            }
            if ($expiry) {
                $output .= ' <small style="color:#666;">(Expires: ' . $expiry . ')</small>';
            }
            $output .= '</li>';
        }

        $output .= '</ul>';
        return $output;
    }

    public function deactivate() {
        wp_clear_scheduled_hook('adb_cron_fetch_deals');
    }
}

$affiliateDealBooster = new AffiliateDealBooster();

register_deactivation_hook(__FILE__, array($affiliateDealBooster, 'deactivate'));