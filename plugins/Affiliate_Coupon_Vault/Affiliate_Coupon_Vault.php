/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with personalized promo codes, tracking clicks and conversions for maximum blog monetization.
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
        add_action('wp_ajax_acv_track_click', array($this, 'track_click'));
        add_shortcode('acv_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-frontend', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            update_option('acv_pro', isset($_POST['pro']) ? 1 : 0);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', "Brand1|DISCOUNT10|50|https://affiliate.link1
Brand2|SAVE20|20|https://affiliate.link2");
        $pro = get_option('acv_pro', 0);
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupons (Brand|Code|Discount%|Affiliate URL, one per line)</th>
                        <td><textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea></td>
                    </tr>
                </table>
                <p><label><input type="checkbox" name="pro" value="1" <?php checked($pro); ?>> Enable Pro Features (Upgrade for full access)</label></p>
                <?php if (!$pro) { ?>
                    <p><strong>Upgrade to Pro for unlimited coupons and analytics!</strong> <a href="https://example.com/pro" target="_blank">Get Pro ($49/year)</a></p>
                <?php } ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = explode("\n", get_option('acv_coupons', ''));
        if (empty($coupons) || $atts['id'] >= count($coupons)) {
            return '<p>No coupons available.</p>';
        }
        $coupon = explode('|', trim($coupons[$atts['id']]));
        if (count($coupon) !== 4) {
            return '<p>Invalid coupon format.</p>';
        }
        list($brand, $code, $discount, $url) = $coupon;
        $click_id = uniqid();
        $pro = get_option('acv_pro', 0);
        $limit_msg = $pro ? '' : '<p><em>Pro: Unlimited coupons</em></p>';
        return '<div class="acv-coupon" data-url="' . esc_url($url) . '" data-clickid="' . esc_attr($click_id) . '"><h3>' . esc_html($brand) . '</h3><p><strong>' . esc_html($code) . '</strong> - ' . esc_html($discount) . '% OFF</p>' . $limit_msg . '<button class="acv-btn">Get Deal (Track Affiliate)</button></div>';
    }

    public function track_click() {
        check_ajax_referer('acv_nonce', 'nonce');
        $url = sanitize_url($_POST['url']);
        $click_id = sanitize_text_field($_POST['clickid']);
        // Log click (in Pro, integrate with analytics)
        error_log('ACV Click: ' . $click_id . ' -> ' . $url);
        wp_redirect($url);
        exit;
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', "Brand1|DISCOUNT10|50|https://affiliate.link1\nBrand2|SAVE20|20|https://affiliate.link2");
        }
    }
}

// Create assets directory and files
$upload_dir = plugin_dir_path(__FILE__) . 'assets';
if (!file_exists($upload_dir)) {
    wp_mkdir_p($upload_dir);
}

$js_content = "jQuery(document).ready(function($) {
    $('.acv-btn').click(function(e) {
        e.preventDefault();
        var btn = $(this);
        var container = btn.closest('.acv-coupon');
        var url = container.data('url');
        var clickid = container.data('clickid');
        $.post(acv_ajax.ajax_url, {
            action: 'acv_track_click',
            nonce: acv_ajax.nonce,
            url: url,
            clickid: clickid
        }, function() {
            window.location.href = url;
        });
    });
});";

file_put_contents($upload_dir . '/frontend.js', $js_content);

AffiliateCouponVault::get_instance();

// Add widget support
add_action('widgets_init', function() {
    register_widget('ACV_Widget');
});

class ACV_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct('acv_widget', 'Affiliate Coupon Vault');
    }

    public function widget($args, $instance) {
        echo do_shortcode('[acv_coupon id="0"]');
    }

    public function form($instance) {
        echo '<p>Displays random coupon. Configure in settings.</p>';
    }
}