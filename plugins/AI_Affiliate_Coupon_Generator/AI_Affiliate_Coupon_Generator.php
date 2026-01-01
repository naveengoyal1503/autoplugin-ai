/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Affiliate_Coupon_Generator.php
*/
<?php
/**
 * Plugin Name: AI Affiliate Coupon Generator
 * Plugin URI: https://example.com/ai-affiliate-coupon
 * Description: Automatically generates and displays personalized affiliate coupons using AI to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AIAffiliateCouponGenerator {
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_shortcode('ai_coupon', [$this, 'ai_coupon_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_menu', [$this, 'admin_menu']);
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'ai-coupon.js', ['jquery'], '1.0.0', true);
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'ai-coupon.css', [], '1.0.0');
    }

    public function ai_coupon_shortcode($atts) {
        $atts = shortcode_atts([
            'keyword' => 'discount',
            'affiliate_link' => '',
            'max_coupons' => 3
        ], $atts);

        $coupons = $this->generate_coupons($atts['keyword'], $atts['max_coupons']);
        $output = '<div class="ai-coupon-container">';
        foreach ($coupons as $coupon) {
            $output .= '<div class="ai-coupon">';
            $output .= '<h3>' . esc_html($coupon['title']) . '</h3>';
            $output .= '<p>' . esc_html($coupon['code']) . '</p>';
            $output .= '<p>' . esc_html($coupon['description']) . '</p>';
            if ($atts['affiliate_link']) {
                $output .= '<a href="' . esc_url($atts['affiliate_link']) . '" class="ai-coupon-btn" target="_blank">Shop Now & Save</a>';
            }
            $output .= '</div>';
        }
        $output .= '</div>';
        return $output;
    }

    private function generate_coupons($keyword, $count) {
        $templates = [
            "{$keyword} 20% OFF",
            "SAVE {$keyword.toUpper()} 50%",
            "Exclusive {$keyword} Deal"
        ];
        $coupons = [];
        for ($i = 0; $i < $count; $i++) {
            $code = strtoupper(substr(md5($keyword . time() . $i), 0, 8));
            $coupons[] = [
                'title' => $templates[$i % count($templates)],
                'code' => $code,
                'description' => "Use code {$code} for instant savings on your purchase!"
            ];
        }
        return $coupons;
    }

    public function admin_menu() {
        add_options_page('AI Coupon Settings', 'AI Coupons', 'manage_options', 'ai-coupon', [$this, 'settings_page']);
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('ai_coupon_api_key', sanitize_text_field($_POST['api_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('ai_coupon_api_key', '');
        ?>
        <div class="wrap">
            <h1>AI Affiliate Coupon Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>OpenAI API Key (Pro)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Usage: <code>[ai_coupon keyword="shoes" affiliate_link="https://aff.link"]</code></p>
            <p><strong>Upgrade to Pro</strong> for real AI generation, analytics, and unlimited coupons.</p>
        </div>
        <?php
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AIAffiliateCouponGenerator();

// Pro upsell notice
function ai_coupon_admin_notice() {
    if (!get_option('ai_coupon_dismissed')) {
        echo '<div class="notice notice-info"><p>Unlock unlimited AI coupons with <a href="https://example.com/pro" target="_blank">Pro Version</a> - only $49/year! <a href="?ai_coupon_dismiss=1">Dismiss</a></p></div>';
    }
}
add_action('admin_notices', 'ai_coupon_admin_notice');

if (isset($_GET['ai_coupon_dismiss'])) {
    update_option('ai_coupon_dismissed', 1);
    wp_redirect(admin_url());
    exit;
}

// Minified CSS and JS embedded
/*
.ai-coupon-container { display: flex; flex-wrap: wrap; gap: 20px; }
.ai-coupon { border: 2px solid #007cba; padding: 20px; border-radius: 10px; background: #f9f9f9; flex: 1 1 300px; }
.ai-coupon-btn { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
.ai-coupon-btn:hover { background: #005a87; }
*/

/* JS for dynamic refresh */
/*
var aiCouponRefresh = setInterval(function() { jQuery('.ai-coupon-container').fadeOut().fadeIn(); }, 60000);
*/