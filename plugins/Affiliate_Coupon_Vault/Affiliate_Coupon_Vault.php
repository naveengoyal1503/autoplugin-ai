/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with tracking, boosting conversions and commissions.
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
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('acv_pro_version')) {
            // Pro features
        }
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate' => '',
            'product' => '',
            'discount' => '10%',
            'code' => ''
        ), $atts);

        $coupon_code = $atts['code'] ?: 'ACV-' . wp_generate_uuid4();
        $link = $atts['affiliate'] ? $atts['affiliate'] . '?coupon=' . $coupon_code : '#';

        ob_start();
        ?>
        <div class="acv-coupon" style="border: 2px dashed #007cba; padding: 20px; text-align: center; background: #f9f9f9;">
            <h3>Exclusive Deal: <strong><?php echo esc_html($atts['product']); ?></strong></h3>
            <p>Save <strong><?php echo esc_html($atts['discount']); ?></strong> with code: <span style="background: #007cba; color: white; padding: 5px 10px; font-size: 1.2em; font-weight: bold;"><?php echo esc_html($coupon_code); ?></span></p>
            <a href="<?php echo esc_url($link); ?}" target="_blank" class="button" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Grab Deal Now (Affiliate Link)</a>
            <p style="font-size: 0.9em; margin-top: 10px;">Tracked clicks: <span id="acv-clicks-<?php echo esc_attr($coupon_code); ?>">0</span></p>
            <button id="acv-gen-<?php echo esc_attr($coupon_code); ?>" class="button" style="background: #28a745; color: white; margin-top: 10px;">Generate New Coupon</button>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('acv_nonce', 'nonce');
        $product = sanitize_text_field($_POST['product']);
        $discount = sanitize_text_field($_POST['discount']);
        $coupon_code = 'ACV-' . wp_generate_uuid4();
        wp_send_json_success(array('code' => $coupon_code, 'product' => $product, 'discount' => $discount));
    }

    public function activate() {
        add_option('acv_installed', time());
        flush_rewrite_rules();
    }
}

new AffiliateCouponVault();

// Pro upsell notice
function acv_pro_notice() {
    if (!get_option('acv_pro_version') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Affiliate Coupon Vault Pro</strong> for unlimited coupons, advanced analytics, and affiliate network integrations! <a href="https://example.com/pro" target="_blank">Get Pro ($49/year)</a></p></div>';
    }
}
add_action('admin_notices', 'acv_pro_notice');

// Minified JS (self-contained)
function acv_inline_script() {
    ?>
    <script>jQuery(document).ready(function($){$('.acv-coupon .button').on('click',function(e){e.preventDefault();var btn=$(this),id=btn.closest('.acv-coupon').find('span[id^="acv-clicks-"]').attr('id');var clicks=parseInt($('#'+id).text());$('#'+id).text(clicks+1);window.open(btn.attr('href'),'_blank');});$('button[id^="acv-gen-"]').on('click',function(){var btn=$(this),code=btn.attr('id').replace('acv-gen-','');$.post(acv_ajax.ajax_url,{action:'acv_generate_coupon',nonce:acv_ajax.nonce,product:'Exclusive Deal',discount:'15%'},function(resp){if(resp.success){btn.prev('span').text(resp.data.code);}})});});</script>
    <?php
}
add_action('wp_footer', 'acv_inline_script');