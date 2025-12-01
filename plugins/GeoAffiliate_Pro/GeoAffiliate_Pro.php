<?php
/*
Plugin Name: GeoAffiliate Pro
Description: Advanced affiliate link manager with geolocation targeting and scheduled promotions.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=GeoAffiliate_Pro.php
*/

if (!defined('ABSPATH')) exit;

class GeoAffiliatePro {
    private $option_name = 'geoaffiliate_links';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'settings_init']);
        add_shortcode('geoaffiliate_link', [$this, 'render_affiliate_link']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('geoaffiliate-js', plugin_dir_url(__FILE__) . 'geoaffiliate.js', ['jquery'], '1.0', true);
        wp_localize_script('geoaffiliate-js', 'geoAffiliateData', ['ajax_url' => admin_url('admin-ajax.php')]);
    }

    public function add_admin_menu() {
        add_menu_page('GeoAffiliate Pro', 'GeoAffiliate Pro', 'manage_options', 'geoaffiliate_pro', [$this, 'options_page']);
    }

    public function settings_init() {
        register_setting('geoaffiliatePro', $this->option_name);

        add_settings_section('geoaffiliatePro_section', '', null, 'geoaffiliatePro');

        add_settings_field(
            'geoaffiliate_links_field',
            __('Affiliate Links (JSON)', 'geoaffiliate'),
            [$this, 'render_links_field'],
            'geoaffiliatePro',
            'geoaffiliatePro_section'
        );
    }

    public function render_links_field() {
        $options = get_option($this->option_name, '{}');
        echo '<textarea name="' . esc_attr($this->option_name) . '" rows="10" style="width:100%; font-family: monospace;">' . esc_textarea($options) . '</textarea>';
        echo '<p class="description">Enter links as JSON array with fields: id, url, country (ISO), active_from (Y-m-d), active_to (Y-m-d). Example:<br>[{"id":"amazon_us","url":"https://amazon.com/dp/product","country":"US","active_from":"2025-01-01","active_to":"2025-12-31"}]</p>';
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>GeoAffiliate Pro</h1>
            <form action='options.php' method='post'>
                <?php
                settings_fields('geoaffiliatePro');
                do_settings_sections('geoaffiliatePro');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function render_affiliate_link($atts) {
        $atts = shortcode_atts(['id' => ''], $atts);
        $id = sanitize_text_field($atts['id']);

        $links_json = get_option($this->option_name, '{}');
        $links = json_decode($links_json, true);
        if (!$links) return '';

        $user_country = $this->detect_user_country();
        $date_now = current_time('Y-m-d');

        foreach ($links as $link) {
            if ($link['id'] === $id) {
                // Check date
                if (isset($link['active_from']) && $link['active_from'] > $date_now) return '';
                if (isset($link['active_to']) && $link['active_to'] < $date_now) return '';
                // Check country
                if (isset($link['country']) && strtoupper($link['country']) !== strtoupper($user_country)) {
                    // Link is country specific
                    return '';
                }
                // Return link anchor
                $url = esc_url($link['url']);
                return '<a href="' . $url . '" target="_blank" rel="nofollow noopener">Affiliate Link</a>';
            }
        }
        return '';
    }

    private function detect_user_country() {
        // Use a simple IP lookup with free API with transient cache, fallback US
        $transient_key = 'geoaffiliate_user_country';
        $country = get_transient($transient_key);
        if ($country) return $country;
        $ip = $_SERVER['REMOTE_ADDR'];
        $response = wp_remote_get('https://ipapi.co/' . $ip . '/country/');
        if (is_wp_error($response)) return 'US';
        $body = wp_remote_retrieve_body($response);
        if ($body && preg_match('/^[A-Z]{2}$/', $body)) {
            set_transient($transient_key, $body, 12 * HOUR_IN_SECONDS);
            return $body;
        }
        return 'US';
    }
}

new GeoAffiliatePro();