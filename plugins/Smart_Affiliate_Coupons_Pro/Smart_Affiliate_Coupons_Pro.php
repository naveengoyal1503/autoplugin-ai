/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupons Pro
 * Plugin URI: https://example.com/smart-affiliate-coupons
 * Description: Automatically generates and displays personalized affiliate coupon codes with tracking to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-coupons
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateCoupons {
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
        add_action('wp_ajax_save_coupon', array($this, 'ajax_save_coupon'));
        add_action('wp_ajax_nopriv_save_coupon', array($this, 'ajax_save_coupon'));
        add_shortcode('sac_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-coupons', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sac-script', plugin_dir_url(__FILE__) . 'sac-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sac-script', 'sac_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sac_nonce')));
        wp_enqueue_style('sac-style', plugin_dir_url(__FILE__) . 'sac-style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Coupons', 'SAC Coupons', 'manage_options', 'sac-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['sac_save'])) {
            update_option('sac_coupons', sanitize_text_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('sac_coupons', '{"coupon1":{"code":"SAVE10","afflink":"https://example.com","desc":"10% off"}}');
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Coupons Settings</h1>
            <form method="post">
                <p><label>Coupons JSON (code, afflink, desc):</label></p>
                <textarea name="coupons" rows="10" cols="80"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="submit"><input type="submit" name="sac_save" class="button-primary" value="Save Coupons"></p>
            </form>
            <p>Use shortcode: <code>[sac_coupon id="coupon1"]</code></p>
            <p><strong>Pro Features:</strong> Unlimited coupons, analytics, custom designs (Upgrade for $49/year).</p>
        </div>
        <?php
    }

    public function ajax_save_coupon() {
        check_ajax_referer('sac_nonce', 'nonce');
        $code = sanitize_text_field($_POST['code']);
        setcookie('sac_coupon_' . $code, '1', time() + 86400, '/');
        wp_send_json_success('Coupon applied!');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 'coupon1'), $atts);
        $coupons = json_decode(get_option('sac_coupons', '{}'), true);
        if (!isset($coupons[$atts['id']])) {
            return 'Coupon not found.';
        }
        $coupon = $coupons[$atts['id']];
        $code = $coupon['code'];
        $used = isset($_COOKIE['sac_coupon_' . $code]);
        ob_start();
        ?>
        <div class="sac-coupon" id="sac-<?php echo esc_attr($atts['id']); ?>">
            <?php if (!$used): ?>
            <div class="sac-code"><?php echo esc_html($code); ?></div>
            <p><?php echo esc_html($coupon['desc']); ?></p>
            <button class="sac-claim button" data-code="<?php echo esc_attr($code); ?>">Claim Coupon</button>
            <?php else: ?>
            <p>Coupon already claimed! <a href="<?php echo esc_url($coupon['afflink']); ?>" target="_blank" rel="nofollow">Shop Now</a></p>
            <?php endif; ?>
            <a href="<?php echo esc_url($coupon['afflink']); ?}" class="sac-link" target="_blank" rel="nofollow">Affiliate Link</a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('sac_coupons')) {
            update_option('sac_coupons', '{"coupon1":{"code":"SAVE10","afflink":"https://example.com","desc":"Get 10% off your purchase!"}}');
        }
    }
}

SmartAffiliateCoupons::get_instance();

// Inline JS and CSS for self-contained
add_action('wp_head', function() {
    echo '<style>
.sac-coupon { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9; }
.sac-code { font-size: 2em; font-weight: bold; color: #007cba; }
.sac-claim { background: #007cba; color: white; padding: 10px 20px; border: none; cursor: pointer; }
.sac-claim:hover { background: #005a87; }
.sac-link { display: inline-block; margin-top: 10px; color: #007cba; text-decoration: none; }
    </style>
    <script>jQuery(document).ready(function($){$(".sac-claim").click(function(){var e=$(this),t=e.data("code");$.post(sac_ajax.ajax_url,{action:"save_coupon",code:t,nonce:sac_ajax.nonce},function(t){t.success?(e.prop("disabled",!0).text("Claimed!"),setTimeout(function(){location.reload()},1500)):alert("Error")})})});</script>';
});