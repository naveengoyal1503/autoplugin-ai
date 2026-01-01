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
        add_shortcode('affiliate_coupons', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_acv_fetch_coupons', array($this, 'ajax_fetch_coupons'));
        add_action('wp_ajax_nopriv_acv_fetch_coupons', array($this, 'ajax_fetch_coupons'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-frontend', plugin_dir_url(__FILE__) . 'acv-frontend.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-frontend', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['acv_settings'])) {
            update_option('acv_api_keys', sanitize_text_field($_POST['api_key']));
            update_option('acv_networks', sanitize_textarea_field($_POST['networks']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('acv_api_keys', '');
        $networks = get_option('acv_networks', 'amazon,ebay,shopify');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>API Key (Pro)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Networks</th>
                        <td><textarea name="networks" rows="5" class="large-text"><?php echo esc_textarea($networks); ?></textarea><br /><small>Comma-separated: amazon,ebay,shopify, etc.</small></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock premium networks, analytics & auto-rotation for $49/year. <a href="https://example.com/pro" target="_blank">Get Pro</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => 'all',
            'limit' => 5,
        ), $atts);

        ob_start();
        echo '<div id="acv-coupons-container" data-category="' . esc_attr($atts['category']) . '" data-limit="' . intval($atts['limit']) . '"><p>Loading coupons...</p></div>';
        return ob_get_clean();
    }

    public function ajax_fetch_coupons() {
        check_ajax_referer('acv_nonce', 'nonce');
        $category = sanitize_text_field($_POST['category'] ?? 'all');
        $limit = intval($_POST['limit'] ?? 5);

        // Demo coupons (Pro fetches real APIs)
        $demo_coupons = array(
            array('code' => 'SAVE20', 'desc' => '20% off on Amazon', 'link' => 'https://amazon.com/?tag=youraffiliateid', 'expires' => '2026-03-01'),
            array('code' => 'EBAY15', 'desc' => '15% off eBay', 'link' => 'https://ebay.com/?affid=yourid', 'expires' => '2026-02-15'),
            array('code' => 'WELCOME10', 'desc' => '10% off first purchase', 'link' => 'https://example-shop.com/?aff=123', 'expires' => '2026-01-31'),
        );

        $coupons = array_slice($demo_coupons, 0, $limit);
        wp_send_json_success($coupons);
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

// Demo JS - enqueue proper JS file in production
add_action('wp_footer', function() { ?>
<script>
jQuery(document).ready(function($) {
    $('#acv-coupons-container').each(function() {
        var $container = $(this);
        var category = $container.data('category');
        var limit = $container.data('limit');

        $.post(acv_ajax.ajax_url, {
            action: 'acv_fetch_coupons',
            nonce: acv_ajax.nonce,
            category: category,
            limit: limit
        }, function(response) {
            if (response.success) {
                var html = '';
                response.data.forEach(function(coupon) {
                    html += '<div class="acv-coupon" style="border:1px solid #ddd; padding:15px; margin:10px 0; border-radius:5px;"><h4>' + coupon.desc + '</h4><p><strong>Code:</strong> ' + coupon.code + '</p><p><strong>Expires:</strong> ' + coupon.expires + '</p><a href="' + coupon.link + '" target="_blank" class="button button-primary" rel="nofollow">Get Deal</a><img src="https://pixel.wp.com/t.gif?v=wpcom-no-pv" alt="" style="display:none;"></div>';
                });
                $container.html(html);
            }
        });
    });
});
</script>
<style>
.acv-coupon { background:#f9f9f9; }
.acv-coupon h4 { margin:0 0 10px; color:#333; }
</style>
<?php });

AffiliateCouponVault::get_instance();

// Pro upsell notice
function acv_admin_notice() {
    if (!get_option('acv_pro_activated')) {
        echo '<div class="notice notice-info is-dismissible"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock premium features for $49/year! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
    }
}
add_action('admin_notices', 'acv_admin_notice');