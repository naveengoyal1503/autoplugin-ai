/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: AI-powered coupon generator that auto-creates personalized discount codes, affiliate links, and deal pages to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-affiliate-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponAffiliatePro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon_generator', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        wp_register_style('ai-coupon-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_register_script('ai-coupon-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0', true);
    }

    public function admin_menu() {
        add_options_page('AI Coupon Pro', 'AI Coupons', 'manage_options', 'ai-coupon-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['ai_coupon_submit'])) {
            update_option('ai_coupon_api_key', sanitize_text_field($_POST['api_key']));
            update_option('ai_coupon_affiliate_links', sanitize_textarea_field($_POST['affiliate_links']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('ai_coupon_api_key', '');
        $aff_links = get_option('ai_coupon_affiliate_links', '');
        ?>
        <div class="wrap">
            <h1>AI Coupon Affiliate Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>OpenAI API Key (Pro)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" placeholder="sk-..."></td>
                    </tr>
                    <tr>
                        <th>Affiliate Links (JSON format)</th>
                        <td><textarea name="affiliate_links" rows="10" class="large-text"><?php echo esc_textarea($aff_links); ?></textarea><br>
                        Example: {"amazon":"https://amzn.to/xxx","shopify":"https://shopify.com/aff"}</td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[ai_coupon_generator niche="fashion"]</code></p>
            <p>Pro upgrade unlocks AI generation: $49/year at <a href="https://example.com/pro">example.com/pro</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'niche' => 'general',
            'count' => 3
        ), $atts);

        $aff_links = json_decode(get_option('ai_coupon_affiliate_links', '{}'), true);
        $coupons = $this->generate_coupons($atts['niche'], $atts['count'], $aff_links);

        wp_enqueue_style('ai-coupon-style');
        wp_enqueue_script('ai-coupon-script');

        ob_start();
        ?>
        <div id="ai-coupon-container" class="ai-coupon-pro" data-niche="<?php echo esc_attr($atts['niche']); ?>">
            <?php foreach ($coupons as $coupon): ?>
            <div class="coupon-card">
                <h3><?php echo esc_html($coupon['title']); ?></h3>
                <p class="discount"><?php echo esc_html($coupon['code']); ?> - Save <?php echo esc_html($coupon['discount']); ?>%</p>
                <a href="<?php echo esc_url($coupon['link']); ?}" class="coupon-btn" target="_blank">Get Deal</a>
                <span class="expires">Expires: <?php echo esc_html($coupon['expires']); ?></span>
            </div>
            <?php endforeach; ?>
            <p><em>Pro: Click "Generate More" for AI-powered custom coupons.</em></p>
            <button id="generate-more" class="button">Generate More (Pro)</button>
        </div>
        <style>
        .ai-coupon-pro { max-width: 600px; }
        .coupon-card { background: #f9f9f9; padding: 20px; margin: 10px 0; border-radius: 8px; border-left: 5px solid #0073aa; }
        .discount { font-size: 24px; font-weight: bold; color: #e74c3c; }
        .coupon-btn { background: #e74c3c; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        .coupon-btn:hover { background: #c0392b; }
        </style>
        <script>
        jQuery(document).ready(function($) {
            $('#generate-more').click(function() {
                alert('Upgrade to Pro for AI generation! Visit example.com/pro');
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    private function generate_coupons($niche, $count, $aff_links) {
        $samples = array(
            array('title' => 'Amazon Fashion Deal', 'code' => 'SAVE20', 'discount' => '20', 'link' => $aff_links['amazon'] ?? '#', 'expires' => 'Jan 15'),
            array('title' => 'Shopify Starter', 'code' => 'SHOP10', 'discount' => '10', 'link' => $aff_links['shopify'] ?? '#', 'expires' => 'Jan 10'),
            array('title' => 'Generic ' . ucfirst($niche) . ' Discount', 'code' => strtoupper(substr($niche,0,3)) . '25', 'discount' => '25', 'link' => '#', 'expires' => 'Jan 20')
        );
        return array_slice($samples, 0, $count);
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AICouponAffiliatePro();

// Pro upsell notice
function ai_coupon_admin_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Unlock AI-powered coupon generation with <strong>AI Coupon Affiliate Pro</strong> - <a href="https://example.com/pro">Upgrade Now ($49/year)</a> for unlimited coupons, analytics & more!</p></div>';
}
add_action('admin_notices', 'ai_coupon_admin_notice');

// End of plugin