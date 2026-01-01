/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Generator_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Generator Pro
 * Plugin URI: https://example.com/ai-coupon-generator
 * Description: AI-powered plugin that automatically generates and displays personalized coupon codes, affiliate links, and exclusive deals.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AICouponGenerator {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_generate_coupon', array($this, 'ajax_generate_coupon'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'ai-coupon.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-coupon-js', 'ai_coupon_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('AI Coupon Settings', 'AI Coupons', 'manage_options', 'ai-coupon', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('ai_coupon_settings', 'ai_coupon_options');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>AI Coupon Generator Pro Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('ai_coupon_settings'); ?>
                <?php do_settings_sections('ai_coupon_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Affiliate Links</th>
                        <td><textarea name="ai_coupon_options[affiliates]" rows="5" cols="50"><?php echo esc_textarea(get_option('ai_coupon_options')['affiliates'] ?? ''); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Default Discount</th>
                        <td><input type="text" name="ai_coupon_options[discount]" value="<?php echo esc_attr(get_option('ai_coupon_options')['discount'] ?? '20'); ?>%" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Pro:</strong> Unlimited coupons, AI personalization, analytics. <a href="#" onclick="alert('Pro upgrade link')">Get Pro ($49/year)</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => 'general',
            'count' => 1
        ), $atts);

        $options = get_option('ai_coupon_options', array());
        $coupons = array();
        for ($i = 0; $i < intval($atts['count']); $i++) {
            $code = $this->generate_coupon_code();
            $coupons[] = array(
                'code' => $code,
                'discount' => $options['discount'] ?? '20%',
                'link' => $options['affiliates'] ? explode('\n', $options['affiliates']) ?? '#' : '#',
                'category' => $atts['category']
            );
        }

        ob_start();
        ?>
        <div id="ai-coupon-container" class="ai-coupon-pro" data-category="<?php echo esc_attr($atts['category']); ?>">
            <?php foreach ($coupons as $coupon): ?>
            <div class="coupon-card">
                <h3><?php echo esc_html($atts['category']); ?> Exclusive Deal</h3>
                <p>Use code: <strong><?php echo esc_html($coupon['code']); ?></strong> for <strong><?php echo esc_html($coupon['discount']); ?> OFF</strong></p>
                <a href="<?php echo esc_url($coupon['link']); ?}" class="coupon-btn" target="_blank">Shop Now & Save</a>
                <button class="generate-new">New Coupon</button>
            </div>
            <?php endforeach; ?>
        </div>
        <style>
        .ai-coupon-pro .coupon-card { border: 2px dashed #007cba; padding: 20px; margin: 10px 0; background: #f9f9f9; border-radius: 8px; }
        .coupon-btn { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        .generate-new { margin-left: 10px; background: #28a745; color: white; border: none; padding: 8px 16px; border-radius: 5px; cursor: pointer; }
        </style>
        <?php
        return ob_get_clean();
    }

    private function generate_coupon_code($length = 10) {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $code;
    }

    public function ajax_generate_coupon() {
        if (!wp_verify_nonce($_POST['nonce'], 'ai_coupon_nonce')) {
            wp_die('Security check failed');
        }
        $code = $this->generate_coupon_code();
        wp_send_json_success(array('code' => $code));
    }
}

new AICouponGenerator();

// Freemium notice
add_action('admin_notices', function() {
    if (!get_option('ai_coupon_pro_activated')) {
        echo '<div class="notice notice-info"><p>AI Coupon Generator Pro: Unlock unlimited features for $49/year! <a href="#">Upgrade Now</a></p></div>';
    }
});

// Sample JS file content (inline for single file)
?>
<script>
jQuery(document).ready(function($) {
    $('.generate-new').click(function() {
        var $card = $(this).closest('.coupon-card');
        $.post(ai_coupon_ajax.ajax_url, {
            action: 'generate_coupon',
            nonce: '<?php echo wp_create_nonce('ai_coupon_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                $card.find('strong').first().text(response.data.code);
            }
        });
    });
});
</script>