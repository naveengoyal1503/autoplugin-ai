/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Manager.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Manager
 * Plugin URI: https://example.com/smart-affiliate
 * Description: Automatically cloaks, tracks, and optimizes affiliate links with A/B testing and performance analytics.
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
        add_action('admin_init', array($this, 'admin_init'));
        add_filter('the_content', array($this, 'cloak_links'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sam-ajax', plugin_dir_url(__FILE__) . 'sam-ajax.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sam-ajax', 'sam_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sam_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Manager', 'Affiliate Manager', 'manage_options', 'smart-affiliate', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('sam_options', 'sam_settings');
        add_settings_section('sam_main', 'Main Settings', null, 'sam');
        add_settings_field('sam_api_key', 'Analytics API Key (Premium)', array($this, 'api_key_field'), 'sam', 'sam_main');
        add_settings_field('sam_links', 'Affiliate Links', array($this, 'links_field'), 'sam', 'sam_main');
    }

    public function api_key_field() {
        $settings = get_option('sam_settings', array());
        echo '<input type="text" name="sam_settings[api_key]" value="' . esc_attr($settings['api_key'] ?? '') . '" class="regular-text" />';
        echo '<p class="description">Enter premium API key for advanced features. <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p>';
    }

    public function links_field() {
        $settings = get_option('sam_settings', array('links' => array()));
        $links = $settings['links'] ?? array();
        echo '<textarea name="sam_settings[links]" rows="10" cols="50">' . esc_textarea(json_encode($links, JSON_PRETTY_PRINT)) . '</textarea>';
        echo '<p class="description">JSON array: {"keyword":"affiliate_url", ...}</p>';
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Link Manager</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('sam_options');
                do_settings_sections('sam');
                submit_button();
                ?>
            </form>
            <h2>Stats (Free Version)</h2>
            <div id="sam-stats">Loading...</div>
        </div>
        <?php
    }

    public function cloak_links($content) {
        $settings = get_option('sam_settings', array());
        $links = json_decode($settings['links'] ?? '[]', true);
        if (empty($links)) return $content;

        foreach ($links as $keyword => $url) {
            $cloak_url = add_query_arg(array(
                'sam' => urlencode(base64_encode($url)),
                'ref' => wp_generate_uuid4()
            ), home_url('/'));
            $content = str_ireplace($keyword, '<a href="' . esc_url($cloak_url) . '" target="_blank" rel="nofollow">' . $keyword . '</a>', $content);
        }
        return $content;
    }

    public function track_click() {
        if (!wp_verify_nonce($_POST['nonce'], 'sam_nonce')) wp_die('Security check failed');
        $url = base64_decode(sanitize_url($_POST['url']));
        // Log click (free version: simple count)
        $stats = get_transient('sam_stats') ?: array('clicks' => 0);
        $stats['clicks']++;
        set_transient('sam_stats', $stats, HOUR_IN_SECONDS);
        wp_redirect(esc_url_raw($url), 301);
        exit;
    }

    public function activate() {
        if (!wp_next_scheduled('sam_stats_cron')) {
            wp_schedule_event(time(), 'hourly', 'sam_stats_cron');
        }
    }

    public function deactivate() {
        wp_clear_scheduled_hook('sam_stats_cron');
    }
}

SmartAffiliateManager::get_instance();

add_action('wp_ajax_sam_track', 'SmartAffiliateManager::track_click');

// Simple JS file content (embed as inline for single file)
function sam_inline_js() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#sam-stats').load('<?php echo admin_url('admin-ajax.php'); ?>?action=sam_stats');
    });
    </script>
    <?php
}
add_action('admin_footer', 'sam_inline_js');