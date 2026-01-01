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
        add_action('wp_ajax_save_coupon', array($this, 'ajax_save_coupon'));
        add_action('wp_ajax_nopriv_save_coupon', array($this, 'ajax_save_coupon'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'acv-style.css', array(), '1.0.0');
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['acv_save'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', '[]');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <textarea name="coupons" rows="20" cols="80" placeholder='[{"name":"10% Off","code":"AFF10","affiliate_link":"https://example.com","description":"Exclusive 10% discount for readers"}]'><?php echo esc_textarea($coupons); ?></textarea>
                <p class="description">Enter JSON array of coupons: {"name":"Name","code":"CODE","affiliate_link":"URL","description":"Desc"}</p>
                <p><input type="submit" name="acv_save" class="button-primary" value="Save Coupons"></p>
            </form>
            <p><strong>Shortcode:</strong> <code>[affiliate_coupon id="1"]</code> or use widget/block.</p>
            <p><em>Pro: Unlimited coupons, analytics, auto-expiration.</em></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = json_decode(get_option('acv_coupons', '[]'), true);
        if (!isset($coupons[$atts['id']])) {
            return '<p>No coupon found.</p>';
        }
        $coupon = $coupons[$atts['id']];
        $personalized_code = $coupon['code'] . '-' . substr(md5(auth()->user_id ?? 'guest'), 0, 4);
        ob_start();
        ?>
        <div class="acv-coupon">
            <h3><?php echo esc_html($coupon['name']); ?></h3>
            <p><?php echo esc_html($coupon['description']); ?></p>
            <div class="acv-code">Promo Code: <strong><?php echo esc_html($personalized_code); ?></strong></div>
            <a href="<?php echo esc_url($coupon['affiliate_link'] . '?coupon=' . urlencode($personalized_code)); ?>" class="acv-button" target="_blank">Get Deal (Affiliate Link)</a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_save_coupon() {
        // Pro feature placeholder
        wp_die();
    }
}

AffiliateCouponVault::get_instance();

// Inline styles and scripts
add_action('wp_head', function() { ?>
<style>
.acv-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; border-radius: 8px; }
.acv-code { font-size: 1.2em; color: #d63638; margin: 10px 0; }
.acv-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; }
.acv-button:hover { background: #005a87; }
</style>
<?php });

// Minimal JS
add_action('wp_footer', function() { ?>
<script>jQuery(document).ready(function($) { /* Pro analytics placeholder */ });</script>
<?php } );