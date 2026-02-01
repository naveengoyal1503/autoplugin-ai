/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into your WordPress content to maximize affiliate earnings.
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_filter('the_content', array($this, 'insert_affiliate_links'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->api_key = get_option('saa_amazon_api_key');
        $this->affiliate_tag = get_option('saa_affiliate_tag');
    }

    public function activate() {
        add_option('saa_enabled', '1');
    }

    public function add_admin_menu() {
        add_options_page('Smart Affiliate Settings', 'Affiliate AutoInserter', 'manage_options', 'smart-affiliate', array($this, 'options_page'));
    }

    public function settings_init() {
        register_setting('pluginPage_smart-affiliate', 'saa_amazon_api_key');
        register_setting('pluginPage_smart-affiliate', 'saa_affiliate_tag');
        register_setting('pluginPage_smart-affiliate', 'saa_enabled');
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('pluginPage_smart-affiliate');
                do_settings_sections('pluginPage_smart-affiliate');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Amazon API Key</th>
                        <td><input type="text" name="saa_amazon_api_key" value="<?php echo esc_attr(get_option('saa_amazon_api_key')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Affiliate Tag</th>
                        <td><input type="text" name="saa_affiliate_tag" value="<?php echo esc_attr(get_option('saa_affiliate_tag')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Enabled</th>
                        <td><input type="checkbox" name="saa_enabled" value="1" <?php checked(1, get_option('saa_enabled')); ?> /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock A/B testing, click tracking, and more for $49/year. <a href="#" onclick="alert('Pro features coming soon!')">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function insert_affiliate_links($content) {
        if (!get_option('saa_enabled') || empty($this->api_key) || empty($this->affiliate_tag) || is_admin()) {
            return $content;
        }

        // Simple keyword-based insertion (pro: AI-powered)
        $keywords = array('iphone', 'laptop', 'book', 'headphones');
        foreach ($keywords as $keyword) {
            if (stripos($content, $keyword) !== false) {
                $asin = $this->get_random_asin($keyword);
                if ($asin) {
                    $link = $this->get_affiliate_link($asin);
                    $content = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', '<a href="' . esc_url($link) . '" target="_blank" rel="nofollow sponsored">$0</a>', $content, 1);
                }
            }
        }
        return $content;
    }

    private function get_random_asin($keyword) {
        $asins = array(
            'iphone' => 'B0CWL8W8Q3',
            'laptop' => 'B0BS8Q3X2V',
            'book' => 'B0B1L8ZJ4Q',
            'headphones' => 'B08Y7Y26QJ'
        );
        return isset($asins[$keyword]) ? $asins[$keyword] : false;
    }

    private function get_affiliate_link($asin) {
        return 'https://www.amazon.com/dp/' . $asin . '?tag=' . $this->affiliate_tag;
    }
}

new SmartAffiliateAutoInserter();
