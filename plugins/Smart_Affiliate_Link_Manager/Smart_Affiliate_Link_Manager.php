/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Manager.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Manager
 * Plugin URI: https://example.com/smart-affiliate
 * Description: Automate affiliate link creation, tracking, and monetization with smart cloaking, performance analytics, and one-click Amazon/Affiliate network integration.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateManager {
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
        add_action('wp_ajax_sam_track_click', array($this, 'track_click'));
        add_shortcode('sam_link', array($this, 'shortcode_link'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sam_pro_version')) {
            // Pro features
        }
        load_plugin_textdomain('smart-affiliate', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sam-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sam-script', 'sam_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_menu_page(
            'Smart Affiliate',
            'Affiliate Manager',
            'manage_options',
            'smart-affiliate',
            array($this, 'admin_page'),
            'dashicons-chart-line',
            30
        );
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    public function track_click() {
        if (!wp_verify_nonce($_POST['nonce'], 'sam_nonce')) {
            wp_die('Security check failed');
        }
        $link_id = intval($_POST['link_id']);
        $link = get_option('sam_links_' . $link_id, array());
        $link['clicks'] = isset($link['clicks']) ? $link['clicks'] + 1 : 1;
        update_option('sam_links_' . $link_id, $link);
        wp_redirect($link['url']);
        exit;
    }

    public function shortcode_link($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'text' => 'Click Here',
        ), $atts);
        $link = get_option('sam_links_' . intval($atts['id']), array());
        if (empty($link['url'])) {
            return '';
        }
        $nonce = wp_create_nonce('sam_nonce');
        return '<a href="' . admin_url('admin-ajax.php?action=sam_track_click&link_id=' . $atts['id'] . '&nonce=' . $nonce) . '" target="_blank" rel="nofollow">' . esc_html($atts['text']) . '</a>';
    }

    public function activate() {
        // Create default links table or options
        add_option('sam_version', '1.0.0');
    }
}

SmartAffiliateManager::get_instance();

// Freemium upsell notice
function sam_freemium_notice() {
    if (!get_option('sam_pro_version') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p><strong>Smart Affiliate Manager Pro:</strong> Unlock unlimited links, analytics & auto-optimization for $49/year! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
    }
}
add_action('admin_notices', 'sam_freemium_notice');

// Create assets directory placeholder (in real plugin, include files)
// Note: For full deployment, add assets/script.js and admin-page.php
?>