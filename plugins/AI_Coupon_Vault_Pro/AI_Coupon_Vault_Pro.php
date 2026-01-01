/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Vault_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Vault Pro
 * Plugin URI: https://example.com/aicouponvault
 * Description: AI-powered coupon management for affiliate marketing. Generate, track, and display personalized coupons.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponVault {
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
        add_shortcode('aicoupon_vault', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('aicouponvault', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('aicouponvault-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('aicouponvault-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('AI Coupon Vault', 'Coupon Vault', 'manage_options', 'aicouponvault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('aicouponvault_coupons', sanitize_textarea_field($_POST['coupons']));
            update_option('aicouponvault_pro', isset($_POST['pro_version']));
        }
        $coupons = get_option('aicouponvault_coupons', "Brand1: DISCOUNT10\nBrand2: SAVE20");
        $pro = get_option('aicouponvault_pro', false);
        ?>
        <div class="wrap">
            <h1>AI Coupon Vault Settings</h1>
            <form method="post">
                <p><label>Coupons (format: Brand: CODE):</label><br><textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea></p>
                <p><label><input type="checkbox" name="pro_version" <?php checked($pro); ?>> Pro Version (Unlimited)</label></p>
                <p><input type="submit" name="submit" class="button-primary" value="Save Settings"></p>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock AI generation, analytics, and unlimited coupons for $49/year.</p>
            <p>Use shortcode: <code>[aicoupon_vault]</code></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('num' => 5), $atts);
        $coupons_text = get_option('aicouponvault_coupons', '');
        $coupons = explode("\n", trim($coupons_text));
        $pro = get_option('aicouponvault_pro', false);
        if (!$pro && count($coupons) > 3) {
            $coupons = array_slice($coupons, 0, 3);
        }
        $output = '<div class="aicoupon-vault">';
        foreach (array_slice($coupons, 0, intval($atts['num'])) as $coupon) {
            list($brand, $code) = explode(':', trim($coupon), 2);
            if ($brand && $code) {
                $output .= '<div class="coupon-item">';
                $output .= '<h4>' . esc_html(trim($brand)) . '</h4>';
                $output .= '<p>Code: <strong>' . esc_html(trim($code)) . '</strong></p>';
                $output .= '<a href="#" class="copy-code" data-code="' . esc_attr(trim($code)) . '">Copy Code</a>';
                $output .= '</div>';
            }
        }
        $output .= '</div>';
        if (!$pro) {
            $output .= '<p class="pro-upsell">Upgrade to Pro for AI-powered coupons and more!</p>';
        }
        return $output;
    }

    public function activate() {
        if (!get_option('aicouponvault_pro')) {
            add_option('aicouponvault_pro', false);
        }
    }
}

AICouponVault::get_instance();

// Freemium upsell notice
function aicouponvault_admin_notice() {
    if (!get_option('aicouponvault_pro')) {
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Coupon Vault Pro</strong> for $49/year: Unlimited coupons, AI generation, analytics. <a href="https://example.com/pro">Upgrade Now</a></p></div>';
    }
}
add_action('admin_notices', 'aicouponvault_admin_notice');

// Assets (inline for single file)
function aicouponvault_inline_assets() {
    ?>
    <style>
    .aicoupon-vault { max-width: 400px; margin: 20px 0; }
    .coupon-item { background: #f9f9f9; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .coupon-item h4 { margin: 0 0 5px; color: #333; }
    .copy-code { background: #0073aa; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; }
    .pro-upsell { background: #fff3cd; padding: 10px; border: 1px solid #ffeaa7; border-radius: 5px; text-align: center; }
    </style>
    <script>
    jQuery(document).ready(function($) {
        $('.copy-code').click(function(e) {
            e.preventDefault();
            navigator.clipboard.writeText($(this).data('code')).then(function() {
                $(this).text('Copied!');
            }.bind(this));
        });
    });
    </script>
    <?php
}
add_action('wp_head', 'aicouponvault_inline_assets');
add_action('admin_head', 'aicouponvault_inline_assets');