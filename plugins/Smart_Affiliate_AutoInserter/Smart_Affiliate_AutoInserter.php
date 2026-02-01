/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into your WordPress posts and pages to boost monetization effortlessly.
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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_filter('the_content', array($this, 'auto_insert_links'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->api_key = get_option('saa_api_key', '');
        $this->affiliate_tag = get_option('saa_affiliate_tag', '');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('saa-script', plugin_dir_url(__FILE__) . 'saa-script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Settings', 'Affiliate AutoInserter', 'manage_options', 'smart-affiliate', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('saa_settings', 'saa_api_key');
        register_setting('saa_settings', 'saa_affiliate_tag');
        register_setting('saa_settings', 'saa_enable_auto');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('saa_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Amazon API Key</th>
                        <td><input type="text" name="saa_api_key" value="<?php echo esc_attr(get_option('saa_api_key')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Tag</th>
                        <td><input type="text" name="saa_affiliate_tag" value="<?php echo esc_attr(get_option('saa_affiliate_tag')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Enable Auto-Insert</th>
                        <td><input type="checkbox" name="saa_enable_auto" value="1" <?php checked(1, get_option('saa_enable_auto', 0)); ?> /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock AI-powered keyword matching, multiple networks, and analytics for $49/year.</p>
        </div>
        <?php
    }

    public function auto_insert_links($content) {
        if (!get_option('saa_enable_auto', 0) || empty($this->affiliate_tag)) {
            return $content;
        }

        // Simple keyword-based insertion (demo keywords)
        $keywords = array('book' => 'B08N5WRWNW', 'phone' => 'B0C7H9R8QW', 'laptop' => 'B0B3GNYN7K');
        foreach ($keywords as $keyword => $asin) {
            if (stripos($content, $keyword) !== false && rand(1, 3) === 1) { // 33% chance
                $link = $this->get_amazon_link($asin);
                $content = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', '<a href="$link" target="_blank" rel="nofollow noopener">$0</a>', $content, 1);
            }
        }
        return $content;
    }

    private function get_amazon_link($asin) {
        return 'https://www.amazon.com/dp/' . $asin . '?tag=' . $this->affiliate_tag;
    }

    public function activate() {
        add_option('saa_enable_auto', 0);
    }
}

new SmartAffiliateAutoInserter();

// Inline JS for demo
function saa_add_inline_script() {
    if (get_option('saa_enable_auto', 0)) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            console.log('Smart Affiliate AutoInserter active!');
        });
        </script>
        <?php
    }
}
add_action('wp_footer', 'saa_add_inline_script');
?>