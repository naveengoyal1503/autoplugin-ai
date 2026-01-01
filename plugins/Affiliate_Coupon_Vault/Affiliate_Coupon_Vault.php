/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with personalized promo codes to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
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
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_ajax_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
    }

    public function activate() {
        add_option('acv_coupons', array());
        add_option('acv_pro_activated', 'no');
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-frontend', plugin_dir_url(__FILE__) . 'acv.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-frontend', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('acv_settings', 'acv_coupons');
        register_setting('acv_settings', 'acv_pro_activated');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <?php if (get_option('acv_pro_activated') === 'no') : ?>
                <div class="notice notice-warning"><p><strong>Pro Upgrade:</strong> Unlock unlimited coupons for <a href="#" onclick="alert('Upgrade to Pro for $49/year')">$49/year</a></p></div>
            <?php endif; ?>
            <form method="post" action="options.php">
                <?php settings_fields('acv_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Coupons</th>
                        <td>
                            <textarea name="acv_coupons" rows="10" cols="50"><?php echo esc_textarea(get_option('acv_coupons', '[]')); ?></textarea>
                            <p class="description">JSON array: [{'name':'Brand','afflink':'https://aff.link','discount':'20%','code':'SAVE20'}]</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Add New Coupon</h2>
            <input type="text" id="coupon_name" placeholder="Brand Name">
            <input type="url" id="coupon_link" placeholder="Affiliate Link">
            <input type="text" id="coupon_discount" placeholder="Discount %">
            <button id="add_coupon">Add Coupon</button>
        </div>
        <script>
        jQuery(document).ready(function($){
            $('#add_coupon').click(function(){
                $.post(ajaxurl, {
                    action: 'acv_generate_coupon',
                    name: $('#coupon_name').val(),
                    link: $('#coupon_link').val(),
                    discount: $('#coupon_discount').val(),
                    nonce: acv_ajax.nonce
                }, function(resp){
                    alert('Coupon generated: ' + resp.code);
                });
            });
        });
        </script>
        <?php
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('acv_nonce', 'nonce');
        $name = sanitize_text_field($_POST['name']);
        $link = esc_url_raw($_POST['link']);
        $discount = sanitize_text_field($_POST['discount']);
        $code = strtoupper(substr(md5($name . time()), 0, 8));

        $coupons = json_decode(get_option('acv_coupons', '[]'), true);
        $coupons[] = array('name' => $name, 'afflink' => $link, 'discount' => $discount, 'code' => $code);
        update_option('acv_coupons', json_encode($coupons));

        if (get_option('acv_pro_activated') === 'no' && count($coupons) > 3) {
            wp_die('Upgrade to Pro for more coupons!');
        }

        wp_send_json_success(array('code' => $code));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = json_decode(get_option('acv_coupons', '[]'), true);
        if (empty($coupons)) return '<p>No coupons available. <a href="' . admin_url('options-general.php?page=affiliate-coupon-vault') . '">Add some</a>.</p>';

        $coupon = $coupons[array_rand($coupons)];
        ob_start();
        ?>
        <div class="acv-coupon" style="border:2px dashed #0073aa; padding:20px; margin:10px 0; background:#f9f9f9;">
            <h3><?php echo esc_html($coupon['name']); ?> Exclusive Deal!</h3>
            <p><strong><?php echo esc_html($coupon['discount']); ?> OFF</strong></p>
            <p><strong>Code: <?php echo esc_html($coupon['code']); ?></strong></p>
            <a href="<?php echo esc_url($coupon['afflink']); ?>&coupon=<?php echo esc_attr($coupon['code']); ?>" target="_blank" class="button button-large" style="background:#0073aa; color:white; padding:10px 20px; text-decoration:none;">Grab Deal Now (Affiliate Link)</a>
        </div>
        <?php
        return ob_get_clean();
    }
}

AffiliateCouponVault::get_instance();

// Minified JS
/*
(function($){$(document).on('click','.acv-copy-code',function(){var code=$(this).data('code');navigator.clipboard.writeText(code).then(function(){$(this).text('Copied!');});});})(jQuery);
*/
?>