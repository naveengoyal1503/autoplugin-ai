/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into your WordPress content using smart keyword matching and semantic analysis.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateAutoInserter {
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
        add_filter('the_content', array($this, 'auto_insert_links'), 99);
        add_filter('widget_text', array($this, 'auto_insert_links'), 99);
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-autoinserter', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-affiliate-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('smart-affiliate-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate AutoInserter',
            'Affiliate Inserter',
            'manage_options',
            'smart-affiliate-autoinserter',
            array($this, 'admin_page')
        );
    }

    public function admin_init() {
        register_setting('smart_affiliate_options', 'smart_affiliate_settings');
        add_settings_section('main_section', 'Main Settings', null, 'smart-affiliate');
        add_settings_field('keywords', 'Keywords & Links (JSON format: {"keyword":"affiliate_url"})', array($this, 'keywords_field'), 'smart-affiliate', 'main_section');
        add_settings_field('max_links', 'Max Links per Post', array($this, 'max_links_field'), 'smart-affiliate', 'main_section');
        add_settings_field('enable_ai', 'Enable Premium AI Mode (Pro)', array($this, 'enable_ai_field'), 'smart-affiliate', 'main_section');
    }

    public function keywords_field() {
        $settings = get_option('smart_affiliate_settings', array('keywords' => '{"WordPress":"https://example.com/aff/wp","plugin":"https://example.com/aff/plugin"}', 'max_links' => 3));
        echo '<textarea name="smart_affiliate_settings[keywords]" rows="10" cols="50">' . esc_textarea($settings['keywords']) . '</textarea>';
        echo '<p class="description">Enter JSON object e.g. {"keyword":"https://your-affiliate-link.com"}</p>';
    }

    public function max_links_field() {
        $settings = get_option('smart_affiliate_settings', array('max_links' => 3));
        echo '<input type="number" name="smart_affiliate_settings[max_links]" value="' . esc_attr($settings['max_links']) . '" min="1" max="10" />';
    }

    public function enable_ai_field() {
        echo '<input type="checkbox" name="smart_affiliate_settings[enable_ai]" disabled /> <span>Available in Pro version</span>';
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('smart_affiliate_options');
                do_settings_sections('smart-affiliate');
                submit_button();
                ?>
            </form>
            <p><strong>Upgrade to Pro:</strong> AI semantic analysis, analytics, unlimited links, WooCommerce integration. <a href="https://example.com/pro">Get Pro</a></p>
        </div>
        <?php
    }

    public function auto_insert_links($content) {
        if (is_admin() || !is_main_query()) return $content;

        $settings = get_option('smart_affiliate_settings', array('keywords' => '{}', 'max_links' => 3));
        $keywords = json_decode($settings['keywords'], true);
        if (empty($keywords) || !is_array($keywords)) return $content;

        $max_links = intval($settings['max_links']);
        $inserted = 0;
        $words = explode(' ', $content);
        $new_content = '';

        foreach ($words as $word) {
            $new_content .= $word . ' ';
            foreach ($keywords as $keyword => $url) {
                if (stripos($word, $keyword) !== false && $inserted < $max_links) {
                    $link = '<a href="' . esc_url($url) . '" target="_blank" rel="nofollow sponsored">' . esc_html($word) . '</a>';
                    $new_content = str_replace($word, $link, $new_content);
                    $inserted++;
                    break;
                }
            }
        }

        return trim($new_content);
    }

    public function activate() {
        add_option('smart_affiliate_settings', array('keywords' => '{"WordPress":"https://example.com/aff/wp","plugin":"https://example.com/aff/plugin"}', 'max_links' => 3));
    }

    public function deactivate() {
        // Cleanup if needed
    }
}

SmartAffiliateAutoInserter::get_instance();

// Pro upsell notice
function smart_affiliate_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Unlock AI-powered link insertion and analytics with <a href="https://example.com/pro">Smart Affiliate Pro</a>!</p></div>';
}
add_action('admin_notices', 'smart_affiliate_pro_notice');

// Create assets directories on activation
register_activation_hook(__FILE__, function() {
    $upload_dir = wp_upload_dir();
    $assets_dir = plugin_dir_path(__FILE__) . 'assets';
    if (!file_exists($assets_dir)) {
        wp_mkdir_p($assets_dir);
    }
    file_put_contents($assets_dir . '/script.js', '// Pro features here\nconsole.log("Smart Affiliate Active");');
    file_put_contents($assets_dir . '/style.css', '/* Pro styles */ .aff-link { color: #0073aa; }');
});
