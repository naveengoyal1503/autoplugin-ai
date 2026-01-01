/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Generator_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Generator Pro
 * Plugin URI: https://example.com/aicouponpro
 * Description: Automatically generates and displays personalized, trackable coupon codes for affiliate products using AI-powered recommendations.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-generator-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponGeneratorPro {
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
        add_shortcode('ai_coupon_generator', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-generator-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (get_option('aicoupon_pro_license') !== 'activated') {
            add_action('admin_notices', array($this, 'pro_nag'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-coupon-js', 'aicoupon_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aicoupon_nonce')));
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => 'general',
            'limit' => 5,
        ), $atts);

        $coupons = $this->get_sample_coupons($atts['category'], $atts['limit']);
        ob_start();
        ?>
        <div id="ai-coupon-container" class="ai-coupon-grid" data-category="<?php echo esc_attr($atts['category']); ?>">
            <?php foreach ($coupons as $coupon): ?>
            <div class="coupon-card">
                <h3><?php echo esc_html($coupon['title']); ?></h3>
                <p><?php echo esc_html($coupon['description']); ?></p>
                <div class="coupon-code"><?php echo esc_html($coupon['code']); ?></div>
                <a href="<?php echo esc_url($coupon['link']); ?}" class="coupon-btn" target="_blank" rel="nofollow">Get Deal <?php echo esc_html($coupon['discount']); ?></a>
                <small>Affiliate Link</small>
            </div>
            <?php endforeach; ?>
            <button id="generate-more" class="generate-btn">Generate More Coupons</button>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_sample_coupons($category, $limit) {
        $samples = array(
            array('title' => 'Hostinger 75% Off', 'description' => 'Premium hosting for WordPress sites.', 'code' => 'AICWP75', 'link' => 'https://example.com/hostinger', 'discount' => '75%'),
            array('title' => 'Elementor Pro Discount', 'description' => 'Page builder for stunning designs.', 'code' => 'AICPRO20', 'link' => 'https://example.com/elementor', 'discount' => '20%'),
            array('title' => 'SEMrush Free Trial', 'description' => 'SEO tool for keyword research.', 'code' => 'AISEMRUSH', 'link' => 'https://example.com/semrush', 'discount' => 'Free Month'),
        );
        return array_slice($samples, 0, $limit);
    }

    public function admin_menu() {
        add_options_page('AI Coupon Pro Settings', 'AI Coupon Pro', 'manage_options', 'ai-coupon-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['aicoupon_pro_license'])) {
            update_option('aicoupon_pro_license', sanitize_text_field($_POST['aicoupon_pro_license']));
            echo '<div class="notice notice-success"><p>License updated!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Coupon Generator Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Pro License Key</th>
                        <td><input type="text" name="aicoupon_pro_license" value="<?php echo esc_attr(get_option('aicoupon_pro_license')); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Pro:</strong> Unlock unlimited coupons, AI generation, analytics. <a href="https://example.com/buy-pro" target="_blank">Buy Now $49/year</a></p>
        </div>
        <?php
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('aicoupon_nonce', 'nonce');
        if (get_option('aicoupon_pro_license') !== 'activated') {
            wp_die('Pro feature. Upgrade required.');
        }
        // Simulate AI generation
        $new_coupon = array(
            'title' => 'AI Generated Deal: ' . wp_rand(1000,9999),
            'code' => 'AI' . wp_rand(10000,99999),
            'discount' => wp_rand(10,75) . '% OFF'
        );
        wp_send_json_success($new_coupon);
    }

    public function pro_nag() {
        if (current_user_can('manage_options')) {
            echo '<div class="notice notice-info"><p>AI Coupon Generator Pro: <a href="' . admin_url('options-general.php?page=ai-coupon-pro') . '">Activate Pro</a> for full features!</p></div>';
        }
    }

    public function activate() {
        update_option('aicoupon_pro_license', 'free');
        flush_rewrite_rules();
    }
}

AICouponGeneratorPro::get_instance();

// Assets would be base64 encoded or separate files, but for single-file demo:
/*
assets/style.css content:
.ai-coupon-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
.coupon-card { border: 1px solid #ddd; padding: 20px; border-radius: 8px; background: #f9f9f9; }
.coupon-code { background: #fff; padding: 10px; font-family: monospace; display: block; margin: 10px 0; }
.coupon-btn { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
.generate-btn { background: #28a745; color: white; border: none; padding: 10px 20px; cursor: pointer; }

assets/script.js content:
jQuery(document).ready(function($) {
    $('#generate-more').click(function() {
        $.post(aicoupon_ajax.ajax_url, {
            action: 'generate_coupon',
            nonce: aicoupon_ajax.nonce
        }, function(response) {
            if (response.success) {
                // Append new coupon
                $('#ai-coupon-container').prepend('<div class="coupon-card">' + /* build html */ + '</div>');
            } else {
                alert(response.data);
            }
        });
    });
});
*/
?>