/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and manages exclusive affiliate coupons for your WordPress site, boosting conversions with personalized discount codes and tracking.
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function activate() {
        add_option('acv_coupons', array());
        add_option('acv_pro_version', false);
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
        register_setting('acv_settings', 'acv_coupons');
        register_setting('acv_settings', 'acv_pro_version');
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_text_field($_POST['acv_coupons']));
            echo '<div class="notice notice-success"><p>Coupons updated!</p></div>';
        }
        $coupons = get_option('acv_coupons', array());
        $pro = get_option('acv_pro_version', false);
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault</h1>
            <form method="post" action="">
                <?php settings_fields('acv_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Coupons (JSON format: {"code":"discount","afflink":"url"})</th>
                        <td><textarea name="acv_coupons" rows="10" cols="50"><?php echo esc_textarea(json_encode($coupons)); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Pro Version</th>
                        <td><input type="checkbox" name="acv_pro_version" <?php checked($pro); ?> value="1"> Unlock unlimited coupons & tracking</td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Shortcode:</strong> [affiliate_coupon id="1"]</p>
            <?php if (!$pro) { ?>
            <p><a href="#" onclick="alert('Upgrade to Pro for $49/year!')">Upgrade to Pro</a></p>
            <?php } ?>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => '1'), $atts);
        $coupons = get_option('acv_coupons', array());
        $pro = get_option('acv_pro_version', false);
        if (!$pro && count($coupons) > 2) {
            return '<p>Upgrade to Pro for more coupons!</p>';
        }
        if (isset($coupons[$atts['id']])) {
            $coupon = $coupons[$atts['id']];
            $clicks = get_option('acv_clicks_' . $atts['id'], 0);
            return '<div class="acv-coupon"><p><strong>Exclusive Coupon:</strong> ' . esc_html($coupon['code']) . ' - Save ' . esc_html($coupon['discount']) . '%</p><a href="' . esc_url($coupon['afflink']) . '" class="button acv-btn" target="_blank" onclick="acvTrack(' . $atts['id'] . ')">Get Deal (Tracked: ' . $clicks . ' clicks)</a></div>';
        }
        return '<p>No coupon found.</p>';
    }
}

// AJAX tracking
add_action('wp_ajax_acv_track', 'acv_track_click');
add_action('wp_ajax_nopriv_acv_track', 'acv_track_click');
function acv_track_click() {
    check_ajax_referer('acv_nonce', 'nonce');
    $id = intval($_POST['id']);
    $clicks = get_option('acv_clicks_' . $id, 0) + 1;
    update_option('acv_clicks_' . $id, $clicks);
    wp_die(json_encode(array('clicks' => $clicks)));
}

// JS file content (embedded for single file)
function acv_js_embed() {
    ?><script>function acvTrack(id) { jQuery.post(acv_ajax.ajax_url, {action: 'acv_track', id: id, nonce: acv_ajax.nonce}, function(r) { console.log('Tracked:', r.clicks); }); }</script><?php
}
add_action('wp_footer', 'acv_js_embed');

AffiliateCouponVault::get_instance();

// Pro upsell notice
add_action('admin_notices', function() {
    if (!get_option('acv_pro_version')) {
        echo '<div class="notice notice-info"><p>Affiliate Coupon Vault Pro: Unlock unlimited coupons for $49/year! <a href="options-general.php?page=affiliate-coupon-vault">Upgrade Now</a></p></div>';
    }
});