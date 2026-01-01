/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons from popular networks to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupons', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_post_save_coupon_settings', array($this, 'save_settings'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('aff-coupon-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('aff-coupon-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'count' => 5,
            'category' => 'all'
        ), $atts);

        $settings = get_option('aff_coupon_settings', array('networks' => array('amazon', 'generic')));
        $coupons = $this->generate_coupons($atts['count'], $settings['networks']);

        ob_start();
        ?>
        <div class="aff-coupon-vault">
            <h3>Exclusive Deals & Coupons</h3>
            <?php foreach ($coupons as $coupon): ?>
            <div class="coupon-item">
                <h4><?php echo esc_html($coupon['title']); ?></h4>
                <p class="discount"><?php echo esc_html($coupon['discount']); ?></p>
                <div class="coupon-code"><?php echo esc_html($coupon['code']); ?></div>
                <a href="<?php echo esc_url($coupon['link']); ?}" class="coupon-btn" target="_blank" rel="nofollow">Shop Now & Save</a>
                <small>Code: <strong><?php echo esc_html($coupon['code']); ?></strong> | Expires: <?php echo esc_html($coupon['expires']); ?></small>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_coupons($count, $networks) {
        $coupons = array();
        $samples = array(
            'amazon' => array(
                array('title' => '50% Off Electronics', 'discount' => '50% OFF', 'code' => 'AFF50', 'link' => '#', 'expires' => '2026-01-31'),
                array('title' => '20% Off Books', 'discount' => '20% OFF', 'code' => 'BOOK20', 'link' => '#', 'expires' => '2026-02-15')
            ),
            'generic' => array(
                array('title' => 'Free Shipping', 'discount' => 'FREE SHIP', 'code' => 'SHIPFREE', 'link' => '#', 'expires' => '2026-01-30'),
                array('title' => '15% Sitewide', 'discount' => '15% OFF', 'code' => 'SITE15', 'link' => '#', 'expires' => '2026-02-01')
            )
        );

        foreach ($networks as $network) {
            if (isset($samples[$network])) {
                $coupons = array_merge($coupons, $samples[$network]);
            }
        }

        return array_slice($coupons, 0, $count);
    }

    public function admin_menu() {
        add_options_page(
            'Affiliate Coupon Vault Settings',
            'Coupon Vault',
            'manage_options',
            'aff-coupon-vault',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_GET['settings-updated'])) {
            add_settings_error('aff_coupon_messages', 'settings_updated', __('Settings saved.'), 'updated');
        }
        settings_errors('aff_coupon_messages');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('aff_coupon_settings', 'aff_coupon_nonce'); ?>
                <input type="hidden" name="action" value="save_coupon_settings">
                <table class="form-table">
                    <tr>
                        <th>Affiliate Networks</th>
                        <td>
                            <?php $settings = get_option('aff_coupon_settings', array('networks' => array('amazon', 'generic'))); ?>
                            <label><input type="checkbox" name="networks[]" value="amazon" <?php checked(in_array('amazon', $settings['networks'])); ?>> Amazon</label><br>
                            <label><input type="checkbox" name="networks[]" value="generic" <?php checked(in_array('generic', $settings['networks'])); ?>> Generic</label>
                            <p class="pro-notice">Pro: Unlock more networks like Shopify, Walmart</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Pro</strong> for unlimited coupons, click tracking, analytics, and premium integrations. <a href="#" target="_blank">Get Pro ($49/year)</a></p>
        </div>
        <?php
    }

    public function save_settings() {
        if (!isset($_POST['aff_coupon_nonce']) || !wp_verify_nonce($_POST['aff_coupon_nonce'], 'aff_coupon_settings')) {
            wp_die('Security check failed');
        }
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        $settings = array('networks' => isset($_POST['networks']) ? array_map('sanitize_text_field', $_POST['networks']) : array());
        update_option('aff_coupon_settings', $settings);
        wp_redirect(add_query_arg(array('settings-updated' => 'true'), wp_get_referer()));
        exit;
    }

    public function activate() {
        add_option('aff_coupon_settings', array('networks' => array('amazon', 'generic')));
    }
}

new AffiliateCouponVault();

// Pro upsell notice
function aff_coupon_admin_notice() {
    if (!current_user_can('manage_options')) return;
    $screen = get_current_screen();
    if ($screen->id == 'settings_page_aff-coupon-vault') return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock premium features! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
}
add_action('admin_notices', 'aff_coupon_admin_notice');

// Assets (in real plugin, create assets folder with script.js and style.css)
/*
assets/style.css:
.aff-coupon-vault { max-width: 600px; }
.coupon-item { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
.coupon-code { background: #f9f9f9; padding: 10px; font-family: monospace; }
.coupon-btn { background: #ff6b35; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; }
.pro-notice { color: #0073aa; font-style: italic; }

assets/script.js:
(jQuery(function($) {
    $('.coupon-btn').on('click', function() {
        $(this).text('Copied! Shop Now');
    });
}))
*/