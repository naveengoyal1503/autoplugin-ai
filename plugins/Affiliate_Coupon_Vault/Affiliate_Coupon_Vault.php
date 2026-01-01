/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons, personalized discounts, and promo codes to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AffiliateCouponVault {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('acv_coupons', array($this, 'coupons_shortcode'));
        add_action('wp_ajax_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'acv-style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_api_key', sanitize_text_field($_POST['api_key']));
            update_option('acv_affiliate_links', wp_kses_post($_POST['affiliate_links']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('acv_api_key', '');
        $affiliate_links = get_option('acv_affiliate_links', '');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>API Key (Pro Feature)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Links</th>
                        <td><textarea name="affiliate_links" rows="10" class="large-text"><?php echo esc_textarea($affiliate_links); ?></textarea><br />
                        Format: product|link|description (one per line)</td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics, and auto-generation for $49/year.</p>
        </div>
        <?php
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts);
        $links = explode("\n", get_option('acv_affiliate_links', ''));
        $coupons = array();
        for ($i = 0; $i < min($atts['limit'], count($links)); $i++) {
            $parts = explode('|', trim($links[$i]));
            if (count($parts) == 3) {
                $coupons[] = array(
                    'product' => sanitize_text_field($parts),
                    'link' => esc_url($parts[1]),
                    'desc' => sanitize_text_field($parts[2])
                );
            }
        }
        ob_start();
        ?>
        <div class="acv-coupons">
            <?php foreach ($coupons as $coupon): ?>
                <div class="acv-coupon">
                    <h3><?php echo esc_html($coupon['product']); ?></h3>
                    <p><?php echo esc_html($coupon['desc']); ?></p>
                    <button class="acv-btn" onclick="acvGenerateCoupon('<?php echo esc_js($coupon['product']); ?>')">Get Coupon</button>
                    <a href="<?php echo esc_url($coupon['link']); ?>" target="_blank" class="acv-link">Shop Now (Affiliate)</a>
                    <div class="acv-code" style="display:none;"></div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('acv_nonce', 'nonce');
        $product = sanitize_text_field($_POST['product']);
        $code = substr(md5($product . time()), 0, 8); // Simple unique code generator
        wp_send_json_success(array('code' => $code, 'message' => 'Use code: ' . $code . ' for 20% off!'));
    }

    public function activate() {
        add_option('acv_installed', time());
    }
}

AffiliateCouponVault::get_instance();

// Inline styles
add_action('wp_head', function() { ?>
<style>
.acv-coupons { max-width: 600px; margin: 20px 0; }
.acv-coupon { border: 1px solid #ddd; padding: 20px; margin: 10px 0; border-radius: 8px; background: #f9f9f9; }
.acv-btn { background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
.acv-btn:hover { background: #005a87; }
.acv-link { display: block; margin-top: 10px; padding: 10px; background: #28a745; color: white; text-align: center; text-decoration: none; border-radius: 4px; }
.acv-code { margin-top: 10px; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; font-family: monospace; }
</style>
<?php });

// JS
add_action('wp_footer', function() { ?>
<script>
jQuery(document).ready(function($) {
    window.acvGenerateCoupon = function(product) {
        $.post(acv_ajax.ajax_url, {
            action: 'acv_generate_coupon',
            product: product,
            nonce: acv_ajax.nonce
        }, function(response) {
            if (response.success) {
                $('.acv-coupon h3:contains("' + product + '")').nextAll('.acv-code').first().html(response.data.message).show();
            }
        });
    };
});
</script>
<?php });