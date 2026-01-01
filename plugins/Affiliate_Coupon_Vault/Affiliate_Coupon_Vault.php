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
    exit;
}

class AffiliateCouponVault {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_acv_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_acv_track_click', array($this, 'track_click'));
        add_shortcode('acv_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons updated!</p></div>';
        }
        $coupons = get_option('acv_coupons', '{"example":{"title":"10% Off","afflink":"https://example.com","code":"SAVE10"}}');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <p><label>Coupons JSON (e.g. {"name":{"title":"Title","afflink":"URL","code":"CODE"}}):</label></p>
                <textarea name="coupons" rows="10" cols="80"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="submit"><input type="submit" name="submit" class="button-primary" value="Save"></p>
            </form>
            <p>Use shortcode: <code>[acv_coupon name="example"]</code></p>
            <p><strong>Pro Upgrade:</strong> Unlimited coupons, analytics dashboard, auto-expiry. <a href="https://example.com/pro">Get Pro ($49/yr)</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('name' => ''), $atts);
        $coupons = json_decode(get_option('acv_coupons', '{}'), true);
        if (!isset($coupons[$atts['name']])) {
            return 'Coupon not found.';
        }
        $coupon = $coupons[$atts['name']];
        ob_start();
        ?>
        <div class="acv-coupon" data-coupon="<?php echo esc_attr($atts['name']); ?>">
            <h3><?php echo esc_html($coupon['title']); ?></h3>
            <p>Code: <strong><?php echo esc_html($coupon['code']); ?></strong></p>
            <button class="acv-reveal">Reveal & Track</button>
            <div class="acv-afflink" style="display:none;"><?php echo esc_url($coupon['afflink']); ?></div>
        </div>
        <style>
        .acv-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; }
        .acv-reveal { background: #0073aa; color: white; border: none; padding: 10px 20px; cursor: pointer; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function track_click() {
        check_ajax_referer('acv_nonce', 'nonce');
        $coupon = sanitize_text_field($_POST['coupon']);
        // In pro version: Log to database, analytics
        error_log('ACV Click: ' . $coupon);
        $coupons = json_decode(get_option('acv_coupons', '{}'), true);
        if (isset($coupons[$coupon])) {
            wp_redirect($coupons[$coupon]['afflink']);
            exit;
        }
        wp_die();
    }

    public function activate() {
        add_option('acv_pro', false);
    }
}

AffiliateCouponVault::get_instance();

// Pro teaser
add_action('admin_notices', function() {
    if (!get_option('acv_pro') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock unlimited coupons & analytics for $49/yr! <a href="https://example.com/pro">Upgrade Now</a></p></div>';
    }
});

// JS file content (inline for single file)
function acv_inline_js() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.acv-reveal').click(function() {
            var $container = $(this).closest('.acv-coupon');
            var coupon = $container.data('coupon');
            var link = $container.find('.acv-afflink').text();
            $.post(acv_ajax.ajax_url, {
                action: 'acv_track_click',
                nonce: acv_ajax.nonce,
                coupon: coupon
            }, function() {
                window.location.href = link;
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'acv_inline_js');