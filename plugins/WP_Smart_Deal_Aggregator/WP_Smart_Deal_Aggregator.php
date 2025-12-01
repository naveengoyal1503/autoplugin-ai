<?php
/*
Plugin Name: WP Smart Deal Aggregator
Description: Aggregates exclusive coupons and deals from affiliate networks and displays them in customizable widgets.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Smart_Deal_Aggregator.php
*/

if (!defined('ABSPATH')) exit;

class WPSmartDealAggregator {
    private $option_name = 'wpsda_options';

    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('wpsda_deals', array($this, 'shortcode_display_deals'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));

        // Schedule automatic feed fetch
        add_action('wpsda_cron_fetch', array($this, 'fetch_and_store_deals'));

        if (!wp_next_scheduled('wpsda_cron_fetch')) {
            wp_schedule_event(time(), 'hourly', 'wpsda_cron_fetch');
        }
    }

    public function enqueue_assets() {
        wp_enqueue_style('wpsda_style', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function admin_menu() {
        add_options_page('WP Smart Deal Aggregator', 'Smart Deal Aggregator', 'manage_options', 'wpsda-settings', array($this, 'settings_page'));
    }

    public function register_settings() {
        register_setting($this->option_name, $this->option_name, array($this, 'validate_options'));
        add_settings_section('wpsda_main_section', 'Basic Settings', null, 'wpsda-settings');

        add_settings_field('affiliate_id', 'Affiliate ID', array($this, 'field_affiliate_id'), 'wpsda-settings', 'wpsda_main_section');
        add_settings_field('feed_urls', 'Affiliate Feed URLs (One per line)', array($this, 'field_feed_urls'), 'wpsda-settings', 'wpsda_main_section');
        add_settings_field('deals_limit', 'Maximum Deals To Display', array($this, 'field_deals_limit'), 'wpsda-settings', 'wpsda_main_section');
    }

    public function validate_options($input) {
        $output = array();
        $output['affiliate_id'] = sanitize_text_field($input['affiliate_id']);
        $output['feed_urls'] = array_map('esc_url_raw', array_filter(array_map('trim', explode("\n", $input['feed_urls']))));
        $output['deals_limit'] = intval($input['deals_limit']);
        if ($output['deals_limit'] <= 0) $output['deals_limit'] = 10;
        return $output;
    }

    public function field_affiliate_id() {
        $options = get_option($this->option_name);
        echo '<input type="text" name="' . $this->option_name . '[affiliate_id]" value="' . esc_attr($options['affiliate_id'] ?? '') . '" class="regular-text" />';
        echo '<p class="description">Your affiliate ID to append to deal URLs for commission tracking.</p>';
    }

    public function field_feed_urls() {
        $options = get_option($this->option_name);
        echo '<textarea name="' . $this->option_name . '[feed_urls]" rows="6" class="large-text">' . esc_textarea(implode("\n", $options['feed_urls'] ?? [])) . '</textarea>';
        echo '<p class="description">Enter each affiliate feed URL on a new line. Feeds should provide JSON deals data. Example format documented in plugin docs.</p>';
    }

    public function field_deals_limit() {
        $options = get_option($this->option_name);
        $val = intval($options['deals_limit'] ?? 10);
        echo '<input type="number" name="' . $this->option_name . '[deals_limit]" value="' . $val . '" min="1" max="50" />';
        echo '<p class="description">Maximum number of deals to show in shortcode output.</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>WP Smart Deal Aggregator Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields($this->option_name);
                do_settings_sections('wpsda-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    // Fetch and cache deals from feed URLs
    public function fetch_and_store_deals() {
        $options = get_option($this->option_name);
        if (empty($options['feed_urls']) || !is_array($options['feed_urls'])) return;

        $all_deals = array();
        foreach ($options['feed_urls'] as $url) {
            $response = wp_remote_get($url, ['timeout' => 10]);
            if (is_wp_error($response)) continue;

            $code = wp_remote_retrieve_response_code($response);
            if ($code != 200) continue;

            $body = wp_remote_retrieve_body($response);
            $deals = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($deals)) continue;

            // Validate and normalize deals
            foreach ($deals as $deal) {
                if (isset($deal['title'], $deal['url'])) {
                    $all_deals[] = array(
                        'title' => sanitize_text_field($deal['title']),
                        'url' => esc_url_raw($deal['url']),
                        'description' => isset($deal['description']) ? sanitize_text_field($deal['description']) : '',
                        'expiry_date' => isset($deal['expiry_date']) ? sanitize_text_field($deal['expiry_date']) : '',
                        'affiliate_link' => '' // to be appended below
                    );
                }
            }
        }

        // Append affiliate ID to URLs
        $affiliate_id = $options['affiliate_id'] ?? '';
        if (!empty($affiliate_id)) {
            foreach ($all_deals as &$deal) {
                $separator = (parse_url($deal['url'], PHP_URL_QUERY) ? '&' : '?');
                $deal['affiliate_link'] = $deal['url'] . $separator . 'aff_id=' . urlencode($affiliate_id);
            }
            unset($deal);
        } else {
            foreach ($all_deals as &$deal) {
                $deal['affiliate_link'] = $deal['url'];
            }
            unset($deal);
        }
        // Store in transient for 1 hour
        set_transient('wpsda_cached_deals', $all_deals, 3600);
    }

    public function shortcode_display_deals($atts) {
        $options = get_option($this->option_name);
        $limit = $options['deals_limit'] ?? 10;

        $deals = get_transient('wpsda_cached_deals');
        if (false === $deals) {
            $this->fetch_and_store_deals();
            $deals = get_transient('wpsda_cached_deals');
        }

        if (empty($deals)) return '<p>No deals available at this time.</p>';

        $deals_to_show = array_slice($deals, 0, $limit);

        $output = '<div class="wpsda-deal-list"><ul>';
        foreach ($deals_to_show as $deal) {
            $title = esc_html($deal['title']);
            $desc = esc_html($deal['description']);
            $link = esc_url($deal['affiliate_link']);
            $expiry_text = '';
            if (!empty($deal['expiry_date'])) {
                $expiry_text = ' <small>(Expires: ' . esc_html($deal['expiry_date']) . ')</small>';
            }
            $output .= "<li><a href='$link' target='_blank' rel='nofollow noopener'>${title}</a>${expiry_text}<br><small>${desc}</small></li>";
        }
        $output .= '</ul></div>';

        return $output;
    }
}

new WPSmartDealAggregator();
