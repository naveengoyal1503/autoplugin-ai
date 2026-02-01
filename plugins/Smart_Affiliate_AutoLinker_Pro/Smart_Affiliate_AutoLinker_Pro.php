/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoLinker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoLinker Pro
 * Plugin URI: https://example.com/smart-affiliate-autolinker
 * Description: Automatically detects keywords in your content and converts them into high-converting affiliate links from your program, boosting commissions effortlessly.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autolinker
 * Requires at least: 5.0
 * Tested up to: 6.6
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SAAL_VERSION', '1.0.0');
define('SAAL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SAAL_PRO', false); // Set to true for pro features or check license

class SmartAffiliateAutoLinker {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_filter('the_content', array($this, 'auto_link_affiliates'));
        add_filter('widget_text', array($this, 'auto_link_affiliates'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-autolinker', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('saal-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), SAAL_VERSION, true);
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate AutoLinker',
            'Affiliate AutoLinker',
            'manage_options',
            'smart-affiliate-autolinker',
            array($this, 'settings_page')
        );
    }

    public function admin_init() {
        register_setting('saal_settings', 'saal_keywords');
        register_setting('saal_settings', 'saal_affiliate_base_url');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Affiliate AutoLinker Settings', 'smart-affiliate-autolinker'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('saal_settings'); ?>
                <?php do_settings_sections('saal_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Affiliate Base URL', 'smart-affiliate-autolinker'); ?></th>
                        <td>
                            <input type="url" name="saal_affiliate_base_url" value="<?php echo esc_attr(get_option('saal_affiliate_base_url')); ?>" class="regular-text" />
                            <p class="description"><?php _e('Your affiliate tracking URL (e.g., https://youraffiliate.com/ref/?ref={keyword})', 'smart-affiliate-autolinker'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Keywords', 'smart-affiliate-autolinker'); ?></th>
                        <td>
                            <textarea name="saal_keywords" rows="10" cols="50" class="large-text code"><?php echo esc_textarea(get_option('saal_keywords')); ?></textarea>
                            <p class="description"><?php _e('One keyword per line: keyword|affiliate_id (Free: up to 5 keywords. Pro: Unlimited)', 'smart-affiliate-autolinker'); ?></p>
                            <?php if (!SAAL_PRO) : ?>
                                <p class="notice notice-warning"><strong><?php _e('Upgrade to Pro for unlimited keywords, analytics, and A/B testing!', 'smart-affiliate-autolinker'); ?></strong></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <?php if (!SAAL_PRO) : ?>
                <div class="notice notice-info">
                    <p><strong><?php _e('Go Pro for advanced features like click tracking, A/B link testing, and integrations with AffiliateWP!', 'smart-affiliate-autolinker'); ?></strong></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function auto_link_affiliates($content) {
        if (is_admin() || !is_singular() || (function_exists('is_shop') && is_shop())) {
            return $content;
        }

        $keywords = get_option('saal_keywords', '');
        if (empty($keywords)) {
            return $content;
        }

        $keyword_array = explode("\n", trim($keywords));
        $free_limit = 5;
        if (!SAAL_PRO && count($keyword_array) > $free_limit) {
            $keyword_array = array_slice($keyword_array, 0, $free_limit);
        }

        $base_url = get_option('saal_affiliate_base_url', '');
        if (empty($base_url)) {
            return $content;
        }

        foreach ($keyword_array as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            list($keyword, $aff_id) = explode('|', $line, 2);
            $keyword = trim($keyword);
            $aff_id = trim($aff_id);

            $link = str_replace('{keyword}', urlencode($keyword), $base_url);
            if (!empty($aff_id)) {
                $link .= '&aff_id=' . urlencode($aff_id);
            }

            $regex = '/\b' . preg_quote($keyword, '/') . '\b/i';
            if (preg_match($regex, $content) && strpos($content, 'href=') === false) { // Avoid linking existing links
                $content = preg_replace($regex, '<a href="' . esc_url($link) . '" target="_blank" rel="nofollow noopener">$0</a>', $content, 1);
            }
        }

        return $content;
    }
}

new SmartAffiliateAutoLinker();

// Pro upsell notice
function saal_pro_upsell() {
    if (!SAAL_PRO && current_user_can('manage_options')) {
        echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(__('Unlock unlimited keywords and pro features with <a href="https://example.com/pro" target="_blank">Smart Affiliate AutoLinker Pro</a>! %s/month recurring revenue booster!', 'smart-affiliate-autolinker'), '$49/year') . '</p></div>';
    }
}
add_action('admin_notices', 'saal_pro_upsell');

// Create assets dir placeholder
register_activation_hook(__FILE__, function() {
    $assets_dir = plugin_dir_path(__FILE__) . 'assets/';
    if (!file_exists($assets_dir)) {
        wp_mkdir_p($assets_dir);
    }
    file_put_contents($assets_dir . 'frontend.js', '// Pro JS features here');
});