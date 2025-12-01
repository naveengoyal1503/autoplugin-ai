<?php
/*
Plugin Name: GeoAffiliate Pro
Plugin URI: https://example.com/geoaffiliate-pro
Description: Geo-targeted Affiliate Link Manager with scheduling
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=GeoAffiliate_Pro.php
License: GPL2
Text Domain: geoaffiliate-pro
*/

if (!defined('ABSPATH')) exit;

class GeoAffiliatePro {
    private $version = '1.0';
    private $option_name = 'geoaffiliate_links';

    public function __construct() {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_post_geoaffiliate_save', [$this, 'save_link']);
        add_shortcode('geoaffiliate', [$this, 'shortcode_handler']);
        add_action('wp_footer', [$this, 'footer_scripts']);
    }

    public function admin_menu() {
        add_menu_page(
            'GeoAffiliate Pro',
            'GeoAffiliate Pro',
            'manage_options',
            'geoaffiliate-pro',
            [$this, 'admin_page'],
            'dashicons-location-alt'
        );
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized access'));
        }

        $links = get_option($this->option_name, []);
        ?>
        <div class="wrap">
            <h1>GeoAffiliate Pro - Manage Affiliate Links</h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="geoaffiliate_save">
                <?php wp_nonce_field('geoaffiliate_save_nonce', 'geoaffiliate_nonce'); ?>
                <table class="form-table" style="max-width:600px;">
                    <tr>
                        <th><label for="slug">Link Slug (shortcode attr)</label></th>
                        <td><input type="text" name="slug" id="slug" required class="regular-text" placeholder="example: mylink"></td>
                    </tr>
                    <tr>
                        <th><label for="default_url">Default URL</label></th>
                        <td><input type="url" name="default_url" id="default_url" required class="regular-text" placeholder="https://example.com"></td>
                    </tr>
                    <tr>
                        <th><label for="geo_targets">Geo Targets (country code => URL, one per line)</label></th>
                        <td><textarea name="geo_targets" id="geo_targets" rows="5" class="large-text" placeholder="US=https://amazon.com
CA=https://amazon.ca"></textarea>
                        <p class="description">Use uppercase ISO 2-letter country codes, one mapping per line.</p></td>
                    </tr>
                    <tr>
                        <th><label for="schedule_start">Schedule Start (YYYY-MM-DD HH:MM)</label></th>
                        <td><input type="text" name="schedule_start" id="schedule_start" class="regular-text" placeholder="Optional"></td>
                    </tr>
                    <tr>
                        <th><label for="schedule_end">Schedule End (YYYY-MM-DD HH:MM)</label></th>
                        <td><input type="text" name="schedule_end" id="schedule_end" class="regular-text" placeholder="Optional"></td>
                    </tr>
                </table>
                <?php submit_button('Save Link'); ?>
            </form>
            <h2>Existing Links</h2>
            <table class="widefat fixed">
                <thead><tr><th>Slug</th><th>Default URL</th><th>Geo Targets</th><th>Schedule</th></tr></thead>
                <tbody>
                <?php if ($links): foreach ($links as $slug => $link): ?>
                    <tr>
                        <td><?php echo esc_html($slug); ?></td>
                        <td><a href="<?php echo esc_url($link['default_url']); ?>" target="_blank"><?php echo esc_html($link['default_url']); ?></a></td>
                        <td>
                            <?php
                            if (!empty($link['geo_targets']) && is_array($link['geo_targets'])) {
                                foreach ($link['geo_targets'] as $cc => $url) {
                                    echo '<code>' . esc_html($cc) . '</code> &rarr; <a href="' . esc_url($url) . '" target="_blank">' . esc_html($url) . '</a><br>'; 
                                }
                            } else {
                                echo 'None';
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if (!empty($link['schedule_start']) || !empty($link['schedule_end'])) {
                                echo esc_html($link['schedule_start']) . ' to ' . esc_html($link['schedule_end']);
                            } else {
                                echo 'Always Active';
                            }
                            ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="4">No links added yet.</td></tr>
                <?php endif; ?></tbody>
            </table>
        </div>
        <?php
    }

