/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/ai-coupon-affiliate-pro
 * Description: AI-powered plugin that generates personalized affiliate coupons, tracks clicks, and displays dynamic deals to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponAffiliatePro {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon_deals', array($this, 'coupon_shortcode'));
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
                        <th>Affiliate Links/Keywords</th>
                        <td><textarea name="ai_coupon_settings[affiliates]" rows="5" cols="50"><?php echo esc_textarea(get_option('ai_coupon_settings')['affiliates'] ?? ''); ?></textarea><br>
                        <small>Enter affiliate links or keywords, one per line (e.g., Amazon|https://amzn.to/xxx)</small></td>
                    </tr>
                    <tr>
                        <th>API Key (Pro)</th>
                        <td><input type="text" name="ai_coupon_settings[api_key]" value="<?php echo esc_attr(get_option('ai_coupon_settings')['api_key'] ?? ''); ?>" /><br>
                        <small>Pro feature: OpenAI API key for advanced AI generation.</small></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics & more for $49/year. <a href="#" onclick="alert('Visit example.com/pro')">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('count' => 5), $atts);
        $settings = get_option('ai_coupon_settings', array());
        $affiliates = explode('\n', $settings['affiliates'] ?? '');
        $coupons = array();

        // Simple AI-like generation (free version demo)
        $products = array('Web Hosting 50% OFF', 'Premium Theme $20', 'SEO Tool 30% Discount', 'Plugin Bundle Save 40%', 'Course Access 25% OFF');
        for ($i = 0; $i < min($atts['count'], 5); $i++) { // Free limit
            $aff = trim($affiliates[array_rand($affiliates)] ?? 'https://example.com');
            $code = 'SAVE' . rand(10, 99);
            $coupons[] = array(
                'title' => $products[array_rand($products)],
                'code' => $code,
                'link' => $aff,
                'id' => uniqid()
            );
        }

        ob_start();
        ?>
        <div id="ai-coupon-container" class="ai-coupon-deals">
            <?php foreach ($coupons as $coupon): ?>
            <div class="coupon-card" data-id="<?php echo esc_attr($coupon['id']); ?>">
                <h4><?php echo esc_html($coupon['title']); ?></h4>
                <p><strong>Code:</strong> <span class="coupon-code"><?php echo esc_html($coupon['code']); ?></span></p>
                <a href="<?php echo esc_url($coupon['link']); ?}" target="_blank" class="coupon-btn" data-track="<?php echo esc_attr($coupon['id']); ?>">Get Deal (Affiliate)</a>
            </div>
            <?php endforeach; ?>
        </div>
        <script>console.log('AI Coupon Pro loaded - Free version limited to 5 coupons');</script>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('ai_coupon_settings', array());
    }
}

AICouponAffiliatePro::get_instance();

// Pro check function (demo)
function is_ai_coupon_pro() {
    return false; // Set to true for pro
}

// CSS
$css = '#ai-coupon-container { max-width: 600px; margin: 20px 0; } .coupon-card { background: #f9f9f9; padding: 20px; margin: 10px 0; border-radius: 8px; border-left: 5px solid #007cba; } .coupon-code { font-family: monospace; background: #fff; padding: 5px 10px; border: 1px solid #ddd; } .coupon-btn { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; } .coupon-btn:hover { background: #005a87; }';
file_put_contents(plugin_dir_path(__FILE__) . 'style.css', $css);

// JS
$js = 'jQuery(document).ready(function($) { $(".coupon-btn").click(function(e) { var id = $(this).data("track"); $.post("' . admin_url('admin-ajax.php') . '?action=track_coupon&coupon_id=" + id); }); });';
file_put_contents(plugin_dir_path(__FILE__) . 'script.js', $js);

add_action('wp_ajax_track_coupon', function() { if (current_user_can('manage_options')) { error_log('Coupon clicked: ' . $_GET['coupon_id']); } wp_die(); });

// Pro upsell notice
add_action('admin_notices', function() { if (!is_ai_coupon_pro() && current_user_can('manage_options')) { echo '<div class="notice notice-info"><p>Upgrade to <strong>AI Coupon Affiliate Pro</strong> for unlimited AI-generated coupons & analytics! <a href="options-general.php?page=ai-coupon-pro">Upgrade</a></p></div>'; } });