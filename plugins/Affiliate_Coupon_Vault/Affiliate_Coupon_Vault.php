/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupon codes with dynamic tracking, boosting conversions for bloggers and eCommerce sites.
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
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'acv-style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['acv_save'])) {
            update_option('acv_affiliate_links', sanitize_textarea_field($_POST['affiliate_links']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $links = get_option('acv_affiliate_links', "Product 1|AFFILIATE_LINK_1|10%\nProduct 2|AFFILIATE_LINK_2|15%");
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Affiliate Links (Format: Name|Link|Discount)</th>
                        <td><textarea name="affiliate_links" rows="10" cols="50"><?php echo esc_textarea($links); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button('Save Settings', 'primary', 'acv_save'); ?>
            </form>
            <p>Use shortcode: <code>[affiliate_coupon]</code></p>
            <p><strong>Pro Upgrade:</strong> Unlimited coupons, analytics, custom designs - <a href="https://example.com/pro" target="_blank">Get Pro ($49/year)</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('product' => ''), $atts);
        ob_start();
        ?>
        <div id="acv-coupon-container" data-product="<?php echo esc_attr($atts['product']); ?>">
            <div class="acv-coupon-code">GENERATE YOUR COUPON</div>
            <button class="acv-generate-btn">Get Coupon</button>
            <div class="acv-affiliate-link" style="display:none;"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('acv_nonce', 'nonce');
        $links = get_option('acv_affiliate_links', '');
        $lines = explode("\n", $links);
        $coupons = array();
        foreach ($lines as $line) {
            $parts = explode('|', trim($line));
            if (count($parts) === 3) {
                $coupons[] = array('name' => $parts, 'link' => $parts[1], 'discount' => $parts[2]);
            }
        }
        if (empty($coupons)) {
            wp_die('No coupons configured.');
        }
        $coupon = $coupons[array_rand($coupons)];
        $unique_code = 'ACV' . wp_generate_uuid4() . substr(md5(uniqid()), 0, 4);
        wp_send_json_success(array(
            'code' => $unique_code,
            'discount' => $coupon['discount'],
            'link' => $coupon['link'] . (strpos($coupon['link'], '?') === false ? '?' : '&') . 'coupon=' . $unique_code,
            'name' => $coupon['name']
        ));
    }

    public function activate() {
        update_option('acv_affiliate_links', "WordPress Hosting|https://example.com/hosting?ref=aff|20%\nPremium Theme|https://example.com/theme?ref=aff|15%");
    }
}

// Enqueue dummy assets (in real plugin, add files)
function acv_add_dummy_assets() {
    wp_add_inline_script('jquery', 'jQuery(document).ready(function($){$(".acv-generate-btn").click(function(){var $container=$(this).closest("#acv-coupon-container");$.post(acv_ajax.ajax_url,{action:"acv_generate_coupon",nonce:acv_ajax.nonce,product:$container.data("product")},function(resp){if(resp.success){$container.find(".acv-coupon-code").text(resp.data.code+" ("+resp.data.discount+" OFF "+resp.data.name+")");$container.find(".acv-affiliate-link").html("<a href=\""+resp.data.link+"\" target=\"_blank\">Shop Now & Save</a>").show();}})});});');
    wp_add_inline_style('acv-style', '#acv-coupon-container{text-align:center;padding:20px;border:2px dashed #0073aa;background:#f9f9f9;margin:20px 0;}.acv-coupon-code{font-size:24px;color:#0073aa;font-weight:bold;margin:10px 0;}.acv-generate-btn{background:#0073aa;color:white;border:none;padding:10px 20px;cursor:pointer;font-size:16px;}.acv-generate-btn:hover{background:#005a87;}.acv-affiliate-link{margin-top:10px;font-size:18px;}');
}
add_action('wp_enqueue_scripts', 'acv_add_dummy_assets');

AffiliateCouponVault::get_instance();

// Pro upsell notice
function acv_admin_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock unlimited coupons, detailed analytics, and custom branding. <a href="https://example.com/pro" target="_blank">Upgrade Now ($49/year)</a></p></div>';
}
add_action('admin_notices', 'acv_admin_notice');