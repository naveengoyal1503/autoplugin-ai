/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into your WordPress posts and pages using keyword matching to boost commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateAutoInserter {

    private $api_key;
    private $affiliate_id;

    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->api_key = get_option('saai_api_key', '');
        $this->affiliate_id = get_option('saai_affiliate_id', '');

        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_init', array($this, 'admin_init'));
        } else {
            add_filter('the_content', array($this, 'insert_affiliate_links'), 99);
            add_filter('the_excerpt', array($this, 'insert_affiliate_links'), 99);
        }
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate AutoInserter',
            'Affiliate Inserter',
            'manage_options',
            'smart-affiliate-autoinserter',
            array($this, 'settings_page')
        );
    }

    public function admin_init() {
        register_setting('saai_settings', 'saai_api_key');
        register_setting('saai_settings', 'saai_affiliate_id');
        register_setting('saai_settings', 'saai_free_limit', array('default' => 2));
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('saai_settings'); ?>
                <?php do_settings_sections('saai_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Amazon API Key</th>
                        <td><input type="text" name="saai_api_key" value="<?php echo esc_attr($this->api_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Your Affiliate ID (tag)</th>
                        <td><input type="text" name="saai_affiliate_id" value="<?php echo esc_attr($this->affiliate_id); ?>" class="regular-text" /> <p class="description">e.g., yourid-20</p></td>
                    </tr>
                    <tr>
                        <th>Free Version Link Limit per Post</th>
                        <td><input type="number" name="saai_free_limit" value="<?php echo esc_attr(get_option('saai_free_limit', 2)); ?>" min="1" max="5" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited links, analytics, and more for $29/year. <a href="https://example.com/pro" target="_blank">Get Pro</a></p>
        </div>
        <?php
    }

    public function insert_affiliate_links($content) {
        if (empty($this->api_key) || empty($this->affiliate_id) || is_admin()) {
            return $content;
        }

        if (!is_singular() || in_the_loop()) {
            preg_match_all('/\b\w+\b/', $content, $keywords);
            $keywords = array_slice(array_count($keywords), 0, 10); // Top 10 keywords

            $limit = get_option('saai_free_limit', 2);
            $inserted = 0;

            foreach ($keywords as $keyword) {
                if ($inserted >= $limit) break;

                $product = $this->search_amazon_product($keyword);
                if ($product) {
                    $link = $this->create_affiliate_link($product, $keyword);
                    $content = $this->insert_link_near_keyword($content, $keyword, $link);
                    $inserted++;
                }
            }
        }

        return $content;
    }

    private function search_amazon_product($keyword) {
        // Simulated Amazon API call (use real Amazon Product Advertising API in production)
        // For demo, returns mock data
        $mock_products = array(
            'laptop' => array('title' => 'Dell XPS 13 Laptop', 'asin' => 'B08N5WRWNW', 'image' => 'https://images-na.ssl-images-amazon.com/images/I/81hj.jpg', 'price' => '$999'),
            'coffee' => array('title' => 'Keurig K-Mini Coffee Maker', 'asin' => 'B07DPJQC9K', 'image' => 'https://images-na.ssl-images-amazon.com/images/I/71.jpg', 'price' => '$79'),
            'book' => array('title' => 'Atomic Habits', 'asin' => 'B07RFSSYBH', 'image' => 'https://images-na.ssl-images-amazon.com/images/I/91.jpg', 'price' => '$12')
        );

        return isset($mock_products[strtolower($keyword)]) ? $mock_products[strtolower($keyword)] : false;
    }

    private function create_affiliate_link($product, $keyword) {
        $link = 'https://www.amazon.com/dp/' . $product['asin'] . '?tag=' . $this->affiliate_id;
        return '<a href="' . esc_url($link) . '" target="_blank" rel="nofollow sponsored" title="' . esc_attr($product['title']) . '">' . esc_html($keyword) . '</a> (' . $product['price'] . ')';
    }

    private function insert_link_near_keyword($content, $keyword, $link) {
        $pos = stripos($content, $keyword);
        if ($pos !== false) {
            $before = substr($content, 0, $pos + strlen($keyword));
            $after = substr($content, $pos + strlen($keyword));
            return $before . ' ' . $link . $after;
        }
        return $content;
    }

    public function activate() {
        add_option('saai_free_limit', 2);
    }

    public function deactivate() {}
}

new SmartAffiliateAutoInserter();

// Pro upsell notice
function saai_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Smart Affiliate AutoInserter Pro:</strong> Upgrade for unlimited links & analytics! <a href="' . admin_url('options-general.php?page=smart-affiliate-autoinserter') . '">Learn more</a></p></div>';
}
add_action('admin_notices', 'saai_pro_notice');