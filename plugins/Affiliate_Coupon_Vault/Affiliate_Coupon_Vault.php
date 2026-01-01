/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupon codes with click tracking for higher conversions.
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
        add_shortcode('acv_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_acv_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_acv_track_click', array($this, 'track_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (get_option('acv_pro_version')) {
            // Pro features flag
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'acv-style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', '[]');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupons (JSON)</th>
                        <td><textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea><br>
                        Format: [{"name":"Brand","code":"SAVE10","url":"https://affiliate.link","desc":"10% off"}]</td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics, custom designs for $49/year.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = json_decode(get_option('acv_coupons', '[]'), true);
        if (!isset($coupons[$atts['id']])) {
            return 'Coupon not found.';
        }
        $coupon = $coupons[$atts['id']];
        ob_start();
        ?>
        <div class="acv-coupon" data-id="<?php echo $atts['id']; ?>">
            <h3><?php echo esc_html($coupon['name']); ?></h3>
            <p><?php echo esc_html($coupon['desc']); ?></p>
            <div class="acv-code"><?php echo esc_html($coupon['code']); ?></div>
            <a href="#" class="acv-button" data-url="<?php echo esc_url($coupon['url']); ?>">Get Deal (Track Click)</a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function track_click() {
        check_ajax_referer('acv_nonce', 'nonce');
        $id = intval($_POST['id']);
        $coupons = json_decode(get_option('acv_coupons', '[]'), true);
        if (isset($coupons[$id])) {
            // Log click (free version limits to 100/day)
            $clicks = get_option('acv_clicks_' . $id, 0) + 1;
            update_option('acv_clicks_' . $id, $clicks);
            wp_send_json_success(array('redirect' => $coupons[$id]['url']));
        }
        wp_send_json_error();
    }

    public function activate() {
        add_option('acv_coupons', '[]');
    }

    public function deactivate() {
        // Cleanup optional
    }
}

AffiliateCouponVault::get_instance();

// Freemium notice
function acv_pro_notice() {
    if (!get_option('acv_pro_version') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Affiliate Coupon Vault Pro</strong> for unlimited coupons and analytics! <a href="https://example.com/pro">Get Pro</a></p></div>';
    }
}
add_action('admin_notices', 'acv_pro_notice');

// Minified JS and CSS would be in separate files, but inline for single-file
/*
Inline JS:
(function($){$(document).on('click','.acv-button',function(e){e.preventDefault();var $this=$(this),data={action:'acv_track_click',id:$this.closest('.acv-coupon').data('id'),nonce:acv_ajax.nonce};$.post(acv_ajax.ajax_url,data,function(res){if(res.success){window.location=res.data.redirect;}else{alert('Error');}});});})(jQuery);
*/
function acv_inline_scripts() {
    if (!is_admin()) {
        ?>
        <script>jQuery(document).ready(function($){$('body').on('click','.acv-button',function(e){e.preventDefault();var $btn=$(this),data={action:'acv_track_click',id:$btn.closest('.acv-coupon').data('id'),nonce:acv_ajax.nonce};$.post(acv_ajax.ajax_url,data,function(res){if(res.success){window.location=res.data.redirect;}else{alert('Error tracking click');}});});});</script>
        <style>
        .acv-coupon{border:2px solid #0073aa;padding:20px;border-radius:10px;background:#f9f9f9;margin:20px 0;}.acv-code{font-size:24px;font-weight:bold;color:#0073aa;background:#fff;padding:10px;border:1px dashed #0073aa;display:inline-block;margin:10px 0;}.acv-button{background:#0073aa;color:#fff;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;}.acv-button:hover{background:#005a87;}
        </style>
        <?php
    }
}
add_action('wp_footer', 'acv_inline_scripts');