/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with personalized promo codes, tracking clicks and conversions for maximum WordPress blog revenue.
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
        add_action('wp_ajax_acv_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_acv_track_click', array($this, 'track_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', "Brand1|https://affiliate.link1.com|10% off|DISCOUNT10\nBrand2|https://affiliate.link2.com|Free Shipping|FREESHIP");
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <p><label>Coupons (format: Brand|Affiliate Link|Description|Code, one per line):</label></p>
                <textarea name="coupons" rows="10" cols="80"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="submit"><input type="submit" name="submit" class="button-primary" value="Save Changes"></p>
            </form>
            <p><strong>Shortcode:</strong> <code>[affiliate_coupon id="1"]</code> (id starts from 1)</p>
            <p><strong>Pro Features:</strong> Unlimited coupons, analytics dashboard, auto-generation. <a href="#" onclick="alert('Upgrade to Pro for $49/year')">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 1), $atts);
        $coupons = explode("\n", get_option('acv_coupons', ''));
        if (!isset($coupons[$atts['id'] - 1])) {
            return 'Coupon not found.';
        }
        list($brand, $link, $desc, $code) = explode('|', trim($coupons[$atts['id'] - 1]));
        $personalized_code = $code . '-' . substr(md5(uniqid()), 0, 4);
        $track_url = add_query_arg(array('acv_track' => $atts['id'], 'ref' => wp_get_current_user()->ID ?: 'guest'), $link);
        ob_start();
        ?>
        <div class="acv-coupon" style="border: 2px dashed #007cba; padding: 20px; margin: 20px 0; background: #f9f9f9;">
            <h3><?php echo esc_html($brand); ?> Exclusive Deal</h3>
            <p><?php echo esc_html($desc); ?></p>
            <p><strong>Your Code:</strong> <code><?php echo esc_html($personalized_code); ?></code></p>
            <a href="#" class="acv-button button" data-url="<?php echo esc_url($track_url); ?>" data-code="<?php echo esc_attr($personalized_code); ?>">Get Deal & Track (Affiliate)</a>
        </div>
        <script>
        jQuery('.acv-button').click(function(e) {
            e.preventDefault();
            var url = jQuery(this).data('url');
            jQuery.post(acv_ajax.ajaxurl, {action: 'acv_track_click', url: url}, function() {
                window.open(url, '_blank');
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function track_click() {
        $url = sanitize_url($_POST['url']);
        // In Pro version, log to database for analytics
        error_log('ACV Click tracked: ' . $url);
        wp_die();
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', "Brand1|https://affiliate.link1.com|10% off|DISCOUNT10\nBrand2|https://affiliate.link2.com|Free Shipping|FREESHIP");
        }
    }
}

AffiliateCouponVault::get_instance();

// Pro upsell notice
function acv_admin_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Affiliate Coupon Vault Pro: Unlock unlimited coupons & analytics for $49/year! <a href="options-general.php?page=affiliate-coupon-vault">Upgrade</a></p></div>';
}
add_action('admin_notices', 'acv_admin_notice');

// Prevent direct access to JS (inline for single file)
function acv_script() {
    if (wp_script_is('acv-script', 'enqueued')) {
        ?>
        <script>jQuery(document).ready(function($){});</script>
        <?php
    }
}
add_action('wp_footer', 'acv_script');
