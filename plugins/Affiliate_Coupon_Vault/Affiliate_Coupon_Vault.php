/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupon codes for popular products, boosting conversions and commissions.
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
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupon_vault', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => 'all',
            'limit' => 5
        ), $atts);

        $coupons = $this->get_coupons($atts['category'], $atts['limit']);
        ob_start();
        ?>
        <div class="affiliate-coupon-vault">
            <?php foreach ($coupons as $coupon): ?>
            <div class="coupon-item">
                <h3><?php echo esc_html($coupon['title']); ?></h3>
                <p><?php echo esc_html($coupon['description']); ?></p>
                <div class="coupon-code"><?php echo esc_html($coupon['code']); ?></div>
                <a href="<?php echo esc_url($coupon['affiliate_link']); ?}" target="_blank" class="coupon-button" rel="nofollow">Shop Now & Save</a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_coupons($category, $limit) {
        $coupons = get_option('acv_coupons', array());
        if (empty($coupons)) {
            $coupons = $this->default_coupons();
            update_option('acv_coupons', $coupons);
        }
        return array_slice($coupons, 0, $limit);
    }

    private function default_coupons() {
        return array(
            array(
                'title' => '10% Off Hosting',
                'description' => 'Get started with premium hosting.',
                'code' => 'AFF10',
                'affiliate_link' => 'https://example.com/hosting?ref=yourid',
                'category' => 'hosting'
            ),
            array(
                'title' => '20% Off VPN',
                'description' => 'Secure your online privacy.',
                'code' => 'VPN20',
                'affiliate_link' => 'https://example.com/vpn?ref=yourid',
                'category' => 'tools'
            ),
            array(
                'title' => 'Free Trial Email Marketing',
                'description' => 'Build your email list effortlessly.',
                'code' => 'EMAILFREE',
                'affiliate_link' => 'https://example.com/email?ref=yourid',
                'category' => 'marketing'
            )
        );
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('acv_settings', 'acv_coupons');
        add_settings_section('acv_main', 'Manage Coupons', null, 'affiliate-coupon-vault');
        add_settings_field('coupons', 'Coupons', array($this, 'coupons_field'), 'affiliate-coupon-vault', 'acv_main');
    }

    public function coupons_field() {
        $coupons = get_option('acv_coupons', array());
        echo '<textarea name="acv_coupons" rows="10" cols="50">' . esc_textarea(json_encode($coupons, JSON_PRETTY_PRINT)) . '</textarea>';
        echo '<p>Enter JSON array of coupons: {"title":"","description":"","code":"","affiliate_link":"","category":""}</p>';
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('acv_settings');
                do_settings_sections('affiliate-coupon-vault');
                submit_button();
                ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, auto-generation, analytics, and premium networks for $49/year.</p>
        </div>
        <?php
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', $this->default_coupons());
        }
    }
}

AffiliateCouponVault::get_instance();

// Pro upsell notice
function acv_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Upgrade for advanced features! <a href="https://example.com/pro" target="_blank">Learn More</a></p></div>';
}
add_action('admin_notices', 'acv_pro_notice');

// Create assets directories if needed
$upload_dir = wp_upload_dir();
$assets_dir = plugin_dir_path(__FILE__) . 'assets';
if (!file_exists($assets_dir)) {
    wp_mkdir_p($assets_dir);
}

// Sample style.css content
file_put_contents($assets_dir . '/style.css', ".affiliate-coupon-vault { max-width: 600px; } .coupon-item { border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 8px; } .coupon-code { background: #f0f0f0; padding: 10px; font-family: monospace; } .coupon-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; }");

// Sample script.js content
file_put_contents($assets_dir . '/script.js', "jQuery(document).ready(function($) { $('.coupon-button').on('click', function() { $(this).text('Copied! Thanks!'); }); });");