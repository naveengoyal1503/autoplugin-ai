/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: AI-powered coupon generator that auto-fetches and displays personalized affiliate coupons.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-affiliate-pro
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

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon_generator', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_generate_coupon', array($this, 'ajax_generate_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-affiliate-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
        wp_localize_script('ai-coupon-js', 'aicoupon_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_coupon_nonce')));
    }

    public function admin_menu() {
        add_options_page('AI Coupon Pro Settings', 'AI Coupon Pro', 'manage_options', 'ai-coupon-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('ai_coupon_api_key', sanitize_text_field($_POST['api_key']));
            update_option('ai_coupon_affiliate_ids', sanitize_textarea_field($_POST['affiliate_ids']));
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
                        <th>AI API Key (Pro)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" placeholder="Enter OpenAI or similar API key" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Network IDs</th>
                        <td><textarea name="affiliate_ids" rows="5" class="large-text"><?php echo esc_textarea($affiliate_ids); ?></textarea><br><small>e.g. Amazon Affiliate ID: yourid123</small></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock AI generation, unlimited coupons, and analytics for $49/year. <a href="https://example.com/pro" target="_blank">Get Pro</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => 'all',
            'count' => 5
        ), $atts);

        ob_start();
        ?>
        <div id="ai-coupon-container" data-category="<?php echo esc_attr($atts['category']); ?>" data-count="<?php echo intval($atts['count']); ?>">
            <h3>Exclusive Deals & Coupons</h3>
            <div id="coupon-list"></div>
            <button id="generate-coupons" class="button">Generate Coupons</button>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('ai_coupon_nonce', 'nonce');

        $category = sanitize_text_field($_POST['category']);
        $count = intval($_POST['count']);

        // Simulate AI generation (Pro feature uses real API)
        $is_pro = get_option('ai_coupon_pro_active', false);
        if ($is_pro && $api_key = get_option('ai_coupon_api_key')) {
            // Real AI call here (OpenAI etc.)
            $prompt = "Generate $count unique coupon codes for $category products with affiliate links.";
            // $coupons = $this->call_ai_api($prompt);
        }

        // Demo coupons with affiliate placeholders
        $demo_coupons = array(
            array('code' => 'SAVE20', 'desc' => '20% off Electronics', 'aff_link' => 'https://amazon.com/?tag=' . get_option('ai_coupon_affiliate_ids')),
            array('code' => 'DEAL50', 'desc' => '50% off Fashion', 'aff_link' => 'https://amazon.com/?tag=' . get_option('ai_coupon_affiliate_ids')),
            array('code' => 'FREESHIP', 'desc' => 'Free Shipping on all', 'aff_link' => 'https://amazon.com/?tag=' . get_option('ai_coupon_affiliate_ids'))
        );

        wp_send_json_success(array_slice($demo_coupons, 0, $count));
    }

    public function activate() {
        add_option('ai_coupon_pro_active', false);
    }
}

AICouponAffiliatePro::get_instance();

// Assets would be created as separate files, but for single-file demo, inline them
/*
Inline CSS:
#ai-coupon-container { border: 1px solid #ddd; padding: 20px; margin: 20px 0; }
#coupon-list { margin: 10px 0; }
.coupon-item { background: #f9f9f9; padding: 10px; margin: 5px 0; border-left: 4px solid #0073aa; }

Inline JS:
function generateCoupons() {
    jQuery.post(aicoupon_ajax.ajax_url, {
        action: 'generate_coupon',
        nonce: aicoupon_ajax.nonce,
        category: jQuery('#ai-coupon-container').data('category'),
        count: jQuery('#ai-coupon-container').data('count')
    }, function(response) {
        if (response.success) {
            var html = '';
            jQuery.each(response.data, function(i, coupon) {
                html += '<div class="coupon-item"><strong>' + coupon.code + '</strong><br>' + coupon.desc + '<br><a href="' + coupon.aff_link + '" target="_blank">Shop Now (Affiliate)</a></div>';
            });
            jQuery('#coupon-list').html(html);
        }
    });
}
jQuery('#generate-coupons').click(generateCoupons);
*/
?>