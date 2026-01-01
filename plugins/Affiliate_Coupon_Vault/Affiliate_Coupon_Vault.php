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
        if (is_admin()) {
            $this->check_pro_status();
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('aff-coupon-vault-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('aff-coupon-vault-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate' => 'default',
            'discount' => '10%',
            'code' => 'SAVE' . wp_generate_password(4, false),
            'link' => 'https://example.com',
            'limit' => 1
        ), $atts);

        $options = get_option('affiliate_coupon_vault_options', array());
        $pro = get_option('affiliate_coupon_vault_pro', false);

        if (!$pro && $atts['limit'] > 1) {
            return '<p>Upgrade to Pro for unlimited coupons.</p>';
        }

        ob_start();
        ?>
        <div class="aff-coupon-vault" data-affiliate="<?php echo esc_attr($atts['affiliate']); ?>">
            <div class="coupon-header">Exclusive Deal: <strong><?php echo esc_html($atts['discount']); ?> Off!</strong></div>
            <div class="coupon-code"><?php echo esc_html($atts['code']); ?></div>
            <a href="<?php echo esc_url($atts['link']); ?><?php echo strpos($atts['link'], '?') === false ? '?ref=' . get_bloginfo('url') : '&ref=' . get_bloginfo('url'); ?>" class="coupon-button" target="_blank">Shop Now & Save</a>
            <small>Limited time offer. <?php echo $pro ? '' : 'Free version - 1 coupon limit.'; ?></small>
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
        register_setting('affiliate_coupon_vault_options', 'affiliate_coupon_vault_options');
        register_setting('affiliate_coupon_vault_options', 'affiliate_coupon_vault_pro');
    }

    public function admin_page() {
        if (isset($_POST['pro_license'])) {
            update_option('affiliate_coupon_vault_pro', true);
            echo '<div class="notice notice-success"><p>Pro activated!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('affiliate_coupon_vault_options');
                do_settings_sections('affiliate_coupon_vault_options');
                ?>
                <table class="form-table">
                    <tr>
                        <th>Default Affiliate Link</th>
                        <td><input type="url" name="affiliate_coupon_vault_options[default_link]" value="<?php echo esc_attr(get_option('affiliate_coupon_vault_options')['default_link'] ?? ''); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Go Pro</h2>
            <p>Unlock unlimited coupons, analytics, and more for $49/year.</p>
            <form method="post">
                <p><input type="submit" name="pro_license" class="button button-primary" value="Activate Pro (Demo)" /></p>
            </form>
        </div>
        <?php
    }

    private function check_pro_status() {
        // Pro check logic
    }

    public function activate() {
        add_option('affiliate_coupon_vault_options', array('default_link' => ''));
    }
}

// Create assets directories if they don't exist
$upload_dir = wp_upload_dir();
$assets_dir = plugin_dir_path(__FILE__) . 'assets';
if (!file_exists($assets_dir)) {
    wp_mkdir_p($assets_dir);
}

// Minimal JS
$js_content = "jQuery(document).ready(function($) {
    $('.aff-coupon-vault .coupon-code').click(function() {
        var code = $(this).text();
        navigator.clipboard.writeText(code).then(function() {
            $(this).append('<span>Copied!</span>');
        }.bind(this));
    });
});";
file_put_contents($assets_dir . '/script.js', $js_content);

// Minimal CSS
$css_content = ".aff-coupon-vault { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9; }
.coupon-header { font-size: 1.2em; margin-bottom: 10px; }
.coupon-code { background: #0073aa; color: white; padding: 10px; font-size: 1.5em; font-weight: bold; cursor: pointer; display: inline-block; margin: 10px 0; }
.coupon-button { background: #ff6b00; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; }";
file_put_contents($assets_dir . '/style.css', $css_content);

AffiliateCouponVault::get_instance();