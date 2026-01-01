/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupon codes with tracking, boosting conversions for bloggers and eCommerce sites.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
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
        add_action('wp_ajax_save_coupon_click', array($this, 'save_coupon_click'));
        add_action('wp_ajax_nopriv_save_coupon_click', array($this, 'save_coupon_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('affiliate-coupon-js', plugin_dir_url(__FILE__) . 'coupon.js', array('jquery'), '1.0.0', true);
        wp_localize_script('affiliate-coupon-js', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', "Brand1|DISCOUNT20|Your Affiliate Link|20% off Brand1\nBrand2|SAVE10|Affiliate Link 2|10% off");
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <p><label>Enter coupons (format: Brand|Code|Affiliate Link|Description, one per line):</label></p>
                <textarea name="coupons" rows="10" cols="80"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="submit"><input type="submit" name="submit" class="button-primary" value="Save Coupons"></p>
            </form>
            <p>Use shortcode: <code>[affiliate_coupon]</code></p>
            <p><strong>Pro Upgrade:</strong> Unlimited coupons, analytics dashboard, auto-expiry. <a href="https://example.com/pro">Get Pro ($49/year)</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = explode("\n", get_option('acv_coupons', ''));
        $output = '<div id="acv-vault">';
        foreach ($coupons as $coupon) {
            $parts = explode('|', trim($coupon));
            if (count($parts) === 4) {
                $brand = sanitize_text_field($parts);
                $code = sanitize_text_field($parts[1]);
                $link = esc_url($parts[2]);
                $desc = sanitize_text_field($parts[3]);
                $output .= '<div class="acv-coupon">'
                         . '<h3>' . $brand . '</h3>'
                         . '<p><strong>Code: ' . $code . '</strong> - ' . $desc . '</p>'
                         . '<a href="' . $link . '" class="acv-button" data-code="' . $code . '" data-brand="' . $brand . '">Get Deal (Track Click)</a>'
                         . '</div>';
            }
        }
        $output .= '</div><p><em>Pro: Advanced tracking & more features. <a href="https://example.com/pro">Upgrade Now</a></em></p>';
        return $output;
    }

    public function save_coupon_click() {
        if (!wp_verify_nonce($_POST['nonce'], 'acv_nonce')) {
            wp_die('Security check failed');
        }
        $code = sanitize_text_field($_POST['code']);
        $brand = sanitize_text_field($_POST['brand']);
        $clicks = get_option('acv_clicks', array());
        $clicks[$code] = isset($clicks[$code]) ? $clicks[$code] + 1 : 1;
        update_option('acv_clicks', $clicks);
        wp_send_json_success('Click tracked');
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', "Brand1|DISCOUNT20|https://aff.link/1|20% off Brand1\nBrand2|SAVE10|https://aff.link/2|10% off");
        }
    }
}

AffiliateCouponVault::get_instance();

// Inline CSS
add_action('wp_head', function() { ?>
<style>
#acv-vault { max-width: 600px; margin: 20px 0; }
.acv-coupon { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; background: #f9f9f9; }
.acv-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block; }
.acv-button:hover { background: #005a87; }
</style>
<script>
var acv_nonce = '<?php echo wp_create_nonce("acv_nonce"); ?>';
</script>
<?php });

// Simple JS file content (embedded for single file)
add_action('wp_footer', function() { ?>
<script>jQuery(document).ready(function($) {
    $('.acv-button').click(function(e) {
        e.preventDefault();
        var btn = $(this);
        $.post(ajax_object.ajax_url, {
            action: 'save_coupon_click',
            code: btn.data('code'),
            brand: btn.data('brand'),
            nonce: acv_nonce
        }, function() {
            window.location = btn.attr('href');
        });
    });
});</script>
<?php });