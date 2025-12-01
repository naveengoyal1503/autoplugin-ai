/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_GeoLinker.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate GeoLinker
 * Description: Cloaks, manages, and geo-targets affiliate links to increase conversion rates.
 * Version: 1.0
 * Author: Perplexity AI
 */

if (!defined('ABSPATH')) {
  exit;
}

class SmartAffiliateGeoLinker {
    private $option_name = 'sagl_links';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('template_redirect', array($this, 'handle_redirect'));
    }

    public function add_admin_menu() {
        add_menu_page('Affiliate GeoLinker', 'Affiliate GeoLinker', 'manage_options', 'sagl', array($this, 'admin_page'), 'dashicons-admin-links');
    }

    public function settings_init() {
        register_setting('sagl_group', $this->option_name, array($this, 'sanitize_links'));

        add_settings_section('sagl_section', 'Affiliate Links Management', null, 'sagl_group');

        add_settings_field('sagl_links_field', 'Affiliate Links', array($this, 'links_field_callback'), 'sagl_group', 'sagl_section');
    }

    public function sanitize_links($input) {
        // Expect input as JSON array of objects [{"slug":"name","url":"http://affiliate-link", "geo":{"US":"url1","FR":"url2"}}]
        $valid = array();
        $links = json_decode($input, true);
        if ($links && is_array($links)) {
            foreach ($links as $link) {
                $slug = sanitize_title($link['slug'] ?? '');
                $url = esc_url_raw($link['url'] ?? '');
                $geo = array();
                if (!empty($link['geo']) && is_array($link['geo'])) {
                    foreach ($link['geo'] as $country => $country_url) {
                        $geo[sanitize_text_field($country)] = esc_url_raw($country_url);
                    }
                }
                if ($slug && $url) {
                    $valid[] = ['slug' => $slug, 'url' => $url, 'geo' => $geo];
                }
            }
        }
        return json_encode($valid);
    }

    public function links_field_callback() {
        $links = get_option($this->option_name, '[]');
        echo '<textarea style="width:100%; height:200px; font-family: monospace;">' . esc_textarea($links) . '</textarea>';
        echo '<p>Enter affiliate links as JSON array. Example:<br>[{"slug":"product1","url":"https://aff.link/product1","geo":{"US":"https://us.link/product1","FR":"https://fr.link/product1"}}]</p>';
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate GeoLinker</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('sagl_group');
                do_settings_sections('sagl_group');
                submit_button();
                ?>
            </form>
            <h2>How to Use</h2>
            <p>Use URL structure: <code>https://yoursite.com/aff/&lt;slug&gt;</code> to redirect users to the affiliate link.</p>
        </div>
        <?php
    }

    public function handle_redirect() {
        $request = trim($_SERVER['REQUEST_URI'], '/');
        if (strpos($request, 'aff/') === 0) {
            $slug = substr($request, strlen('aff/'));
            $links = json_decode(get_option($this->option_name, '[]'), true);
            foreach ($links as $link) {
                if ($link['slug'] === $slug) {
                    $target_url = $link['url'];
                    $user_country = $this->get_user_country();
                    if ($user_country && isset($link['geo'][$user_country])) {
                        $target_url = $link['geo'][$user_country];
                    }
                    wp_redirect($target_url, 301);
                    exit;
                }
            }
            // If no slug matched
            wp_redirect(home_url(), 302);
            exit;
        }
    }

    // Basic geo-detection by IP (using a free geolocation API)
    public function get_user_country() {
        if (empty($_SERVER['REMOTE_ADDR'])) {
            return false;
        }
        $ip = $_SERVER['REMOTE_ADDR'];
        // Avoid repeated calls by transient caching 12h
        $cache_key = 'sagl_geo_' . md5($ip);
        $country = get_transient($cache_key);
        if ($country !== false) {
            return $country;
        }
        $response = wp_safe_remote_get('https://ipapi.co/' . $ip . '/country/');
        if (is_wp_error($response)) {
            return false;
        }
        $country_code = trim(wp_remote_retrieve_body($response));
        if (preg_match('/^[A-Z]{2}$/', $country_code)) {
            set_transient($cache_key, $country_code, 12 * HOUR_IN_SECONDS);
            return $country_code;
        }
        return false;
    }
}

new SmartAffiliateGeoLinker();