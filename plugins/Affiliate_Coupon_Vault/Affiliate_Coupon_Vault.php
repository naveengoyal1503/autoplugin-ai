/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons from top networks, boosting conversions with personalized discount codes and tracking.
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
            'network' => 'amazon',
            'category' => 'all',
            'limit' => 5
        ), $atts);

        $options = get_option('affiliate_coupon_vault_options', array('api_key' => ''));
        $coupons = $this->generate_coupons($atts['network'], $atts['category'], $atts['limit']);

        ob_start();
        ?>
        <div class="affiliate-coupon-vault">
            <h3>Exclusive Deals</h3>
            <?php foreach ($coupons as $coupon): ?>
            <div class="coupon-item">
                <h4><?php echo esc_html($coupon['title']); ?></h4>
                <p>Code: <strong><?php echo esc_html($coupon['code']); ?></strong></p>
                <p>Discount: <?php echo esc_html($coupon['discount']); ?></p>
                <a href="<?php echo esc_url($coupon['link']); ?}" class="coupon-btn" target="_blank" rel="nofollow">Shop Now & Save</a>
                <span class="clicks"><?php echo intval($coupon['clicks']); ?> clicks</span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_coupons($network, $category, $limit) {
        // Demo coupons - Pro version integrates real APIs
        $demo_coupons = array(
            array('title' => '50% Off Hosting', 'code' => 'SAVE50', 'discount' => '50%', 'link' => 'https://example.com/hosting?aff=123', 'clicks' => 45),
            array('title' => 'Free Domain', 'code' => 'DOMAINFREE', 'discount' => 'Free', 'link' => 'https://example.com/domain?aff=123', 'clicks' => 23),
            array('title' => '20% Off Themes', 'code' => 'WP20', 'discount' => '20%', 'link' => 'https://example.com/themes?aff=123', 'clicks' => 67)
        );
        return array_slice($demo_coupons, 0, $limit);
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
    }

    public function admin_page() {
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
                        <th>Affiliate API Key</th>
                        <td><input type="text" name="affiliate_coupon_vault_options[api_key]" value="<?php echo esc_attr(get_option('affiliate_coupon_vault_options')['api_key'] ?? ''); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Features:</strong> Real-time coupon feeds, analytics, A/B testing. <a href="https://example.com/pro">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function activate() {
        add_option('affiliate_coupon_vault_options', array('api_key' => ''));
    }
}

// Initialize
AffiliateCouponVault::get_instance();

// Inline CSS
add_action('wp_head', function() { ?>
<style>
.affiliate-coupon-vault { max-width: 600px; margin: 20px 0; }
.coupon-item { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
.coupon-btn { background: #ff9900; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; }
.coupon-btn:hover { background: #e68900; }
.clicks { font-size: 12px; color: #666; }
</style>
<?php });

// Track clicks
add_action('wp_ajax_track_coupon_click', 'track_coupon_click');
add_action('wp_ajax_nopriv_track_coupon_click', 'track_coupon_click');
function track_coupon_click() {
    // Pro feature: Log clicks
    wp_die('');
}

// JS for click tracking
add_action('wp_footer', function() { ?>
<script>
jQuery(document).ready(function($) {
    $('.coupon-btn').click(function(e) {
        $.post(ajaxurl, {action: 'track_coupon_click'});
    });
});
</script>
<?php });