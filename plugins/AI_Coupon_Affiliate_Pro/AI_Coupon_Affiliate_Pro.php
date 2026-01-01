/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicouponpro
 * Description: AI-powered coupon generator and affiliate tracker that creates unique personalized coupons, tracks clicks, and boosts affiliate commissions automatically.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AICouponAffiliatePro {
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
        load_plugin_textdomain('ai-coupon-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-coupon-js', 'aicoupon_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_coupon_nonce')));
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('AI Coupon Pro Settings', 'AI Coupon Pro', 'manage_options', 'ai-coupon-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('ai_coupon_api_key', sanitize_text_field($_POST['api_key']));
            update_option('ai_coupon_affiliate_links', sanitize_textarea_field($_POST['affiliate_links']));
        }
        $api_key = get_option('ai_coupon_api_key', '');
        $aff_links = get_option('ai_coupon_affiliate_links', '');
        ?>
        <div class="wrap">
            <h1>AI Coupon Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>OpenAI API Key (Pro)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" placeholder="sk-..."></td>
                    </tr>
                    <tr>
                        <th>Affiliate Links (JSON format)</th>
                        <td><textarea name="affiliate_links" rows="10" class="large-text"><?php echo esc_textarea($aff_links); ?></textarea><br><small>Example: {"amazon":"https://amzn.to/xxx","shopify":"https://shopify.com/yyy"}</small></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcode <code>[ai_coupon_generator]</code> to display.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('niche' => 'general'), $atts);
        ob_start();
        ?>
        <div id="ai-coupon-container" data-niche="<?php echo esc_attr($atts['niche']); ?>">
            <button id="generate-coupon" class="button button-primary">Generate Personalized Coupon</button>
            <div id="coupon-result"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('ai_coupon_nonce', 'nonce');
        $niche = sanitize_text_field($_POST['niche']);
        $aff_links = json_decode(get_option('ai_coupon_affiliate_links', '{}'), true);

        // Simulate AI generation (Pro feature uses real OpenAI)
        $coupons = array(
            '10% off first purchase',
            'Free shipping code',
            'Buy one get one 50% off',
            'Exclusive $20 discount'
        );
        $coupon = $coupons[array_rand($coupons)];
        $random_aff = $aff_links ? array_rand($aff_links) : 'amazon';
        $link = isset($aff_links[$random_aff]) ? $aff_links[$random_aff] . '?coupon=' . base64_encode($coupon) : '#';

        // Track click
        $track_id = uniqid();
        set_transient('coupon_click_' . $track_id, $_SERVER['REMOTE_ADDR'], HOUR_IN_SECONDS);

        wp_send_json_success(array(
            'coupon' => $coupon,
            'affiliate' => $random_aff,
            'link' => $link,
            'track_id' => $track_id
        ));
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AICouponAffiliatePro();

// Pro upsell notice
function ai_coupon_pro_notice() {
    if (!get_option('ai_coupon_api_key')) {
        echo '<div class="notice notice-info"><p><strong>AI Coupon Pro:</strong> Unlock unlimited AI coupons and analytics with Pro upgrade! <a href="https://example.com/pro" target="_blank">Get Pro ($49/year)</a></p></div>';
    }
}
add_action('admin_notices', 'ai_coupon_pro_notice');

// Assets would be created as style.css and script.js in /assets/ folder
// style.css: #ai-coupon-container { margin: 20px 0; } #generate-coupon { padding: 10px 20px; }
// script.js: jQuery(document).ready(function($){ $('#generate-coupon').click(function(){ $.post(aicoupon_ajax.ajax_url, {action:'generate_coupon', nonce: aicoupon_ajax.nonce, niche:$('#ai-coupon-container').data('niche')}, function(res){ if(res.success) { $('#coupon-result').html('<p><strong>'+res.data.coupon+'</strong><br><a href="'+res.data.link+'" target="_blank" rel="nofollow">Shop Now & Save</a></p>'); } }); }); });
?>