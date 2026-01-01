/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupon codes with tracking to boost conversions.
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
        add_action('wp_ajax_save_coupon_click', array($this, 'track_coupon_click'));
        add_action('wp_ajax_nopriv_save_coupon_click', array($this, 'track_coupon_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
        }
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('affiliate-coupon-vault', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['acv_settings'])) {
            update_option('acv_api_key', sanitize_text_field($_POST['api_key']));
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('acv_api_key', '');
        $coupons = get_option('acv_coupons', '{"demo":"DEMO20"}');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>API Key (Pro)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Coupons JSON</th>
                        <td><textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea><br><small>e.g. {"brand1":"CODE20","brand2":"SAVE10"}</small></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics, and integrations for $49/year.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'brand' => 'demo',
            'afflink' => '#',
            'discount' => '20%'
        ), $atts);

        $coupons = json_decode(get_option('acv_coupons', '{"demo":"DEMO20"}'), true);
        $code = isset($coupons[$atts['brand']]) ? $coupons[$atts['brand']] : 'SAVE' . rand(10,50);

        ob_start();
        ?>
        <div class="acv-coupon" data-brand="<?php echo esc_attr($atts['brand']); ?>" data-afflink="<?php echo esc_url($atts['afflink']); ?>">
            <div class="acv-code"><?php echo esc_html($code); ?></div>
            <div class="acv-discount"><?php echo esc_html($atts['discount']); ?> OFF</div>
            <button class="acv-copy" onclick="copyCoupon(this)">Copy Code</button>
            <a href="#" class="acv-use" onclick="useCoupon(event, this)">Use Coupon</a>
            <div class="acv-tracked">Tracked! <span class="acv-count">0</span> uses</div>
        </div>
        <style>
        .acv-coupon { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9; }
        .acv-code { font-size: 2em; font-weight: bold; color: #007cba; }
        .acv-copy, .acv-use { background: #007cba; color: white; padding: 10px 20px; margin: 10px; border: none; cursor: pointer; }
        .acv-tracked { font-size: 0.9em; color: #666; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function track_coupon_click() {
        if (!wp_verify_nonce($_POST['nonce'], 'acv_nonce')) {
            wp_die('Security check failed');
        }
        $brand = sanitize_text_field($_POST['brand']);
        $count = get_option('acv_clicks_' . $brand, 0) + 1;
        update_option('acv_clicks_' . $brand, $count);
        wp_send_json_success(array('count' => $count));
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', json_encode(array('demo' => 'DEMO20')));
        }
    }

    public function deactivate() {}
}

AffiliateCouponVault::get_instance();

// Pro upsell notice
function acv_admin_notice() {
    if (!get_option('acv_pro_activated')) {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock premium features for $49/year! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
    }
}
add_action('admin_notices', 'acv_admin_notice');

// JS needs to be enqueued properly, but for single file, inline it
add_action('wp_footer', function() {
    if (is_singular() && has_shortcode(get_post()->post_content, 'affiliate_coupon')) {
        ?>
        <script>
        function copyCoupon(btn) {
            var code = btn.parentNode.querySelector('.acv-code').textContent;
            navigator.clipboard.writeText(code).then(function() {
                alert('Copied: ' + code);
            });
        }
        function useCoupon(e, btn) {
            e.preventDefault();
            var coupon = btn.closest('.acv-coupon');
            var brand = coupon.dataset.brand;
            var afflink = coupon.dataset.afflink;
            jQuery.post(acv_ajax.ajax_url, {
                action: 'save_coupon_click',
                nonce: '<?php echo wp_create_nonce('acv_nonce'); ?>',
                brand: brand
            }, function(resp) {
                if (resp.success) {
                    coupon.querySelector('.acv-count').textContent = resp.data.count;
                    window.open(afflink, '_blank');
                }
            });
        }
        </script>
        <?php
    }
});