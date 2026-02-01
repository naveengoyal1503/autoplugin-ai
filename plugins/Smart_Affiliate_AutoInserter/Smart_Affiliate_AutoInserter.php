/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into posts and pages using keyword matching.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoInserter {
    private $api_key;
    private $affiliate_tag;

    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->api_key = get_option('saai_api_key', '');
        $this->affiliate_tag = get_option('saai_affiliate_tag', '');
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_init', array($this, 'settings_init'));
        } else {
            add_filter('the_content', array($this, 'auto_insert_links'), 99);
        }
    }

    public function activate() {
        add_option('saai_keywords', "laptop,phone,book");
        add_option('saai_link_limit', 3);
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Settings', 'Affiliate AutoInserter', 'manage_options', 'saai-settings', array($this, 'settings_page'));
    }

    public function settings_init() {
        register_setting('saai_settings', 'saai_api_key');
        register_setting('saai_settings', 'saai_affiliate_tag');
        register_setting('saai_settings', 'saai_keywords');
        register_setting('saai_settings', 'saai_link_limit');
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
                        <th>Amazon Affiliate Tag</th>
                        <td><input type="text" name="saai_affiliate_tag" value="<?php echo esc_attr(get_option('saai_affiliate_tag')); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Keywords (comma-separated)</th>
                        <td><input type="text" name="saai_keywords" value="<?php echo esc_attr(get_option('saai_keywords')); ?>" style="width: 300px;" /></td>
                    </tr>
                    <tr>
                        <th>Max Links per Post</th>
                        <td><input type="number" name="saai_link_limit" value="<?php echo esc_attr(get_option('saai_link_limit', 3)); ?>" min="1" max="10" /></td>
                    </tr>
                    <tr>
                        <th>Pro Feature: OpenAI API Key (for AI matching)</th>
                        <td><input type="text" name="saai_api_key" value="<?php echo esc_attr(get_option('saai_api_key')); ?>" /> <p class="description">Upgrade to Pro for AI-powered product suggestions.</p></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Pro</strong> for AI matching, analytics, and more: <a href="https://example.com/pro">Get Pro</a></p>
        </div>
        <?php
    }

    public function auto_insert_links($content) {
        if (!is_single() || empty($this->affiliate_tag)) {
            return $content;
        }

        $keywords = explode(',', get_option('saai_keywords', ''));
        $limit = intval(get_option('saai_link_limit', 3));
        $inserted = 0;

        foreach ($keywords as $keyword) {
            $keyword = trim($keyword);
            if ($inserted >= $limit) break;

            if (stripos($content, $keyword) !== false) {
                $product_link = $this->get_amazon_link($keyword);
                if ($product_link) {
                    $link = '<a href="' . esc_url($product_link) . '" target="_blank" rel="nofollow sponsored">' . esc_html($keyword) . '</a>';
                    $content = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', $link, $content, 1);
                    $inserted++;
                }
            }
        }

        return $content;
    }

    private function get_amazon_link($keyword) {
        // Mock Amazon PA API call - replace with real API integration in Pro
        $base_url = 'https://www.amazon.com/s?k=' . urlencode($keyword) . '&tag=' . $this->affiliate_tag;
        return $base_url;

        // Pro AI version would use OpenAI to find best product and real PA API
    }
}

new SmartAffiliateAutoInserter();

// Freemium upsell notice
add_action('admin_notices', function() {
    if (!get_option('saai_api_key') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Unlock AI-powered affiliate matching with <a href="/wp-admin/options-general.php?page=saai-settings">Smart Affiliate Pro</a>!</p></div>';
    }
});