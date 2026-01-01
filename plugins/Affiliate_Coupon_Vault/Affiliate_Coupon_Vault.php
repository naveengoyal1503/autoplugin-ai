/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically fetches, displays, and tracks exclusive affiliate coupons from multiple networks to boost conversions and commissions.
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('acv_coupons', array($this, 'coupons_shortcode'));
        add_action('wp_ajax_acv_track_click', array($this, 'track_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
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
        register_setting('acv_settings', 'acv_api_keys');
        register_setting('acv_settings', 'acv_pro_active', array('type' => 'boolean', 'default' => false));
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Affiliate Coupon Vault Settings', 'affiliate-coupon-vault'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('acv_settings'); ?>
                <?php do_settings_sections('acv_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th><?php _e('API Keys (Pro Feature)', 'affiliate-coupon-vault'); ?></th>
                        <td>
                            <input type="text" name="acv_api_keys[cj]" value="<?php echo esc_attr(get_option('acv_api_keys')['cj'] ?? ''); ?>" placeholder="Commission Junction API" class="regular-text" />
                            <p class="description">Enter Pro API keys for live data. <a href="#" onclick="alert('Upgrade to Pro for unlimited integrations!')">Upgrade to Pro</a></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Pro Status', 'affiliate-coupon-vault'); ?></th>
                        <td><input type="checkbox" name="acv_pro_active" <?php checked(get_option('acv_pro_active')); ?> disabled /> <em>Pro activated</em></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics, and premium networks for $49/year.</p>
        </div>
        <?php
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => 'all',
            'limit' => 5
        ), $atts);

        $coupons = $this->get_demo_coupons($atts['limit']);
        ob_start();
        ?>
        <div class="acv-coupons-grid">
            <?php foreach ($coupons as $coupon): ?>
                <div class="acv-coupon-card">
                    <h3><?php echo esc_html($coupon['title']); ?></h3>
                    <p><?php echo esc_html($coupon['description']); ?></p>
                    <div class="acv-coupon-code"><?php echo esc_html($coupon['code']); ?></div>
                    <a href="#" class="acv-btn" data-aff-link="<?php echo esc_attr($coupon['aff_link']); ?>" data-id="<?php echo esc_attr($coupon['id']); ?>"><?php _e('Get Deal', 'affiliate-coupon-vault'); ?> (<?php echo esc_html($coupon['discount']); ?>)</a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_demo_coupons($limit = 5) {
        // Demo data; Pro fetches live from APIs
        return array_slice([
            ['id' => 1, 'title' => '50% Off Hosting', 'description' => 'Bluehost exclusive deal', 'code' => 'AFF50', 'discount' => '50%', 'aff_link' => 'https://example.com/aff/bluehost'],
            ['id' => 2, 'title' => 'WordPress Themes 30% Off', 'description' => 'Premium themes discount', 'code' => 'WP30', 'discount' => '30%', 'aff_link' => 'https://example.com/aff/themes'],
            ['id' => 3, 'title' => 'SEO Tools Free Trial', 'description' => 'Ahrefs starter pack', 'code' => 'SEO2026', 'discount' => 'Free Trial', 'aff_link' => 'https://example.com/aff/seo'],
            ['id' => 4, 'title' => 'Email Marketing 20% Off', 'description' => 'Mailchimp promo', 'code' => 'EMAIL20', 'discount' => '20%', 'aff_link' => 'https://example.com/aff/email'],
            ['id' => 5, 'title' => 'Plugin Bundle Deal', 'description' => 'Essential WP plugins', 'code' => 'PLUGINS', 'discount' => '40%', 'aff_link' => 'https://example.com/aff/plugins'],
        ], 0, $limit);
    }

    public function track_click() {
        check_ajax_referer('acv_nonce', 'nonce');
        $id = intval($_POST['id']);
        $link = sanitize_url($_POST['link']);
        // Log click (Pro: advanced analytics)
        error_log('ACV Click: ID ' . $id . ' -> ' . $link);
        wp_redirect($link);
        exit;
    }

    public function activate() {
        add_option('acv_pro_active', false);
    }
}

// Include CSS
/*
.acv-coupons-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
.acv-coupon-card { border: 1px solid #ddd; padding: 20px; border-radius: 8px; background: #fff; }
.acv-coupon-code { background: #f0f0f0; padding: 10px; font-family: monospace; }
.acv-btn { background: #0073aa; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; }
.acv-btn:hover { background: #005a87; }
*/

// Include JS
/*
jQuery(document).ready(function($) {
    $('.acv-btn').click(function(e) {
        e.preventDefault();
        var btn = $(this);
        var id = btn.data('id');
        var link = btn.data('aff-link');
        $.post(acv_ajax.ajax_url, {
            action: 'acv_track_click',
            nonce: acv_ajax.nonce,
            id: id,
            link: link
        }, function() {
            window.open(link, '_blank');
        });
    });
});
*/

AffiliateCouponVault::get_instance();

// Pro upsell notice
function acv_admin_notice() {
    if (!get_option('acv_pro_active')) {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault:</strong> Unlock Pro for live APIs & analytics! <a href="options-general.php?page=affiliate-coupon-vault">Upgrade now</a></p></div>';
    }
}
add_action('admin_notices', 'acv_admin_notice');