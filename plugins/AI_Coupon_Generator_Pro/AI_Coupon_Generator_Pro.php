/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Generator_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Generator Pro
 * Plugin URI: https://example.com/ai-coupon-generator
 * Description: Automatically generates unique, personalized coupon codes for your WordPress site visitors using AI, boosting affiliate conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-generator
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AICouponGenerator {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_shortcode('ai_coupon_generator', array($this, 'coupon_shortcode'));
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

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate_link' => '',
            'brand' => 'Brand',
            'discount' => '10%',
        ), $atts);

        ob_start();
        ?>
        <div id="ai-coupon-container">
            <h3>Get Your Personal <?php echo esc_html($atts['brand']); ?> Coupon!</h3>
            <p>Enter your email for an exclusive <strong><?php echo esc_html($atts['discount']); ?> OFF</strong> code.</p>
            <input type="email" id="coupon-email" placeholder="your@email.com" required>
            <button id="generate-coupon">Generate Coupon</button>
            <div id="coupon-result"></div>
            <input type="hidden" id="affiliate-link" value="<?php echo esc_url($atts['affiliate_link']); ?>">
            <input type="hidden" id="brand-name" value="<?php echo esc_attr($atts['brand']); ?>">
            <input type="hidden" id="discount-value" value="<?php echo esc_attr($atts['discount']); ?>">
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        if (!wp_verify_nonce($_POST['nonce'], 'ai_coupon_nonce')) {
            wp_die('Security check failed');
        }

        $email = sanitize_email($_POST['email']);
        if (!is_email($email)) {
            wp_send_json_error('Invalid email');
        }

        // Simple AI-like unique code generation (hash-based for uniqueness)
        $unique_id = hash('sha256', $email . time() . rand(1000, 9999));
        $coupon_code = strtoupper(substr($unique_id, 0, 8)) . '-' . $atts['discount'];

        // Pro feature check (simulate)
        $is_pro = get_option('ai_coupon_pro', false);
        if (!$is_pro && wp_rand(1, 3) === 1) {
            wp_send_json_error('Upgrade to Pro for unlimited coupons!');
        }

        // Store coupon (simple option for demo)
        $coupons = get_option('ai_coupons', array());
        $coupons[] = array(
            'code' => $coupon_code,
            'email' => $email,
            'time' => current_time('mysql'),
            'used' => false
        );
        update_option('ai_coupons', $coupons);

        $affiliate_link = sanitize_url($_POST['affiliate_link']);
        $link = add_query_arg('coupon', $coupon_code, $affiliate_link);

        ob_start();
        ?>
        <div class="coupon-generated">
            <strong>Your Coupon: <?php echo esc_html($coupon_code); ?></strong><br>
            <a href="<?php echo esc_url($link); ?>" target="_blank">Redeem Now (Affiliate Link)</a>
        </div>
        <?php
        wp_send_json_success(ob_get_clean());
    }

    public function admin_menu() {
        add_options_page('AI Coupon Settings', 'AI Coupons', 'manage_options', 'ai-coupon', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['pro_license'])) {
            update_option('ai_coupon_pro', true);
            echo '<div class="notice notice-success"><p>Pro activated!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Coupon Generator Settings</h1>
            <form method="post">
                <?php wp_nonce_field('ai_coupon_settings'); ?>
                <p><input type="submit" name="pro_license" class="button-primary" value="Activate Pro (Demo)"></p>
            </form>
            <h2>Generated Coupons</h2>
            <pre><?php print_r(get_option('ai_coupons', array())); ?></pre>
        </div>
        <?php
    }

    public function activate() {
        // Create assets dir if needed
        $upload_dir = plugin_dir_path(__FILE__) . 'assets/';
        if (!file_exists($upload_dir)) {
            wp_mkdir_p($upload_dir);
        }
    }
}

new AICouponGenerator();

// Dummy JS file content (in reality, create ai-coupon.js)
/*
Create a file assets/ai-coupon.js with:

jQuery(document).ready(function($) {
    $('#generate-coupon').click(function() {
        var email = $('#coupon-email').val();
        var affiliate = $('#affiliate-link').val();
        var brand = $('#brand-name').val();
        var discount = $('#discount-value').val();
        $.post(ajax_object.ajax_url, {
            action: 'generate_coupon',
            email: email,
            affiliate_link: affiliate,
            nonce: '<?php echo wp_create_nonce("ai_coupon_nonce"); ?>'
        }, function(response) {
            $('#coupon-result').html(response.data);
        });
    });
});
*/
?>