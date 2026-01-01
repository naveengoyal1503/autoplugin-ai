/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Generator_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Generator Pro
 * Plugin URI: https://example.com/ai-coupon-generator
 * Description: AI-powered coupon generator for affiliate marketing and sales boost.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponGenerator {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('ai_coupon_generator', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_generate_coupon', array($this, 'ajax_generate_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'ai-coupon.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-coupon-js', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('AI Coupon Settings', 'AI Coupons', 'manage_options', 'ai-coupons', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('ai_coupon_affiliate_links', sanitize_textarea_field($_POST['affiliate_links']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $links = get_option('ai_coupon_affiliate_links', "Amazon: https://amazon.com/deal\nHostinger: https://hostinger.com/coupon");
        echo '<div class="wrap"><h1>AI Coupon Generator Settings</h1><form method="post"><p><label>Affiliate Links (one per line: Brand: URL)</label><textarea name="affiliate_links" rows="10" cols="50">' . esc_textarea($links) . '</textarea></p><p class="submit"><input type="submit" name="submit" class="button-primary" value="Save Settings"></p></form></div>';
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('brands' => ''), $atts);
        ob_start();
        ?>
        <div id="ai-coupon-container">
            <h3>Get Your Personal Coupon!</h3>
            <input type="text" id="visitor-email" placeholder="Enter your email">
            <button id="generate-coupon">Generate Coupon</button>
            <div id="coupon-result"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        $email = sanitize_email($_POST['email']);
        if (!is_email($email)) {
            wp_die('Invalid email');
        }

        $links = get_option('ai_coupon_affiliate_links', "Amazon: https://amazon.com/deal\nHostinger: https://hostinger.com/coupon");
        $link_array = explode("\n", $links);
        $selected = $link_array[array_rand($link_array)];
        list($brand, $url) = explode(':', $selected, 2);
        $brand = trim($brand);
        $url = trim($url);

        $code = 'SAVE' . wp_generate_password(6, false) . rand(100,999);
        $discount = rand(10,50) . '% OFF';
        $expires = date('Y-m-d', strtotime('+7 days'));

        // Simulate AI generation (premium could integrate real AI API)
        $response = array(
            'success' => true,
            'brand' => $brand,
            'code' => $code,
            'discount' => $discount,
            'url' => $url,
            'expires' => $expires,
            'message' => "<strong>{$discount}</strong> on {$brand}! Use code: <strong>{$code}</strong> (Expires: {$expires}) <a href='{$url}' target='_blank'>Shop Now (Affiliate)</a>"
        );

        // Log for analytics (premium feature)
        error_log("Coupon generated for: " . $email);

        wp_send_json($response);
    }

    public function activate() {
        update_option('ai_coupon_affiliate_links', "Amazon: https://amazon.com/deal\nHostinger: https://hostinger.com/coupon");
    }
}

new AICouponGenerator();

// Inline JS for simplicity (self-contained)
function ai_coupon_inline_js() {
    if (has_shortcode(get_post()->post_content, 'ai_coupon_generator')) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('#generate-coupon').click(function() {
                var email = $('#visitor-email').val();
                if (!email) return alert('Enter email');
                $('#coupon-result').html('<p>Generating...</p>');
                $.post(ajax_object.ajax_url, {action: 'generate_coupon', email: email}, function(res) {
                    if (res.success) {
                        $('#coupon-result').html(res.message);
                    }
                });
            });
        });
        </script>
        <?php
    }
}
add_action('wp_footer', 'ai_coupon_inline_js');

?>