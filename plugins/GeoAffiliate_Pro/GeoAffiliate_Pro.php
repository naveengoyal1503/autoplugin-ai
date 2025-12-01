<?php
/*
Plugin Name: GeoAffiliate Pro
Description: Affiliate link management with geolocation targeting, link cloaking, and scheduled promotions.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=GeoAffiliate_Pro.php
*/

if (!defined('ABSPATH')) exit;

class GeoAffiliatePro {
    private $version = '1.0';
    private $option_name = 'geoaffiliate_links';

    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('geoaffiliate_link', array($this, 'geoaffiliate_link_shortcode'));
        add_action('init', array($this, 'handle_redirects'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function activate() {
        if (get_option($this->option_name) === false) {
            update_option($this->option_name, array());
        }
    }

    public function deactivate() {
        // Cleanup or options remove if needed
    }

    public function admin_menu() {
        add_menu_page('GeoAffiliate Pro', 'GeoAffiliate Pro', 'manage_options', 'geoaffiliate-pro', array($this, 'settings_page'), 'dashicons-admin-links');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('geoaffiliate-style', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized user')); 
        }

        $links = get_option($this->option_name, array());

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('geoaffiliate_save_links')) {
            $new_links = $this->sanitize_links($_POST['geoaffiliate_links']);
            update_option($this->option_name, $new_links);
            $links = $new_links;
            echo '<div class="updated"><p>Links updated successfully.</p></div>';
        }

        echo '<div class="wrap"><h1>GeoAffiliate Pro Settings</h1><form method="post">';
        wp_nonce_field('geoaffiliate_save_links');

        echo '<table class="widefat fixed">
                <thead><tr><th>Link Name</th><th>Base URL (Cloaked)</th><th>Affiliate URL</th><th>Country Codes (comma separated)</th><th>Start Date (Y-m-d)</th><th>End Date (Y-m-d)</th></tr></thead><tbody>';

        if (empty($links)) {
            $links = array(array('name' => '', 'base_url' => '', 'affiliate_url' => '', 'countries' => '', 'start_date' => '', 'end_date' => ''));
        }

        foreach ($links as $index => $link) {
            echo '<tr>' .
                '<td><input type="text" name="geoaffiliate_links[' . $index . '][name]" value="' . esc_attr($link['name']) . '" required></td>' .
                '<td><input type="text" name="geoaffiliate_links[' . $index . '][base_url]" value="' . esc_attr($link['base_url']) . '" placeholder="e.g. promo" required></td>' .
                '<td><input type="url" name="geoaffiliate_links[' . $index . '][affiliate_url]" value="' . esc_url($link['affiliate_url']) . '" required></td>' .
                '<td><input type="text" name="geoaffiliate_links[' . $index . '][countries]" value="' . esc_attr($link['countries']) . '" placeholder="e.g. US,CA,UK"></td>' .
                '<td><input type="date" name="geoaffiliate_links[' . $index . '][start_date]" value="' . esc_attr($link['start_date']) . '"></td>' .
                '<td><input type="date" name="geoaffiliate_links[' . $index . '][end_date]" value="' . esc_attr($link['end_date']) . '"></td>' .
                '</tr>';
        }

        echo '</tbody></table>';
        echo '<p><button type="submit" class="button button-primary">Save Links</button></p>';
        echo '</form></div>';
    }

    private function sanitize_links($links) {
        $clean = array();
        foreach ($links as $link) {
            $clean[] = array(
                'name' => sanitize_text_field($link['name']),
                'base_url' => sanitize_title($link['base_url']),
                'affiliate_url' => esc_url_raw($link['affiliate_url']),
                'countries' => sanitize_text_field($link['countries']),
                'start_date' => sanitize_text_field($link['start_date']),
                'end_date' => sanitize_text_field($link['end_date'])
            );
        }
        return $clean;
    }

    public function geoaffiliate_link_shortcode($atts) {
        $atts = shortcode_atts(array('name' => ''), $atts, 'geoaffiliate_link');
        $name = sanitize_text_field($atts['name']);
        if (!$name) return '';

        $links = get_option($this->option_name, array());
        foreach ($links as $link) {
            if ($link['name'] === $name) {
                $cloaked_url = site_url('/') . $link['base_url'];
                return '<a href="' . esc_url($cloaked_url) . '" target="_blank" rel="nofollow noopener">' . esc_html($link['name']) . '</a>';
            }
        }
        return '';
    }

    public function handle_redirects() {
        global $wp;
        $links = get_option($this->option_name, array());
        $request = trim($wp->request, '/');

        foreach ($links as $link) {
            if ($link['base_url'] === $request) {
                // Check schedule
                $now = current_time('Y-m-d');
                if ((empty($link['start_date']) || $now >= $link['start_date']) && (empty($link['end_date']) || $now <= $link['end_date'])) {
                    $country = $this->get_user_country();
                    $target_url = $link['affiliate_url'];
                    if (!empty($link['countries'])) {
                        $allowed_countries = array_map('trim', explode(',', strtoupper($link['countries'])));
                        if (!in_array(strtoupper($country), $allowed_countries)) {
                            // If country not allowed, redirect to base affiliate URL without targeting
                            $target_url = $link['affiliate_url'];
                        }
                    }
                    wp_redirect($target_url, 301);
                    exit();
                } else {
                    wp_redirect(home_url('/'), 302);
                    exit();
                }
            }
        }
    }

    private function get_user_country() {
        if (isset($_SERVER['HTTP_CF_IPCOUNTRY'])) {
            return $_SERVER['HTTP_CF_IPCOUNTRY']; // Cloudflare header
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            return '';
        }

        // Basic IP to country lookup fallback using a free API (could cache results in real plugin)
        $response = wp_remote_get('https://ipapi.co/' . $ip . '/country/');
        if (is_wp_error($response)) {
            return '';
        }
        $country_code = trim(wp_remote_retrieve_body($response));
        return $country_code ? $country_code : '';
    }
}

new GeoAffiliatePro();
