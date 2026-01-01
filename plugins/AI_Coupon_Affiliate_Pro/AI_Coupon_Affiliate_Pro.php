/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: AI-powered coupon and affiliate manager to generate deals, track clicks, and monetize your site.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponAffiliatePro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon_deals', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('AI Coupon Pro Settings', 'AI Coupon Pro', 'manage_options', 'ai-coupon-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('ai_coupon_affiliates', sanitize_textarea_field($_POST['affiliates']));
            update_option('ai_coupon_pro', isset($_POST['pro_version']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $affiliates = get_option('ai_coupon_affiliates', "Amazon|https://amazon.com/affiliate-link?tag=yourtag\nShopify|https://shopify.com/aff?ref=yourref");
        $pro = get_option('ai_coupon_pro', false);
        ?>
        <div class="wrap">
            <h1>AI Coupon Affiliate Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Affiliate Links</th>
                        <td><textarea name="affiliates" rows="10" cols="50"><?php echo esc_textarea($affiliates); ?></textarea><br>
                        Format: Brand|Affiliate URL (one per line)</td>
                    </tr>
                    <tr>
                        <th>Pro Version</th>
                        <td><input type="checkbox" name="pro_version" <?php checked($pro); ?> /> Enable Pro Features (Enter license key in future updates)</td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock AI generation, analytics, and unlimited coupons for $49/year. <a href="https://example.com/pro">Buy Now</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('count' => 5), $atts);
        $affiliates = explode('\n', get_option('ai_coupon_affiliates', ''));
        $deals = array();
        foreach ($affiliates as $aff) {
            list($brand, $url) = explode('|', trim($aff), 2);
            if ($brand && $url) {
                $code = $this->generate_coupon_code($brand);
                $deals[] = array('brand' => trim($brand), 'code' => $code, 'url' => trim($url));
            }
        }
        shuffle($deals);
        $deals = array_slice($deals, 0, intval($atts['count']));

        ob_start();
        ?>
        <div class="ai-coupon-deals">
            <?php foreach ($deals as $deal): ?>
                <div class="coupon-item">
                    <h3><?php echo esc_html($deal['brand']); ?> Deal</h3>
                    <p>Use code: <strong><?php echo esc_html($deal['code']); ?></strong> - Save 20%!</p>
                    <a href="<?php echo esc_url(add_query_arg('ref', 'ai-coupon-pro', $deal['url'])); ?>" class="coupon-btn" target="_blank">Shop Now & Track</a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_coupon_code($brand) {
        $pro = get_option('ai_coupon_pro', false);
        if ($pro) {
            // Simulate AI: Pro generates smarter codes
            return strtoupper(substr(md5($brand . time()), 0, 8));
        }
        return 'SAVE' . rand(10, 50) . '%';
    }

    public function activate() {
        update_option('ai_coupon_pro', false);
        flush_rewrite_rules();
    }
}

new AICouponAffiliatePro();

// Track clicks
function ai_coupon_track_click() {
    if (isset($_GET['ref']) && $_GET['ref'] === 'ai-coupon-pro') {
        $clicks = get_option('ai_coupon_clicks', 0) + 1;
        update_option('ai_coupon_clicks', $clicks);
    }
}
add_action('init', 'ai_coupon_track_click');

// Assets placeholder - create assets folder with empty script.js and style.css
// script.js: jQuery(document).ready(function($) { $('.coupon-btn').click(function(){ $(this).text('Tracked!'); }); });
// style.css: .ai-coupon-deals { display: grid; gap: 20px; } .coupon-item { border: 1px solid #ddd; padding: 20px; } .coupon-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; }
?>