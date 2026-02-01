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
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoInserter {
    private $api_key;
    private $affiliate_tag;
    private $enabled;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'insert_affiliate_links'), 99);
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->api_key = get_option('saa_api_key', '');
        $this->affiliate_tag = get_option('saa_affiliate_tag', '');
        $this->enabled = get_option('saa_enabled', true);
    }

    public function enqueue_scripts() {
        if (is_admin()) return;
        wp_enqueue_script('saa-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
    }

    public function insert_affiliate_links($content) {
        if (!is_single() || !$this->enabled || empty($this->api_key) || empty($this->affiliate_tag)) {
            return $content;
        }

        // Simple keyword to product mapping (free version limit: 5 keywords)
        $keywords = array(
            'laptop' => 'B08N5WRWNW',
            'phone' => 'B0C35G9MLG',
            'book' => 'B0C1234567',
            'headphones' => 'B07XJ8C8F8',
            'camera' => 'B08J5W9K3L'
        );

        foreach ($keywords as $keyword => $asin) {
            if (stripos($content, $keyword) !== false && stripos($content, 'amazon') === false) {
                $link = $this->get_amazon_link($asin);
                $content = preg_replace('/(' . preg_quote($keyword, '/') . ')(?![^<>]*>)/i', '<a href="$link" target="_blank" rel="nofollow sponsored">$1</a> <span style="font-size:0.8em;color:#666;">(aff link)</span>', $content, 1);
            }
        }

        return $content;
    }

    private function get_amazon_link($asin) {
        return 'https://www.amazon.com/dp/' . $asin . '?tag=' . $this->affiliate_tag;
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate AutoInserter', 'Affiliate Inserter', 'manage_options', 'smart-affiliate-autoinserter', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('saa_settings', 'saa_api_key');
        register_setting('saa_settings', 'saa_affiliate_tag');
        register_setting('saa_settings', 'saa_enabled');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('saa_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Amazon Affiliate Tag</th>
                        <td><input type="text" name="saa_affiliate_tag" value="<?php echo esc_attr($this->affiliate_tag); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>OpenAI API Key (Pro)</th>
                        <td><input type="password" name="saa_api_key" value="<?php echo esc_attr($this->api_key); ?>" class="regular-text" /> <p>Upgrade to Pro for AI-powered matching.</p></td>
                    </tr>
                    <tr>
                        <th>Enable Auto-Insertion</th>
                        <td><input type="checkbox" name="saa_enabled" value="1" <?php checked($this->enabled); ?> /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Features:</strong> Unlimited keywords, AI product suggestions, click analytics, custom placements. <a href="https://example.com/pro" target="_blank">Upgrade Now ($49/year)</a></p>
        </div>
        <?php
    }

    public function activate() {
        add_option('saa_enabled', true);
    }

    public function deactivate() {
        // Cleanup if needed
    }
}

new SmartAffiliateAutoInserter();

// Pro upsell notice
function saa_pro_notice() {
    if (!current_user_can('manage_options')) return;
    $screen = get_current_screen();
    if ($screen->id === 'settings_page_smart-affiliate-autoinserter') return;
    echo '<div class="notice notice-info"><p><strong>Smart Affiliate AutoInserter Pro:</strong> Unlock AI matching & analytics for 10x commissions! <a href="' . admin_url('options-general.php?page=smart-affiliate-autoinserter') . '">Upgrade Now</a></p></div>';
}
add_action('admin_notices', 'saa_pro_notice');

// Create assets dir on activation
register_activation_hook(__FILE__, function() {
    $assets_dir = plugin_dir_path(__FILE__) . 'assets/';
    if (!file_exists($assets_dir)) {
        wp_mkdir_p($assets_dir);
    }
    $js_content = "jQuery(document).ready(function($) { console.log('Smart Affiliate AutoInserter loaded'); });";
    file_put_contents($assets_dir . 'script.js', $js_content);
});
?>