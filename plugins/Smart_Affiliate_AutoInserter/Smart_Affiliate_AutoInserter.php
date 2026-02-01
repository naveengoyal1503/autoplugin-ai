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
    private $affiliate_tag;
    private $enabled;

    public function __construct() {
        $this->api_key = get_option('saa_api_key', '');
        $this->affiliate_tag = get_option('saa_affiliate_tag', '');
        $this->enabled = get_option('saa_enabled', false);

        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_filter('the_content', array($this, 'insert_affiliate_links'), 99);
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-autoinserter', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        if ($this->enabled) {
            wp_enqueue_script('saa-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        }
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate AutoInserter Settings',
            'Affiliate AutoInserter',
            'manage_options',
            'smart-affiliate-autoinserter',
            array($this, 'settings_page')
        );
    }

    public function admin_init() {
        register_setting('saa_settings', 'saa_api_key');
        register_setting('saa_settings', 'saa_affiliate_tag');
        register_setting('saa_settings', 'saa_enabled');
        register_setting('saa_settings', 'saa_keywords');
        register_setting('saa_settings', 'saa_pro_version');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Affiliate AutoInserter', 'smart-affiliate-autoinserter'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('saa_settings'); ?>
                <?php do_settings_sections('saa_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Amazon Affiliate Tag', 'smart-affiliate-autoinserter'); ?></th>
                        <td><input type="text" name="saa_affiliate_tag" value="<?php echo esc_attr($this->affiliate_tag); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><?php _e('OpenAI API Key (Optional for AI matching)', 'smart-affiliate-autoinserter'); ?></th>
                        <td><input type="password" name="saa_api_key" value="<?php echo esc_attr($this->api_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><?php _e('Keywords to Products (JSON: {"keyword":"asin"})', 'smart-affiliate-autoinserter'); ?></th>
                        <td><textarea name="saa_keywords" rows="10" cols="50"><?php echo esc_textarea(get_option('saa_keywords', '{}')); ?></textarea><br>
                        <small><?php _e('Example: {"laptop":"B08N5WRWNW","phone":"B0BTYFS3WC"}', 'smart-affiliate-autoinserter'); ?></small></td>
                    </tr>
                    <tr>
                        <th><?php _e('Enable Auto-Insertion', 'smart-affiliate-autoinserter'); ?></th>
                        <td><input type="checkbox" name="saa_enabled" value="1" <?php checked($this->enabled); ?> /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Version:</strong> Unlock unlimited keywords, analytics, and more for $29/year. <a href="#" onclick="alert('Upgrade to Pro!')">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function insert_affiliate_links($content) {
        if (!$this->enabled || is_admin() || !is_single()) {
            return $content;
        }

        $keywords = json_decode(get_option('saa_keywords', '{}'), true);
        if (empty($keywords)) {
            return $content;
        }

        foreach ($keywords as $keyword => $asin) {
            $link = $this->get_amazon_link($asin);
            $content = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', $link, $content, -1, $count);
            if ($count > 0 && !is_pro()) {
                break; // Free version: only one link
            }
        }

        return $content;
    }

    private function get_amazon_link($asin) {
        $tag = $this->affiliate_tag;
        return '<a href="https://www.amazon.com/dp/' . $asin . '?tag=' . $tag . '" target="_blank" rel="nofollow sponsored">$0</a>';
    }

    public function activate() {
        add_option('saa_enabled', false);
    }
}

new SmartAffiliateAutoInserter();

// Pro check
function is_pro() {
    return get_option('saa_pro_version', false);
}

// Create assets dir placeholder
if (!file_exists(plugin_dir_path(__FILE__) . 'assets/')) {
    mkdir(plugin_dir_path(__FILE__) . 'assets/', 0755, true);
}

// Sample script.js content
$js_content = "jQuery(document).ready(function($) { console.log('Smart Affiliate AutoInserter loaded'); });";
file_put_contents(plugin_dir_path(__FILE__) . 'assets/script.js', $js_content);
?>