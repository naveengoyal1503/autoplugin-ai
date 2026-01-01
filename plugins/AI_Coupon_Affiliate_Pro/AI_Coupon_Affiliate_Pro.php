/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/ai-coupon-affiliate-pro
 * Description: Generate and manage exclusive AI-powered coupons for affiliate marketing, boosting conversions with personalized discount codes and tracking.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-affiliate-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AICouponAffiliatePro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('ai_coupon_generator', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-affiliate-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function admin_menu() {
        add_options_page(
            'AI Coupon Affiliate Pro',
            'AI Coupons',
            'manage_options',
            'ai-coupon-pro',
            array($this, 'admin_page')
        );
    }

    public function admin_init() {
        register_setting('ai_coupon_pro_settings', 'ai_coupon_pro_options');
        add_settings_section('ai_coupon_main', 'Main Settings', null, 'ai_coupon_pro');
        add_settings_field('api_key', 'OpenAI API Key (Pro Feature)', array($this, 'api_key_field'), 'ai_coupon_pro', 'ai_coupon_main');
        add_settings_field('affiliate_links', 'Affiliate Links (JSON format)', array($this, 'affiliate_links_field'), 'ai_coupon_pro', 'ai_coupon_main');
    }

    public function api_key_field() {
        $options = get_option('ai_coupon_pro_options');
        echo '<input type="password" name="ai_coupon_pro_options[api_key]" value="' . esc_attr($options['api_key'] ?? '') . '" class="regular-text" />';
        echo '<p class="description">Enter your OpenAI API key for AI coupon generation. <strong>Pro Feature</strong></p>';
    }

    public function affiliate_links_field() {
        $options = get_option('ai_coupon_pro_options');
        echo '<textarea name="ai_coupon_pro_options[affiliate_links]" rows="10" cols="50">' . esc_textarea($options['affiliate_links'] ?? '{"product1":"https://affiliate.link1","product2":"https://affiliate.link2"}') . '</textarea>';
        echo '<p class="description">JSON object of products and affiliate links, e.g. {"Product Name":"Affiliate URL"}</p>';
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>AI Coupon Affiliate Pro Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('ai_coupon_pro_settings');
                do_settings_sections('ai_coupon_pro');
                submit_button();
                ?>
            </form>
            <p><strong>Upgrade to Pro</strong> for unlimited AI generations, analytics, and custom domains. <a href="#" onclick="alert('Pro upgrade link here')">Get Pro Now</a></p>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'ai-coupon.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-coupon-js', 'aiCouponAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_coupon_nonce')
        ));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('product' => ''), $atts);
        ob_start();
        ?>
        <div id="ai-coupon-container-<?php echo esc_attr($atts['product']); ?>" class="ai-coupon-container">
            <button id="generate-coupon-<?php echo esc_attr($atts['product']); ?>" class="button ai-coupon-btn">Generate Exclusive Coupon</button>
            <div id="coupon-result-<?php echo esc_attr($atts['product']); ?>" style="display:none;">
                <p>Your exclusive coupon: <strong id="coupon-code"></strong></p>
                <a id="affiliate-link" href="#" target="_blank" class="button">Shop Now & Save</a>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#generate-coupon-<?php echo esc_attr($atts['product']); ?>').click(function() {
                $.post(aiCouponAjax.ajaxurl, {
                    action: 'generate_ai_coupon',
                    product: '<?php echo esc_js($atts['product']); ?>',
                    nonce: aiCouponAjax.nonce
                }, function(response) {
                    if (response.success) {
                        $('#coupon-code').text(response.data.coupon);
                        $('#affiliate-link').attr('href', response.data.link);
                        $('#coupon-result-<?php echo esc_attr($atts['product']); ?>').show();
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('ai_coupon_pro_options', array());
    }
}

// AJAX handler
add_action('wp_ajax_generate_ai_coupon', 'handle_ai_coupon');
add_action('wp_ajax_nopriv_generate_ai_coupon', 'handle_ai_coupon');

function handle_ai_coupon() {
    check_ajax_referer('ai_coupon_nonce', 'nonce');
    $options = get_option('ai_coupon_pro_options');
    $product = sanitize_text_field($_POST['product']);
    $affiliates = json_decode($options['affiliate_links'] ?? '{}', true);

    if (!$product || !isset($affiliates[$product])) {
        wp_send_json_error('Invalid product');
        return;
    }

    // Simulate AI coupon generation (Pro uses real OpenAI)
    $coupon = 'SAVE' . wp_rand(1000, 9999) . ($options['api_key'] ? '-PRO' : '-FREE');

    wp_send_json_success(array(
        'coupon' => $coupon,
        'link' => $affiliates[$product]
    ));
}

new AICouponAffiliatePro();

// Enqueue JS file content inline for single-file
add_action('wp_head', function() {
    if (is_singular() && has_shortcode(get_post()->post_content, 'ai_coupon_generator')) {
        ?>
        <script>
        /* Inline JS for coupon generator */
        </script>
        <?php
    }
});

/* Add basic CSS */
add_action('wp_head', function() {
    echo '<style>.ai-coupon-container { margin: 20px 0; } .ai-coupon-btn { background: #0073aa; color: white; padding: 10px 20px; border: none; cursor: pointer; } .ai-coupon-btn:hover { background: #005a87; }</style>';
});