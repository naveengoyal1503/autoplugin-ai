/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into your WordPress content to maximize earnings.
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
    private $api_key;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_filter('the_content', array($this, 'insert_affiliate_links'), 99);
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->affiliate_id = get_option('saa_affiliate_id', '');
        $this->api_key = get_option('saa_api_key', ''); // For Amazon Product Advertising API
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

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('saa_affiliate_id', sanitize_text_field($_POST['affiliate_id']));
            update_option('saa_api_key', sanitize_text_field($_POST['api_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Amazon Affiliate ID (tag)</th>
                        <td><input type="text" name="affiliate_id" value="<?php echo esc_attr($this->affiliate_id); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Amazon API Key (optional for advanced search)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($this->api_key); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited links, AI keyword matching, and performance analytics for $29/year.</p>
        </div>
        <?php
    }

    public function insert_affiliate_links($content) {
        if (is_admin() || empty($this->affiliate_id)) {
            return $content;
        }

        // Free version limit: 2 links per post
        $link_limit = 2;

        // Extract keywords from content (simple approach)
        preg_match_all('/\b\w{4,12}\b/', strip_tags($content), $matches);
        $keywords = array_slice(array_unique($matches), 0, 5);

        $inserted = 0;
        foreach ($keywords as $keyword) {
            if ($inserted >= $link_limit) break;

            // Mock product search (in Pro, use real Amazon API)
            $product_asin = $this->mock_search_product($keyword);
            if ($product_asin) {
                $link = $this->generate_affiliate_link($product_asin, $keyword);
                $content = $this->insert_link_into_content($content, $link, $keyword);
                $inserted++;
            }
        }

        return $content;
    }

    private function mock_search_product($keyword) {
        // Mock ASINs for demo (replace with Amazon PA API call in Pro)
        $mock_products = array(
            'phone' => 'B08N5WRWNW',
            'laptop' => 'B0B3H6JPY2',
            'book' => 'B0CGL4W3C9',
            'camera' => 'B07XJ8C8F6'
        );
        return isset($mock_products[strtolower($keyword)]) ? $mock_products[strtolower($keyword)] : false;
    }

    private function generate_affiliate_link($asin, $keyword) {
        $link = 'https://www.amazon.com/dp/' . $asin . '?tag=' . $this->affiliate_id;
        return '<a href="' . esc_url($link) . '" target="_blank" rel="nofollow sponsored">' . esc_html($keyword) . '</a>';
    }

    private function insert_link_into_content($content, $link, $keyword) {
        $pos = strpos(stripos($content, $keyword), $keyword);
        if ($pos !== false) {
            $before = substr($content, 0, $pos);
            $after = substr($content, $pos + strlen($keyword));
            return $before . $link . $after;
        }
        return $content;
    }

    public function activate() {
        add_option('saa_version', '1.0.0');
    }

    public function deactivate() {}
}

new SmartAffiliateAutoInserter();

// Pro upsell notice
function saa_pro_upsell() {
    if (!is_super_admin()) return;
    echo '<div class="notice notice-info"><p><strong>Smart Affiliate AutoInserter Pro:</strong> Upgrade for unlimited links & AI features! <a href="https://example.com/pro" target="_blank">Get Pro</a></p></div>';
}
add_action('admin_notices', 'saa_pro_upsell');