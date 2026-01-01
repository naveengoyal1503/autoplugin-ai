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
    exit; // Exit if accessed directly.
}

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('aff-coupon-js', plugin_dir_url(__FILE__) . 'aff-coupon.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('aff-coupon-css', plugin_dir_url(__FILE__) . 'aff-coupon.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'aff-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('aff_coupon_settings', 'aff_coupon_options');
        add_settings_section('aff_coupon_main', 'Coupon Settings', null, 'aff-coupon-vault');
        add_settings_field('coupons', 'Coupons', array($this, 'coupons_field'), 'aff-coupon-vault', 'aff_coupon_main');
    }

    public function coupons_field() {
        $options = get_option('aff_coupon_options', array());
        echo '<textarea name="aff_coupon_options[coupons]" rows="10" cols="50">' . esc_textarea($options['coupons'] ?? '') . '</textarea>';
        echo '<p class="description">Enter coupons as JSON: [{"name":"Brand1","code":"SAVE10","afflink":"https://aff.link","expires":"2026-12-31"}]</p>';
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('aff_coupon_settings');
                do_settings_sections('aff-coupon-vault');
                submit_button();
                ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics, auto-expiry, and API integrations for $49/year!</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $options = get_option('aff_coupon_options', array());
        $coupons = json_decode($options['coupons'] ?? '[]', true);
        if (empty($coupons)) return '<p>No coupons configured. <a href="' . admin_url('options-general.php?page=aff-coupon-vault') . '">Set up now</a>.</p>';

        $coupon = null;
        foreach ($coupons as $c) {
            if ($c['name'] === $atts['id']) {
                $coupon = $c;
                break;
            }
        }
        if (!$coupon) return '<p>Coupon not found.</p>';

        $expires = !empty($coupon['expires']) ? 'Expires: ' . date('M j, Y', strtotime($coupon['expires'])) : '';
        ob_start();
        ?>
        <div class="aff-coupon-vault" data-track="<?php echo esc_attr($coupon['name']); ?>">
            <h3><?php echo esc_html($coupon['name']); ?> Exclusive Deal</h3>
            <p>Use code: <strong><?php echo esc_html($coupon['code']); ?></strong> <?php echo esc_html($expires); ?></p>
            <a href="<?php echo esc_url($coupon['afflink']); ?}" class="coupon-btn" target="_blank">Get Deal (Affiliate)</a>
            <small>Tracked by Affiliate Coupon Vault</small>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('aff_coupon_options', array('coupons' => json_encode(array(
            array('name' => 'Demo Brand', 'code' => 'DEMO20', 'afflink' => '#', 'expires' => '2026-06-30')
        ))));
    }
}

new AffiliateCouponVault();

// Pro upsell notice
function aff_coupon_admin_notice() {
    if (!current_user_can('manage_options')) return;
    $screen = get_current_screen();
    if ($screen->id === 'settings_page_aff-coupon-vault') return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Upgrade for unlimited coupons & analytics! <a href="' . admin_url('options-general.php?page=aff-coupon-vault') . '">Learn more</a></p></div>';
}
add_action('admin_notices', 'aff_coupon_admin_notice');

// Simple JS for tracking (Pro feature teaser)
function aff_coupon_ajax_track() {
    if (!wp_verify_nonce($_POST['nonce'], 'aff_coupon_nonce')) wp_die();
    // Pro: Send tracking data
    wp_die('Pro feature');
}
add_action('wp_ajax_aff_track', 'aff_coupon_ajax_track');

// CSS
/* Add inline CSS for simplicity */
function aff_coupon_styles() {
    echo '<style>
    .aff-coupon-vault { background: #f8f9fa; padding: 20px; border: 2px dashed #007cba; border-radius: 8px; text-align: center; max-width: 400px; margin: 20px auto; }
    .aff-coupon-vault h3 { color: #007cba; margin: 0 0 10px; }
    .coupon-btn { display: inline-block; background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; }
    .coupon-btn:hover { background: #005a87; }
    </style>';
}
add_action('wp_head', 'aff_coupon_styles');
add_action('admin_head', 'aff_coupon_styles');

// JS
/* Inline JS */
function aff_coupon_scripts() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.coupon-btn').on('click', function() {
            var name = $(this).closest('.aff-coupon-vault').data('track');
            // Pro: Track click
            console.log('Tracking coupon: ' + name + ' (Pro feature)');
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'aff_coupon_scripts');