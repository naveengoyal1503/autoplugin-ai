/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Generator_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Generator Pro
 * Plugin URI: https://example.com/ai-coupon-generator
 * Description: AI-powered plugin that generates personalized, trackable coupons and affiliate promo codes for WordPress sites to boost conversions and monetization.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-generator
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponGenerator {
    const VERSION = '1.0.0';
    const PRO_VERSION = '1.0.0';
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
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_shortcode('ai_coupon_generator', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-generator', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if ($this->is_pro()) {
            // Pro features
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), self::VERSION, true);
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), self::VERSION);
        wp_localize_script('ai-coupon-js', 'aicg_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aicg_nonce')));
    }

    public function admin_menu() {
        add_options_page('AI Coupon Generator', 'AI Coupons', 'manage_options', 'ai-coupon-generator', array($this, 'admin_page'));
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('AI Coupon Generator', 'ai-coupon-generator'); ?></h1>
            <form id="coupon-form">
                <?php wp_nonce_field('aicg_nonce', 'aicg_nonce'); ?>
                <p><label><?php _e('Product/Brand:', 'ai-coupon-generator'); ?> <input type="text" name="brand" id="brand" required></label></p>
                <p><label><?php _e('Discount %:', 'ai-coupon-generator'); ?> <input type="number" name="discount" id="discount" min="1" max="100" value="20"></label></p>
                <p><label><?php _e('Affiliate Link (Pro):', 'ai-coupon-generator'); ?> <input type="url" name="afflink" id="afflink"></label></p>
                <p><input type="submit" class="button-primary" value="<?php _e('Generate Coupon', 'ai-coupon-generator'); ?>"></p>
            </form>
            <div id="coupon-output"></div>
            <?php if (!$this->is_pro()) : ?>
            <div class="notice notice-info"><p><?php _e('Upgrade to Pro for unlimited coupons, analytics, and affiliate tracking!', 'ai-coupon-generator'); ?></p></div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('aicg_nonce', 'nonce');

        if (!$this->is_pro() && get_option('aicg_generated_count', 0) >= 5) {
            wp_die(json_encode(array('error' => __('Free limit reached. Upgrade to Pro!', 'ai-coupon-generator'))));
        }

        $brand = sanitize_text_field($_POST['brand']);
        $discount = intval($_POST['discount']);
        $afflink = esc_url_raw($_POST['afflink'] ?? '');

        // Simple AI-like generation (deterministic for demo)
        $code = strtoupper(substr(md5($brand . time()), 0, 8));
        $expiry = date('Y-m-d', strtotime('+30 days'));

        $coupon = array(
            'code' => $code,
            'brand' => $brand,
            'discount' => $discount . '%',
            'expiry' => $expiry,
            'link' => $afflink,
            'embed' => $this->get_coupon_embed($code, $brand, $discount, $expiry, $afflink)
        );

        update_option('aicg_generated_count', get_option('aicg_generated_count', 0) + 1);

        wp_die(json_encode($coupon));
    }

    private function get_coupon_embed($code, $brand, $discount, $expiry, $link) {
        $link_html = $link ? '<a href="' . $link . '" target="_blank">' . __('Shop Now', 'ai-coupon-generator') . '</a>' : '';
        return '<div class="ai-coupon-card"><h3>' . sprintf(__('Save %s on %s!', 'ai-coupon-generator'), $discount, $brand) . '</h3><p><strong>Code: ' . $code . '</strong></p><p>Expires: ' . $expiry . '</p>' . $link_html . '</div>';
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        // In Pro, load saved coupon by ID
        return '<div class="ai-coupon-shortcode">[Use [ai_coupon id="' . $atts['id'] . '"] for Pro]</div>';
    }

    public function activate() {
        add_option('aicg_generated_count', 0);
    }

    private function is_pro() {
        return defined('AICG_PRO') || file_exists(plugin_dir_path(__FILE__) . 'pro/pro.php');
    }
}

AICouponGenerator::get_instance();

// Assets would be created separately: script.js for AJAX, style.css for styling
// For single file, inline minimal CSS/JS if needed, but kept external for cleanliness

?>