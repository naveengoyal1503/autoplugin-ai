/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Vault_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Vault Pro
 * Plugin URI: https://example.com/aicouponvault
 * Description: AI-powered coupon management with affiliate tracking for WordPress monetization.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponVault {
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
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_shortcode('ai_coupon_vault', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_style('ai-coupon-vault', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('ai-coupon-vault', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-coupon-vault', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_coupon_nonce')));
    }

    public function admin_menu() {
        add_options_page('AI Coupon Vault', 'Coupon Vault', 'manage_options', 'ai-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['save_coupons'])) {
            update_option('ai_coupon_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('ai_coupon_coupons', '[]');
        ?>
        <div class="wrap">
            <h1>AI Coupon Vault Pro</h1>
            <form method="post">
                <textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea>
                <p><input type="submit" name="save_coupons" class="button-primary" value="Save Coupons"></p>
            </form>
            <h2>Generate AI Coupon</h2>
            <input type="text" id="coupon-prompt" placeholder="e.g., Generate coupon for AI tools" style="width:300px;">
            <button id="generate-coupon" class="button">Generate</button>
            <div id="generated-coupon"></div>
            <p><strong>Pro Tip:</strong> Upgrade to Pro for unlimited AI generations and analytics. <a href="#pro">Get Pro</a></p>
        </div>
        <?php
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('ai_coupon_nonce', 'nonce');
        $prompt = sanitize_text_field($_POST['prompt']);
        // Simulate AI generation (Pro feature uses real API)
        $coupon = array(
            'code' => strtoupper(substr(md5($prompt . time()), 0, 8)),
            'description' => 'AI Generated: 20% OFF on ' . $prompt . ' - Exclusive deal!',
            'affiliate_link' => 'https://example-affiliate.com/?ref=' . substr(md5(time()), 0, 10),
            'expiry' => date('Y-m-d', strtotime('+30 days'))
        );
        wp_send_json_success($coupon);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = json_decode(get_option('ai_coupon_coupons', '[]'), true);
        if (empty($coupons)) return '';
        $coupon = $coupons[array_rand($coupons)];
        ob_start();
        ?>
        <div class="ai-coupon-vault" style="border:2px solid #007cba; padding:20px; background:#f9f9f9; border-radius:10px;">
            <h3><?php echo esc_html($coupon['title']); ?></h3>
            <p><strong>Code:</strong> <code><?php echo esc_html($coupon['code']); ?></code></p>
            <p><?php echo esc_html($coupon['description']); ?></p>
            <a href="<?php echo esc_url($coupon['affiliate_link']); ?>" target="_blank" class="button" style="background:#007cba;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;">Grab Deal Now!</a>
            <p style="font-size:12px;color:#666;">Expires: <?php echo esc_html($coupon['expiry']); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('ai_coupon_coupons', json_encode(array(
            array('title'=>'Starter Deal','code'=>'SAVE20','description'=>'20% off first purchase','affiliate_link'=>'https://affiliate.com/ref1','expiry'=>'2026-06-30'),
            array('title'=>'Pro Offer','code'=>'AI50','description'=>'50% off AI tools','affiliate_link'=>'https://affiliate.com/ref2','expiry'=>'2026-03-31')
        )));
    }
}

AICouponVault::get_instance();

// Create assets folders
add_action('init', function() {
    $css = plugin_dir_path(__FILE__) . 'style.css';
    if (!file_exists($css)) {
        file_put_contents($css, '.ai-coupon-vault { max-width: 400px; margin: 20px 0; box-shadow: 0 4px 8px rgba(0,0,0,0.1); } .ai-coupon-vault code { background: #007cba; color: white; padding: 5px 10px; border-radius: 3px; }');
    }
    $js = plugin_dir_path(__FILE__) . 'script.js';
    if (!file_exists($js)) {
        file_put_contents($js, "jQuery(document).ready(function($) { $('#generate-coupon').click(function() { var prompt = $('#coupon-prompt').val(); $.post(ajax_object.ajax_url, { action: 'generate_coupon', prompt: prompt, nonce: ajax_object.nonce }, function(res) { if(res.success) { $('#generated-coupon').html('<div class="ai-coupon-vault"><h4>New Coupon:</h4><p><strong>' + res.data.code + '</strong><br>' + res.data.description + '<br><a href="' + res.data.affiliate_link + '" target="_blank">Link</a></p><p>Pro: Unlock unlimited!</p></div>'); } }); }); });");
    }
});