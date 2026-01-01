/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons, personalized discounts, and promo codes to boost conversions and commissions.
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affiliate_coupon_vault', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-vault-css', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault-js', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
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
        $coupons = get_option('acv_coupons', "Coupon 10% off: https://affiliate.link?coupon=10OFF\nFree Shipping: https://affiliate.link?coupon=FREE\n");
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupons (one per line: Name: Affiliate URL)</th>
                        <td><textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Shortcode:</strong> <code>[affiliate_coupon_vault]</code> or <code>[affiliate_coupon_vault count="3"]</code></p>
            <p><em>Premium: Unlimited coupons, analytics, auto-expiration, custom designs. <a href="#" onclick="alert('Upgrade to Pro!')">Upgrade Now</a></em></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('count' => 3), $atts);
        $coupons_text = get_option('acv_coupons', '');
        $coupons = explode("\n", trim($coupons_text));
        $coupons = array_filter(array_map('trim', $coupons));
        $selected = array_slice($coupons, 0, intval($atts['count']));
        if (empty($selected)) {
            return '<p>No coupons configured. <a href="' . admin_url('options-general.php?page=affiliate-coupon-vault') . '">Set up now</a>.</p>';
        }
        $output = '<div class="acv-coupons">';
        foreach ($selected as $coupon) {
            if (preg_match('/^([^:]+):\s*(https?\S+)/', $coupon, $matches)) {
                $name = sanitize_text_field($matches[1]);
                $url = esc_url($matches[2]);
                $output .= '<div class="acv-coupon"><a href="' . $url . '" target="_blank" rel="nofollow noopener">' . esc_html($name) . '</a></div>';
            }
        }
        $output .= '</div><p class="acv-pro-upsell">Unlock more with <strong>Premium</strong>: Analytics & Unlimited Coupons!</p>';
        return $output;
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', "Coupon 10% off: https://affiliate.link?coupon=10OFF\nFree Shipping: https://affiliate.link?coupon=FREE\n");
        }
    }
}

AffiliateCouponVault::get_instance();

// Inline styles and scripts for self-contained

function acv_add_inline_styles() {
    echo '<style>
.acv-coupons { display: flex; flex-direction: column; gap: 10px; max-width: 300px; }
.acv-coupon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; text-decoration: none; border-radius: 8px; font-weight: bold; text-align: center; transition: transform 0.2s; }
.acv-coupon:hover { transform: scale(1.05); }
.acv-pro-upsell { text-align: center; margin-top: 15px; font-size: 0.9em; color: #666; }
@media (max-width: 768px) { .acv-coupons { max-width: 100%; } }
</style>';
}
add_action('wp_head', 'acv_add_inline_styles');

function acv_add_inline_scripts() {
    echo '<script>jQuery(document).ready(function($) { $(".acv-coupon").on("click", function() { gtag("event", "coupon_click", {"event_category": "affiliate"}); }); });</script>';
}
add_action('wp_footer', 'acv_add_inline_scripts');