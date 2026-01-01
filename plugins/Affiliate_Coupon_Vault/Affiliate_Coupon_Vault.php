/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with personalized promo codes, tracking clicks and conversions for maximum blog monetization.
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_acv_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_acv_track_click', array($this, 'track_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_menu() {
        if (!current_user_can('manage_options')) return;
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['acv_save'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', '[]');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupons (JSON format)</th>
                        <td><textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea><br>
                        Example: [{"name":"10% Off","code":"SAVE10","afflink":"https://affiliate.com/?ref=123","desc":"Exclusive deal!"}]</td>
                    </tr>
                </table>
                <?php submit_button('Save Coupons', 'primary', 'acv_save'); ?>
            </form>
            <h2>Shortcode</h2>
            <p>Use <code>[affiliate_coupon id="0"]</code> to display coupon #0 (0-indexed).</p>
            <p><strong>Pro Upgrade:</strong> Unlimited coupons, analytics dashboard, auto-expiration. <a href="#pro">Get Pro ($49/yr)</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = json_decode(get_option('acv_coupons', '[]'), true);
        if (!isset($coupons[$atts['id']])) {
            return '<p>No coupon found.</p>';
        }
        $coupon = $coupons[$atts['id']];
        $id = $atts['id'];
        ob_start();
        ?>
        <div class="acv-coupon" data-id="<?php echo $id; ?>">
            <h3><?php echo esc_html($coupon['name']); ?></h3>
            <p><?php echo esc_html($coupon['desc']); ?></p>
            <div class="acv-code"><?php echo esc_html($coupon['code']); ?></div>
            <a href="#" class="acv-button button">Get Deal (Track Click)</a>
            <div class="acv-afflink" style="display:none;"><?php echo esc_url($coupon['afflink']); ?></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function track_click() {
        check_ajax_referer('acv_nonce', 'nonce');
        $id = intval($_POST['id']);
        // Pro: Log to database
        error_log('ACV Click tracked: ' . $id);
        $coupons = json_decode(get_option('acv_coupons', '[]'), true);
        if (isset($coupons[$id])) {
            wp_redirect($coupons[$id]['afflink']);
            exit;
        }
        wp_die();
    }

    public function activate() {
        if (get_option('acv_coupons') === false) {
            update_option('acv_coupons', json_encode(array(
                array('name' => 'Demo 20% Off', 'code' => 'DEMO20', 'afflink' => '#', 'desc' => 'Limited time demo coupon')
            )));
        }
    }
}

// Create style.css content
$css = ".acv-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; border-radius: 8px; text-align: center; } .acv-code { font-size: 24px; font-weight: bold; color: #0073aa; margin: 10px 0; } .acv-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; } .acv-button:hover { background: #005a87; }";
file_put_contents(plugin_dir_path(__FILE__) . 'style.css', $css);

// Create script.js content
$js = "jQuery(document).ready(function($) { $('.acv-button').click(function(e) { e.preventDefault(); var $coupon = $(this).closest('.acv-coupon'); var id = $coupon.data('id'); var afflink = $coupon.find('.acv-afflink').text(); $.post(acv_ajax.ajax_url, { action: 'acv_track_click', id: id, nonce: acv_ajax.nonce }, function() { window.location = afflink; }); }); });";
file_put_contents(plugin_dir_path(__FILE__) . 'script.js', $js);

AffiliateCouponVault::get_instance();

// Pro nag
function acv_pro_nag() {
    if (!get_option('acv_pro')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock unlimited coupons & analytics for $49/yr! <a href="https://example.com/pro">Upgrade Now</a></p></div>';
        });
    }
}
add_action('plugins_loaded', 'acv_pro_nag');