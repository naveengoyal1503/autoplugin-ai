<?php
/*
Plugin Name: SmartAffiliate Insights
Plugin URI: https://example.com/smartaffiliate-insights
Description: Automatically optimizes affiliate marketing links with dynamic product recommendations and coupon insertions.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=SmartAffiliate_Insights.php
License: GPL2
*/

if (!defined('ABSPATH')) exit;

class SmartAffiliateInsights {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->init_hooks();
        }
        return self::$instance;
    }

    private function init_hooks() {
        add_filter('the_content', array($this, 'insert_affiliate_links'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function admin_menu() {
        add_options_page('SmartAffiliate Insights', 'SmartAffiliate Insights', 'manage_options', 'smartaffiliate-insights', array($this, 'settings_page'));
    }

    public function register_settings() {
        register_setting('smartaffiliate_insights_group', 'sai_affiliate_id');
        register_setting('smartaffiliate_insights_group', 'sai_coupon_code');
        register_setting('smartaffiliate_insights_group', 'sai_enabled');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>SmartAffiliate Insights Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('smartaffiliate_insights_group'); ?>
                <?php do_settings_sections('smartaffiliate_insights_group'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Enable Plugin</th>
                        <td><input type="checkbox" name="sai_enabled" value="1" <?php checked(1, get_option('sai_enabled'), true); ?> /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Affiliate Base URL or ID</th>
                        <td><input type="text" name="sai_affiliate_id" value="<?php echo esc_attr(get_option('sai_affiliate_id')); ?>" size="50" placeholder="https://affiliate.example.com/?ref=yourID" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Default Coupon Code</th>
                        <td><input type="text" name="sai_coupon_code" value="<?php echo esc_attr(get_option('sai_coupon_code')); ?>" size="30" placeholder="SAVE10" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    // Basic keyword detection and affiliate link insertion
    public function insert_affiliate_links($content) {
        if (!get_option('sai_enabled')) {
            return $content;
        }

        $affiliate_base = trim(get_option('sai_affiliate_id', ''));
        if (empty($affiliate_base)) {
            return $content;
        }

        // Simple sample product keywords
        $keywords = array(
            'hosting' => 'hosting-plan',
            'seo' => 'seo-tool',
            'email marketing' => 'email-software',
            'vpn' => 'vpn-service'
        );

        foreach ($keywords as $keyword => $product_ref) {
            // Regex to find keyword standalone, case-insensitive
            $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
            if (preg_match($pattern, $content)) {
                $url = esc_url($affiliate_base) . urlencode($product_ref);
                $coupon = get_option('sai_coupon_code');
                $coupon_text = $coupon ? " Get discount with code <strong>" . esc_html($coupon) . "</strong>." : '';
                $replacement = '<a href="' . $url . '" target="_blank" rel="nofollow noopener">' . ucfirst($keyword) . '</a>' . $coupon_text;
                $content = preg_replace($pattern, $replacement, $content, 1);
            }
        }

        return $content;
    }
}

SmartAffiliateInsights::instance();
