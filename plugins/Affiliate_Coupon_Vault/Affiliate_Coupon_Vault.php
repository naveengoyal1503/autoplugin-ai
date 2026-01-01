/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupon codes and deals from popular networks to boost conversions and commissions.
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
        add_shortcode('affiliate_coupons', array($this, 'coupon_shortcode'));
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
            'count' => 5,
            'category' => 'all'
        ), $atts);

        $coupons = $this->get_coupons($atts['count'], $atts['category']);
        ob_start();
        ?>
        <div class="affiliate-coupon-vault">
            <?php foreach ($coupons as $coupon): ?>
            <div class="coupon-item">
                <h4><?php echo esc_html($coupon['title']); ?></h4>
                <p class="discount"><?php echo esc_html($coupon['discount']); ?></p>
                <a href="<?php echo esc_url($coupon['link']); ?}" class="coupon-btn" target="_blank">Get Deal <?php echo esc_html($coupon['code']); ?></a>
                <span class="expires">Expires: <?php echo esc_html($coupon['expires']); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_coupons($count, $category) {
        // Demo coupons - Pro version integrates with APIs like CJ Affiliate, ShareASale
        $demo_coupons = array(
            array(
                'title' => 'Hostinger 75% Off',
                'discount' => '75% OFF',
                'code' => 'AFF75',
                'link' => 'https://hostinger.com/?ref=affiliate',
                'expires' => '2026-03-01'
            ),
            array(
                'title' => 'Elementor Pro Discount',
                'discount' => '50% OFF',
                'code' => 'PRO50',
                'link' => 'https://elementor.com/?ref=affiliate',
                'expires' => '2026-02-15'
            ),
            array(
                'title' => 'WP Rocket Speed Up',
                'discount' => '10% OFF',
                'code' => 'ROCKET10',
                'link' => 'https://wp-rocket.me/?ref=affiliate',
                'expires' => '2026-04-01'
            ),
            array(
                'title' => 'Bluehost Hosting Deal',
                'discount' => '$2.95/mo',
                'code' => 'BLUE',
                'link' => 'https://bluehost.com/?ref=affiliate',
                'expires' => '2026-01-31'
            ),
            array(
                'title' => 'SEMRush SEO Tool',
                'discount' => '30% OFF',
                'code' => 'SEM30',
                'link' => 'https://semrush.com/?ref=affiliate',
                'expires' => '2026-02-28'
            )
        );

        return array_slice($demo_coupons, 0, intval($count));
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

    public function admin_init() {
        register_setting('affiliate_coupon_vault_options', 'affiliate_coupon_vault_options');

        add_settings_section(
            'affiliate_coupon_section',
            'Coupon Settings',
            null,
            'affiliate-coupon-vault'
        );

        add_settings_field(
            'api_key',
            'Affiliate API Key (Pro)',
            array($this, 'api_key_callback'),
            'affiliate-coupon-vault',
            'affiliate_coupon_section'
        );
    }

    public function api_key_callback() {
        $options = get_option('affiliate_coupon_vault_options');
        echo '<input type="text" name="affiliate_coupon_vault_options[api_key]" value="' . esc_attr($options['api_key'] ?? '') . '" class="regular-text" />';
        echo '<p class="description">Enter your affiliate network API key for live deals (Pro feature).</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('affiliate_coupon_vault_options');
                do_settings_sections('affiliate-coupon-vault');
                submit_button();
                ?>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[affiliate_coupons count="5" category="hosting"]</code></p>
            <h2>Upgrade to Pro</h2>
            <p>Get live API integrations, unlimited coupons, analytics, and custom branding for $49/year.</p>
        </div>
        <?php
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

// Create CSS file content
$css_content = ".affiliate-coupon-vault { max-width: 600px; margin: 20px 0; }
.coupon-item { background: #f9f9f9; padding: 20px; margin-bottom: 15px; border-radius: 8px; border-left: 5px solid #0073aa; }
.coupon-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
.coupon-btn:hover { background: #005a87; }
.discount { font-size: 24px; font-weight: bold; color: #e74c3c; margin: 10px 0; }";
file_put_contents(plugin_dir_path(__FILE__) . 'style.css', $css_content);

// Create JS file content
$js_content = "jQuery(document).ready(function($) {
    $('.coupon-btn').on('click', function() {
        $(this).html('Copied! <span style="font-size:12px;">(Copied)</span>');
        var code = $(this).text().replace('Get Deal ', '').replace(' (Copied)', '');
        navigator.clipboard.writeText(code);
        setTimeout(function() {
            $('.coupon-btn').html('Get Deal ' + code);
        }, 2000);
    });
});";
file_put_contents(plugin_dir_path(__FILE__) . 'script.js', $js_content);

AffiliateCouponVault::get_instance();

// Pro upsell notice
add_action('admin_notices', function() {
    if (!get_option('affiliate_coupon_vault_pro')) {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault:</strong> Unlock Pro features like live API deals and analytics! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
    }
});