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
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('acv_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'acv-style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('acv_options', 'acv_settings');
        add_settings_section('acv_main', 'Coupon Settings', null, 'acv-settings');
        add_settings_field('acv_coupons', 'Coupons', array($this, 'coupons_field'), 'acv-settings', 'acv_main');
    }

    public function coupons_field() {
        $settings = get_option('acv_settings', array());
        $coupons = isset($settings['coupons']) ? $settings['coupons'] : array(
            array('code' => 'SAVE10', 'afflink' => '', 'desc' => '10% off')
        );
        echo '<textarea name="acv_settings[coupons]" rows="10" cols="50">' . esc_textarea(json_encode($coupons)) . '</textarea>';
        echo '<p>JSON array: [{"code":"SAVE10","afflink":"https://aff.link","desc":"10% off"}]</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('acv_options');
                do_settings_sections('acv-settings');
                submit_button();
                ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, click tracking, analytics dashboard for $49/year.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $settings = get_option('acv_settings', array());
        $coupons = json_decode($settings['coupons'] ?? '[]', true);
        if (!isset($coupons[$atts['id']])) return 'Coupon not found.';
        $coupon = $coupons[$atts['id']];
        ob_start();
        ?>
        <div class="acv-coupon" data-id="<?php echo esc_attr($atts['id']); ?>">
            <h3><?php echo esc_html($coupon['desc']); ?></h3>
            <p><strong>Code:</strong> <span class="acv-code"><?php echo esc_html($coupon['code']); ?></span></p>
            <a href="<?php echo esc_url($coupon['afflink']); ?}" class="acv-button" target="_blank">Get Deal & Track Click</a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('acv_settings', array('coupons' => json_encode(array(array('code' => 'WELCOME20', 'afflink' => '#', 'desc' => '20% Off Welcome')))));
    }
}

new AffiliateCouponVault();

// Inline JS and CSS for single file
add_action('wp_head', function() {
    echo '<style>
        .acv-coupon { border: 2px solid #007cba; padding: 20px; border-radius: 10px; background: #f9f9f9; text-align: center; max-width: 300px; }
        .acv-code { font-size: 24px; color: #e74c3c; font-weight: bold; }
        .acv-button { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
        .acv-button:hover { background: #005a87; }
    </style>';

    echo '<script>jQuery(document).ready(function($) {
        $(".acv-button").click(function() {
            var id = $(this).closest(".acv-coupon").data("id");
            // Pro: Track click to server
            console.log("Coupon " + id + " clicked");
            gtag?.("event", "coupon_click", {"coupon_id": id}); // GA4 ready
        });
    });</script>';
});