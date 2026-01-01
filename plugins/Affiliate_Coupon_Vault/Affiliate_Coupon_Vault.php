/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and manages exclusive affiliate coupons for your WordPress site, boosting conversions and revenue.
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
            self::$instance = new self;
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
            echo '<div class="notice notice-success"><p>Coupons updated!</p></div>';
        }
        $coupons = get_option('acv_coupons', "Brand1: DISCOUNT10 - 10% off\nBrand2: SAVE20 - $20 off");
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupons (Format: Brand: CODE - Description)</th>
                        <td><textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics, auto-generation, and more for $49/year.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = explode('\n', get_option('acv_coupons', ''));
        $html = '<div class="acv-vault">';
        foreach ($coupons as $coupon) {
            if (trim($coupon)) {
                $parts = explode(':', $coupon, 2);
                $brand = trim($parts);
                $code_desc = isset($parts[1]) ? trim($parts[1]) : '';
                $html .= '<div class="acv-coupon"><strong>' . esc_html($brand) . '</strong>: ' . esc_html($code_desc) . ' <span class="acv-use">Use now!</span></div>';
            }
        }
        $html .= '</div><p class="acv-pro"><a href="https://example.com/pro">Go Pro for more features</a></p>';
        return $html;
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', "Brand1: DISCOUNT10 - 10% off\nBrand2: SAVE20 - $20 off");
        }
    }
}

AffiliateCouponVault::get_instance();

// Inline styles and scripts for self-contained

function acv_inline_styles() {
    echo '<style>
.acv-vault { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
.acv-coupon { margin-bottom: 10px; font-size: 16px; }
.acv-use { color: #0073aa; font-weight: bold; }
.acv-pro { text-align: center; margin-top: 20px; }
    </style>';
}
add_action('wp_head', 'acv_inline_styles');

function acv_inline_scripts() {
    echo '<script>jQuery(document).ready(function($) { $(".acv-use").click(function() { $(this).text("Copied!"); }); });</script>';
}
add_action('wp_footer', 'acv_inline_scripts');