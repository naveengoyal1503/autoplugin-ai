/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoLink_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoLink Pro
 * Plugin URI: https://example.com/smart-affiliate-autolink
 * Description: Automatically inserts relevant affiliate links into your content to maximize earnings.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autolink
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateAutoLink {
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
        add_filter('the_content', array($this, 'auto_link_content'), 99);
        add_filter('widget_text', array($this, 'auto_link_content'), 99);
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-autolink', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-affiliate-autolink', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('smart-affiliate-autolink', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate AutoLink Settings',
            'Affiliate AutoLink',
            'manage_options',
            'smart-affiliate-autolink',
            array($this, 'settings_page')
        );
    }

    public function admin_init() {
        register_setting('smart_affiliate_options', 'smart_affiliate_settings');
        add_settings_section('main_section', 'Main Settings', null, 'smart_affiliate');
        add_settings_field('keywords', 'Keywords & Links (JSON format: {"keyword":"affiliate_url"})', array($this, 'keywords_field'), 'smart_affiliate', 'main_section');
        add_settings_field('max_links', 'Max Links Per Post', array($this, 'max_links_field'), 'smart_affiliate', 'main_section');
        add_settings_field('pro_nudge', 'Upgrade to Pro', array($this, 'pro_nudge_field'), 'smart_affiliate', 'main_section');
    }

    public function keywords_field() {
        $settings = get_option('smart_affiliate_settings', array('keywords' => '{"amazon":"https://amazon.com/affiliate-link","clickbank":"https://clickbank.net/affiliate-link"}', 'max_links' => 3));
        echo '<textarea name="smart_affiliate_settings[keywords]" rows="10" cols="50">' . esc_textarea($settings['keywords']) . '</textarea>';
        echo '<p class="description">Enter keywords and affiliate links in JSON format. Pro version supports unlimited entries and AI keyword detection.</p>';
    }

    public function max_links_field() {
        $settings = get_option('smart_affiliate_settings', array('max_links' => 3));
        echo '<input type="number" name="smart_affiliate_settings[max_links]" value="' . esc_attr($settings['max_links']) . '" min="1" max="10" />';
        echo '<p class="description">Maximum affiliate links to insert per post. Pro unlocks unlimited.</p>';
    }

    public function pro_nudge_field() {
        echo '<div class="notice notice-info"><p><strong>Pro Features:</strong> AI keyword suggestions, A/B testing, analytics dashboard, premium integrations (Amazon, ClickBank, etc.). <a href="https://example.com/pro" target="_blank">Upgrade Now!</a></p></div>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoLink Pro</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('smart_affiliate_options');
                do_settings_sections('smart_affiliate');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function auto_link_content($content) {
        if (is_admin() || !is_main_query()) return $content;

        $settings = get_option('smart_affiliate_settings', array('keywords' => '{}', 'max_links' => 3));
        $keywords = json_decode($settings['keywords'], true);
        if (empty($keywords) || !is_array($keywords)) return $content;

        $max_links = intval($settings['max_links']);
        $inserted = 0;

        foreach ($keywords as $keyword => $url) {
            if ($inserted >= $max_links) break;
            $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
            $content = preg_replace_callback($pattern, function($matches) use ($url, &$inserted) {
                if ($inserted >= 3) return $matches; // Free limit
                $inserted++;
                return '<a href="' . esc_url($url) . '" target="_blank" rel="nofollow sponsored">' . $matches . '</a>';
            }, $content);
        }

        // Pro teaser
        if (rand(1, 10) === 1) {
            $content .= '<p><em>Unlock AI-powered linking with <a href="https://example.com/pro">Smart Affiliate Pro</a></em></p>';
        }

        return $content;
    }

    public function activate() {
        add_option('smart_affiliate_settings', array('keywords' => '{"hosting":"https://your-affiliate-link.com/hosting","plugin":"https://your-affiliate-link.com/plugin"}', 'max_links' => 3));
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

SmartAffiliateAutoLink::get_instance();

// Create assets directories if needed
$upload_dir = wp_upload_dir();
$assets_dir = plugin_dir_path(__FILE__) . 'assets';
if (!file_exists($assets_dir)) {
    wp_mkdir_p($assets_dir);
}

// Minimal JS
file_put_contents($assets_dir . '/script.js', "jQuery(document).ready(function($){ $('.aff-link').hover(function(){ $(this).css('color','#0073aa'); }); });");

// Minimal CSS
file_put_contents($assets_dir . '/style.css', ".aff-link { text-decoration: none; color: #333; } .aff-link:hover { text-decoration: underline; }");

?>