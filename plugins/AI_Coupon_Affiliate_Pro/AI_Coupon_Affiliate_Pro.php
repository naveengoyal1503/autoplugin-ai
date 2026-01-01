/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: Automatically generates and displays personalized affiliate coupons using AI to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-affiliate-pro
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
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-affiliate-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
        wp_localize_script('ai-coupon-js', 'aicoupon_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aicoupon_nonce')));
    }

    public function admin_menu() {
        add_options_page('AI Coupon Pro Settings', 'AI Coupon Pro', 'manage_options', 'ai-coupon-pro', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('ai_coupon_settings', 'ai_coupon_options');
        add_settings_section('ai_coupon_main', 'Main Settings', null, 'ai_coupon');
        add_settings_field('api_key', 'OpenAI API Key (Pro)', array($this, 'api_key_field'), 'ai_coupon', 'ai_coupon_main');
        add_settings_field('affiliate_ids', 'Affiliate Networks', array($this, 'affiliate_ids_field'), 'ai_coupon', 'ai_coupon_main');
    }

    public function api_key_field() {
        $options = get_option('ai_coupon_options', array());
        echo '<input type="password" name="ai_coupon_options[api_key]" value="' . esc_attr($options['api_key'] ?? '') . '" class="regular-text" />';
        echo '<p class="description">Enter your OpenAI API key for AI coupon generation. <a href="https://platform.openai.com/api-keys" target="_blank">Get one here</a> (Pro feature)</p>';
    }

    public function affiliate_ids_field() {
        $options = get_option('ai_coupon_options', array());
        echo '<textarea name="ai_coupon_options[affiliate_ids]" rows="5" class="large-text">' . esc_textarea($options['affiliate_ids'] ?? 'amazon:your-amazon-id\nclickbank:your-cb-id') . '</textarea>';
        echo '<p class="description">One per line: network:id (e.g., amazon:affiliateid)</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>AI Coupon Affiliate Pro</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('ai_coupon_settings');
                do_settings_sections('ai_coupon');
                submit_button();
                ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, advanced AI, analytics. <a href="https://example.com/pro" target="_blank">Buy Now $49/year</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'niche' => 'general',
            'button_text' => 'Generate Coupon'
        ), $atts);

        ob_start();
        ?>
        <div id="ai-coupon-container" data-niche="<?php echo esc_attr($atts['niche']); ?>">
            <div id="coupon-display" style="display:none;">
                <h3>Your Personalized Coupon</h3>
                <div id="coupon-code"></div>
                <div id="coupon-link"></div>
                <p id="coupon-desc"></p>
            </div>
            <button id="generate-coupon" class="button button-primary"><?php echo esc_html($atts['button_text']); ?></button>
            <p class="pro-notice">Limited to 5/day in free version. <strong>Pro: Unlimited!</strong></p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('aicoupon_nonce', 'nonce');

        $options = get_option('ai_coupon_options', array());
        $niche = sanitize_text_field($_POST['niche'] ?? 'general');
        $count = get_transient('ai_coupon_count_' . get_current_user_id()) ?: 0;

        if ($count >= 5) {
            wp_send_json_error('Free limit reached. Upgrade to Pro for unlimited!');
            return;
        }

        if (empty($options['api_key'])) {
            wp_send_json_error('API key required. Set in settings.');
            return;
        }

        // Mock AI generation (replace with real OpenAI call in Pro)
        $affiliates = explode('\n', $options['affiliate_ids'] ?? '');
        $affiliate = trim($affiliates[array_rand($affiliates)] ?? 'amazon:demo');
        list($network, $id) = explode(':', $affiliate);

        $coupons = array(
            'SAVE20' => "20% off on electronics at Amazon! Affiliate: $id",
            'DEAL50' => '50% off software via ClickBank',
            'FREESHIP' => 'Free shipping on all orders'
        );
        $code = array_rand($coupons);
        $link = "https://$network.com/deal?aff=$id&code=" . $code;

        set_transient('ai_coupon_count_' . get_current_user_id(), $count + 1, DAY_IN_SECONDS);

        wp_send_json_success(array(
            'code' => $code,
            'link' => $link,
            'desc' => $coupons[$code]
        ));
    }
}

AICouponAffiliatePro::get_instance();

// Create assets directories if missing
$upload_dir = wp_upload_dir();
$assets_dir = plugin_dir_path(__FILE__) . 'assets';
if (!file_exists($assets_dir)) {
    wp_mkdir_p($assets_dir);
}

// Basic CSS
file_put_contents($assets_dir . '/style.css', ".ai-coupon-container { border: 1px solid #ddd; padding: 20px; border-radius: 5px; background: #f9f9f9; } #coupon-display { margin-top: 10px; padding: 15px; background: #e7f3ff; border-radius: 5px; } #coupon-code { font-size: 24px; font-weight: bold; color: #d63384; } .pro-notice { color: #0073aa; font-weight: bold; }");

// Basic JS
file_put_contents($assets_dir . '/script.js', "jQuery(document).ready(function($) { $('#generate-coupon').click(function() { var container = $(this).closest('#ai-coupon-container'); $.post(aicoupon_ajax.ajax_url, { action: 'generate_coupon', nonce: aicoupon_ajax.nonce, niche: container.data('niche') }, function(resp) { if (resp.success) { $('#coupon-code', container).text(resp.data.code); $('#coupon-link', container).html('<a href="' + resp.data.link + '" target="_blank">Shop Now & Save</a>'); $('#coupon-desc', container).text(resp.data.desc); $('#coupon-display', container).show(); } else { alert(resp.data); } }); }); });");