    public function save_link() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized access'));
        }
        if (!isset($_POST['geoaffiliate_nonce']) || !wp_verify_nonce($_POST['geoaffiliate_nonce'], 'geoaffiliate_save_nonce')) {
            wp_die(__('Security check failed')); 
        }

        $slug = sanitize_title($_POST['slug'] ?? '');
        $default_url = esc_url_raw($_POST['default_url'] ?? '');
        $geo_targets_raw = trim($_POST['geo_targets'] ?? '');
        $schedule_start = sanitize_text_field($_POST['schedule_start'] ?? '');
        $schedule_end = sanitize_text_field($_POST['schedule_end'] ?? '');

        if (!$slug || !$default_url) {
            wp_redirect(admin_url('admin.php?page=geoaffiliate-pro&error=missing_fields'));
            exit;
        }

        $geo_targets = [];
        if ($geo_targets_raw) {
            $lines = explode("\n", $geo_targets_raw);
            foreach ($lines as $line) {
                $line = trim($line);
                if (!$line) continue;
                list($cc, $url) = array_map('trim', explode('=', $line . '='));
                if ($cc && $url) {
                    $cc = strtoupper($cc);
                    $geo_targets[$cc] = esc_url_raw($url);
                }
            }
        }

        $links = get_option($this->option_name, []);
        $links[$slug] = [
            'default_url' => $default_url,
            'geo_targets' => $geo_targets,
            'schedule_start' => $schedule_start,
            'schedule_end' => $schedule_end
        ];

        update_option($this->option_name, $links);

        wp_redirect(admin_url('admin.php?page=geoaffiliate-pro&success=1'));
        exit;
    }

    private function get_user_country_code() {
        // Simple IP geolocation using a free API
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            $response = wp_remote_get('https://ipapi.co/' . $ip . '/country/');
            if (!is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
                if ($body && strlen($body) === 2) {
                    return strtoupper(trim($body));
                }
            }
        }
        return '';
    }

    public function shortcode_handler($atts) {
        $atts = shortcode_atts(['slug' => ''], $atts, 'geoaffiliate');
        if (!$atts['slug']) return '';

        $links = get_option($this->option_name, []);
        if (!isset($links[$atts['slug']])) return '';

        $link = $links[$atts['slug']];

        // Check schedule
        $now = current_time('Y-m-d H:i');
        if ($link['schedule_start'] && $now < $link['schedule_start']) {
            return esc_url($link['default_url']);
        }
        if ($link['schedule_end'] && $now > $link['schedule_end']) {
            return esc_url($link['default_url']);
        }

        // Geo-target
        $country = $this->get_user_country_code();
        if ($country && isset($link['geo_targets'][$country])) {
            return esc_url($link['geo_targets'][$country]);
        }

        return esc_url($link['default_url']);
    }

    public function footer_scripts() {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            var links = document.querySelectorAll('a[data-geoaffiliate]');
            links.forEach(function(link) {
                var slug = link.getAttribute('data-geoaffiliate');
                if (!slug) return;
                fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=geoaffiliate_get_link&slug=' + encodeURIComponent(slug))
                    .then(response => response.text())
                    .then(url => {
                        if(url) link.setAttribute('href', url);
                    });
            });
        });
        </script>
        <?php
    }
}

new GeoAffiliatePro();

// AJAX handler for frontend dynamic links
add_action('wp_ajax_geoaffiliate_get_link', 'geoaffiliate_get_link_callback');
add_action('wp_ajax_nopriv_geoaffiliate_get_link', 'geoaffiliate_get_link_callback');
function geoaffiliate_get_link_callback() {
    $slug = sanitize_title($_GET['slug'] ?? '');
    if (!$slug) wp_die('');

    $links = get_option('geoaffiliate_links', []);
    if (!isset($links[$slug])) wp_die('');

    $link = $links[$slug];
    $now = current_time('Y-m-d H:i');

    if ($link['schedule_start'] && $now < $link['schedule_start']) {
        echo esc_url($link['default_url']);
        wp_die();
    }

    if ($link['schedule_end'] && $now > $link['schedule_end']) {
        echo esc_url($link['default_url']);
        wp_die();
    }

    // Simple IP geolocation server side
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $country = '';
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        $response = wp_remote_get('https://ipapi.co/' . $ip . '/country/');
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            if ($body && strlen($body) === 2) {
                $country = strtoupper(trim($body));
            }
        }
    }

    if ($country && isset($link['geo_targets'][$country])) {
        echo esc_url($link['geo_targets'][$country]);
    } else {
        echo esc_url($link['default_url']);
    }
    wp_die();
}
