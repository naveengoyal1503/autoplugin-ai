/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupons with custom promo codes to boost affiliate commissions.
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
        add_options_page(
            'Affiliate Coupon Vault',
            'Coupon Vault',
            'manage_options',
            'affiliate-coupon-vault',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', "Brand1|DISCOUNT10|50|Affiliate Link 1
Brand2|SAVE20|20|Affiliate Link 2");
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <p><label>Enter coupons (format: Brand|Code|Discount%|Affiliate Link), one per line:</label></p>
                <textarea name="coupons" rows="10" cols="80"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="submit"><input type="submit" name="submit" class="button-primary" value="Save Settings"></p>
            </form>
            <p><strong>Shortcode:</strong> <code>[affiliate_coupon]</code> or <code>[affiliate_coupon brand="Brand1"]</code></p>
            <?php if (!function_exists('is_pro_version')) : ?>
            <p><strong>Go Pro:</strong> Unlock unlimited coupons, analytics, and custom designs for $49/year!</p>
            <?php endif; ?>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('brand' => ''), $atts);
        $coupons = explode("\n", get_option('acv_coupons', ''));
        $output = '';
        foreach ($coupons as $coupon) {
            $parts = explode('|', trim($coupon));
            if (count($parts) === 4 && ($atts['brand'] === '' || strpos($parts, $atts['brand']) !== false)) {
                $output .= '<div class="acv-coupon"><h3>' . esc_html($parts) . '</h3><p>Code: <strong>' . esc_html($parts[1]) . '</strong> - ' . esc_html($parts[2]) . '% OFF</p><a href="' . esc_url($parts[3]) . '" class="acv-button" target="_blank">Shop Now & Save</a></div>';
            }
        }
        if (empty($output)) {
            $output = '<p>No coupons available.</p>';
        }
        return $output;
    }

    public function activate() {
        if (get_option('acv_coupons') === false) {
            update_option('acv_coupons', "Brand1|DISCOUNT10|50|https://affiliate-link1.com
Brand2|SAVE20|20|https://affiliate-link2.com");
        }
    }
}

AffiliateCouponVault::get_instance();

/* Pro Teaser */
function is_pro_version() { return false; } // Replace with true in pro version

/* CSS */
function acv_inline_css() {
    echo '<style>.acv-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 10px 0; background: #f9f9f9; border-radius: 8px; }.acv-coupon h3 { color: #0073aa; margin: 0 0 10px; }.acv-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }.acv-button:hover { background: #005a87; }</style>';
}
add_action('wp_head', 'acv_inline_css');

/* JS */
function acv_inline_js() {
    echo '<script>jQuery(document).ready(function($) { $(".acv-button").on("click", function() { $(this).text("Copied! Go Save!"); }); });</script>';
}
add_action('wp_footer', 'acv_inline_js');