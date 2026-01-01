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
    exit; // Exit if accessed directly.
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
        wp_enqueue_style('affiliate-coupon-vault-css', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault-js', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate' => 'default',
            'discount' => '10%',
            'code' => 'SAVE' . wp_generate_password(4, false),
            'link' => 'https://example.com',
            'expires' => date('Y-m-d', strtotime('+30 days'))
        ), $atts);

        ob_start();
        ?>
        <div class="affiliate-coupon-vault" data-affiliate="<?php echo esc_attr($atts['affiliate']); ?>">
            <h3>Exclusive Deal: <strong><?php echo esc_html($atts['discount']); ?> OFF</strong></h3>
            <p>Use code: <code><?php echo esc_html($atts['code']); ?></code></p>
            <p>Expires: <?php echo esc_html($atts['expires']); ?></p>
            <a href="<?php echo esc_url($atts['link']); ?>?coupon=<?php echo esc_attr($atts['code']); ?>" class="coupon-btn" target="_blank">Shop Now & Save</a>
            <div class="coupon-stats">Clicks: <span class="click-count">0</span></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function admin_menu() {
        add_options_page(
            'Affiliate Coupon Vault Settings',
            'Coupon Vault',
            'manage_options',
            'affiliate-coupon-vault',
            array($this, 'admin_page')
        );
    }

    public function admin_init() {
        register_setting('affiliate_coupon_vault_settings', 'acv_settings');
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_settings', $_POST['acv_settings']);
        }
        $settings = get_option('acv_settings', array());
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th>Default Affiliate Link</th>
                        <td><input type="url" name="acv_settings[default_link]" value="<?php echo esc_attr($settings['default_link'] ?? ''); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Default Discount</th>
                        <td><input type="text" name="acv_settings[default_discount]" value="<?php echo esc_attr($settings['default_discount'] ?? '10%'); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics, and integrations for $49/year.</p>
        </div>
        <?php
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

// Initialize
AffiliateCouponVault::get_instance();

// Inline styles and scripts for self-contained plugin
function acv_add_inline_assets() {
    ?>
    <style>
    .affiliate-coupon-vault { border: 2px dashed #007cba; padding: 20px; background: #f9f9f9; text-align: center; max-width: 400px; margin: 20px auto; border-radius: 10px; }
    .coupon-btn { background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold; }
    .coupon-btn:hover { background: #005a87; }
    .coupon-stats { margin-top: 10px; font-size: 14px; }
    </style>
    <script>
    jQuery(document).ready(function($) {
        $('.coupon-btn').click(function() {
            var $container = $(this).closest('.affiliate-coupon-vault');
            var count = parseInt($container.find('.click-count').text()) + 1;
            $container.find('.click-count').text(count);
            // Track click (Pro feature placeholder)
            console.log('Coupon clicked:', $container.data('affiliate'));
        });
    });
    </script>
    <?php
}
add_action('wp_head', 'acv_add_inline_assets');

// Freemium upsell notice
function acv_admin_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Upgrade for analytics, unlimited coupons & more! <a href="https://example.com/pro" target="_blank">Get Pro</a></p></div>';
}
add_action('admin_notices', 'acv_admin_notice');