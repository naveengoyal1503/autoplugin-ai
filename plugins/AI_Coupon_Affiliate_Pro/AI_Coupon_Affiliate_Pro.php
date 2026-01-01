/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: AI-powered coupon generator with affiliate tracking for WordPress.
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
        add_shortcode('ai_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_generate_coupon', array($this, 'ajax_generate_coupon'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-pro', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        if (get_option('aicoupon_pro_license') !== 'activated') {
            add_action('admin_notices', array($this, 'pro_nag'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'ai-coupon.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'ai-coupon.css', array(), '1.0.0');
        wp_localize_script('ai-coupon-js', 'aicoupon_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_coupon_nonce')));
    }

    public function admin_menu() {
        add_options_page('AI Coupon Pro Settings', 'AI Coupon Pro', 'manage_options', 'ai-coupon-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('aicoupon_affiliate_ids', sanitize_text_field($_POST['affiliate_ids']));
            update_option('aicoupon_max_coupons', intval($_POST['max_coupons']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $affiliate_ids = get_option('aicoupon_affiliate_ids', '');
        $max_coupons = get_option('aicoupon_max_coupons', 5);
        ?>
        <div class="wrap">
            <h1>AI Coupon Affiliate Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Affiliate Program IDs (comma-separated)</th>
                        <td><input type="text" name="affiliate_ids" value="<?php echo esc_attr($affiliate_ids); ?>" class="regular-text" placeholder="amazon123,clickbank456" /></td>
                    </tr>
                    <tr>
                        <th>Max Coupons per Page (Free: 3)</th>
                        <td><input type="number" name="max_coupons" value="<?php echo $max_coupons; ?>" min="1" max="50" /></td>
                    </tr>
                </table>
                <?php if (get_option('aicoupon_pro_license') !== 'activated') { ?>
                    <p class="description">Upgrade to Pro for unlimited coupons and AI features! <a href="https://example.com/pro">Get Pro ($49/year)</a></p>
                <?php } ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('niche' => 'general'), $atts);
        $max = get_option('aicoupon_pro_license') === 'activated' ? get_option('aicoupon_max_coupons', 10) : 3;
        $coupons = $this->generate_coupons($max, $atts['niche']);
        ob_start();
        ?>
        <div id="ai-coupon-container" class="ai-coupon-pro" data-niche="<?php echo esc_attr($atts['niche']); ?>">
            <?php foreach ($coupons as $coupon) : ?>
                <div class="coupon-item">
                    <h3><?php echo esc_html($coupon['title']); ?></h3>
                    <p>Code: <strong><?php echo esc_html($coupon['code']); ?></strong></p>
                    <p>Discount: <?php echo esc_html($coupon['discount']); ?> off</p>
                    <a href="<?php echo esc_url($coupon['link']); ?}" class="coupon-btn" target="_blank" rel="nofollow">Shop Now & Save <?php echo $this->track_affiliate($coupon['affiliate_id']); ?></a>
                </div>
            <?php endforeach; ?>
            <button id="generate-more" class="button">Generate More Coupons</button>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_coupons($count, $niche) {
        $affiliates = explode(',', get_option('aicoupon_affiliate_ids', 'amazon,clickbank'));
        $coupons = array();
        $words = array('SAVE', 'DEAL', 'OFF', 'PROMO', 'COUPON');
        for ($i = 0; $i < $count; $i++) {
            $code = $words[array_rand($words)] . rand(1000, 9999);
            $coupons[] = array(
                'title' => ucwords($niche) . ' Exclusive Deal',
                'code' => $code,
                'discount' => rand(10, 50) . '%',
                'link' => 'https://example.com/deal?' . $code,
                'affiliate_id' => $affiliates[array_rand($affiliates)]
            );
        }
        return $coupons;
    }

    private function track_affiliate($id) {
        return '?aff=' . urlencode($id);
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('ai_coupon_nonce', 'nonce');
        if (get_option('aicoupon_pro_license') !== 'activated' && get_option('aicoupon_generated_today', 0) >= 5) {
            wp_die('Upgrade to Pro for unlimited generations.');
        }
        $niche = sanitize_text_field($_POST['niche']);
        $coupon = $this->generate_coupons(1, $niche);
        update_option('aicoupon_generated_today', get_option('aicoupon_generated_today', 0) + 1);
        wp_send_json_success($coupon);
    }

    public function pro_nag() {
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Coupon Affiliate Pro</strong> full features: <a href="https://example.com/pro">Upgrade Now ($49/year)</a></p></div>';
    }
}

AICouponAffiliatePro::get_instance();

// Dummy JS/CSS placeholders (in real plugin, include files)
function ai_coupon_placeholder_js() { return 'jQuery(document).ready(function($){ $("#generate-more").click(function(){ $.post(aicoupon_ajax.ajax_url, {action:"generate_coupon", nonce:aicoupon_ajax.nonce, niche:$("#ai-coupon-container").data("niche")}, function(res){ if(res.success) { $(".coupon-item:last").after("<div class=\"coupon-item\">"+res.data.title+"</div>"); } }); }); });'; }
function ai_coupon_placeholder_css() { return '.ai-coupon-pro { max-width:600px; } .coupon-item { border:1px solid #ddd; padding:15px; margin:10px 0; } .coupon-btn { background:#0073aa; color:white; padding:10px 20px; text-decoration:none; }'; }

// Activation hook
register_activation_hook(__FILE__, function() { update_option('aicoupon_pro_license', 'free'); });