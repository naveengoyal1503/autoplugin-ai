/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: AI-powered coupon affiliate plugin for WordPress monetization.
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
        add_shortcode('ai_coupon_section', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        // Simulate AI coupon generation (in pro: integrate real AI API)
        $this->coupons = get_option('ai_coupon_coupons', array(
            array('title' => '50% Off Hosting', 'code' => 'HOST50', 'afflink' => '#', 'desc' => 'Best hosting deal'),
            array('title' => 'Free Trial VPN', 'code' => 'VPNFREE', 'afflink' => '#', 'desc' => 'Secure browsing')
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('ai-coupon-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('ai-coupon-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('AI Coupon Pro Settings', 'AI Coupon Pro', 'manage_options', 'ai-coupon-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('ai_coupon_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons updated!</p></div>';
        }
        $coupons = get_option('ai_coupon_coupons', '');
        ?>
        <div class="wrap">
            <h1>AI Coupon Pro Settings</h1>
            <form method="post">
                <p><label>Coupons JSON:</label></p>
                <textarea name="coupons" rows="10" cols="80"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="description">Enter coupons as JSON array: [{'title':'Title','code':'CODE','afflink':'https://aff.link','desc':'Description'}]</p>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock AI generation, analytics, unlimited coupons for $49/year.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts, 'ai_coupon_section');
        $html = '<div class="ai-coupon-section"><h3>Exclusive Deals</h3><div class="coupons-grid">';
        $limit = min((int)$atts['limit'], count($this->coupons));
        for ($i = 0; $i < $limit; $i++) {
            $coupon = $this->coupons[$i];
            $html .= '<div class="coupon-card">';
            $html .= '<h4>' . esc_html($coupon['title']) . '</h4>';
            $html .= '<p>' . esc_html($coupon['desc']) . '</p>';
            $html .= '<code>' . esc_html($coupon['code']) . '</code>';
            $html .= '<a href="' . esc_url($coupon['afflink']) . '" class="coupon-btn" target="_blank" rel="nofollow">Get Deal (Affiliate)</a>';
            $html .= '</div>';
        }
        $html .= '</div></div>';
        return $html;
    }

    public function activate() {
        if (!get_option('ai_coupon_coupons')) {
            update_option('ai_coupon_coupons', json_encode(array(
                array('title' => 'Sample Deal', 'code' => 'SAMPLE50', 'afflink' => 'https://example.com/aff', 'desc' => 'Sample affiliate coupon')
            )));
        }
    }
}

new AICouponAffiliatePro();

// Inline CSS for self-contained

function ai_coupon_pro_styles() {
    echo '<style>
.ai-coupon-section { max-width: 800px; margin: 20px 0; }
.coupons-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
.coupon-card { background: #f9f9f9; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
.coupon-card h4 { margin: 0 0 10px; color: #333; }
.coupon-card code { background: #ffeb3b; padding: 5px 10px; border-radius: 4px; font-size: 1.1em; display: block; margin: 10px 0; }
.coupon-btn { background: #0073aa; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; }
.coupon-btn:hover { background: #005a87; }
@media (max-width: 768px) { .coupons-grid { grid-template-columns: 1fr; } }
</style>';
}
add_action('wp_head', 'ai_coupon_pro_styles');

// Inline JS
function ai_coupon_pro_scripts() {
    echo '<script>jQuery(document).ready(function($) { $(".coupon-btn").click(function() { gtag("event", "coupon_click", {"event_category": "affiliate", "event_label": $(this).text()}); }); });</script>';
}
add_action('wp_footer', 'ai_coupon_pro_scripts');