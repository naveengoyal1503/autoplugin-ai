/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with personalized promo codes to boost your affiliate commissions.
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
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        if (is_admin()) {
            $this->pro_nag();
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate' => 'generic',
            'discount' => '20%',
            'code' => '',
            'link' => '',
            'expires' => '',
        ), $atts);

        $code = $atts['code'] ?: 'SAVE' . wp_generate_uuid4() . substr(md5(uniqid()), 0, 5);
        $expires = $atts['expires'] ? date('M d, Y', strtotime($atts['expires'])) : 'Limited time';

        ob_start();
        ?>
        <div class="affiliate-coupon-vault" style="border: 2px dashed #007cba; padding: 20px; background: #f9f9f9; border-radius: 10px; text-align: center; max-width: 400px;">
            <h3 style="color: #007cba; margin: 0 0 10px;">🎉 Exclusive Deal!</h3>
            <p style="font-size: 24px; margin: 10px 0; color: #333;"><strong><?php echo esc_html($atts['discount']); ?> OFF</strong></p>
            <div style="background: #fff; padding: 15px; border-radius: 5px; margin: 10px 0;">
                <strong>Promo Code: <code style="background: #007cba; color: white; padding: 5px 10px; border-radius: 3px; font-size: 18px; letter-spacing: 2px;"><?php echo esc_html($code); ?></code></strong>
            </div>
            <p style="margin: 10px 0; color: #666;">Expires: <?php echo esc_html($expires); ?></p>
            <a href="<?php echo esc_url($atts['link'] ?: '#'); ?}" target="_blank" class="coupon-btn" style="display: inline-block; background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">🛒 Shop Now & Save</a>
            <p style="font-size: 12px; margin: 10px 0 0; color: #999;">*Affiliate link. We earn a commission at no extra cost to you.</p>
        </div>
        <script>
        jQuery('.coupon-btn').on('click', function() {
            gtag && gtag('event', 'coupon_click', {'event_category': 'affiliate', 'event_label': '<?php echo esc_js($atts['affiliate']); ?>'});
        });
        </script>
        <?php
        return ob_get_clean();
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

    public function admin_init() {
        register_setting('affiliate_coupon_vault', 'affiliate_coupon_settings');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('affiliate_coupon_vault'); ?>
                <?php do_settings_sections('affiliate_coupon_vault'); ?>
                <table class="form-table">
                    <tr>
                        <th>Default Affiliate Link</th>
                        <td><input type="url" name="affiliate_coupon_settings[default_link]" value="<?php echo esc_attr(get_option('affiliate_coupon_settings')['default_link'] ?? ''); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Default Discount</th>
                        <td><input type="text" name="affiliate_coupon_settings[default_discount]" value="<?php echo esc_attr(get_option('affiliate_coupon_settings')['default_discount'] ?? '20%'); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin-top: 20px; border-radius: 5px;">
                <h3>🚀 Go Pro for Unlimited Features!</h3>
                <ul>
                    <li>Unlimited coupons</li>
                    <li>Analytics dashboard</li>
                    <li>Custom branding</li>
                    <li>Auto-expiry & email capture</li>
                    <li>Premium integrations (Amazon, etc.)</li>
                </ul>
                <a href="https://example.com/pro" target="_blank" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;">Upgrade to Pro - $49/year</a>
            </div>
        </div>
        <?php
    }

    public function pro_nag() {
        if (!current_user_can('manage_options')) return;
        add_action('admin_notices', function() {
            echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault:</strong> Unlock <strong>Pro</strong> for unlimited coupons & analytics! <a href="' . admin_url('options-general.php?page=affiliate-coupon-vault') . '">Upgrade now →</a></p></div>';
        });
    }

    public function activate() {
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

AffiliateCouponVault::get_instance();

// Inline styles
add_action('wp_head', function() {
    echo '<style>
    .affiliate-coupon-vault { animation: pulse 2s infinite; }
    @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(0,124,186,0.7); } 70% { box-shadow: 0 0 0 10px rgba(0,124,186,0); } 100% { box-shadow: 0 0 0 0 rgba(0,124,186,0); } }
    .coupon-btn:hover { background: #005a87 !important; transform: scale(1.05); }
    </style>';
});

// Prevent direct access to style.css and script.js if they don't exist
add_action('wp', function() {
    if (is_admin()) return;
    global $wp;
    if (strpos($wp->request ?? '', 'style.css') !== false || strpos($wp->request ?? '', 'script.js') !== false) {
        status_header(404);
        exit();
    }
});