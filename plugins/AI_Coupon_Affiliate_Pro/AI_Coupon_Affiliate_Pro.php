/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: AI-powered coupon and affiliate link manager with auto-generation and tracking.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-pro
 */

if (!defined('ABSPATH')) exit;

class AICouponAffiliatePro {
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_shortcode('ai_coupon', [$this, 'coupon_shortcode']);
        add_action('wp_ajax_generate_coupon', [$this, 'ajax_generate_coupon']);
        add_action('wp_ajax_nopriv_generate_coupon', [$this, 'ajax_generate_coupon']);
    }

    public function init() {
        if (get_option('aicoupon_pro_license') !== 'activated') {
            add_action('admin_notices', [$this, 'pro_notice']);
        }
    }

    public function pro_notice() {
        echo '<div class="notice notice-info"><p>Unlock AI features in <strong>AI Coupon Affiliate Pro</strong> with Pro upgrade!</p></div>';
    }

    public function enqueue_scripts() {
        wp_enqueue_script('aicoupon-js', plugin_dir_url(__FILE__) . 'aicoupon.js', ['jquery'], '1.0.0', true);
        wp_localize_script('aicoupon-js', 'aicoupon_ajax', ['ajax_url' => admin_url('admin-ajax.php')]);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(['id' => '', 'afflink' => '', 'discount' => '10%'], $atts);
        $coupon_code = $this->generate_coupon_code($atts['id']);
        $pro_ai = get_option('aicoupon_pro_license') === 'activated';
        $ai_desc = $pro_ai ? $this->ai_generate_description($atts['afflink']) : 'Save ' . $atts['discount'] . ' on your purchase!';

        ob_start();
        ?>
        <div class="ai-coupon-box" data-id="<?php echo esc_attr($atts['id']); ?>">
            <h3>Exclusive Deal: <span class="coupon-code"><?php echo esc_html($coupon_code); ?></span></h3>
            <p><?php echo esc_html($ai_desc); ?></p>
            <a href="<?php echo esc_url($atts['afflink']); ?><?php echo strpos($atts['afflink'], '?') === false ? '?coupon=' : '&coupon='; ?><?php echo esc_attr($coupon_code); ?}" class="coupon-btn" target="_blank">Get Deal Now (Affiliate Link)</a>
            <?php if ($pro_ai) : ?>
            <button class="generate-new">New Coupon</button>
            <?php endif; ?>
            <small>Tracked clicks: <span class="click-count">0</span></small>
        </div>
        <style>
        .ai-coupon-box { border: 2px solid #007cba; padding: 20px; margin: 20px 0; border-radius: 10px; background: #f9f9f9; }
        .coupon-code { font-size: 24px; color: #e74c3c; font-weight: bold; }
        .coupon-btn { background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
        .coupon-btn:hover { background: #219a52; }
        </style>
        <?php
        return ob_get_clean();
    }

    private function generate_coupon_code($id) {
        return 'SAVE' . strtoupper(substr(md5($id . time()), 0, 6));
    }

    private function ai_generate_description($afflink) {
        // Simulated AI: In Pro, integrate real AI API like OpenAI
        $keywords = ['deal', 'discount', 'exclusive', 'limited'];
        return 'AI-Powered Deal: Grab this ' . $keywords[array_rand($keywords)] . ' offer via affiliate link!';
    }

    public function ajax_generate_coupon() {
        if (!wp_verify_nonce($_POST['nonce'], 'aicoupon_nonce')) wp_die();
        $id = sanitize_text_field($_POST['id']);
        wp_send_json_success(['code' => $this->generate_coupon_code($id)]);
    }
}

new AICouponAffiliatePro();

// Admin settings page
add_action('admin_menu', function() {
    add_options_page('AI Coupon Pro', 'AI Coupon Pro', 'manage_options', 'ai-coupon-pro', function() {
        echo '<h1>AI Coupon Affiliate Pro Settings</h1><form method="post" action="options.php">';
        settings_fields('aicoupon_settings');
        do_settings_sections('aicoupon_settings');
        submit_button();
        echo '<p><strong>Upgrade to Pro</strong> for AI descriptions, analytics, and unlimited coupons ($49/year).</p>';
        echo '</form>';
    });
});

// JS file content (embedded for single-file)
function aicoupon_inline_js() {
    if (!is_admin()) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('.generate-new').click(function() {
                var box = $(this).closest('.ai-coupon-box');
                var id = box.data('id');
                $.post(aicoupon_ajax.ajax_url, {
                    action: 'generate_coupon',
                    id: id,
                    nonce: '<?php echo wp_create_nonce('aicoupon_nonce'); ?>'
                }, function(res) {
                    if (res.success) {
                        box.find('.coupon-code').text(res.data.code);
                        box.find('.click-count').text(parseInt(box.find('.click-count').text()) + 1);
                    }
                });
            });

            $('.coupon-btn').click(function() {
                $(this).closest('.ai-coupon-box').find('.click-count').text(
                    parseInt($(this).closest('.ai-coupon-box').find('.click-count').text()) + 1
                );
            });
        });
        </script>
        <?php
    }
}
add_action('wp_footer', 'aicoupon_inline_js');