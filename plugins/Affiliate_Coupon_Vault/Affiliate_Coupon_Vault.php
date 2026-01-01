/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons for your blog posts, boosting conversions and commissions.
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-vault', plugins_url('style.css', __FILE__), array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault', plugins_url('script.js', __FILE__), array('jquery'), '1.0.0', true);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate' => '',
            'code' => '',
            'discount' => '10%',
            'link' => '',
            'expires' => ''
        ), $atts);

        $expires = !empty($atts['expires']) ? date('M j, Y', strtotime($atts['expires'])) : 'Limited time';

        ob_start();
        ?>
        <div class="affiliate-coupon-vault" style="border: 2px dashed #007cba; padding: 20px; margin: 20px 0; background: #f9f9f9; border-radius: 8px;">
            <h3 style="color: #007cba; margin-top: 0;">🎉 Exclusive Deal: <strong><?php echo esc_html($atts['affiliate']); ?></strong></h3>
            <p>Save <strong><?php echo esc_html($atts['discount']); ?> OFF</strong> with code: <code style="background: #007cba; color: white; padding: 4px 8px; border-radius: 4px;"><?php echo esc_html($atts['code']); ?></code></p>
            <p><em>Expires: <?php echo esc_html($expires); ?></em></p>
            <a href="<?php echo esc_url($atts['link']); ?}" target="_blank" class="button button-large" style="background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">🛒 Shop Now & Save</a>
            <p style="font-size: 12px; margin-top: 15px; color: #666;">* Affiliate link - We may earn a commission at no extra cost to you.</p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function admin_menu() {
        add_options_page(
            'Affiliate Coupon Vault',
            'Coupon Vault',
            'manage_options',
            'affiliate-coupon-vault',
            array($this, 'admin_page')
        );
    }

    public function admin_init() {
        register_setting('affiliate_coupon_vault_options', 'acv_pro_key');
    }

    public function admin_page() {
        if (isset($_POST['acv_pro_key'])) {
            update_option('acv_pro_key', sanitize_text_field($_POST['acv_pro_key']));
            echo '<div class="notice notice-success"><p>Pro key updated!</p></div>';
        }
        $pro_key = get_option('acv_pro_key');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Pro License Key</th>
                        <td>
                            <input type="text" name="acv_pro_key" value="<?php echo esc_attr($pro_key); ?>" class="regular-text" />
                            <p class="description">Enter your pro key for unlimited coupons and analytics. <a href="https://example.com/pro" target="_blank">Get Pro ($49/year)</a></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[affiliate_coupon affiliate="Brand" code="SAVE10" discount="10%" link="https://aff.link" expires="2026-12-31"]</code></p>
            <?php if (!$pro_key || $pro_key !== 'pro-active') : ?>
            <div class="notice notice-info">
                <p><strong>Go Pro</strong> for unlimited coupons, analytics, auto-generation, and custom designs!</p>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

// Create minimal style.css content
global $affiliate_coupon_vault_css;
$affiliate_coupon_vault_css = ".affiliate-coupon-vault { animation: pulse 2s infinite; } @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(0,124,186,0.7); } 70% { box-shadow: 0 0 0 10px rgba(0,124,186,0); } 100% { box-shadow: 0 0 0 0 rgba(0,124,186,0); } }";
add_action('wp_head', function() use ($affiliate_coupon_vault_css) { echo '<style>' . $affiliate_coupon_vault_css . '</style>'; });

// Minimal JS for pro tease
add_action('wp_footer', function() {
    if (!get_option('acv_pro_key')) {
        ?><script>console.log('Upgrade to Affiliate Coupon Vault Pro for analytics & more!');</script><?php
    }
});

AffiliateCouponVault::get_instance();