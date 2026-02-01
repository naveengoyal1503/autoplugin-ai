/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into posts using keyword matching to maximize affiliate earnings.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateAutoInserter {
    private $affiliate_tag;
    private $api_key;
    private $enabled;

    public function __construct() {
        $this->affiliate_tag = get_option('saa_affiliate_tag', '');
        $this->api_key = get_option('saa_api_key', '');
        $this->enabled = get_option('saa_enabled', false);

        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_filter('the_content', array($this, 'insert_affiliate_links'), 99);
        add_action('wp_ajax_saa_search_products', array($this, 'ajax_search_products'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-autoinserter');
    }

    public function add_admin_menu() {
        add_options_page(
            'Smart Affiliate AutoInserter Settings',
            'Affiliate AutoInserter',
            'manage_options',
            'smart-affiliate-autoinserter',
            array($this, 'options_page')
        );
    }

    public function settings_init() {
        register_setting('saa_plugin_page', 'saa_affiliate_tag');
        register_setting('saa_plugin_page', 'saa_api_key');
        register_setting('saa_plugin_page', 'saa_enabled');
        register_setting('saa_plugin_page', 'saa_max_links');
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Smart Affiliate AutoInserter', 'smart-affiliate-autoinserter'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('saa_plugin_page');
                do_settings_sections('saa_plugin_page');
                submit_button();
                ?>
            </form>
            <div id="saa-test-section">
                <h2><?php esc_html_e('Test Keyword Search', 'smart-affiliate-autoinserter'); ?></h2>
                <input type="text" id="saa-keyword" placeholder="Enter keyword (e.g., laptop)">
                <button id="saa-search-btn" class="button">Search Products</button>
                <div id="saa-results"></div>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#saa-search-btn').click(function() {
                var keyword = $('#saa-keyword').val();
                $.post(ajaxurl, {
                    action: 'saa_search_products',
                    keyword: keyword
                }, function(response) {
                    $('#saa-results').html(response);
                });
            });
        });
        </script>
        <?php
    }

    public function ajax_search_products() {
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        $keyword = sanitize_text_field($_POST['keyword']);
        $products = $this->search_amazon_products($keyword);
        echo '<ul>';
        foreach ($products as $product) {
            echo '<li>' . esc_html($product['title']) . ' - <a href="' . esc_url($product['url']) . '" target="_blank">View</a></li>';
        }
        echo '</ul>';
        wp_die();
    }

    private function search_amazon_products($keyword) {
        // Mock Amazon PA API response for demo (replace with real API in Pro)
        $mock_products = array(
            array('title' => 'Sample Product 1 for ' . $keyword, 'url' => 'https://amazon.com/sample1?tag=' . $this->affiliate_tag),
            array('title' => 'Sample Product 2 for ' . $keyword, 'url' => 'https://amazon.com/sample2?tag=' . $this->affiliate_tag)
        );
        return $mock_products;
    }

    public function insert_affiliate_links($content) {
        if (!$this->enabled || empty($this->affiliate_tag) || is_admin()) {
            return $content;
        }

        $max_links = intval(get_option('saa_max_links', 2));
        $keywords = array('laptop', 'phone', 'book', 'camera'); // Demo keywords
        $inserted = 0;

        foreach ($keywords as $keyword) {
            if (stripos($content, $keyword) !== false && $inserted < $max_links) {
                $products = $this->search_amazon_products($keyword);
                if (!empty($products)) {
                    $link = '<p><strong>Recommended:</strong> Check out this <a href="' . esc_url($products['url']) . '" target="_blank" rel="nofollow">' . esc_html($products['title']) . '</a></p>';
                    $content = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', $keyword . '<span class="saa-aff-link">' . $link . '</span>', $content, 1);
                    $inserted++;
                }
            }
        }
        return $content;
    }
}

new SmartAffiliateAutoInserter();