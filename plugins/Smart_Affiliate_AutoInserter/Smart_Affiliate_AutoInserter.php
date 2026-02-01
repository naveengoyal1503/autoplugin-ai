/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into your posts and pages using keyword matching and context analysis.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 * Requires at least: 5.0
 * Tested up to: 6.4
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateAutoInserter {
    private $affiliate_id = '';
    private $keywords = array();
    private $products = array();

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_loaded', array($this, 'load_settings'));
        add_filter('the_content', array($this, 'insert_affiliate_links'), 99);
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-autoinserter');
    }

    public function load_settings() {
        $this->affiliate_id = get_option('saa_affiliate_id', '');
        $this->keywords = get_option('saa_keywords', array('laptop', 'phone', 'book'));
        $this->products = get_option('saa_products', array(
            'laptop' => 'https://amazon.com/dp/B08N5WRWNW?tag=YOURAFFILIATEID',
            'phone' => 'https://amazon.com/dp/B0C7V4L2NH?tag=YOURAFFILIATEID',
            'book' => 'https://amazon.com/dp/1234567890?tag=YOURAFFILIATEID'
        ));
    }

    public function insert_affiliate_links($content) {
        if (empty($this->affiliate_id) || is_admin() || is_feed()) {
            return $content;
        }

        global $post;
        if (!$post || in_array($post->post_status, array('draft', 'auto-draft'))) {
            return $content;
        }

        $words = explode(' ', $content);
        $inserted = 0;
        $max_links = 3;

        foreach ($this->keywords as $keyword) {
            if ($inserted >= $max_links) break;

            foreach ($words as $key => $word) {
                if (stripos($word, $keyword) !== false && isset($this->products[$keyword])) {
                    $link = '<a href="' . esc_url($this->products[$keyword]) . '" target="_blank" rel="nofollow sponsored">' . esc_html($keyword) . '</a>';
                    $words[$key] = $link;
                    $inserted++;
                    break;
                }
            }
        }

        return implode(' ', $words);
    }

    public function add_admin_menu() {
        add_options_page(
            'Smart Affiliate AutoInserter Settings',
            'Affiliate Inserter',
            'manage_options',
            'smart-affiliate-autoinserter',
            array($this, 'options_page')
        );
    }

    public function settings_init() {
        register_setting('saa_plugin_page', 'saa_affiliate_id');
        register_setting('saa_plugin_page', 'saa_keywords');
        register_setting('saa_plugin_page', 'saa_products');

        add_settings_section('saa_plugin_page_section', 'Core Settings', null, 'saa_plugin_page');

        add_settings_field('saa_affiliate_id', 'Amazon Affiliate ID', array($this, 'affiliate_id_callback'), 'saa_plugin_page', 'saa_plugin_page_section');
        add_settings_field('saa_keywords', 'Keywords (comma-separated)', array($this, 'keywords_callback'), 'saa_plugin_page', 'saa_plugin_page_section');
        add_settings_field('saa_products', 'Products (JSON: {"keyword":"amazon_url"})', array($this, 'products_callback'), 'saa_plugin_page', 'saa_plugin_page_section');
    }

    public function affiliate_id_callback() {
        $value = get_option('saa_affiliate_id', '');
        echo '<input type="text" id="saa_affiliate_id" name="saa_affiliate_id" value="' . esc_attr($value) . '" size="50" />';
    }

    public function keywords_callback() {
        $value = get_option('saa_keywords', 'laptop,phone,book');
        echo '<textarea id="saa_keywords" name="saa_keywords" rows="3" cols="50">' . esc_textarea(implode(',', (array)$value)) . '</textarea>';
        echo '<p class="description">Comma-separated keywords to scan for.</p>';
    }

    public function products_callback() {
        $value = get_option('saa_products', json_encode(array(
            'laptop' => 'https://amazon.com/dp/B08N5WRWNW?tag=YOURID',
            'phone' => 'https://amazon.com/dp/B0C7V4L2NH?tag=YOURID',
            'book' => 'https://amazon.com/dp/1234567890?tag=YOURID'
        )));
        echo '<textarea id="saa_products" name="saa_products" rows="5" cols="50">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">JSON object mapping keywords to Amazon affiliate URLs. Replace YOURID with your affiliate tag.</p>';
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
            <p><strong>Upgrade to Pro:</strong> Advanced AI context matching, link analytics, A/B testing, and more! <a href="https://example.com/pro">Get Pro</a></p>
        </div>
        <?php
    }

    public function activate() {
        add_option('saa_keywords', array('laptop', 'phone', 'book'));
    }

    public function deactivate() {
        // Cleanup if needed
    }
}

new SmartAffiliateAutoInserter();