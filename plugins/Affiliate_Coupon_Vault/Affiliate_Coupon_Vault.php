/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupons with custom promo codes.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            return;
        }
        $this->load_textdomain();
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate' => 'default',
            'code' => 'SAVE10',
            'discount' => '10%',
            'link' => 'https://example.com',
            'text' => 'Get Discount'
        ), $atts);

        ob_start();
        ?>
        <div class="affiliate-coupon-vault" data-affiliate="<?php echo esc_attr($atts['affiliate']); ?>">
            <div class="coupon-code"><?php echo esc_html($atts['code']); ?></div>
            <div class="coupon-discount"><?php echo esc_html($atts['discount']); ?> OFF</div>
            <a href="<?php echo esc_url($atts['link']); ?>" class="coupon-button" target="_blank"><?php echo esc_html($atts['text']); ?></a>
            <div class="coupon-copy">Click to Copy Code</div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('affiliate_coupon_vault_options', 'affiliate_coupon_vault_settings');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('affiliate_coupon_vault_options'); ?>
                <?php do_settings_sections('affiliate_coupon_vault_options'); ?>
                <table class="form-table">
                    <tr>
                        <th>Default Affiliate Link</th>
                        <td><input type="url" name="affiliate_coupon_vault_settings[default_link]" value="<?php echo esc_attr(get_option('affiliate_coupon_vault_settings')['default_link'] ?? ''); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Default Code</th>
                        <td><input type="text" name="affiliate_coupon_vault_settings[default_code]" value="<?php echo esc_attr(get_option('affiliate_coupon_vault_settings')['default_code'] ?? 'SAVE10'); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Shortcode:</strong> [affiliate_coupon affiliate="brand" code="PROMO20" discount="20%" link="https://affiliate.link" text="Shop Now"]</p>
            <p><em>Upgrade to Pro for unlimited coupons and analytics!</em></p>
        </div>
        <?php
    }

    public function activate() {
        add_option('affiliate_coupon_vault_settings', array());
    }

    public function load_textdomain() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
}

new AffiliateCouponVault();

/* Pro Upsell Notice */
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault:</strong> Unlock Pro features like analytics and custom designs. <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
});

/* CSS */
function acv_inline_css() {
    echo '<style>
    .affiliate-coupon-vault { border: 2px dashed #007cba; padding: 20px; text-align: center; background: #f9f9f9; border-radius: 10px; max-width: 300px; margin: 20px auto; }
    .coupon-code { font-size: 24px; font-weight: bold; color: #007cba; margin-bottom: 10px; }
    .coupon-discount { font-size: 18px; color: #28a745; margin-bottom: 15px; }
    .coupon-button { display: inline-block; background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; }
    .coupon-button:hover { background: #005a87; }
    .coupon-copy { margin-top: 10px; font-size: 12px; color: #666; cursor: pointer; }
    </style>';
}
add_action('wp_head', 'acv_inline_css');

/* JS */
function acv_inline_js() {
    echo '<script>
    jQuery(document).ready(function($) {
        $(".coupon-copy").click(function() {
            var code = $(this).siblings(".coupon-code").text();
            navigator.clipboard.writeText(code).then(function() {
                $(this).text("Copied!");
            }.bind(this));
        });
    });
    </script>';
}
add_action('wp_footer', 'acv_inline_js');