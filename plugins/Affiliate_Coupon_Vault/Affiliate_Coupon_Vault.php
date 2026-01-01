/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with personalized promo codes, tracking clicks and conversions for WordPress bloggers.
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
        add_action('wp_ajax_acv_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_acv_track_click', array($this, 'track_click'));
        add_shortcode('acv_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', "Brand1|DISCOUNT10|50|https://example.com/aff\nBrand2|SAVE20|20|https://example.com/aff");
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <p><label>Coupons (format: Brand|Code|Discount%|Affiliate URL, one per line):</label></p>
                <textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="submit"><input type="submit" name="submit" class="button-primary" value="Save Settings"></p>
            </form>
            <p>Upgrade to Pro for unlimited coupons and analytics: <a href="https://example.com/pro">Get Pro</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = explode("\n", get_option('acv_coupons', ''));
        if (empty($coupons) || count($coupons) > 5 && !defined('ACV_PRO')) {
            return '<p>Upgrade to Pro for more coupons!</p>';
        }
        $html = '<div class="acv-coupons">';
        foreach ($coupons as $coupon) {
            $parts = explode('|', trim($coupon));
            if (count($parts) == 4) {
                $html .= '<div class="acv-coupon"><h4>' . esc_html($parts) . '</h4><p>Code: <strong>' . esc_html($parts[1]) . '</strong> (' . esc_html($parts[2]) . '% off)</p><a href="#" class="acv-btn" data-url="' . esc_url($parts[3]) . '" data-brand="' . esc_attr($parts) . '">Get Deal (Track)</a></div>';
            }
        }
        $html .= '</div><style>.acv-coupons .acv-coupon {border:1px solid #ddd; padding:15px; margin:10px 0; background:#f9f9f9;}.acv-btn {background:#0073aa; color:#fff; padding:10px 20px; text-decoration:none; border-radius:5px;}</style>';
        return $html;
    }

    public function track_click() {
        check_ajax_referer('acv_nonce', 'nonce');
        $url = sanitize_url($_POST['url']);
        $brand = sanitize_text_field($_POST['brand']);
        // In Pro: Log to database
        if (!defined('ACV_PRO')) {
            update_option('acv_clicks', (int)get_option('acv_clicks', 0) + 1);
        }
        wp_redirect($url);
        exit;
    }

    public function activate() {
        update_option('acv_clicks', 0);
    }
}

AffiliateCouponVault::get_instance();

// Pro teaser
if (!defined('ACV_PRO')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-info"><p>Affiliate Coupon Vault Pro: Unlock unlimited coupons & analytics! <a href="https://example.com/pro">Upgrade Now</a></div>';
    });
}

// JS file content (inline for single file)
function acv_inline_script() {
    if (!is_admin()) {
        ?>
        <script>jQuery(document).ready(function($){$('.acv-btn').click(function(e){e.preventDefault();var url=$(this).data('url'),brand=$(this).data('brand');$.post(acv_ajax.ajax_url,{action:'acv_track_click',url:url,brand:brand,nonce:acv_ajax.nonce});window.location.href=url;});});</script>
        <?php
    }
}
add_action('wp_footer', 'acv_inline_script');
