<?php
/*
Plugin Name: GeoAffiliate Link Booster
Description: Auto-insert and cloak affiliate links with geolocation targeting and scheduled promotions.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=GeoAffiliate_Link_Booster.php
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class GeoAffiliateLinkBooster {
    private $option_name = 'galb_options';

    public function __construct() {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'settings_init']);
        add_filter('the_content', [$this, 'insert_affiliate_links']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('geoaffiliate-js', plugin_dir_url(__FILE__) . 'geoaffiliate.js', ['jquery'], '1.0', true);
    }

    public function admin_menu() {
        add_options_page('GeoAffiliate Link Booster', 'GeoAffiliate Links', 'manage_options', 'geoaffiliate', [$this, 'options_page']);
    }

    public function settings_init() {
        register_setting('geoaffiliateGroup', $this->option_name);

        add_settings_section(
            'geoaffiliateSection',
            __('Configure Affiliate Links and Geo-targeting', 'geoaffiliate'),
            null,
            'geoaffiliate'
        );

        add_settings_field(
            'affiliate_links',
            __('Affiliate Links JSON', 'geoaffiliate'),
            [$this, 'affiliate_links_render'],
            'geoaffiliate',
            'geoaffiliateSection'
        );
    }

    public function affiliate_links_render() {
        $options = get_option($this->option_name);
        $val = isset($options['affiliate_links']) ? esc_textarea($options['affiliate_links']) : '';
        echo '<textarea cols="60" rows="10" name="' . $this->option_name . '[affiliate_links]" placeholder="[{\"keyword\":\"product\", \"url\":\"http://aff.example.com/product?ref=123\", \"geo\": [\"US\", \"CA\"], \"start\": \"2025-12-01\", \"end\": \"2025-12-31\"}]">' . $val . '</textarea>';
        echo '<p class="description">Enter an array of affiliate link objects in JSON format with fields: keyword, url, geo (array of country codes), start (optional YYYY-MM-DD), end (optional YYYY-MM-DD).</p>';
    }

    private function is_active_link($link){
        $now = current_time('Y-m-d');
        if (!empty($link['start']) && $now < $link['start']) return false;
        if (!empty($link['end']) && $now > $link['end']) return false;
        return true;
    }

    public function insert_affiliate_links($content) {
        if (!is_singular() || !in_the_loop() || !is_main_query()) return $content;

        $options = get_option($this->option_name);
        if (empty($options['affiliate_links'])) return $content;

        $affiliate_links = json_decode($options['affiliate_links'], true);
        if (json_last_error() !== JSON_ERROR_NONE) return $content;

        $user_country = $this->detect_country();

        foreach ($affiliate_links as $link) {
            if (!$this->is_active_link($link)) continue;

            if (!empty($link['geo']) && !in_array($user_country, $link['geo'])) continue;

            $keyword = preg_quote($link['keyword'], '/');
            $url = esc_url($link['url']);

            // Cloaked link URL
            $cloaked_url = esc_url(add_query_arg('ref', 'geoaffiliate', $url));

            // Replace first occurrence of keyword with affiliate link
            $content = preg_replace('/\b(' . $keyword . ')\b/i', '<a href="' . $cloaked_url . '" target="_blank" rel="nofollow noopener">$1</a>', $content, 1);
        }

        return $content;
    }

    private function detect_country() {
        if (!empty($_COOKIE['geoaffiliate_country'])) {
            $country = sanitize_text_field($_COOKIE['geoaffiliate_country']);
            if (preg_match('/^[A-Z]{2}$/', $country)) return $country;
        }

        // Use a free IP Geolocation API fallback
        $ip = $_SERVER['REMOTE_ADDR'];
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) return '';

        $response = wp_remote_get("https://ipapi.co/{$ip}/country/");
        if (is_wp_error($response)) return '';

        $country_code = trim(wp_remote_retrieve_body($response));

        if (preg_match('/^[A-Z]{2}$/', $country_code)) {
            setcookie('geoaffiliate_country', $country_code, time() + 3600 * 24, COOKIEPATH, COOKIE_DOMAIN);
            return $country_code;
        }

        return '';
    }

    public function options_page() {
        ?>
        <form action='options.php' method='post'>
            <h2>GeoAffiliate Link Booster</h2>
            <?php
            settings_fields('geoaffiliateGroup');
            do_settings_sections('geoaffiliate');
            submit_button();
            ?>
        </form>
        <?php
    }
}

new GeoAffiliateLinkBooster();