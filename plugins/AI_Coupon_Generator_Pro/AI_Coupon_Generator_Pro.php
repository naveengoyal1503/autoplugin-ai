/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Generator_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Generator Pro
 * Plugin URI: https://example.com/aicoupon-generator
 * Description: AI-powered coupon generator for affiliate marketing and monetization.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponGenerator {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('ai_coupon_generator', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        add_menu_page('AI Coupon Generator', 'AI Coupons', 'manage_options', 'ai-coupon-generator', array($this, 'admin_page'));
        if (get_option('ai_coupon_api_key') === false) {
            add_option('ai_coupon_api_key', '');
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'ai-coupon.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'ai-coupon.css', array(), '1.0.0');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => 'general',
            'affiliate' => '',
        ), $atts);

        $coupons = $this->generate_coupons($atts['category'], $atts['affiliate']);
        ob_start();
        ?>
        <div id="ai-coupon-container" class="ai-coupon-grid">
            <?php foreach ($coupons as $coupon): ?>
            <div class="coupon-card">
                <h3><?php echo esc_html($coupon['title']); ?></h3>
                <p>Code: <strong><?php echo esc_html($coupon['code']); ?></strong></p>
                <p>Discount: <?php echo esc_html($coupon['discount']); ?></p>
                <a href="<?php echo esc_url($coupon['link']); ?}" class="coupon-btn" target="_blank">Get Deal <?php echo $coupon['affiliate'] ? '(Affiliate)' : ''; ?></a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_coupons($category, $affiliate) {
        // Simulated AI generation - in pro version, integrate OpenAI API
        $templates = array(
            array('title' => '10% Off Sitewide', 'code' => 'AI10OFF-' . wp_generate_uuid4(), 'discount' => '10%', 'link' => 'https://example.com/deal1', 'affiliate' => $affiliate),
            array('title' => 'Free Shipping', 'code' => 'AIFREESHIP-' . substr(md5(time()), 0, 8), 'discount' => 'Free Shipping', 'link' => 'https://example.com/deal2', 'affiliate' => $affiliate),
            array('title' => '$20 Off Orders $100+', 'code' => 'AI20OFF-' . rand(1000,9999), 'discount' => '$20', 'link' => 'https://example.com/deal3', 'affiliate' => $affiliate),
        );

        if ($category === 'tech') {
            $templates['title'] = '15% Off Gadgets';
            $templates[1]['title'] = 'Buy One Get One 50% Off';
        }

        return $templates;
    }

    public function admin_page() {
        if (isset($_POST['ai_coupon_api_key'])) {
            update_option('ai_coupon_api_key', sanitize_text_field($_POST['ai_coupon_api_key']));
            echo '<div class="notice notice-success"><p>API Key saved!</p></div>';
        }
        $api_key = get_option('ai_coupon_api_key');
        ?>
        <div class="wrap">
            <h1>AI Coupon Generator Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>OpenAI API Key (Pro)</th>
                        <td><input type="text" name="ai_coupon_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Usage: Use shortcode <code>[ai_coupon_generator category="tech" affiliate="Amazon"]</code></p>
            <p><strong>Upgrade to Pro</strong> for real AI generation, analytics, and unlimited coupons.</p>
        </div>
        <?php
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AICouponGenerator();

// Dummy JS/CSS - in real plugin, include files
function ai_coupon_add_files() {
    $js = "jQuery(document).ready(function($){ $('.coupon-btn').click(function(){ $(this).text('Copied!'); }); });");
    wp_add_inline_script('ai-coupon-js', $js);

    $css = ".ai-coupon-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; } .coupon-card { border: 1px solid #ddd; padding: 20px; border-radius: 8px; } .coupon-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }";
    wp_add_inline_style('ai-coupon-css', $css);
}
add_action('wp_enqueue_scripts', 'ai_coupon_add_files');

// Premium upsell notice
function ai_coupon_admin_notice() {
    echo '<div class="notice notice-info"><p>Unlock <strong>AI Coupon Generator Pro</strong> for unlimited coupons and real AI integration! <a href="https://example.com/pro">Get Pro ($49/year)</a></p></div>';
}
add_action('admin_notices', 'ai_coupon_admin_notice');
