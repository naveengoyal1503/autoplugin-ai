/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: AI-powered coupon generator with affiliate tracking for monetization.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AICouponAffiliatePro {
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_shortcode('ai_coupon_generator', [$this, 'coupon_shortcode']);
        add_action('wp_ajax_generate_coupon', [$this, 'ajax_generate_coupon']);
        add_action('wp_ajax_nopriv_generate_coupon', [$this, 'ajax_generate_coupon']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function init() {
        if (get_option('aicoupon_pro') !== 'pro') {
            add_action('wp_footer', [$this, 'free_footer_ad']);
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('aicoupon-js', plugin_dir_url(__FILE__) . 'aicoupon.js', ['jquery'], '1.0.0', true);
        wp_localize_script('aicoupon-js', 'aicoupon_ajax', ['ajaxurl' => admin_url('admin-ajax.php')]);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(['niche' => 'general'], $atts);
        ob_start();
        ?>
        <div id="ai-coupon-container" data-niche="<?php echo esc_attr($atts['niche']); ?>">
            <button id="generate-coupon">Generate AI Coupon</button>
            <div id="coupon-output"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        if (!wp_verify_nonce($_POST['nonce'], 'aicoupon_nonce')) {
            wp_die('Security check failed');
        }

        $niche = sanitize_text_field($_POST['niche']);
        $limit = get_option('aicoupon_pro') === 'pro' ? 999 : 3;

        // Simple AI-like coupon generator (mock data for demo)
        $coupons = [
            'SAVE20: 20% off on all items - Affiliate: https://affiliate.link1',
            'DEAL50: $50 off orders over $200 - Affiliate: https://affiliate.link2',
            'FREESHIP: Free shipping today - Affiliate: https://affiliate.link3'
        ];

        $generated = array_slice($coupons, 0, $limit);
        wp_send_json_success(['coupons' => $generated]);
    }

    public function free_footer_ad() {
        echo '<div style="position:fixed;bottom:10px;right:10px;background:#007cba;color:white;padding:10px;z-index:9999;">
                <small>Upgrade to <a href="https://example.com/pro" style="color:#fff;">AI Coupon Pro</a> for unlimited coupons & no ads!</small>
              </div>';
    }

    public function activate() {
        update_option('aicoupon_pro', 'free');
    }
}

new AICouponAffiliatePro();

// Mock JS file content (embedded for single-file)
/*
<script>
jQuery(document).ready(function($) {
    $('#generate-coupon').click(function() {
        $.post(aicoupon_ajax.ajaxurl, {
            action: 'generate_coupon',
            niche: $('#ai-coupon-container').data('niche'),
            nonce: '<?php echo wp_create_nonce("aicoupon_nonce"); ?>'
        }, function(res) {
            if (res.success) {
                let html = '';
                res.data.coupons.forEach(c => {
                    html += '<div class="coupon">' + c + '</div>';
                });
                $('#coupon-output').html(html);
            }
        });
    });
});
</script>
*/
// Note: In production, extract JS to separate file for better performance.
?>