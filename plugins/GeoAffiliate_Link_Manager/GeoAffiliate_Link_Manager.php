/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=GeoAffiliate_Link_Manager.php
*/
<?php
/**
 * Plugin Name: GeoAffiliate Link Manager
 * Description: Manage affiliate links with geolocation targeting, scheduling, and automatic insertion to boost conversions.
 * Version: 1.0
 * Author: Plugin Developer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class GeoAffiliateLinkManager {

    private $option_name = 'gaflm_links';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_shortcode('gaflm_affiliate_link', [$this, 'render_affiliate_link']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function add_admin_menu() {
        add_menu_page(
            'GeoAffiliate Links',
            'GeoAffiliate Links',
            'manage_options',
            'gaflm_settings',
            [$this, 'settings_page'],
            'dashicons-admin-links'
        );
    }

    public function register_settings() {
        register_setting('gaflm_settings_group', $this->option_name);
    }

    public function enqueue_scripts() {
        // Enqueue simple JS for geolocation/ip via free API
        wp_enqueue_script('gaflm-script', plugin_dir_url(__FILE__) . 'gaflm-script.js', ['jquery'], '1.0', true);
        wp_localize_script('gaflm-script', 'gaflm_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gaflm_nonce')
        ]);
    }

    public function settings_page() {
        $links = get_option($this->option_name, []);
        ?>
        <div class="wrap">
            <h1>GeoAffiliate Link Manager</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('gaflm_settings_group');
                do_settings_sections('gaflm_settings_group');
                ?>
                <table class="form-table" id="gaflm-links-table">
                    <thead>
                        <tr>
                            <th>Link Name</th>
                            <th>URL</th>
                            <th>Country Code</th>
                            <th>Start Date (Y-m-d)</th>
                            <th>End Date (Y-m-d)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($links)): ?>
                        <?php foreach ($links as $index => $link): ?>
                            <tr>
                                <td><input type="text" name="<?php echo $this->option_name; ?>[<?php echo $index; ?>][name]" value="<?php echo esc_attr($link['name']); ?>" required></td>
                                <td><input type="url" name="<?php echo $this->option_name; ?>[<?php echo $index; ?>][url]" value="<?php echo esc_url($link['url']); ?>" required></td>
                                <td><input type="text" maxlength="2" placeholder="US" name="<?php echo $this->option_name; ?>[<?php echo $index; ?>][country]" value="<?php echo esc_attr($link['country']); ?>"></td>
                                <td><input type="date" name="<?php echo $this->option_name; ?>[<?php echo $index; ?>][start_date]" value="<?php echo esc_attr($link['start_date']); ?>"></td>
                                <td><input type="date" name="<?php echo $this->option_name; ?>[<?php echo $index; ?>][end_date]" value="<?php echo esc_attr($link['end_date']); ?>"></td>
                                <td><button type="button" class="button gaflm-remove-row">Remove</button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
                <p><button type="button" class="button" id="gaflm-add-row">Add New Link</button></p>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
        (function($){
            $('#gaflm-add-row').on('click', function() {
                var $tableBody = $('#gaflm-links-table tbody');
                var index = $tableBody.find('tr').length;
                var newRow = '<tr>' +
                    '<td><input type="text" name="<?php echo $this->option_name; ?>[' + index + '][name]" required></td>' +
                    '<td><input type="url" name="<?php echo $this->option_name; ?>[' + index + '][url]" required></td>' +
                    '<td><input type="text" maxlength="2" placeholder="US" name="<?php echo $this->option_name; ?>[' + index + '][country]"></td>' +
                    '<td><input type="date" name="<?php echo $this->option_name; ?>[' + index + '][start_date]"></td>' +
                    '<td><input type="date" name="<?php echo $this->option_name; ?>[' + index + '][end_date]"></td>' +
                    '<td><button type="button" class="button gaflm-remove-row">Remove</button></td>' +
                    '</tr>';
                $tableBody.append(newRow);
            });
            $(document).on('click', '.gaflm-remove-row', function() {
                $(this).closest('tr').remove();
            });
        })(jQuery);
        </script>
        <?php
    }

    public function render_affiliate_link($atts) {
        $atts = shortcode_atts(['name' => ''], $atts, 'gaflm_affiliate_link');
        $links = get_option($this->option_name, []);

        if (!$atts['name']) {
            return '';
        }

        // Get visitor IP country via free API
        $visitor_country = $this->get_visitor_country();

        $now = current_time('Y-m-d');

        foreach ($links as $link) {
            if (strtolower($link['name']) === strtolower($atts['name'])) {
                // Check date range
                $start_ok = empty($link['start_date']) || ($now >= $link['start_date']);
                $end_ok = empty($link['end_date']) || ($now <= $link['end_date']);

                if (!$start_ok || !$end_ok) {
                    return '';// outside scheduled time
                }

                // Check country match or if no country specified
                if (empty($link['country']) || strtoupper($link['country']) === strtoupper($visitor_country)) {
                    return '<a href="' . esc_url($link['url']) . '" target="_blank" rel="nofollow noopener">' . esc_html($link['name']) . '</a>';
                }
            }
        }
        // Default fallback if no match
        return '';
    }

    private function get_visitor_country() {
        // Basic geolocation IP via external free service, fallback to 'US'
        if (isset($_SERVER['HTTP_CF_IPCOUNTRY'])) {
            return $_SERVER['HTTP_CF_IPCOUNTRY'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
            $response = wp_remote_get("https://ipapi.co/" . $ip . "/country/");
            if (is_array($response) && !is_wp_error($response)) {
                $country = trim(wp_remote_retrieve_body($response));
                if (strlen($country) === 2) {
                    return strtoupper($country);
                }
            }
        }
        return 'US';
    }
}

new GeoAffiliateLinkManager();
