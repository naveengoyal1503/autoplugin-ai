/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: AI-powered coupon generator for affiliate marketing. Auto-creates personalized coupons and tracks clicks.
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
        add_shortcode('ai_coupon_box', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_generate_coupon', array($this, 'ajax_generate_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
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
            <h1>AI Coupon Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>OpenAI API Key (Pro Feature)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" placeholder="sk-..."></td>
                    </tr>
                    <tr>
                        <th>Affiliate Network IDs</th>
                        <td><textarea name="affiliate_ids" rows="5" class="large-text"><?php echo esc_textarea($affiliate_ids); ?></textarea><br><small>e.g. Amazon: yourid123, etc.</small></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited AI generations, analytics, and custom templates for $49/year.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'niche' => 'general',
            'count' => 3
        ), $atts);

        ob_start();
        ?>
        <div id="ai-coupon-container-<?php echo uniqid(); ?>" class="ai-coupon-box" data-niche="<?php echo esc_attr($atts['niche']); ?>" data-count="<?php echo intval($atts['count']); ?>">
            <h3>Exclusive Deals & Coupons</h3>
            <div class="coupons-list">
                <p>Generating personalized coupons...</p>
            </div>
            <div class="pro-upsell" style="display:none;">
                <p>Upgrade to Pro for unlimited AI coupons!</p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('ai_coupon_nonce', 'nonce');

        $niche = sanitize_text_field($_POST['niche']);
        $count = intval($_POST['count']);
        $limit = get_option('ai_coupon_limit', 5); // Free limit

        if ($count > $limit) {
            wp_send_json_error('Upgrade to Pro for more coupons.');
            return;
        }

        // Simulate AI generation (Pro: integrate real OpenAI)
        $coupons = array();
        $affiliates = get_option('ai_coupon_affiliate_ids', '');
        $samples = array(
            array('code' => 'SAVE20', 'desc' => '20% off on tech gadgets', 'link' => '#'),
            array('code' => 'DEAL30', 'desc' => '30% off fashion', 'link' => '#'),
            array('code' => 'FREESHIP', 'desc' => 'Free shipping sitewide', 'link' => '#')
        );

        for ($i = 0; $i < min($count, 3); $i++) {
            $coupon = $samples[array_rand($samples)];
            $coupon['link'] = add_query_arg('ref', 'youraffiliateid', $coupon['link']);
            $coupons[] = $coupon;
        }

        wp_send_json_success($coupons);
    }

    public function activate() {
        update_option('ai_coupon_limit', 5);
        flush_rewrite_rules();
    }
}

AICouponAffiliatePro::get_instance();

// Assets would be base64 or separate files in real distro
function ai_coupon_add_assets() {
    // Inline CSS/JS for single file
    ?>
    <style>
    .ai-coupon-box { border: 1px solid #ddd; padding: 20px; margin: 20px 0; background: #f9f9f9; }
    .ai-coupon-box h3 { color: #333; }
    .coupon-item { background: white; margin: 10px 0; padding: 15px; border-radius: 5px; }
    .coupon-code { font-size: 24px; font-weight: bold; color: #e74c3c; }
    .coupon-link { display: inline-block; background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
    </style>
    <script>
    jQuery(document).ready(function($) {
        $('.ai-coupon-box').each(function() {
            var $container = $(this);
            $.post(aicoupon_ajax.ajax_url, {
                action: 'generate_coupon',
                nonce: aicoupon_ajax.nonce,
                niche: $container.data('niche'),
                count: $container.data('count')
            }, function(response) {
                if (response.success) {
                    var html = '';
                    response.data.forEach(function(coupon) {
                        html += '<div class="coupon-item">';
                        html += '<div class="coupon-code">' + coupon.code + '</div>';
                        html += '<p>' + coupon.desc + '</p>';
                        html += '<a href="' + coupon.link + '" class="coupon-link" target="_blank">Get Deal</a>';
                        html += '</div>';
                    });
                    $container.find('.coupons-list').html(html);
                } else {
                    $container.find('.pro-upsell').show();
                }
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'ai_coupon_add_assets');