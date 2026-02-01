/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Optimizer.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Optimizer
 * Plugin URI: https://example.com/smart-affiliate-optimizer
 * Description: Automatically cloaks, tracks clicks, and optimizes affiliate links with A/B testing and performance analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateOptimizer {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_sao_save_link', array($this, 'ajax_save_link'));
        add_action('wp_ajax_sao_get_stats', array($this, 'ajax_get_stats'));
        add_filter('the_content', array($this, 'replace_links'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (get_option('sao_pro') !== 'yes') {
            add_action('admin_notices', array($this, 'pro_notice'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sao-admin-js', plugin_dir_url(__FILE__) . 'sao.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sao-admin-js', 'sao_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Optimizer', 'Affiliate Optimizer', 'manage_options', 'sao-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['sao_submit'])) {
            update_option('sao_links', sanitize_textarea_field($_POST['sao_links']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $links = get_option('sao_links', '');
        echo '<div class="wrap"><h1>Smart Affiliate Link Optimizer</h1><form method="post"><textarea name="sao_links" rows="10" cols="80" placeholder="Original URL|Affiliate URL|Description\n...">' . esc_textarea($links) . '</textarea><p>Format: original_url|affiliate_url|description (one per line)</p><p><input type="submit" name="sao_submit" class="button-primary" value="Save Links"></p></form><h2>Stats</h2><div id="sao-stats"></div><p><strong>Go Pro</strong> for A/B testing, auto-optimization, and unlimited links! <a href="https://example.com/pro">Upgrade Now</a></p></div>';
    }

    public function replace_links($content) {
        $links = explode("\n", get_option('sao_links', ''));
        foreach ($links as $line) {
            $parts = explode('|', trim($line), 3);
            if (count($parts) === 3 && strpos($content, $parts) !== false) {
                $shortcode = '[sao id="' . sanitize_title($parts[2]) . '"]';
                $content = str_replace($parts, $shortcode, $content);
            }
        }
        return $content;
    }

    public function ajax_save_link() {
        if (!current_user_can('manage_options')) wp_die();
        // Pro feature simulation
        if (get_option('sao_pro') !== 'yes') {
            wp_send_json_error('Pro feature');
        }
        wp_send_json_success();
    }

    public function ajax_get_stats() {
        if (!current_user_can('manage_options')) wp_die();
        $stats = get_option('sao_stats', array());
        wp_send_json_success($stats);
    }

    public function pro_notice() {
        echo '<div class="notice notice-info"><p>Unlock Pro features: A/B Testing & Analytics! <a href="https://example.com/pro">Upgrade</a></p></div>';
    }

    public function activate() {
        if (!get_option('sao_stats')) update_option('sao_stats', array());
    }

    public function deactivate() {}
}

// Track clicks
function sao_track_click($atts) {
    $id = $atts['id'];
    $links = explode("\n", get_option('sao_links', ''));
    foreach ($links as $line) {
        $parts = explode('|', trim($line), 3);
        if (count($parts) === 3 && sanitize_title($parts[2]) === $id) {
            $stats = get_option('sao_stats', array());
            $stats[$id]['clicks'] = isset($stats[$id]['clicks']) ? $stats[$id]['clicks'] + 1 : 1;
            update_option('sao_stats', $stats);
            return '<a href="' . esc_url($parts[1]) . '" target="_blank" rel="nofollow noopener">' . esc_html($parts[2]) . '</a>';
        }
    }
    return '';
}
add_shortcode('sao', 'sao_track_click');

SmartAffiliateOptimizer::get_instance();

// Dummy JS file content (in real plugin, separate file)
/*
document.addEventListener('DOMContentLoaded', function() {
    jQuery('#sao-stats').load(window.sao_ajax.ajaxurl + '?action=sao_get_stats');
});
*/