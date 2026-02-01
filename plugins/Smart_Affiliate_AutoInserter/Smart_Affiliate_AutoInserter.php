/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into posts and pages using keyword matching to maximize commissions.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit;
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
    }

    public function init() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'auto_insert_links'), 99);
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-affiliate-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
    }

    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'smart-affiliate') !== false) {
            wp_enqueue_script('smart-affiliate-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), '1.0.0', true);
        }
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate Settings',
            'Affiliate Inserter',
            'manage_options',
            'smart-affiliate',
            array($this, 'settings_page')
        );
    }

    public function admin_init() {
        register_setting('smart_affiliate_options', 'smart_affiliate_settings');
        add_settings_section('general_section', 'General Settings', null, 'smart_affiliate');
        add_settings_field('affiliate_links', 'Affiliate Links', array($this, 'affiliate_links_field'), 'smart_affiliate', 'general_section');
        add_settings_field('max_links', 'Max Links per Post (Free: 2, Pro: Unlimited)', array($this, 'max_links_field'), 'smart_affiliate', 'general_section');
        add_settings_field('enable_pro', 'Enable Pro Features', array($this, 'enable_pro_field'), 'smart_affiliate', 'general_section');
    }

    public function affiliate_links_field() {
        $settings = get_option('smart_affiliate_settings', array());
        $links = isset($settings['links']) ? $settings['links'] : array();
        echo '<textarea name="smart_affiliate_settings[links]" rows="10" cols="50" placeholder="Keyword|Affiliate URL\nexample|https://amazon.com/product">' . esc_textarea(implode("\n", $links)) . '</textarea>';
        echo '<p class="description">Enter keywords and affiliate URLs, one per line: keyword|url</p>';
    }

    public function max_links_field() {
        $settings = get_option('smart_affiliate_settings', array());
        $max = isset($settings['max_links']) ? $settings['max_links'] : 2;
        echo '<input type="number" name="smart_affiliate_settings[max_links]" value="' . esc_attr($max) . '" min="1" max="10">';
        echo '<p class="description">Maximum links to insert per post. Upgrade to Pro for unlimited.</p>';
    }

    public function enable_pro_field() {
        $settings = get_option('smart_affiliate_settings', array());
        $pro = isset($settings['pro_key']) ? $settings['pro_key'] : '';
        echo '<input type="text" name="smart_affiliate_settings[pro_key]" value="' . esc_attr($pro) . '" placeholder="Enter Pro License Key">';
        echo '<p class="description">Get Pro at <a href="https://example.com/pro" target="_blank">example.com/pro</a></p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('smart_affiliate_options');
                do_settings_sections('smart_affiliate');
                submit_button();
                ?>
            </form>
            <div id="pro-upsell">
                <h2>Upgrade to Pro</h2>
                <p>Unlock unlimited links, A/B testing, analytics, and priority support for $49/year!</p>
                <a href="https://example.com/pro" class="button button-primary" target="_blank">Buy Pro Now</a>
            </div>
        </div>
        <?php
    }

    public function auto_insert_links($content) {
        if (is_admin() || !is_single() || is_admin()) return $content;

        $settings = get_option('smart_affiliate_settings', array());
        $links = isset($settings['links']) ? $settings['links'] : array();
        if (empty($links)) return $content;

        $pro_key = isset($settings['pro_key']) ? $settings['pro_key'] : '';
        $is_pro = !empty($pro_key) && hash('sha256', $pro_key) === 'pro_verified_hash_example'; // Demo verification
        $max_links = $is_pro ? 999 : (isset($settings['max_links']) ? (int)$settings['max_links'] : 2);

        $inserted = 0;
        $words = explode(' ', $content);

        foreach ($words as &$word) {
            if ($inserted >= $max_links) break;

            foreach ($links as $link_entry) {
                $parts = explode('|', $link_entry, 2);
                if (count($parts) !== 2) continue;
                list($keyword, $url) = $parts;
                $keyword = strtolower(trim($keyword));

                if (stripos($word, $keyword) !== false) {
                    $link = '<a href="' . esc_url($url) . '" target="_blank" rel="nofollow sponsored">' . $word . '</a>';
                    $word = $link;
                    $inserted++;
                    break;
                }
            }
        }

        $content = implode(' ', $words);
        return $content;
    }
}

SmartAffiliateAutoInserter::get_instance();

// Pro teaser notice
function smart_affiliate_admin_notice() {
    $settings = get_option('smart_affiliate_settings', array());
    if (empty($settings['pro_key'])) {
        echo '<div class="notice notice-info"><p>Boost your earnings with <strong>Smart Affiliate AutoInserter Pro</strong>! <a href="' . admin_url('options-general.php?page=smart-affiliate') . '">Upgrade now</a>.</p></div>';
    }
}
add_action('admin_notices', 'smart_affiliate_admin_notice');

// Asset placeholders - create empty dirs/files in assets/
// assets/frontend.js and assets/admin.js should be empty or minimal JS
?>