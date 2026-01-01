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
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_generate_coupon', array($this, 'ajax_generate_coupon'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            $this->handle_pro_upgrade();
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
            update_option('ai_coupon_api_key', sanitize_text_field($_POST['api_key']));
            update_option('ai_coupon_affiliate_id', sanitize_text_field($_POST['affiliate_id']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('ai_coupon_api_key', '');
        $affiliate_id = get_option('ai_coupon_affiliate_id', '');
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
                        <th>Affiliate ID</th>
                        <td><input type="text" name="affiliate_id" value="<?php echo esc_attr($affiliate_id); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock AI generation for $49/year. <a href="https://example.com/pro" target="_blank">Buy Now</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'niche' => 'general',
            'count' => 3
        ), $atts);

        ob_start();
        echo '<div id="ai-coupon-container" data-niche="' . esc_attr($atts['niche']) . '" data-count="' . intval($atts['count']) . '"><p>Loading coupons...</p></div>';
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('ai_coupon_nonce', 'nonce');
        $niche = sanitize_text_field($_POST['niche']);
        $count = intval($_POST['count']);
        $is_pro = get_option('ai_coupon_pro_active', false);

        if (!$is_pro) {
            // Free version: Static demo coupons
            $coupons = array(
                array('code' => 'SAVE20', 'store' => 'Example Store', 'discount' => '20% Off', 'link' => '#'),
                array('code' => 'DEAL10', 'store' => 'Shop Now', 'discount' => '10% Off', 'link' => '#')
            );
        } else {
            // Pro: Simulate AI generation (replace with real AI API call)
            $api_key = get_option('ai_coupon_api_key');
            $affiliate_id = get_option('ai_coupon_affiliate_id');
            $coupons = $this->generate_ai_coupons($niche, $count, $affiliate_id);
        }

        wp_send_json_success($coupons);
    }

    private function generate_ai_coupons($niche, $count, $affiliate_id) {
        // Simulate AI response - in pro version, integrate real AI API
        $stores = array('Amazon', 'BestBuy', 'Walmart');
        $coupons = array();
        for ($i = 0; $i < $count; $i++) {
            $coupons[] = array(
                'code' => 'AI' . wp_generate_uuid4() . substr(md5($niche), 0, 4),
                'store' => $stores[array_rand($stores)],
                'discount' => rand(10, 50) . '% Off',
                'link' => 'https://affiliate.link/' . $affiliate_id . '?coupon=AI' . rand(1000,9999)
            );
        }
        return $coupons;
    }

    private function handle_pro_upgrade() {
        if (isset($_GET['pro_activate']) && wp_verify_nonce($_GET['_wpnonce'], 'pro_activate')) {
            update_option('ai_coupon_pro_active', true);
        }
    }
}

new AICouponAffiliatePro();

// Create JS and CSS files placeholders (in real plugin, include them)
/* ai-coupon.js content:
$(document).ready(function() {
    $('#ai-coupon-container').on('click', '.generate-btn', function() {
        $.post(aicoupon_ajax.ajax_url, {
            action: 'generate_coupon',
            nonce: aicoupon_ajax.nonce,
            niche: $('#ai-coupon-container').data('niche'),
            count: $('#ai-coupon-container').data('count')
        }, function(response) {
            if (response.success) {
                let html = '';
                response.data.forEach(function(coupon) {
                    html += `<div class="coupon-card"><strong>${coupon.code}</strong> - ${coupon.discount} at ${coupon.store} <a href="${coupon.link}" target="_blank">Shop Now</a></div>`;
                });
                $('#ai-coupon-container').html(html);
            }
        });
    });
});
*/

/* ai-coupon.css content:
#ai-coupon-container { max-width: 600px; }
.coupon-card { background: #f0f8ff; padding: 15px; margin: 10px 0; border-radius: 5px; }
.generate-btn { background: #007cba; color: white; padding: 10px; border: none; cursor: pointer; }
*/