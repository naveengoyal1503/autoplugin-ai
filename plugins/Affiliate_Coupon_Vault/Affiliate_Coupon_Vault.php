/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with tracking to boost your commissions.
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
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page(
            'Affiliate Coupon Vault',
            'Coupon Vault',
            'manage_options',
            'affiliate-coupon-vault',
            array($this, 'settings_page')
        );
    }

    public function admin_init() {
        register_setting('affiliate_coupon_settings', 'affiliate_coupon_options');
        add_settings_section('main_section', 'Coupon Settings', null, 'affiliate-coupon-vault');
        add_settings_field('coupons', 'Coupons', array($this, 'coupons_field'), 'affiliate-coupon-vault', 'main_section');
    }

    public function coupons_field() {
        $options = get_option('affiliate_coupon_options', array());
        $coupons = isset($options['coupons']) ? $options['coupons'] : array();
        echo '<textarea name="affiliate_coupon_options[coupons]" rows="10" cols="50">' . esc_textarea(json_encode($coupons, JSON_PRETTY_PRINT)) . '</textarea>';
        echo '<p class="description">JSON array of coupons: {"name":"Coupon Name","code":"SAVE20","afflink":"https://aff.link","desc":"20% off"}</p>';
        echo '<p><strong>Pro Upgrade:</strong> Unlimited coupons, analytics, auto-generation. <a href="https://example.com/pro" target="_blank">Get Pro ($49/yr)</a></p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('affiliate_coupon_settings');
                do_settings_sections('affiliate-coupon-vault');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $options = get_option('affiliate_coupon_options', array());
        $coupons = isset($options['coupons']) ? json_decode($options['coupons'], true) : array();
        if (!isset($coupons[$atts['id']])) {
            return '<p>No coupon found.</p>';
        }
        $coupon = $coupons[$atts['id']];
        $track_id = uniqid('acv_');
        $click_url = add_query_arg('acv_track', $track_id, $coupon['afflink']);
        ob_start();
        ?>
        <div class="affiliate-coupon-vault" data-track="<?php echo esc_attr($track_id); ?>">
            <h3><?php echo esc_html($coupon['name']); ?></h3>
            <p><?php echo esc_html($coupon['desc']); ?></p>
            <div class="coupon-code"><?php echo esc_html($coupon['code']); ?></div>
            <a href="<?php echo esc_url($click_url); ?>" class="coupon-btn" target="_blank">Get Deal & Track Commission</a>
            <small>Copied to clipboard on click. Pro tracks all clicks.</small>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('affiliate_coupon_options')) {
            update_option('affiliate_coupon_options', array('coupons' => json_encode(array(
                array('name' => 'Sample 20% Off', 'code' => 'SAVE20', 'afflink' => '#', 'desc' => 'Exclusive deal')
            ))));
        }
    }
}

AffiliateCouponVault::get_instance();

// Inline CSS
add_action('wp_head', function() { ?>
<style>
.affiliate-coupon-vault { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; border-radius: 8px; text-align: center; }
.coupon-code { background: #fff; font-size: 24px; font-weight: bold; color: #e74c3c; padding: 10px; margin: 10px 0; border: 1px solid #ddd; display: inline-block; }
.coupon-btn { background: #0073aa; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px; }
.coupon-btn:hover { background: #005a87; }
</style>
<?php });

// Inline JS
add_action('wp_footer', function() { ?>
<script>jQuery(document).ready(function($) { $('.coupon-btn').on('click', function(e) { var code = $(this).siblings('.coupon-code').text(); navigator.clipboard.writeText(code).then(function() { console.log('Coupon copied: ' + code); }); }); });</script>
<?php });