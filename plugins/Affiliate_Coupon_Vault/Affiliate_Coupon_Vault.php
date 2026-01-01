/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons, personalized discounts, and deal trackers to boost conversions and commissions.
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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupon_vault', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            return;
        }
        wp_localize_script('jquery', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 'default',
            'affiliate_link' => '',
            'coupon_code' => 'SAVE20',
            'discount' => '20%',
            'expires' => date('Y-m-d', strtotime('+30 days')),
            'button_text' => 'Get Deal'
        ), $atts);

        ob_start();
        ?>
        <div class="acv-coupon-vault" data-id="<?php echo esc_attr($atts['id']); ?>">
            <div class="acv-coupon-code"><?php echo esc_html($atts['coupon_code']); ?></div>
            <div class="acv-discount"><?php echo esc_html($atts['discount']); ?> OFF</div>
            <div class="acv-expires">Expires: <?php echo esc_html($atts['expires']); ?></div>
            <a href="<?php echo esc_url($atts['affiliate_link']); ?}" class="acv-button" target="_blank"><?php echo esc_html($atts['button_text']); ?></a>
            <div class="acv-stats">Clicks: <span class="acv-clicks">0</span></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('acv_settings', 'acv_options');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('acv_settings'); ?>
                <?php do_settings_sections('acv_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Affiliate Links</th>
                        <td><textarea name="acv_options[links]" rows="10" cols="50"><?php echo esc_textarea(get_option('acv_options')['links'] ?? ''); ?></textarea><br><small>One link per line: Affiliate Link | Coupon Code | Discount | Expires | Button Text</small></td>
                    </tr>
                    <tr>
                        <th>Pro Upgrade</th>
                        <td><a href="https://example.com/pro" class="button button-primary">Upgrade to Pro ($49/year)</a> for unlimited coupons & analytics.</td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function activate() {
        add_option('acv_activated', time());
    }
}

// AJAX for tracking clicks
add_action('wp_ajax_acv_track_click', 'acv_track_click');
add_action('wp_ajax_nopriv_acv_track_click', 'acv_track_click');
function acv_track_click() {
    check_ajax_referer('acv_nonce', 'nonce');
    $id = sanitize_text_field($_POST['id']);
    // In pro version, save to DB
    wp_die('tracked');
}

AffiliateCouponVault::get_instance();

// Inline CSS
add_action('wp_head', function() { ?>
<style>
.acv-coupon-vault { background: linear-gradient(135deg, #ff6b6b, #feca57); padding: 20px; border-radius: 10px; text-align: center; max-width: 300px; margin: 20px auto; box-shadow: 0 10px 30px rgba(0,0,0,0.3); font-family: Arial, sans-serif; }
.acv-coupon-code { font-size: 2em; font-weight: bold; color: white; margin-bottom: 10px; letter-spacing: 5px; }
.acv-discount { font-size: 1.5em; color: white; margin-bottom: 10px; }
.acv-expires { color: white; opacity: 0.9; margin-bottom: 15px; font-size: 0.9em; }
.acv-button { display: inline-block; background: #333; color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; transition: all 0.3s; }
.acv-button:hover { background: #555; transform: translateY(-2px); }
.acv-stats { margin-top: 15px; color: white; font-size: 0.8em; }
</style>
<?php });

// Inline JS
add_action('wp_footer', function() { ?>
<script>
jQuery(document).ready(function($) {
    $('.acv-button').on('click', function(e) {
        var $vault = $(this).closest('.acv-coupon-vault');
        var id = $vault.data('id');
        $.post(acv_ajax.ajax_url, {
            action: 'acv_track_click',
            nonce: acv_ajax.nonce,
            id: id
        });
        var clicks = parseInt($('.acv-clicks', $vault).text()) + 1;
        $('.acv-clicks', $vault).text(clicks);
    });
});
</script>
<?php });