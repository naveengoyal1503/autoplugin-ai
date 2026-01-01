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
            'Affiliate Coupon Vault Settings',
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
        $coupons = get_option('acv_coupons', "Brand1|10% Off|https://affiliate.link1\nBrand2|Free Shipping|https://affiliate.link2");
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupons (Brand|Code|Affiliate Link, one per line)</th>
                        <td><textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcode: <code>[affiliate_coupon]</code> or <code>[affiliate_coupon category="Brand1"]</code></p>
            <p><strong>Pro Upgrade:</strong> Unlimited coupons, analytics, auto-rotation, custom designs. <a href="#" onclick="alert('Pro features coming soon!')">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('category' => ''), $atts);
        $coupons = explode("\n", get_option('acv_coupons', ''));
        $filtered = array();
        foreach ($coupons as $coupon) {
            $parts = explode('|', trim($coupon));
            if (count($parts) === 3 && ($atts['category'] === '' || strpos($parts, $atts['category']) !== false)) {
                $filtered[] = $parts;
            }
        }
        if (empty($filtered)) {
            return '<p>No coupons available.</p>';
        }
        $rand = array_rand($filtered);
        $coupon = $filtered[$rand];
        $html = '<div class="acv-coupon">';
        $html .= '<h3>' . esc_html($coupon) . '</h3>';
        $html .= '<div class="acv-code">' . esc_html($coupon[1]) . '</div>';
        $html .= '<a href="' . esc_url($coupon[2]) . '" class="acv-button" target="_blank">Shop Now & Save</a>';
        $html .= '<p class="acv-pro">Pro: Track clicks & more!</p>';
        $html .= '</div>';
        return $html;
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', "Amazon|10% Off|https://your-amazon-affiliate-link\nShopify|Free Trial|https://your-shopify-affiliate-link");
        }
    }
}

AffiliateCouponVault::get_instance();

/* Pro Teaser */
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock analytics, unlimited coupons, and more for $49/year! <a href="#" onclick="alert(\'Coming soon!\')">Learn More</a></p></div>';
});

/* CSS */
function acv_add_styles() {
    echo '<style>
.acv-coupon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; text-align: center; max-width: 300px; margin: 20px auto; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
.acv-code { font-size: 24px; font-weight: bold; background: rgba(255,255,255,0.2); padding: 10px; border-radius: 5px; margin: 10px 0; }
.acv-button { display: inline-block; background: #ff6b6b; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; transition: background 0.3s; }
.acv-button:hover { background: #ff5252; }
.acv-pro { font-size: 12px; opacity: 0.8; margin-top: 10px; }
    </style>';
}
add_action('wp_head', 'acv_add_styles');
add_action('admin_head', 'acv_add_styles');

/* JS for interaction */
function acv_add_script() {
    echo '<script>
jQuery(document).ready(function($) {
    $(".acv-button").on("click", function() {
        $(this).text("Thanks! Tracking...");
        // Pro: Send analytics
    });
});
</script>';
}
add_action('wp_footer', 'acv_add_script');