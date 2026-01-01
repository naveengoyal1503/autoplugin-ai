/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with custom promo codes, boosting conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_save_coupon', array($this, 'save_coupon'));
        add_action('wp_ajax_nopriv_save_coupon', array($this, 'save_coupon'));
    }

    public function init() {
        if (get_option('acv_pro_version')) {
            return;
        }
        add_action('admin_notices', array($this, 'pro_nag'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'acv.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['acv_coupon_data'])) {
            update_option('acv_coupons', sanitize_text_field($_POST['acv_coupon_data']));
            echo '<div class="notice notice-success"><p>Coupon saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', '{"coupon1":{"code":"SAVE20","desc":"20% off","link":"https://example.com","affiliate":"Amazon"}}');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault</h1>
            <p>Free version limited to 3 coupons. <a href="https://example.com/pro" target="_blank">Upgrade to Pro</a> for unlimited!</p>
            <form method="post">
                <textarea name="acv_coupon_data" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea>
                <p><input type="submit" class="button-primary" value="Save Coupons (JSON format)"></p>
            </form>
            <p>Example JSON: {"coupon1":{"code":"SAVE20","desc":"20% off","link":"https://example.com","affiliate":"Amazon"}}</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 'coupon1'), $atts);
        $coupons = json_decode(get_option('acv_coupons', '{}'), true);
        if (!isset($coupons[$atts['id']])) {
            return 'Coupon not found.';
        }
        $coupon = $coupons[$atts['id']];
        $pro = get_option('acv_pro_version');
        $limit = $pro ? 'Unlimited' : '(Free: 3 max)';
        return '<div class="acv-coupon"><h3>Exclusive Deal: ' . esc_html($coupon['code']) . '</h3><p>' . esc_html($coupon['desc']) . ' ' . $limit . '</p><a href="' . esc_url($coupon['link']) . '" class="acv-button" target="_blank" rel="nofollow">Get Deal (Aff: ' . esc_html($coupon['affiliate']) . ')</a></div>';
    }

    public function save_coupon() {
        if (!wp_verify_nonce($_POST['nonce'], 'acv_nonce')) {
            wp_die('Security check failed');
        }
        $data = sanitize_text_field($_POST['data']);
        $coupons = json_decode(get_option('acv_coupons', '{}'), true);
        $coupons[$_POST['id']] = json_decode($data, true);
        if (count($coupons) > 3 && !get_option('acv_pro_version')) {
            wp_send_json_error('Upgrade to Pro for more coupons');
        }
        update_option('acv_coupons', json_encode($coupons));
        wp_send_json_success('Saved');
    }

    public function pro_nag() {
        if (!current_user_can('manage_options')) return;
        echo '<div class="notice notice-info"><p>Affiliate Coupon Vault Pro: Unlock unlimited coupons! <a href="https://example.com/pro" target="_blank">Get Pro</a></p></div>';
    }
}

new AffiliateCouponVault();

// Dummy JS/CSS - in real plugin, add files
$js = "jQuery(document).ready(function($){ $('.acv-add').click(function(){ $.post(ajaxurl, {action:'save_coupon', data:JSON.stringify({code:'NEW10',desc:'New deal'}), id:'new1', nonce:acv_ajax.nonce}, function(r){alert(r.data);}); }); });";
file_put_contents(plugin_dir_path(__FILE__) . 'acv.js', $js);
$css = ".acv-coupon { border:2px solid #007cba; padding:20px; margin:10px 0; background:#f9f9f9; } .acv-button { background:#007cba; color:white; padding:10px 20px; text-decoration:none; display:inline-block; }";
file_put_contents(plugin_dir_path(__FILE__) . 'acv.css', $css);

// AJAX
wp_localize_script('acv-script', 'acv_ajax', array('nonce' => wp_create_nonce('acv_nonce')));