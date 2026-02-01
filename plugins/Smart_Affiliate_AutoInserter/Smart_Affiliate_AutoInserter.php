/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate
 * Description: Automatically inserts Amazon affiliate links into content using smart keyword matching.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoInserter {
    private static $instance = null;
    public $options;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->options = get_option('smart_affiliate_options', array('api_key' => '', 'affiliate_id' => '', 'enabled' => 1));
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'auto_insert_links'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-affiliate-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
    }

    public function admin_scripts($hook) {
        if (strpos($hook, 'smart-affiliate') !== false) {
            wp_enqueue_script('smart-affiliate-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), '1.0.0', true);
        }
    }

    public function auto_insert_links($content) {
        if (!$this->options['enabled'] || is_admin() || in_the_loop() === false) {
            return $content;
        }

        $keywords = array(
            'laptop' => 'https://amazon.com/dp/B08N5WRWNW?tag=YOURAFFILIATEID',
            'phone' => 'https://amazon.com/dp/B0BTY2G5T3?tag=YOURAFFILIATEID',
            'book' => 'https://amazon.com/dp/0596008275?tag=YOURAFFILIATEID',
            'coffee' => 'https://amazon.com/dp/B07H585Q71?tag=YOURAFFILIATEID'
        );

        $words = explode(' ', $content);
        foreach ($words as $key => $word) {
            $lower_word = strtolower(trim($word, '.,!?'));
            if (isset($keywords[$lower_word]) && rand(1, 5) === 1) { // 20% chance
                $link = '<a href="' . esc_url($keywords[$lower_word]) . '" target="_blank" rel="nofollow sponsored" class="smart-aff-link">' . esc_html($word) . '</a> ';
                $words[$key] = $link;
            }
        }

        return implode(' ', $words);
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate Settings',
            'Smart Affiliate',
            'manage_options',
            'smart-affiliate',
            array($this, 'settings_page')
        );
    }

    public function admin_init() {
        register_setting('smart_affiliate_group', 'smart_affiliate_options');
        add_settings_section('smart_main_section', 'Main Settings', null, 'smart-affiliate');
        add_settings_field('enabled', 'Enable Auto-Insertion', array($this, 'enabled_cb'), 'smart-affiliate', 'smart_main_section');
        add_settings_field('affiliate_id', 'Amazon Affiliate ID', array($this, 'affiliate_id_cb'), 'smart-affiliate', 'smart_main_section');
        add_settings_field('api_key', 'Premium API Key (Pro)', array($this, 'api_key_cb'), 'smart-affiliate', 'smart_main_section');
    }

    public function enabled_cb() {
        $checked = isset($this->options['enabled']) ? checked(1, $this->options['enabled'], false) : '';
        echo '<input type="checkbox" id="enabled" name="smart_affiliate_options[enabled]" value="1" ' . $checked . ' />';
    }

    public function affiliate_id_cb() {
        echo '<input type="text" id="affiliate_id" name="smart_affiliate_options[affiliate_id]" value="' . esc_attr($this->options['affiliate_id']) . '" class="regular-text" placeholder="your-affiliate-id" />';
        echo '<p class="description">Replace YOURAFFILIATEID in links with this tag.</p>';
    }

    public function api_key_cb() {
        echo '<input type="text" id="api_key" name="smart_affiliate_options[api_key]" value="' . esc_attr($this->options['api_key']) . '" class="regular-text" placeholder="Pro Feature" />';
        echo '<p class="description"><strong>Pro:</strong> Unlock AI keyword detection and analytics. <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('smart_affiliate_group');
                do_settings_sections('smart-affiliate');
                submit_button();
                ?>
            </form>
            <div class="smart-pro-upsell">
                <h3>Go Pro Today!</h3>
                <ul>
                    <li>✅ AI-Powered Keyword Matching</li>
                    <li>✅ WooCommerce Product Integration</li>
                    <li>✅ Click Analytics Dashboard</li>
                    <li>✅ Custom Product Database</li>
                </ul>
                <a href="https://example.com/pro" class="button button-primary button-large">Get Pro - $49/year</a>
            </div>
        </div>
        <?php
    }

    public function activate() {
        add_option('smart_affiliate_options', array('enabled' => 1, 'affiliate_id' => '', 'api_key' => ''));
    }

    public function deactivate() {
        // Cleanup if needed
    }
}

SmartAffiliateAutoInserter::get_instance();

// Premium teaser notice
function smart_affiliate_admin_notice() {
    if (!current_user_can('manage_options')) return;
    $screen = get_current_screen();
    if ($screen->id !== 'settings_page_smart-affiliate') {
        echo '<div class="notice notice-info"><p><strong>Smart Affiliate:</strong> Upgrade to Pro for AI features and analytics! <a href="' . admin_url('options-general.php?page=smart-affiliate') . '">Settings →</a></p></div>';
    }
}
add_action('admin_notices', 'smart_affiliate_admin_notice');

// Create assets directories on activation
register_activation_hook(__FILE__, function() {
    $upload_dir = wp_upload_dir();
    $assets_dir = $upload_dir['basedir'] . '/smart-affiliate/';
    if (!file_exists($assets_dir)) {
        wp_mkdir_p($assets_dir . 'assets');
        wp_mkdir_p($assets_dir . 'assets/frontend');
        wp_mkdir_p($assets_dir . 'assets/admin');
    }
});