<?php
/*
Plugin Name: GeoAffiliate Pro
Description: Display region-targeted affiliate links automatically with cloaking and scheduling.
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
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('geoaffiliate', array($this, 'shortcode_affiliate'));
    }

    public function admin_menu() {
        add_menu_page('GeoAffiliate Pro', 'GeoAffiliate Pro', 'manage_options', 'geoaffiliate-pro', array($this, 'admin_page'), 'dashicons-admin-links');
    }

    public function register_settings() {
        register_setting('geoaffiliate-settings-group', $this->option_name, array($this, 'validate_links'));
    }

    public function validate_links($input) {
        if (!is_array($input)) {
            return array();
        }
        $output = array();
        foreach ($input as $key => $link) {
            // sanitize fields
            $output[$key]['region'] = sanitize_text_field($link['region']);
            $output[$key]['url'] = esc_url_raw($link['url']);
            $output[$key]['start'] = sanitize_text_field($link['start']);
            $output[$key]['end'] = sanitize_text_field($link['end']);
        }
        return $output;
    }

    public function admin_page() {
        $links = get_option($this->option_name, array());
        ?>
        <div class="wrap">
            <h1>GeoAffiliate Pro Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('geoaffiliate-settings-group'); ?>
                <table class="widefat fixed">
                    <thead><tr><th>Region (Country Code)</th><th>Affiliate URL</th><th>Start Date (YYYY-MM-DD)</th><th>End Date (YYYY-MM-DD)</th></tr></thead>
                    <tbody id="geoaffiliate-links-body">
                    <?php foreach ($links as $index => $link) : ?>
                    <tr>
                        <td><input type="text" name="<?php echo $this->option_name;?>[<?php echo $index;?>][region]" value="<?php echo esc_attr($link['region']); ?>" placeholder="US" required></td>
                        <td><input type="url" name="<?php echo $this->option_name;?>[<?php echo $index;?>][url]" value="<?php echo esc_attr($link['url']); ?>" placeholder="https://" required></td>
                        <td><input type="date" name="<?php echo $this->option_name;?>[<?php echo $index;?>][start]" value="<?php echo esc_attr($link['start']); ?>"></td>
                        <td><input type="date" name="<?php echo $this->option_name;?>[<?php echo $index;?>][end]" value="<?php echo esc_attr($link['end']); ?>"></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="button" class="button" id="add-link">Add Link</button>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
        document.getElementById('add-link').addEventListener('click', function() {
            var tbody = document.getElementById('geoaffiliate-links-body');
            var index = tbody.rows.length;
            var row = document.createElement('tr');
            row.innerHTML = `
            <td><input type="text" name="<?php echo $this->option_name; ?>[${index}][region]" value="" placeholder="US" required></td>
            <td><input type="url" name="<?php echo $this->option_name; ?>[${index}][url]" value="" placeholder="https://" required></td>
            <td><input type="date" name="<?php echo $this->option_name; ?>[${index}][start]" value=""></td>
            <td><input type="date" name="<?php echo $this->option_name; ?>[${index}][end]" value=""></td>
            `;
            tbody.appendChild(row);
        });
        </script>
        <?php
    }

    private function get_user_country_code() {
        if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) { // Cloudflare header
            return sanitize_text_field($_SERVER['HTTP_CF_IPCOUNTRY']);
        }

        $ip = $_SERVER['REMOTE_ADDR'];
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            $response = wp_remote_get("https://ipapi.co/" . $ip . "/country/");
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
                return sanitize_text_field(trim(wp_remote_retrieve_body($response)));
            }
        }
        return 'US'; // Default fallback
    }

    public function shortcode_affiliate() {
        $links = get_option($this->option_name, array());
        if (empty($links)) return '';

        $user_country = $this->get_user_country_code();
        $today = date('Y-m-d');

        foreach ($links as $link) {
            if (strtoupper($link['region']) === strtoupper($user_country)) {
                $start = !empty($link['start']) ? $link['start'] : '0000-00-00';
                $end = !empty($link['end']) ? $link['end'] : '9999-12-31';

                if ($today >= $start && $today <= $end) {
                    $cloaked = esc_url(home_url('/go/?url=') . urlencode($link['url']));
                    return '<a href="' . $cloaked . '" target="_blank" rel="nofollow noopener">Special Offer for ' . esc_html($user_country) . '</a>';
                }
            }
        }

        return ''; // No match
    }
}

// Simple cloaking redirect handler
function geoaffiliate_redirect() {
    if (isset($_GET['url'])) {
        $url = esc_url_raw($_GET['url']);
        if (!empty($url)) {
            wp_redirect($url, 302);
            exit;
        }
    }
}
add_action('template_redirect', 'geoaffiliate_redirect');

new GeoAffiliatePro();