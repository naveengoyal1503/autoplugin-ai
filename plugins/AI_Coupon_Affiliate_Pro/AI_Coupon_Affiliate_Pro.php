/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: AI-powered coupon generator for affiliate marketing. Free version generates coupons; Pro unlocks advanced features.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponAffiliatePro {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon_generator', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('ai-coupon-pro-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('ai-coupon-pro-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('AI Coupon Pro Settings', 'AI Coupon Pro', 'manage_options', 'ai-coupon-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('ai_coupon_api_key', sanitize_text_field($_POST['api_key']));
            update_option('ai_coupon_affiliate_ids', sanitize_text_field($_POST['affiliate_ids']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('ai_coupon_api_key', '');
        $affiliate_ids = get_option('ai_coupon_affiliate_ids', '');
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
                        <th>Affiliate Program IDs (e.g., Amazon, etc.)</th>
                        <td><textarea name="affiliate_ids" rows="3" class="large-text"><?php echo esc_textarea($affiliate_ids); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, real-time tracking, and more for $49/year.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'niche' => 'general',
            'count' => 5
        ), $atts);

        $coupons = $this->generate_coupons($atts['niche'], $atts['count']);
        ob_start();
        ?>
        <div id="ai-coupon-container" class="ai-coupon-pro-grid">
            <?php foreach ($coupons as $coupon): ?>
            <div class="coupon-card">
                <h3><?php echo esc_html($coupon['title']); ?></h3>
                <p><?php echo esc_html($coupon['description']); ?></p>
                <div class="coupon-code"><?php echo esc_html($coupon['code']); ?></div>
                <a href="<?php echo esc_url($coupon['link']); ?}" class="coupon-btn" target="_blank" rel="nofollow">Get Deal <?php echo $coupon['discount']; ?></a>
                <?php if (!$this->is_pro()): ?>
                <small><a href="#" onclick="alert('Upgrade to Pro for tracking!')">Track Clicks (Pro)</a></small>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php if (!$this->is_pro()): ?>
            <div class="pro-upgrade-banner">
                <p>Upgrade to <strong>AI Coupon Pro</strong> for unlimited coupons & analytics! <a href="https://example.com/buy-pro" target="_blank">Buy Now $49</a></p>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_coupons($niche, $count) {
        // Free version: Demo coupons. Pro: Real AI + affiliate API integration
        $demo_coupons = array(
            array('title' => '50% Off Hosting', 'description' => 'Exclusive deal for new users.', 'code' => 'SAVE50', 'link' => '#', 'discount' => '50%'),
            array('title' => 'Free Domain', 'description' => 'Get a free .com domain.', 'code' => 'FREEDOMAIN', 'link' => '#', 'discount' => 'Free'),
            // Add more demo data up to $count
        );
        return array_slice($demo_coupons, 0, $count);
    }

    private function is_pro() {
        return false; // Check license in Pro version
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

AICouponAffiliatePro::get_instance();

// Inline CSS/JS for single file

function ai_coupon_pro_assets() {
    echo '<style>
    .ai-coupon-pro-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
    .coupon-card { border: 1px solid #ddd; padding: 20px; border-radius: 8px; background: #f9f9f9; }
    .coupon-code { background: #fff; padding: 10px; font-family: monospace; text-align: center; margin: 10px 0; }
    .coupon-btn { display: block; background: #0073aa; color: white; padding: 12px; text-decoration: none; text-align: center; border-radius: 5px; }
    .pro-upgrade-banner { grid-column: 1 / -1; background: #ffeb3b; padding: 20px; text-align: center; border-radius: 8px; }
    </style>';
    echo '<script>jQuery(document).ready(function($) { $(".coupon-btn").click(function() { if(!window.aiProActive) { console.log("Pro upgrade needed for tracking"); } }); });</script>';
}
add_action('wp_head', 'ai_coupon_pro_assets');

?>