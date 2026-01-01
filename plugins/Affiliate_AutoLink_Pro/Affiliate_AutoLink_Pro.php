/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_AutoLink_Pro.php
*/
<?php
/**
 * Plugin Name: Affiliate AutoLink Pro
 * Plugin URI: https://example.com/affiliate-autolink-pro
 * Description: Automatically detects keywords in your content and converts them into cloaked affiliate links from Amazon, boosting commissions with smart tracking and performance reports.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AffiliateAutoLinkPro {
    private $api_key;
    private $affiliate_id;
    private $keywords;
    private $enabled;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'replace_keywords'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->api_key = get_option('aalp_api_key', '');
        $this->affiliate_id = get_option('aalp_affiliate_id', '');
        $this->keywords = get_option('aalp_keywords', array());
        $this->enabled = get_option('aalp_enabled', true);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('aalp-script', plugin_dir_url(__FILE__) . 'js/aalp.js', array('jquery'), '1.0.0', true);
    }

    public function replace_keywords($content) {
        if (!$this->enabled || empty($this->keywords) || empty($this->affiliate_id)) {
            return $content;
        }

        foreach ($this->keywords as $keyword => $asin) {
            $link = $this->generate_amazon_link($asin);
            $regex = '/\b' . preg_quote($keyword, '/') . '\b/i';
            $content = preg_replace($regex, '<a href="' . esc_url($link) . '" target="_blank" rel="nofollow noopener" class="aalp-link">$0</a>', $content, 1);
        }
        return $content;
    }

    private function generate_amazon_link($asin) {
        $url = 'https://www.amazon.com/dp/' . $asin . '?tag=' . $this->affiliate_id;
        return $url;
    }

    public function admin_menu() {
        add_options_page('Affiliate AutoLink Pro', 'AutoLink Pro', 'manage_options', 'aalp-settings', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('aalp_settings', 'aalp_api_key');
        register_setting('aalp_settings', 'aalp_affiliate_id');
        register_setting('aalp_settings', 'aalp_keywords');
        register_setting('aalp_settings', 'aalp_enabled');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate AutoLink Pro Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('aalp_settings'); ?>
                <?php do_settings_sections('aalp_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Enable Auto-Linking</th>
                        <td><input type="checkbox" name="aalp_enabled" value="1" <?php checked(get_option('aalp_enabled')); ?> /></td>
                    </tr>
                    <tr>
                        <th>Amazon Affiliate ID (tag)</th>
                        <td><input type="text" name="aalp_affiliate_id" value="<?php echo esc_attr(get_option('aalp_affiliate_id')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Keywords (JSON: {"keyword":"ASIN"})</th>
                        <td><textarea name="aalp_keywords" rows="10" cols="50"><?php echo esc_textarea(json_encode(get_option('aalp_keywords'))); ?></textarea><br><small>Example: {"iPhone":"B08N5WRWNW","Laptop":"B09G9FPGT6"}</small></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Performance Report (Free Version Limited)</h2>
            <p>Upgrade to Pro for click tracking and analytics.</p>
        </div>
        <?php
    }

    public function activate() {
        add_option('aalp_enabled', true);
    }

    public function deactivate() {
        // Cleanup if needed
    }
}

new AffiliateAutoLinkPro();

// Pro upgrade notice
function aalp_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Affiliate AutoLink Pro:</strong> Unlock unlimited keywords and analytics. <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
}
add_action('admin_notices', 'aalp_pro_notice');

// Create JS file placeholder (in real plugin, include actual file)
// For single-file, inline simple JS
add_action('wp_head', function() {
    ?>
    <script>jQuery(document).ready(function($) { $('.aalp-link').on('click', function() { console.log('Affiliate link clicked'); }); });</script>
    <?php
});

?>