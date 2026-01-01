/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with personalized promo codes, boosting conversions and commissions.
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
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate' => '',
            'product' => 'Featured Product',
            'discount' => '20%',
            'link' => '#',
            'code' => ''
        ), $atts);

        $unique_code = $atts['code'] ?: 'SAVE' . substr(md5(uniqid()), 0, 5);

        ob_start();
        ?>
        <div class="affiliate-coupon-vault" style="border: 2px dashed #007cba; padding: 20px; background: #f9f9f9; text-align: center; max-width: 400px;">
            <h3 style="color: #007cba;">Exclusive Deal: <?php echo esc_html($atts['product']); ?></h3>
            <p><strong><?php echo esc_html($atts['discount']); ?> OFF</strong> with code:</p>
            <div class="coupon-code" style="background: #fff; font-size: 24px; font-weight: bold; padding: 10px; margin: 10px 0; border: 1px solid #ddd; letter-spacing: 3px;"><?php echo esc_html($unique_code); ?></div>
            <a href="<?php echo esc_url($atts['link']); ?}" target="_blank" class="coupon-button" style="display: inline-block; background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">Shop Now & Save</a>
            <p style="font-size: 12px; margin-top: 10px; color: #666;">Limited time offer - Affiliate exclusive!</p>
        </div>
        <script>
        jQuery('.affiliate-coupon-vault .coupon-code').on('click', function() {
            navigator.clipboard.writeText('<?php echo esc_js($unique_code); ?>');
            alert('Coupon code copied!');
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
            array($this, 'admin_page')
        );
    }

    public function admin_init() {
        register_setting('affiliate_coupon_vault_options', 'affiliate_coupon_vault_settings');
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('affiliate_coupon_vault_settings', $_POST['affiliate_coupon_vault_settings']);
        }
        $settings = get_option('affiliate_coupon_vault_settings', array());
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Pro Upgrade Notice</th>
                        <td>Unlock unlimited coupons, analytics, and integrations with <strong>Pro version ($49/year)</strong></td>
                    </tr>
                </table>
                <p><strong>Usage:</strong> Use shortcode <code>[affiliate_coupon affiliate="amazon" product="Product Name" discount="20%" link="https://aff.link" code="SAVE20"]</code></p>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

// Create style.css inline for single file
add_action('wp_head', function() {
    echo '<style>
    .affiliate-coupon-vault .coupon-code { cursor: pointer; transition: background 0.3s; }
    .affiliate-coupon-vault .coupon-code:hover { background: #e9f5ff !important; }
    .affiliate-coupon-vault .coupon-button:hover { background: #005a87 !important; }
    </style>';
});

// Initialize
AffiliateCouponVault::get_instance();

// Pro upsell notice
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault:</strong> Upgrade to Pro for unlimited coupons & analytics! <a href="https://example.com/pro" target="_blank">Get Pro</a></p></div>';
});