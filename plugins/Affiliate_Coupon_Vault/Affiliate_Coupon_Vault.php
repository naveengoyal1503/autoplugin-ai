/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupon codes and deals to boost conversions and commissions.
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
        add_shortcode('affiliate_coupon_vault', array($this, 'coupon_shortcode'));
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
            'affiliate_id' => 'default',
            'discount' => '10%',
            'product' => 'Featured Product',
            'link' => '#',
        ), $atts);

        $coupon_code = $this->generate_coupon_code($atts['affiliate_id']);

        ob_start();
        ?>
        <div class="affiliate-coupon-vault" data-affiliate="<?php echo esc_attr($atts['affiliate_id']); ?>">
            <div class="coupon-header">
                <h3>Exclusive Deal: <?php echo esc_html($atts['product']); ?></h3>
                <span class="discount-badge"><?php echo esc_html($atts['discount']); ?> OFF</span>
            </div>
            <div class="coupon-code-container">
                <code class="coupon-code" id="coupon-<?php echo esc_attr($atts['affiliate_id']); ?>"><?php echo esc_html($coupon_code); ?></code>
                <button class="copy-coupon" data-clipboard-text="<?php echo esc_html($coupon_code); ?>">Copy Code</button>
            </div>
            <a href="<?php echo esc_url($atts['link']); ?}" class="affiliate-btn" target="_blank">Shop Now & Save</a>
            <p class="coupon-expires">Limited time offer! Expires in 7 days.</p>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_coupon_code($affiliate_id) {
        $prefix = get_option('acv_prefix_' . $affiliate_id, 'SAVE') . '-';
        $random = wp_rand(1000, 9999);
        $timestamp = date('mdy');
        return strtoupper($prefix . $random . $timestamp);
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
        register_setting('acv_settings', 'acv_prefix_default');
        foreach (array('amazon', 'clickbank', 'other') as $id) {
            register_setting('acv_settings', 'acv_prefix_' . $id);
        }
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            check_admin_referer('acv_save');
        }
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('acv_settings'); ?>
                <?php do_settings_sections('acv_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Default Coupon Prefix</th>
                        <td><input type="text" name="acv_prefix_default" value="<?php echo esc_attr(get_option('acv_prefix_default', 'SAVE')); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Amazon Prefix</th>
                        <td><input type="text" name="acv_prefix_amazon" value="<?php echo esc_attr(get_option('acv_prefix_amazon', 'AMZ')); ?>" /></td>
                    </tr>
                    <tr>
                        <th>ClickBank Prefix</th>
                        <td><input type="text" name="acv_prefix_clickbank" value="<?php echo esc_attr(get_option('acv_prefix_clickbank', 'CB')); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[affiliate_coupon_vault affiliate_id="amazon" discount="20%" product="Laptop" link="https://amazon.com/product"]</code></p>
            <p><strong>Pro Features (Upgrade for $49/year):</strong> Analytics, unlimited affiliates, auto-expiry, email capture.</p>
        </div>
        <style>
        .affiliate-coupon-vault { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; background: #f9f9f9; border-radius: 8px; text-align: center; }
        .coupon-header h3 { margin: 0 0 10px; color: #007cba; }
        .discount-badge { background: #ff6b35; color: white; padding: 5px 15px; border-radius: 20px; font-weight: bold; }
        .coupon-code-container { margin: 20px 0; }
        .coupon-code { background: #333; color: #fff; padding: 10px 20px; font-family: monospace; font-size: 24px; border-radius: 5px; letter-spacing: 3px; }
        .copy-coupon { background: #007cba; color: white; border: none; padding: 10px 20px; margin-left: 10px; cursor: pointer; border-radius: 5px; }
        .affiliate-btn { display: inline-block; background: #ff6b35; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 10px; }
        .coupon-expires { font-size: 14px; color: #666; margin-top: 10px; }
        </style>
        <script>
        jQuery(document).ready(function($) {
            $('.copy-coupon').click(function() {
                var code = $(this).data('clipboard-text');
                navigator.clipboard.writeText(code).then(function() {
                    $(this).text('Copied!');
                }.bind(this));
            });
        });
        </script>
        <?php
    }

    public function activate() {
        add_option('acv_prefix_default', 'SAVE');
        add_option('acv_prefix_amazon', 'AMZ');
        add_option('acv_prefix_clickbank', 'CB');
    }
}

AffiliateCouponVault::get_instance();

// Pro upsell notice
function acv_admin_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault:</strong> Unlock Pro features like analytics & unlimited coupons for $49/year! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
}
add_action('admin_notices', 'acv_admin_notice');