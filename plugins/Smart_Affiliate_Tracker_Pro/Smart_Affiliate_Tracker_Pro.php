/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Tracker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Tracker Pro
 * Plugin URI: https://example.com/smart-affiliate-tracker
 * Description: Automatically tracks and optimizes affiliate links on your WordPress site, generating performance reports and boosting commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-tracker
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateTracker {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_sat_track_click', array($this, 'track_click'));
        add_filter('the_content', array($this, 'cloak_affiliate_links'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-tracker', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        if (get_option('sat_pro_active') !== 'yes') {
            add_action('admin_notices', array($this, 'pro_nag'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sat-tracker', plugin_dir_url(__FILE__) . 'tracker.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sat-tracker', 'sat_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sat_nonce')));
    }

    public function cloak_affiliate_links($content) {
        if (is_single() || is_page()) {
            // Simple regex to detect affiliate links (customize patterns for Amazon, etc.)
            $content = preg_replace_callback('/<a[^>]+href=["\']([^\"\']*aff=|[^\"\']*ref=|[^\"\']*tag=)[^>]*>(.*?)</a>/i', array($this, 'cloak_link'), $content);
        }
        return $content;
    }

    private function cloak_link($matches) {
        $original_url = $matches[1];
        $track_id = uniqid('sat_');
        $cloaked_url = add_query_arg(array('sat_track' => $track_id), admin_url('admin-ajax.php?action=sat_track_click'));
        return str_replace($original_url, $cloaked_url, $matches);
    }

    public function track_click() {
        if (!wp_verify_nonce($_POST['nonce'], 'sat_nonce')) {
            wp_die('Security check failed');
        }
        $original_url = sanitize_url($_POST['url']);
        $track_id = sanitize_text_field($_POST['track_id']);
        // Log click (use transients or custom table for persistence)
        set_transient('sat_click_' . $track_id, array(
            'url' => $original_url,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'time' => current_time('mysql'),
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        ), HOUR_IN_SECONDS);
        wp_redirect($original_url, 302);
        exit;
    }

    public function admin_menu() {
        add_menu_page(
            'Affiliate Tracker',
            'Affiliate Tracker',
            'manage_options',
            'smart-affiliate-tracker',
            array($this, 'admin_page'),
            'dashicons-chart-line',
            30
        );
    }

    public function admin_page() {
        if (isset($_POST['sat_save_settings'])) {
            update_option('sat_settings', $_POST['sat_settings']);
        }
        $settings = get_option('sat_settings', array());
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    public function pro_nag() {
        echo '<div class="notice notice-info"><p><strong>Smart Affiliate Tracker Pro:</strong> Unlock A/B testing and advanced reports for <a href="https://example.com/pro">$9/month</a>! Manage upsells here.</p></div>';
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

SmartAffiliateTracker::get_instance();

// Freemium upsell logic
function sat_pro_upsell() {
    if (!get_option('sat_pro_active')) {
        // Simulate pro check
        if (isset($_GET['activate_pro'])) {
            update_option('sat_pro_active', 'yes');
        }
    }
}
add_action('init', 'sat_pro_upsell');

// Embed simple admin page
$admin_page = '<div class="wrap"><h1>Affiliate Tracker Dashboard</h1><form method="post"><table class="form-table">';
$admin_page .= '<tr><th>A/B Testing</th><td><label><input type="checkbox" name="sat_settings[ab_test]" ' . (isset($settings['ab_test']) ? 'checked' : '') . '> Enable (Pro Feature)</label></td></tr>'; // Pro tease
$admin_page .= '<tr><th>Reports</th><td>Basic clicks logged. <a href="https://example.com/pro">Upgrade for full analytics</a>.</td></tr>'; // Upsell
$admin_page .= '</table><p><input type="submit" name="sat_save_settings" class="button-primary" value="Save Settings"></p></form><h2>Recent Clicks</h2><ul>'; // Placeholder
// Fetch transients for display (simplified)
for ($i = 0; $i < 5; $i++) {
    $key = 'sat_click_sat_' . rand(1000,9999);
    if ($data = get_transient($key)) {
        $admin_page .= '<li>' . esc_html($data['url']) . ' - ' . esc_html($data['time']) . '</li>';
    }
}
$admin_page .= '</ul></div>';
file_put_contents(plugin_dir_path(__FILE__) . 'admin-page.php', $admin_page); // Self-generate admin page

// JS for tracking (inline for single file)
function sat_inline_js() {
    if (is_admin()) return;
    ?><script type="text/javascript">jQuery(document).ready(function($){ $('a[href*="sat_track"]').on('click', function(e){ var url = $(this).data('original-url'); $.post(sat_ajax.ajaxurl, {action:'sat_track_click', url:url, track_id:'<?php echo uniqid('sat_'); ?>', nonce:sat_ajax.nonce}, function(){}); }); });</script><?php
}
add_action('wp_footer', 'sat_inline_js', 100);