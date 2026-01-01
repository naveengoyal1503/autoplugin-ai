/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Vault_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Vault Pro
 * Plugin URI: https://example.com/aicouponvault
 * Description: AI-powered coupon management for affiliate revenue.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AICouponVault {
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_shortcode('ai_coupon_vault', [$this, 'coupon_shortcode']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_post_save_coupons', [$this, 'save_coupons']);
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', 'jQuery(document).ready(function($){ $(".coupon-btn").click(function(){ $(this).next(".coupon-code").show(); }); });');
        wp_enqueue_style('aicv-style', plugin_dir_url(__FILE__) . 'style.css', [], '1.0');
    }

    public function admin_menu() {
        add_menu_page('AI Coupon Vault', 'Coupon Vault', 'manage_options', 'ai-coupon-vault', [$this, 'admin_page']);
    }

    public function admin_page() {
        if (isset($_POST['coupons'])) {
            update_option('ai_coupon_vault_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('ai_coupon_vault_coupons', '{"coupons":[]}');
        ?>
        <div class="wrap">
            <h1>AI Coupon Vault Settings</h1>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="save_coupons">
                <?php wp_nonce_field('save_coupons'); ?>
                <textarea name="coupons" rows="20" cols="80" style="width:100%;" placeholder='[{"code":"SAVE20","desc":"20% off","afflink":"https://aff.link","expiry":"2026-12-31"}]'><?php echo esc_textarea($coupons); ?></textarea>
                <p class="description">JSON format: {"code":"CODE","desc":"Description","afflink":"Affiliate URL","expiry":"YYYY-MM-DD"}</p>
                <p><input type="submit" class="button-primary" value="Save Coupons"></p>
            </form>
            <h2>Shortcode</h2>
            <p>Use <code>[ai_coupon_vault]</code> to display coupons.</p>
            <?php if (!function_exists('OpenAI')) { ?>
            <div class="notice notice-warning">
                <p>Premium: Integrate OpenAI for auto-generation (API key required).</p>
            </div><?php } ?>
        </div>
        <?php
    }

    public function save_coupons() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'save_coupons')) wp_die('Security check failed');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        update_option('ai_coupon_vault_coupons', sanitize_text_field($_POST['coupons']));
        wp_redirect(admin_url('admin.php?page=ai-coupon-vault'));
        exit;
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(['limit' => 5], $atts);
        $coupons_json = get_option('ai_coupon_vault_coupons', '{"coupons":[]}');
        $data = json_decode($coupons_json, true);
        $coupons = isset($data['coupons']) ? array_slice($data['coupons'], 0, $atts['limit']) : [];
        $output = '<div class="ai-coupon-vault">';
        foreach ($coupons as $coupon) {
            if (isset($coupon['expiry']) && strtotime($coupon['expiry']) < time()) continue;
            $output .= '<div class="coupon-item">';
            $output .= '<h3>' . esc_html($coupon['desc']) . '</h3>';
            $output .= '<button class="coupon-btn button">Reveal Code</button>';
            $output .= '<div class="coupon-code" style="display:none;"><strong>' . esc_html($coupon['code']) . '</strong> <a href="' . esc_url($coupon['afflink']) . '" target="_blank" rel="nofollow">Shop Now (Affiliate)</a></div>';
            $output .= '</div>';
        }
        $output .= '</div>';
        return $output;
    }

    public function activate() {
        add_option('ai_coupon_vault_coupons', '{"coupons":[{"code":"WELCOME10","desc":"10% off first purchase","afflink":"https://exampleaff.com","expiry":"2026-12-31"}]}');
    }
}

new AICouponVault();

// Premium teaser
function aicv_pro_teaser() {
    if (!is_super_admin()) return;
    echo '<div class="notice notice-info"><p><strong>AI Coupon Vault Pro:</strong> Unlock AI generation, analytics & more! <a href="https://example.com/pro" target="_blank">Upgrade</a></p></div>';
}
add_action('admin_notices', 'aicv_pro_teaser');

// Inline CSS
add_action('wp_head', function() {
    echo '<style>.ai-coupon-vault .coupon-item {border:1px solid #ddd; padding:15px; margin:10px 0; border-radius:5px;}.coupon-btn {background:#0073aa; color:white; border:none; padding:10px 20px; cursor:pointer;}.coupon-code {margin-top:10px;}</style>';
});