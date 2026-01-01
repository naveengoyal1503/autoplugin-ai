/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupon codes with tracking, boosting conversions for bloggers and eCommerce sites.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateCouponVault {
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
        add_action('wp_ajax_save_coupon', array($this, 'save_coupon'));
        add_action('wp_ajax_nopriv_save_coupon', array($this, 'save_coupon'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'acv-style.css', array(), '1.0.0');
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['acv_save'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', "Brand1:10OFF|Brand2:FREEDEL");
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <p><label>Coupons (format: Brand:Code|Brand:Code):</label></p>
                <textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="submit"><input type="submit" name="acv_save" class="button-primary" value="Save Coupons"></p>
            </form>
            <p>Pro Upgrade: Unlock tracking, analytics, unlimited coupons for $49/year.</p>
        </div>
        <?php
    }

    public function save_coupon() {
        check_ajax_referer('acv_nonce', 'nonce');
        // Pro feature placeholder
        wp_die('Pro feature');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('brand' => ''), $atts);
        $coupons = explode('|', get_option('acv_coupons', ''));
        $code = '';
        foreach ($coupons as $coupon) {
            $parts = explode(':', $coupon);
            if (strtolower(trim($parts)) === strtolower($atts['brand'])) {
                $code = trim($parts[1]);
                break;
            }
        }
        if (!$code) {
            $code = 'SAVE' . rand(10, 99);
        }
        $aff_link = 'https://affiliate.com/?coupon=' . $code . '&ref=' . get_bloginfo('url');
        ob_start();
        ?>
        <div class="acv-coupon">
            <h3>Exclusive Coupon: <?php echo esc_html($atts['brand']); ?></h3>
            <p>Code: <strong><?php echo esc_html($code); ?></strong></p>
            <a href="<?php echo esc_url($aff_link); ?>" target="_blank" class="button acv-btn">Shop Now & Save</a>
            <p class="acv-tracking" style="display:none;" data-code="<?php echo esc_attr($code); ?>">Tracked</p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', "Amazon:WP10|Shopify:FREEDEL");
        }
    }
}

AffiliateCouponVault::get_instance();

// Minified JS (embedded for single file)
function acv_inline_js() {
    if (!is_admin()) {
        ?>
        <script>jQuery(document).ready(function($){$('.acv-btn').click(function(){var code=$(this).closest('.acv-coupon').find('.acv-tracking').data('code');$.post(acv_ajax.ajax_url,{action:'save_coupon',nonce:acv_ajax.nonce,code:code});});});</script>
        <style>.acv-coupon{background:#f9f9f9;padding:20px;border:2px dashed #0073aa;margin:20px 0;border-radius:5px;text-align:center;}.acv-btn{background:#0073aa;color:#fff;padding:10px 20px;text-decoration:none;display:inline-block;border-radius:3px;}.acv-btn:hover{background:#005a87;}</style>
        <?php
    }
}
add_action('wp_footer', 'acv_inline_js');

// Pro upsell notice
function acv_pro_notice() {
    if (current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock advanced tracking & more for $49/year! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
    }
}
add_action('admin_notices', 'acv_pro_notice');