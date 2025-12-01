/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_GeoLinker.php
*/
<?php
/**
 * Plugin Name: Affiliate GeoLinker
 * Description: Automatically insert and cloak affiliate links with geolocation targeting for optimized affiliate marketing.
 * Version: 1.0
 * Author: YourName
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateGeoLinker {
    private $option_name = 'aglinker_settings';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_filter('the_content', array($this, 'auto_link_affiliates'));
    }

    public function add_admin_menu() {
        add_options_page('Affiliate GeoLinker', 'Affiliate GeoLinker', 'manage_options', 'affiliate_geolinker', array($this, 'options_page'));
    }

    public function settings_init() {
        register_setting('aglinker', $this->option_name);

        add_settings_section(
            'aglinker_section',
            __('Affiliate GeoLinker Settings', 'affiliate-geolinker'),
            null,
            'aglinker'
        );

        add_settings_field(
            'aglinker_affiliates',
            __('Affiliate Links (one per line, url|replacement text|country code comma separated)', 'affiliate-geolinker'),
            array($this, 'affiliates_render'),
            'aglinker',
            'aglinker_section'
        );
    }

    public function affiliates_render() {
        $options = get_option($this->option_name);
        $text = isset($options['affiliate_links']) ? esc_textarea($options['affiliate_links']) : '';
        echo '<textarea cols="60" rows="8" name="' . $this->option_name . '[affiliate_links]" placeholder="http://example.com|Buy Now|US,CA">' . $text . '</textarea>';
        echo '<p class="description">Format: affiliate URL | link text | country codes (optional, comma separated)</p>';
    }

    public function options_page() {
        ?>
        <form action='options.php' method='post'>
            <h1>Affiliate GeoLinker</h1>
            <?php
            settings_fields('aglinker');
            do_settings_sections('aglinker');
            submit_button();
            ?>
        </form>
        <?php
    }

    private function get_user_country() {
        if (isset($_SERVER['HTTP_CF_IPCOUNTRY'])) {
            return sanitize_text_field($_SERVER['HTTP_CF_IPCOUNTRY']); // Cloudflare header
        }
        // Basic fallback - can be enhanced with IP geolocation APIs
        return '';
    }

    public function auto_link_affiliates($content) {
        $options = get_option($this->option_name);
        if (empty($options['affiliate_links'])) return $content;

        $lines = explode("\n", $options['affiliate_links']);
        $country = $this->get_user_country();
        $replacements = array();

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            $parts = explode('|', $line);
            if (count($parts) < 2) continue;

            $url = esc_url(trim($parts));
            $text = sanitize_text_field(trim($parts[1]));
            $countries = isset($parts[2]) ? array_map('trim', explode(',', strtoupper($parts[2]))) : array();

            // Show only if country matches or no country restriction
            if (empty($countries) || in_array(strtoupper($country), $countries)) {
                // Cloak link - basic cloaking by redirecting through site URL
                $cloaked_url = home_url('/?agl_redirect=' . urlencode(base64_encode($url)));
                $replacements[$text] = '<a href="' . esc_url($cloaked_url) . '" target="_blank" rel="nofollow noopener">' . esc_html($text) . '</a>';
            }
        }

        if (empty($replacements)) return $content;

        // Replace all occurrences of link text with cloaked affiliate link
        $content = str_replace(array_keys($replacements), array_values($replacements), $content);

        return $content;
    }

}

// Cloak redirect handler
add_action('template_redirect', function () {
    if (isset($_GET['agl_redirect'])) {
        $url = base64_decode(sanitize_text_field($_GET['agl_redirect']));
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            wp_redirect($url, 302);
            exit;
        }
    }
});

new AffiliateGeoLinker();
