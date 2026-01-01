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
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate' => '',
            'code' => '',
            'discount' => '10%',
            'link' => '',
            'expires' => '',
        ), $atts);

        $settings = get_option('affiliate_coupon_vault_settings', array());
        $pro_version = isset($settings['pro_license']) && !empty($settings['pro_license']);

        if (!$pro_version && $this->coupon_count() >= 5) {
            return '<p>Upgrade to Pro for unlimited coupons!</p>';
        }

        ob_start();
        ?>
        <div class="affiliate-coupon-vault" data-affiliate="<?php echo esc_attr($atts['affiliate']); ?>">
            <div class="coupon-header">
                <h3>Exclusive Deal: <span class="discount"><?php echo esc_html($atts['discount']); ?> OFF</span></h3>
            </div>
            <div class="coupon-code"><?php echo esc_html($atts['code']); ?></div>
            <a href="<?php echo esc_url($atts['link']); ?}" class="coupon-button" target="_blank">Shop Now & Save</a>
            <?php if (!empty($atts['expires'])): ?>
            <p class="expires">Expires: <?php echo esc_html($atts['expires']); ?></p>
            <?php endif; if (!$pro_version): ?>
            <p><small><a href="#pro-upgrade">Pro: Analytics & More</a></small></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function coupon_count() {
        global $post;
        if (!$post) return 0;
        preg_match_all('/\[affiliate_coupon.*?\]/', $post->post_content, $matches);
        return count($matches);
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
        register_setting('affiliate_coupon_vault_settings', 'affiliate_coupon_vault_settings');
    }

    public function admin_page() {
        if (isset($_POST['pro_license'])) {
            update_option('affiliate_coupon_vault_settings', array('pro_license' => sanitize_text_field($_POST['pro_license'])));
            echo '<div class="notice notice-success"><p>Pro activated!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="">
                <?php settings_fields('affiliate_coupon_vault_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Pro License Key</th>
                        <td><input type="text" name="pro_license" value="" placeholder="Enter Pro key for unlimited features" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button('Activate Pro'); ?>
            </form>
            <p><strong>Free Usage:</strong> Up to 5 coupons per post. <a href="https://example.com/pro" target="_blank">Upgrade to Pro ($49/yr)</a> for unlimited, analytics, auto-generation.</p>
        </div>
        <?php
    }

    public function activate() {
        add_option('affiliate_coupon_vault_settings', array());
    }
}

// Create CSS file content
$css = ".affiliate-coupon-vault { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; text-align: center; border-radius: 10px; } .coupon-code { font-size: 2em; font-weight: bold; color: #0073aa; background: white; padding: 10px; margin: 10px 0; border-radius: 5px; } .coupon-button { display: inline-block; background: #0073aa; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; } .coupon-button:hover { background: #005a87; } .discount { color: #ff0000; }";
file_put_contents(plugin_dir_path(__FILE__) . 'style.css', $css);

// Create JS file content
$js = "jQuery(document).ready(function($) { $('.affiliate-coupon-vault .coupon-code').click(function() { var $code = $(this); navigator.clipboard.writeText($code.text()).then(function() { $code.css('background', '#d4edda').text('Copied!'); setTimeout(function() { $code.css('background', 'white').text($code.attr('data-original') || $code.text().replace('Copied!', '')); }, 2000); }); }); });";
file_put_contents(plugin_dir_path(__FILE__) . 'script.js', $js);

AffiliateCouponVault::get_instance();

// Pro upgrade nag
function affiliate_coupon_vault_nag() {
    if (!current_user_can('manage_options')) return;
    $settings = get_option('affiliate_coupon_vault_settings');
    if (empty($settings['pro_license'])) {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault:</strong> Unlock unlimited coupons & analytics! <a href="' . admin_url('options-general.php?page=affiliate-coupon-vault') . '">Upgrade now</a></p></div>';
    }
}
add_action('admin_notices', 'affiliate_coupon_vault_nag');