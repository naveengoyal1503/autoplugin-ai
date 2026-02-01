/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into posts and pages using keyword matching to boost affiliate earnings.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateAutoInserter {
    private $affiliate_id;
    private $keywords;
    private $products;

    public function __construct() {
        $this->affiliate_id = get_option('saa_affiliate_id', '');
        $this->keywords = get_option('saa_keywords', array());
        $this->products = get_option('saa_products', array());

        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_filter('the_content', array($this, 'insert_affiliate_links'));
        add_filter('wp_head', array($this, 'pro_nag'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-autoinserter');
    }

    public function add_admin_menu() {
        add_options_page(
            'Smart Affiliate AutoInserter Settings',
            'Affiliate AutoInserter',
            'manage_options',
            'saa-settings',
            array($this, 'options_page')
        );
    }

    public function settings_init() {
        register_setting('saa_plugin', 'saa_affiliate_id');
        register_setting('saa_plugin', 'saa_keywords');
        register_setting('saa_plugin', 'saa_products');

        add_settings_section('saa_plugin_page_section', __('Main Settings', 'smart-affiliate-autoinserter'), null, 'saa-settings');

        add_settings_field('saa_affiliate_id', __('Amazon Affiliate ID (tag)', 'smart-affiliate-autoinserter'), array($this, 'affiliate_id_callback'), 'saa-settings', 'saa_plugin_page_section');
        add_settings_field('saa_keywords', __('Keywords and Products (JSON format: {"keyword":"product_url"})', 'smart-affiliate-autoinserter'), array($this, 'keywords_callback'), 'saa-settings', 'saa_plugin_page_section');
    }

    public function affiliate_id_callback() {
        $value = get_option('saa_affiliate_id', '');
        echo '<input type="text" name="saa_affiliate_id" value="' . esc_attr($value) . '" class="regular-text" placeholder="youraffiliate-21" />';
        echo '<p class="description">Enter your Amazon Associates tag (e.g., youraffiliate-21).</p>';
    }

    public function keywords_callback() {
        $value = get_option('saa_keywords', '{"laptop":"https://amazon.com/dp/B08N5WRWNW","phone":"https://amazon.com/dp/B0C7C4G5T6"}');
        echo '<textarea name="saa_keywords" rows="10" cols="50" class="large-text code">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">Enter JSON like: {"keyword":"amazon_product_url"}. Free version supports up to 10 keywords.</p>';
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Smart Affiliate AutoInserter Settings', 'smart-affiliate-autoinserter'); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('saa_plugin');
                do_settings_sections('saa-settings');
                submit_button();
                ?>
            </form>
            <div class="notice notice-info">
                <p><strong>Pro Features (Upgrade for $49/year):</strong> Unlimited keywords, AI-powered matching, click analytics, link cloaking, multi-site support.</p>
                <p><a href="https://example.com/pro" target="_blank" class="button button-primary">Upgrade to Pro</a></p>
            </div>
        </div>
        <?php
    }

    public function insert_affiliate_links($content) {
        if (empty($this->affiliate_id) || empty($this->keywords)) {
            return $content;
        }

        $keywords = json_decode($this->keywords, true);
        if (!$keywords || count($keywords) > 10) {
            return $content; // Free limit
        }

        $words = explode(' ', strip_tags($content));
        foreach ($keywords as $keyword => $product_url) {
            foreach ($words as $i => $word) {
                if (stripos($word, $keyword) !== false && rand(1, 5) === 1) { // Insert ~20% chance
                    $link = '<a href="' . esc_url($product_url . '?tag=' . esc_attr($this->affiliate_id)) . '" target="_blank" rel="nofollow sponsored">' . esc_html($word) . '</a>';
                    $words[$i] = $link;
                    break 2;
                }
            }
        }

        return implode(' ', $words);
    }

    public function pro_nag() {
        if (current_user_can('manage_options') && !defined('SAA_PRO_VERSION')) {
            echo '<div style="position:fixed;bottom:20px;right:20px;background:#0073aa;color:white;padding:10px;border-radius:5px;z-index:9999;">
                <strong>Pro Tip:</strong> Unlock unlimited features! <a href="/wp-admin/options-general.php?page=saa-settings" style="color:#fff;">Upgrade Now</a>
            </div>';
        }
    }
}

new SmartAffiliateAutoInserter();