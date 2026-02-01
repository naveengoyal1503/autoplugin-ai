/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoLinker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoLinker Pro
 * Plugin URI: https://example.com/smart-affiliate-autolinker
 * Description: Automatically converts keywords in your content to affiliate links from Amazon, generating passive commissions with smart cloaking and performance tracking.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autolinker
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateAutoLinker {
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_settings_init'));
        add_filter('the_content', array($this, 'auto_link_keywords'), 20);
        add_filter('widget_text', array($this, 'auto_link_keywords'), 20);
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-autolinker');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-affiliate-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('smart-affiliate-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function add_admin_menu() {
        add_options_page(
            'Smart Affiliate AutoLinker Settings',
            'Affiliate AutoLinker',
            'manage_options',
            'smart-affiliate-autolinker',
            array($this, 'settings_page')
        );
    }

    public function admin_settings_init() {
        register_setting('smart_affiliate_settings', 'smart_affiliate_options');

        add_settings_section('main_section', 'Main Settings', null, 'smart_affiliate_page');

        add_settings_field('amazon_affiliate_id', 'Amazon Affiliate ID (Tag)', array($this, 'amazon_affiliate_id_callback'), 'smart_affiliate_page', 'main_section');
        add_settings_field('keywords', 'Keywords (one per line: keyword|product_url)', array($this, 'keywords_callback'), 'smart_affiliate_page', 'main_section');
        add_settings_field('max_links_per_post', 'Max Links Per Post', array($this, 'max_links_callback'), 'smart_affiliate_page', 'main_section');
        add_settings_field('enable_cloaking', 'Enable Link Cloaking', array($this, 'cloaking_callback'), 'smart_affiliate_page', 'main_section');
    }

    public function amazon_affiliate_id_callback() {
        $options = get_option('smart_affiliate_options');
        $affiliate_id = isset($options['amazon_affiliate_id']) ? esc_attr($options['amazon_affiliate_id']) : '';
        echo '<input type="text" id="amazon_affiliate_id" name="smart_affiliate_options[amazon_affiliate_id]" value="' . $affiliate_id . '" class="regular-text" />';
        echo '<p class="description">Your Amazon Associates tag (e.g., yourid-20).</p>';
    }

    public function keywords_callback() {
        $options = get_option('smart_affiliate_options');
        $keywords = isset($options['keywords']) ? esc_textarea($options['keywords']) : '';
        echo '<textarea id="keywords" name="smart_affiliate_options[keywords]" rows="10" cols="50">' . $keywords . '</textarea>';
        echo '<p class="description">One per line: keyword|amazon_product_url (e.g., wireless headphones|https://amazon.com/dp/B08N5WRWNW)</p>';
    }

    public function max_links_callback() {
        $options = get_option('smart_affiliate_options');
        $max = isset($options['max_links']) ? esc_attr($options['max_links']) : '3';
        echo '<input type="number" id="max_links" name="smart_affiliate_options[max_links]" value="' . $max . '" min="1" max="10" />';
    }

    public function cloaking_callback() {
        $options = get_option('smart_affiliate_options');
        $cloaking = isset($options['enable_cloaking']) ? checked(1, $options['enable_cloaking'], false) : '';
        echo '<input type="checkbox" id="enable_cloaking" name="smart_affiliate_options[enable_cloaking]" value="1" ' . $cloaking . ' />';
        echo '<p class="description">Cloak affiliate links to improve click-through rates.</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoLinker Pro</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('smart_affiliate_settings');
                do_settings_sections('smart_affiliate_page');
                submit_button();
                ?>
            </form>
            <?php if (get_option('smart_affiliate_options')): ?>
            <h2>Stats</h2>
            <p>Upgrade to Pro for click tracking and earnings dashboard.</p>
            <?php endif; ?>
        </div>
        <?php
    }

    public function auto_link_keywords($content) {
        if (is_admin() || !is_main_query()) return $content;

        $options = get_option('smart_affiliate_options');
        if (empty($options['keywords']) || empty($options['amazon_affiliate_id'])) return $content;

        $keywords = explode("\n", trim($options['keywords']));
        $max_links = isset($options['max_links']) ? (int)$options['max_links'] : 3;
        $link_count = 0;

        foreach ($keywords as $line) {
            $parts = explode('|', trim($line), 2);
            if (count($parts) !== 2) continue;

            $keyword = trim($parts);
            $url = trim($parts[1]);
            if (empty($keyword) || empty($url)) continue;

            // Append affiliate tag if not present
            if (strpos($url, $options['amazon_affiliate_id']) === false) {
                $url = add_query_arg('tag', $options['amazon_affiliate_id'], $url);
            }

            // Cloak if enabled
            $cloaked_url = $options['enable_cloaking'] ? $this->cloak_url($url) : $url;

            // Replace keyword (case insensitive, whole word only)
            $regex = '/\b' . preg_quote($keyword, '/') . '\b/i';
            if (preg_match_all($regex, $content, $matches, PREG_OFFSET_CAPTURE) && $link_count < $max_links) {
                $content = preg_replace($regex, '<a href="' . esc_url($cloaked_url) . '" rel="nofollow sponsored" target="_blank">$0</a>', $content, 1);
                $link_count++;
            }
        }

        return $content;
    }

    private function cloak_url($url) {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);
        return $scheme . '://' . $host . '/go/?url=' . base64_encode($url);
    }

    public function activate() {
        add_option('smart_affiliate_options', array());
    }

    public function deactivate() {
        // No-op
    }
}

SmartAffiliateAutoLinker::get_instance();

// Pro upsell notice
function smart_affiliate_admin_notice() {
    if (!current_user_can('manage_options')) return;
    $screen = get_current_screen();
    if ($screen->id === 'settings_page_smart-affiliate-autolinker') {
        echo '<div class="notice notice-info"><p><strong>Pro Features:</strong> Unlimited keywords, click tracking, A/B testing, analytics dashboard. <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
    }
}
add_action('admin_notices', 'smart_affiliate_admin_notice');

// Free version limits
function smart_affiliate_limit_notice($content) {
    static $shown = false;
    if (!$shown && is_singular() && rand(1, 10) === 1) {
        $content .= '<p style="background: #fff3cd; padding: 10px; margin: 20px 0; border-left: 4px solid #ffeaa7;"><strong>Love AutoLinker?</strong> Unlock unlimited links & stats with Pro! <a href="' . admin_url('options-general.php?page=smart-affiliate-autolinker') . '">Upgrade</a></p>';
        $shown = true;
    }
    return $content;
}
add_filter('the_content', 'smart_affiliate_limit_notice', 100);