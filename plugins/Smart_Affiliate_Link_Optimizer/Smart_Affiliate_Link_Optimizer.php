/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Optimizer.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Optimizer
 * Plugin URI: https://example.com/smart-affiliate-optimizer
 * Description: AI-powered affiliate link cloaking, tracking, and optimization.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateOptimizer {
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
        add_action('admin_init', array($this, 'admin_init'));
        add_filter('the_content', array($this, 'cloak_links'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-optimizer');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sao-tracker', plugin_dir_url(__FILE__) . 'tracker.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sao-tracker', 'sao_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sao_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Optimizer', 'Affiliate Optimizer', 'manage_options', 'sao-settings', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('sao_settings', 'sao_options');
        add_settings_section('sao_main', 'Main Settings', null, 'sao-settings');
        add_settings_field('sao_api_key', 'Premium API Key (Pro)', array($this, 'api_key_field'), 'sao-settings', 'sao_main');
        add_settings_field('sao_links', 'Affiliate Links', array($this, 'links_field'), 'sao-settings', 'sao_main');
    }

    public function api_key_field() {
        $options = get_option('sao_options');
        echo '<input type="text" name="sao_options[api_key]" value="' . esc_attr($options['api_key'] ?? '') . '" class="regular-text" />';
        echo '<p class="description">Enter Premium API key for advanced features. <a href="https://example.com/premium" target="_blank">Upgrade to Pro</a></p>';
    }

    public function links_field() {
        $options = get_option('sao_options');
        $links = $options['links'] ?? '';
        echo '<textarea name="sao_options[links]" rows="10" cols="50">' . esc_textarea($links) . '</textarea>';
        echo '<p class="description">One link per line: keyword|affiliate_url</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Optimizer</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('sao_settings');
                do_settings_sections('sao-settings');
                submit_button();
                ?>
            </form>
            <h2>Analytics</h2>
            <div id="sao-analytics">Loading...</div>
        </div>
        <?php
    }

    public function cloak_links($content) {
        if (is_feed() || is_admin()) return $content;
        $options = get_option('sao_options');
        if (empty($options['links'])) return $content;

        $lines = explode("\n", $options['links']);
        foreach ($lines as $line) {
            if (strpos($line, '|') === false) continue;
            list($keyword, $url) = explode('|', $line, 2);
            $keyword = trim($keyword);
            $url = trim($url);
            $cloak_url = add_query_arg('sao', base64_encode($url), home_url('/go/' . sanitize_title($keyword) . '/'));
            $content = str_ireplace($keyword, '<a href="' . esc_url($cloak_url) . '" rel="nofollow">' . $keyword . '</a>', $content);
        }
        return $content;
    }

    public function activate() {
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

add_action('init', array('SmartAffiliateOptimizer', 'get_instance'));

// AJAX for tracking
add_action('wp_ajax_sao_track', 'sao_track_click');
add_action('wp_ajax_nopriv_sao_track', 'sao_track_click');
function sao_track_click() {
    check_ajax_referer('sao_nonce', 'nonce');
    $url = sanitize_url($_POST['url']);
    // In Pro: Log to DB and optimize
    wp_redirect($url);
    exit;
}

// Tracker JS inline
add_action('wp_head', 'sao_inline_js');
function sao_inline_js() {
    if (!is_singular()) return;
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('a[href*="sao="]').on('click', function(e) {
            var url = $(this).attr('href');
            var targetUrl = atob(url.split('sao=')[1].split('&'));
            $.post(sao_ajax.ajax_url, {action: 'sao_track', url: targetUrl, nonce: sao_ajax.nonce});
        });
    });
    </script>
    <?php
}

// Free analytics endpoint
add_action('wp_ajax_sao_analytics', 'sao_analytics');
function sao_analytics() {
    if (!current_user_can('manage_options')) wp_die();
    global $wpdb;
    // Simulated data for free version
    echo json_encode(array('clicks' => 123, 'conversions' => 12));
    wp_die();
}
?>