/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: AI-powered coupon and affiliate link generator for WordPress monetization.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponAffiliatePro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon_generator', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_style('ai-coupon-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('ai-coupon-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('AI Coupon Pro Settings', 'AI Coupon Pro', 'manage_options', 'ai-coupon-pro', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('ai_coupon_options', 'ai_coupon_settings');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>AI Coupon Affiliate Pro Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('ai_coupon_options'); ?>
                <?php do_settings_sections('ai_coupon_options'); ?>
                <table class="form-table">
                    <tr>
                        <th>Affiliate Networks</th>
                        <td><input type="text" name="ai_coupon_settings[networks]" value="<?php echo esc_attr(get_option('ai_coupon_settings')['networks'] ?? 'Amazon,ClickBank,ShareASale'); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>API Key (Premium)</th>
                        <td><input type="password" name="ai_coupon_settings[api_key]" value="<?php echo esc_attr(get_option('ai_coupon_settings')['api_key'] ?? ''); ?>" class="regular-text" /> <p>Upgrade to Pro for AI generation.</p></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Pro:</strong> Unlimited coupons, AI personalization, analytics. <a href="https://example.com/pro">Get Pro</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'niche' => 'general',
            'count' => 3
        ), $atts);

        $coupons = $this->generate_coupons($atts['niche'], $atts['count']);
        ob_start();
        ?>
        <div id="ai-coupon-container" class="ai-coupon-pro" data-niche="<?php echo esc_attr($atts['niche']); ?>">
            <?php foreach ($coupons as $coupon): ?>
            <div class="coupon-item">
                <h4><?php echo esc_html($coupon['title']); ?></h4>
                <p>Code: <strong><?php echo esc_html($coupon['code']); ?></strong></p>
                <p>Discount: <?php echo esc_html($coupon['discount']); ?></p>
                <a href="<?php echo esc_url($coupon['link']); ?}" target="_blank" class="coupon-btn" rel="nofollow">Get Deal (Affiliate)</a>
            </div>
            <?php endforeach; ?>
            <button id="generate-more" class="button">Generate More</button>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_coupons($niche, $count) {
        // Simulated AI generation (Pro uses real API)
        $samples = array(
            array('title' => 'Amazon 20% Off Electronics', 'code' => 'AICPN20', 'discount' => '20% OFF', 'link' => 'https://amazon.com/?tag=youraffiliate'),
            array('title' => 'ClickBank Digital Course Deal', 'code' => 'WPDEAL50', 'discount' => '50% OFF', 'link' => 'https://clickbank.net/?aff=yourid'),
            array('title' => 'ShareASale Fashion Discount', 'code' => 'FASHION25', 'discount' => '25% OFF', 'link' => 'https://shareasale.com/?aff=yourid')
        );
        shuffle($samples);
        return array_slice($samples, 0, $count);
    }

    public function activate() {
        add_option('ai_coupon_settings', array('networks' => 'Amazon,ClickBank'));
    }
}

new AICouponAffiliatePro();

// Inline CSS
add_action('wp_head', function() { ?>
<style>
.ai-coupon-pro { max-width: 600px; margin: 20px 0; }
.coupon-item { background: #f9f9f9; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #0073aa; }
.coupon-btn { background: #ff6600; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; }
.coupon-btn:hover { background: #e65c00; }
#generate-more { background: #0073aa; color: white; border: none; padding: 10px 20px; cursor: pointer; }
</style>
<?php });

// Inline JS
add_action('wp_footer', function() { ?>
<script>
jQuery(document).ready(function($) {
    $('#generate-more').click(function() {
        var container = $(this).closest('#ai-coupon-container');
        var niche = container.data('niche');
        // AJAX call to generate more (Pro feature)
        alert('Pro feature: Generate unlimited AI coupons!');
    });
});
</script>
<?php });