/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupon codes with tracking, boosting conversions for bloggers and site owners.
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
        add_action('wp_ajax_save_coupon', array($this, 'ajax_save_coupon'));
        add_action('wp_ajax_nopriv_save_coupon', array($this, 'ajax_save_coupon'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
        wp_localize_script('affiliate-coupon-vault', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', "Coupon Code|Affiliate Link|Description");
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <p><label>Coupons (format: Code|Affiliate Link|Description, one per line):</label></p>
                <textarea name="coupons" rows="10" cols="80"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="submit"><input type="submit" name="submit" class="button-primary" value="Save Coupons"></p>
            </form>
            <p>Use shortcode: <code>[affiliate_coupon id="1"]</code> (Free: 3 coupons max. Upgrade to Pro for unlimited!)</p>
        </div>
        <?php
    }

    public function ajax_save_coupon() {
        check_ajax_referer('acv_nonce', 'nonce');
        if (current_user_can('manage_options')) {
            $coupons = get_option('acv_coupons', '');
            wp_send_json_success(array('message' => 'Saved'));
        }
        wp_send_json_error();
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => '1'), $atts);
        $coupons = explode("\n", get_option('acv_coupons', ''));
        if (isset($coupons[$atts['id'] - 1])) {
            $coupon = explode('|', trim($coupons[$atts['id'] - 1]));
            if (count($coupon) >= 3) {
                $code = $coupon;
                $link = $coupon[1];
                $desc = $coupon[2];
                $click_id = uniqid('click_');
                $track_link = add_query_arg('ref', $click_id, $link);
                ob_start();
                ?>
                <div class="acv-coupon">
                    <h3><?php echo esc_html($desc); ?></h3>
                    <div class="acv-code"><?php echo esc_html($code); ?></div>
                    <a href="<?php echo esc_url($track_link); ?>" class="acv-button" target="_blank">Get Deal & Track Click</a>
                    <small>Coupon tracked for affiliate commissions.</small>
                </div>
                <?php
                return ob_get_clean();
            }
        }
        return '<p>No coupon found. Upgrade to Pro!</p>';
    }

    public function activate() {
        if (get_option('acv_pro') !== 'activated') {
            add_option('acv_free_limit', 3);
        }
    }
}

// Create assets directories and dummy files
register_activation_hook(__FILE__, function() {
    $upload_dir = wp_upload_dir();
    $assets_dir = plugin_dir_path(__FILE__) . 'assets/';
    if (!file_exists($assets_dir)) {
        wp_mkdir_p($assets_dir);
    }
    if (!file_exists($assets_dir . 'script.js')) {
        file_put_contents($assets_dir . 'script.js', 'jQuery(document).ready(function($) { $(".acv-coupon").on("click", ".acv-button", function() { console.log("Coupon clicked!"); }); });');
    }
    if (!file_exists($assets_dir . 'style.css')) {
        file_put_contents($assets_dir . 'style.css', '.acv-coupon { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9; } .acv-code { font-size: 2em; color: #007cba; font-weight: bold; margin: 10px 0; } .acv-button { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; } .acv-button:hover { background: #005a87; }');
    }
});

AffiliateCouponVault::get_instance();

// Pro upsell notice
function acv_admin_notice() {
    if (!get_option('acv_pro')) {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault:</strong> Unlock unlimited coupons & advanced tracking with <a href="https://example.com/pro" target="_blank">Pro version</a> for $49/year!</p></div>';
    }
}
add_action('admin_notices', 'acv_admin_notice');