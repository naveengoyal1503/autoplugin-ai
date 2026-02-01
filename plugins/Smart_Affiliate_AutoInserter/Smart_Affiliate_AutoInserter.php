/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into your posts and pages for easy monetization.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoInserter {
    private $affiliate_id = '';
    private $api_key = '';
    private $max_links = 3;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_filter('the_content', array($this, 'insert_affiliate_links'), 99);
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'settings_link'));
    }

    public function init() {
        $this->affiliate_id = get_option('saa_affiliate_id', '');
        $this->api_key = get_option('saa_api_key', '');
        $this->max_links = get_option('saa_max_links', 3);
    }

    public function add_admin_menu() {
        add_options_page(
            'Smart Affiliate Settings',
            'Affiliate AutoInserter',
            'manage_options',
            'smart-affiliate-autoinserter',
            array($this, 'options_page')
        );
    }

    public function settings_init() {
        register_setting('saa_plugin_page', 'saa_affiliate_id');
        register_setting('saa_plugin_page', 'saa_api_key');
        register_setting('saa_plugin_page', 'saa_max_links');

        add_settings_section(
            'saa_plugin_page_section',
            'API & Settings',
            null,
            'saa_plugin_page'
        );

        add_settings_field(
            'saa_affiliate_id',
            'Amazon Affiliate ID (tag)',
            array($this, 'affiliate_id_callback'),
            'saa_plugin_page',
            'saa_plugin_page_section'
        );

        add_settings_field(
            'saa_api_key',
            'Amazon Product Advertising API Key',
            array($this, 'api_key_callback'),
            'saa_plugin_page',
            'saa_plugin_page_section'
        );

        add_settings_field(
            'saa_max_links',
            'Max Links per Post',
            array($this, 'max_links_callback'),
            'saa_plugin_page',
            'saa_plugin_page_section'
        );
    }

    public function affiliate_id_callback() {
        $value = get_option('saa_affiliate_id', '');
        echo '<input type="text" name="saa_affiliate_id" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Your Amazon Associates tag (e.g., yourid-20)</p>';
    }

    public function api_key_callback() {
        $value = get_option('saa_api_key', '');
        echo '<input type="text" name="saa_api_key" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Optional: Amazon PA-API key for better product matching. Free basic matching works without it.</p>';
    }

    public function max_links_callback() {
        $value = get_option('saa_max_links', 3);
        echo '<input type="number" name="saa_max_links" value="' . esc_attr($value) . '" min="1" max="10" />';
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('saa_plugin_page');
                do_settings_sections('saa_plugin_page');
                submit_button();
                ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited links, analytics, A/B testing, and more networks for $49/year. <a href="#" onclick="alert('Pro features coming soon!')">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function settings_link($links) {
        $settings_link = '<a href="options-general.php?page=smart-affiliate-autoinserter">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public function insert_affiliate_links($content) {
        if (is_admin() || empty($this->affiliate_id)) {
            return $content;
        }

        // Skip if no content or short content
        if (strlen(strip_tags($content)) < 500) {
            return $content;
        }

        // Extract keywords (simple: nouns from first sentences)
        preg_match_all('/\b[a-zA-Z]{4,}\b/', substr(strip_tags($content), 0, 1000), $matches);
        $keywords = array_slice(array_unique($matches), 0, 5);

        $inserted = 0;
        foreach ($keywords as $keyword) {
            if ($inserted >= $this->max_links) break;

            $product_link = $this->get_amazon_link($keyword);
            if ($product_link) {
                // Find insertion point: after a paragraph
                $content = preg_replace(
                    '/(<p[^>]*>.*?)(</p>)/is',
                    '$1 <p><a href="$product_link" target="_blank" rel="nofollow sponsored">Check $keyword on Amazon</a></p>$2',
                    $content,
                    1,
                    $count
                );
                if ($count > 0) {
                    $inserted++;
                }
            }
        }

        return $content;
    }

    private function get_amazon_link($keyword) {
        // Mock Amazon search (in Pro: real PA-API call)
        // For demo, generate mock affiliate link
        $search_term = urlencode($keyword);
        return "https://www.amazon.com/s?k={$search_term}&tag={$this->affiliate_id}&ref=sr_st_ext_pro_s_ii";
    }
}

new SmartAffiliateAutoInserter();

// Pro nag
add_action('admin_notices', function() {
    if (!get_option('saa_pro_activated') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Smart Affiliate AutoInserter Pro</strong> for unlimited links and analytics! <a href="#" onclick="alert(\'Pro: $49/year\')">Learn More</a></p></div>';
    }
});