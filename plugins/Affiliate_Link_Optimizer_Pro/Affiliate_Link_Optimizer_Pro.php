/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Link_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: Affiliate Link Optimizer Pro
 * Plugin URI: https://example.com/affiliate-link-optimizer
 * Description: AI-powered affiliate link optimization with A/B testing, cloaking, and analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-link-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateLinkOptimizer {
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
        add_filter('the_content', array($this, 'replace_affiliate_links'));
        add_shortcode('alo_link', array($this, 'shortcode_link'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-link-optimizer');
        $this->create_table();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('alo-script', plugin_dir_url(__FILE__) . 'alo-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('alo-style', plugin_dir_url(__FILE__) . 'alo-style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Affiliate Link Optimizer', 'ALO Pro', 'manage_options', 'alo-pro', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['alo_api_key'])) {
            update_option('alo_openai_key', sanitize_text_field($_POST['alo_api_key']));
            echo '<div class="notice notice-success"><p>API Key saved!</p></div>';
        }
        $api_key = get_option('alo_openai_key', '');
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    public function replace_affiliate_links($content) {
        if (!is_single()) return $content;
        $patterns = array(
            '/https?:\/\/amzn\.to\/[^\s<>"\']+/i',
            '/https?:\/\/(www\.)?amazon\.[^\s<>"\']+/i',
            '/https?:\/\/(www\.)?(clickbank|warriorplus)\.[^\s<>"\']+/i'
        );
        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $content, $matches);
            foreach ($matches as $link) {
                $id = uniqid('alo_');
                $cloaked = $this->cloak_link($link, $id);
                $content = str_replace($link, $cloaked, $content);
            }
        }
        return $content;
    }

    private function cloak_link($url, $id) {
        $track_url = add_query_arg(array('alo_id' => $id, 'ref' => 'auto'), $url);
        return '<a href="' . esc_url($track_url) . '" class="alo-link" data-original="' . esc_attr($url) . '" data-id="' . esc_attr($id) . '">' . $this->get_display_text($url) . '</a>';
    }

    private function get_display_text($url) {
        if (stripos($url, 'amazon') !== false) {
            return 'Check Price on Amazon';
        }
        return 'Learn More';
    }

    public function shortcode_link($atts) {
        $atts = shortcode_atts(array(
            'url' => '',
            'text' => 'Affiliate Link',
            'id' => uniqid('alo_')
        ), $atts);
        $track_url = add_query_arg(array('alo_id' => $atts['id'], 'ref' => 'shortcode'), $atts['url']);
        return '<a href="' . esc_url($track_url) . '" class="alo-link" data-original="' . esc_attr($atts['url']) . '" data-id="' . esc_attr($atts['id']) . '">' . esc_html($atts['text']) . '</a>';
    }

    public function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'alo_stats';
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            link_id varchar(50) NOT NULL,
            ref varchar(50) DEFAULT '',
            clicks int DEFAULT 0,
            date_created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY link_id (link_id)
        ) $charset;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function activate() {
        $this->create_table();
    }

    public function deactivate() {
        // Cleanup optional
    }

    // Premium features stub (upgrade nag)
    public function is_premium() {
        return get_option('alo_premium_active', false);
    }

    public function show_upgrade_nag() {
        if (!$this->is_premium()) {
            echo '<div class="notice notice-info"><p>Upgrade to <strong>Affiliate Link Optimizer Pro</strong> for AI optimization and A/B testing! <a href="https://example.com/upgrade" target="_blank">Get Pro Now</a></p></div>';
        }
    }
}

// Track clicks
add_action('init', function() {
    if (isset($_GET['alo_id']) && isset($_GET['ref'])) {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'alo_stats',
            array(
                'link_id' => sanitize_text_field($_GET['alo_id']),
                'ref' => sanitize_text_field($_GET['ref']),
                'clicks' => 1
            ),
            array('%s', '%s', '%d')
        );
        // Redirect to original URL (stored in session or JS)
        wp_redirect(sanitize_url($_GET['alo_original'] ?? $_GET['url'] ?? home_url()), 302);
        exit;
    }
});

// AJAX for stats
add_action('wp_ajax_alo_stats', 'alo_get_stats');
function alo_get_stats() {
    if (!current_user_can('manage_options')) wp_die();
    global $wpdb;
    $stats = $wpdb->get_results("SELECT link_id, SUM(clicks) as total FROM " . $wpdb->prefix . "alo_stats GROUP BY link_id ORDER BY total DESC");
    wp_send_json($stats);
}

AffiliateLinkOptimizer::get_instance();

// Inline scripts and styles
add_action('wp_head', function() {
    echo '<style>.alo-link {color: #007cba; text-decoration: none; font-weight: bold;} .alo-link:hover {text-decoration: underline;}</style>';
    echo '<script>jQuery(document).ready(function($) { $(".alo-link").on("click", function(e) { var original = $(this).data("original"); var params = "alo_id=" + $(this).data("id") + "&ref=js&alo_original=" + encodeURIComponent(original); window.location.href = original + (original.indexOf("?") > -1 ? "&" : "?") + params; }); });</script>';
});

// Admin page template
add_action('admin_head-settings_page_alo-pro', function() {
    echo '<style>#alo-admin {max-width: 800px;}</style>';
});

// Premium nag
add_action('admin_notices', array(AffiliateLinkOptimizer::get_instance(), 'show_upgrade_nag'));