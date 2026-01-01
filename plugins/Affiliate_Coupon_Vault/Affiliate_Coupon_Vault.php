/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with personalized promo codes to boost conversions and commissions.
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
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons updated!</p></div>';
        }
        $coupons = get_option('acv_coupons', "Brand1|50%OFF|https://affiliate.link1\nBrand2|DEAL20|https://affiliate.link2");
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <p><label>Enter coupons (format: Brand|Code|Affiliate Link, one per line):</label></p>
                <textarea name="coupons" rows="10" cols="80"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="submit"><input type="submit" name="submit" class="button-primary" value="Save Coupons"></p>
            </form>
            <p>Use shortcode: <code>[affiliate_coupon]</code> or PRO: Gutenberg block, widgets.</p>
            <p><strong>Upgrade to PRO</strong> for unlimited coupons, analytics, auto-expiration, custom designs ($49/year).</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = explode("\n", get_option('acv_coupons', ''));
        $coupon = '';
        if (!empty($coupons)) {
            $random_coupon = $coupons[array_rand($coupons)];
            list($brand, $code, $link) = explode('|', trim($random_coupon));
            $personalized = $code . '-' . substr(md5(auth()->user_id ?? 'guest'), 0, 4);
            $coupon = '<div class="acv-coupon"><h3>Exclusive: ' . esc_html($brand) . ' - ' . esc_html($personalized) . '</h3><p>Save big! <a href="' . esc_url($link) . '" target="_blank" rel="nofollow">Get Deal Now</a></p></div>';
        }
        return $coupon ?: '<p>No coupons available. <a href="' . admin_url('options-general.php?page=affiliate-coupon-vault') . '">Add some in settings</a>.</p>';
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', "Brand1|50%OFF|https://affiliate.link1\nBrand2|DEAL20|https://affiliate.link2");
        }
    }
}

AffiliateCouponVault::get_instance();

/* PRO Teaser */
function acv_pro_teaser() {
    if (!defined('ACV_PRO_VERSION')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-info"><p>Affiliate Coupon Vault PRO: Unlimited coupons, analytics & more! <a href="https://example.com/pro" target="_blank">Upgrade ($49)</a></p></div>';
        });
    }
}
add_action('plugins_loaded', 'acv_pro_teaser');

/* Inline CSS/JS for self-contained */
function acv_inline_assets() {
    ?>
    <style>
    .acv-coupon { background: #fff3cd; border: 2px dashed #ffc107; padding: 20px; border-radius: 10px; text-align: center; margin: 20px 0; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    .acv-coupon h3 { color: #856404; margin: 0 0 10px; }
    .acv-coupon a { background: #ffc107; color: #212529; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block; transition: background 0.3s; }
    .acv-coupon a:hover { background: #e0a800; }
    </style>
    <script>jQuery(document).ready(function($){ $('.acv-coupon a').click(function(){ /* Track clicks for free version */ gtag?.('event', 'coupon_click'); }); });</script>
    <?php
}
add_action('wp_head', 'acv_inline_assets');