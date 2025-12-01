/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateLink_Optimizer.php
*/
<?php
/**
 * Plugin Name: AffiliateLink Optimizer
 * Description: Automate affiliate link cloaking, tracking, and optimization to boost conversions.
 * Version: 1.0
 * Author: OpenAI
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AffiliateLinkOptimizer {
    private $option_name = 'alo_affiliate_links';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_filter('the_content', array($this, 'auto_cloak_affiliate_links'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_tracking_script'));
        add_action('wp_ajax_alo_track_click', array($this, 'ajax_track_click'));
        add_action('wp_ajax_nopriv_alo_track_click', array($this, 'ajax_track_click'));
    }

    public function add_admin_page() {
        add_menu_page(
            'AffiliateLink Optimizer',
            'AffiliateLink Optimizer',
            'manage_options',
            'affiliate-link-optimizer',
            array($this, 'admin_page_html'),
            'dashicons-admin-links'
        );
    }

    public function register_settings() {
        register_setting('alo_settings_group', $this->option_name, array($this, 'validate_links'));
    }

    public function validate_links($input) {
        if (!is_array($input)) {
            return array();
        }
        $output = array();
        foreach ($input as $key => $link) {
            $output[sanitize_text_field($key)] = esc_url_raw($link);
        }
        return $output;
    }

    public function admin_page_html() {
        if (!current_user_can('manage_options')) {
            return;
        }
        $links = get_option($this->option_name, array());
        ?>
        <div class="wrap">
            <h1>AffiliateLink Optimizer Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('alo_settings_group'); ?>
                <table class="form-table" id="alo-affiliate-links-table">
                    <thead>
                        <tr><th>Link Name</th><th>Affiliate URL</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    <?php if ($links): ?>
                        <?php foreach ($links as $name => $url): ?>
                            <tr>
                                <td><input type="text" name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($name); ?>][name]" value="<?php echo esc_attr($name); ?>" readonly /></td>
                                <td><input type="url" name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($name); ?>]" value="<?php echo esc_url($url); ?>" required /></td>
                                <td><button type="button" class="button alo-remove-link">Remove</button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
                <button type="button" class="button" id="alo-add-link">Add New Link</button>
                <?php submit_button('Save Links'); ?>
            </form>
        </div>
        <script>
        document.getElementById('alo-add-link').addEventListener('click', function() {
            let tableBody = document.querySelector('#alo-affiliate-links-table tbody');
            let timestamp = Date.now();
            let row = document.createElement('tr');
            row.innerHTML =
                '<td><input type="text" name="<?php echo esc_js($this->option_name); ?>['+timestamp+'][name]" value="" required /></td>' +
                '<td><input type="url" name="<?php echo esc_js($this->option_name); ?>['+timestamp+']" value="" required /></td>' +
                '<td><button type="button" class="button alo-remove-link">Remove</button></td>';
            tableBody.appendChild(row);
        });
        document.querySelector('#alo-affiliate-links-table').addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('alo-remove-link')) {
                e.target.closest('tr').remove();
            }
        });
        </script>
        <?php
    }

    public function auto_cloak_affiliate_links($content) {
        $links = get_option($this->option_name, array());
        if (!$links) return $content;

        foreach ($links as $name => $url) {
            // Cloak the URL
            $cloaked_url = esc_url(add_query_arg(array('alo' => rawurlencode($name))));

            // Replace all occurrences of the raw affiliate URL with the cloaked URL
            $pattern = '/'.preg_quote($url, '/').'/i';
            $content = preg_replace($pattern, $cloaked_url, $content);
        }
        return $content;
    }

    public function enqueue_tracking_script() {
        if (is_singular()) {
            wp_enqueue_script('alo-tracking', plugin_dir_url(__FILE__).'tracking.js', array('jquery'), '1.0', true);
            wp_localize_script('alo-tracking', 'ALO_Ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('alo_nonce'),
            ));
        }
    }

    public function ajax_track_click() {
        check_ajax_referer('alo_nonce', 'nonce');
        $link_name = isset($_POST['link_name']) ? sanitize_text_field($_POST['link_name']) : '';
        if (!$link_name) {
            wp_send_json_error('Invalid link name');
        }
        // For simplicity, store counts in options - in real plugin store in custom tables
        $counts = get_option('alo_click_counts', array());
        if (!isset($counts[$link_name])) {
            $counts[$link_name] = 0;
        }
        $counts[$link_name]++;
        update_option('alo_click_counts', $counts);
        wp_send_json_success(array('count' => $counts[$link_name]));
    }

}

new AffiliateLinkOptimizer();

// Handle redirect of cloaked URLs
add_action('template_redirect', function() {
    if (isset($_GET['alo'])) {
        $name = sanitize_text_field($_GET['alo']);
        $links = get_option('alo_affiliate_links', array());
        if (isset($links[$name])) {
            wp_redirect(esc_url_raw($links[$name]), 301);
            exit;
        }
    }
});
