/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Smart_Link_Optimizer.php
*/
<?php
/**
 * Plugin Name: Affiliate Smart Link Optimizer
 * Plugin URI: https://example.com/affiliate-smart-link-optimizer
 * Description: Autofinds product mentions and turns them into optimized affiliate links with tracking and coupons.
 * Version: 1.0
 * Author: YourName
 * License: GPL2
 */

if(!defined('ABSPATH')) { exit; }

class AffiliateSmartLinkOptimizer {
    private $plugin_url;

    public function __construct() {
        $this->plugin_url = plugin_dir_url(__FILE__);
        add_filter('the_content', array($this, 'auto_convert_links'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function auto_convert_links($content) {
        $keywords = get_option('asl_keywords');
        $affiliate_base_url = get_option('asl_affiliate_base_url');

        if(empty($keywords) || empty($affiliate_base_url)) {
            return $content;
        }

        // Keywords stored as comma separated in settings
        $terms = array_map('trim', explode(',', $keywords));

        foreach ($terms as $term) {
            $pattern = '/\b('.preg_quote($term, '/').')\b/i';
            $replacement = '<a href="'.esc_url($this->build_affiliate_url($term)).'" target="_blank" rel="nofollow noopener">$1</a>';
            $content = preg_replace($pattern, $replacement, $content, 1); // Replace first occurrence per term
        }

        return $content;
    }

    private function build_affiliate_url($term) {
        $base = get_option('asl_affiliate_base_url');
        $coupon_code = get_option('asl_coupon_code');

        $url = $base . '?product=' . urlencode($term);
        if($coupon_code) {
            $url .= '&coupon=' . urlencode($coupon_code);
        }
        // Add tracking param
        $url .= '&utm_source=asl_plugin';

        return $url;
    }

    public function add_admin_menu() {
        add_options_page('Affiliate Smart Link Optimizer Settings', 'Affiliate Link Optimizer', 'manage_options', 'asl-settings', array($this, 'settings_page'));
    }

    public function register_settings() {
        register_setting('asl_settings_group', 'asl_keywords');
        register_setting('asl_settings_group', 'asl_affiliate_base_url');
        register_setting('asl_settings_group', 'asl_coupon_code');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Smart Link Optimizer Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('asl_settings_group'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Product Keywords (comma separated)</th>
                        <td><input type="text" name="asl_keywords" value="<?php echo esc_attr(get_option('asl_keywords')); ?>" size="50" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Affiliate Base URL</th>
                        <td><input type="text" name="asl_affiliate_base_url" value="<?php echo esc_attr(get_option('asl_affiliate_base_url')); ?>" size="50" placeholder="https://affiliate.example.com/ref" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Coupon Code (optional)</th>
                        <td><input type="text" name="asl_coupon_code" value="<?php echo esc_attr(get_option('asl_coupon_code')); ?>" size="20" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

new AffiliateSmartLinkOptimizer();
