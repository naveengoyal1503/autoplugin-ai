/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and manages exclusive affiliate coupons, tracks clicks, and displays personalized deals to boost conversions and commissions.
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

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_acv_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_acv_track_click', array($this, 'track_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-frontend', plugin_dir_url(__FILE__) . 'acv-frontend.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-frontend', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('acv_settings', 'acv_options');
        add_settings_section('acv_main', 'Main Settings', null, 'acv-settings');
        add_settings_field('acv_coupons', 'Coupons', array($this, 'coupons_field'), 'acv-settings', 'acv_main');
    }

    public function coupons_field() {
        $options = get_option('acv_options', array());
        $coupons = isset($options['coupons']) ? $options['coupons'] : array(
            array('name' => 'Sample Deal', 'code' => 'SAVE20', 'url' => '#', 'affiliate_id' => '1')
        );
        echo '<textarea name="acv_options[coupons]" rows="10" cols="80">' . esc_textarea(json_encode($coupons, JSON_PRETTY_PRINT)) . '</textarea>';
        echo '<p>JSON format: [{ "name": "Deal Name", "code": "COUPONCODE", "url": "https://affiliate-link.com", "affiliate_id": "unique_id" }]</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('acv_settings');
                do_settings_sections('acv-settings');
                submit_button();
                ?>
            </form>
            <h2>Shortcode Usage</h2>
            <p>Use <code>[acv_coupons]</code> to display coupons on any page/post.</p>
            <?php if (!class_exists('AffiliateCouponVaultPro')) { ?>
            <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffeaa7;">
                <p><strong>Go Pro!</strong> Unlock unlimited coupons, analytics dashboard, auto-expiration, and more for $49/year.</p>
            </div>
            <?php } ?>
        </div>
        <?php
    }

    public function activate() {
        add_option('acv_options', array('coupons' => array()));
    }

    public function deactivate() {}

    public function track_click() {
        check_ajax_referer('acv_nonce', 'nonce');
        $affiliate_id = sanitize_text_field($_POST['affiliate_id']);
        $options = get_option('acv_options', array());
        $coupons = isset($options['coupons']) ? $options['coupons'] : array();
        foreach ($coupons as &$coupon) {
            if ($coupon['affiliate_id'] === $affiliate_id) {
                $coupon['clicks'] = isset($coupon['clicks']) ? $coupon['clicks'] + 1 : 1;
                break;
            }
        }
        update_option('acv_options', $options);
        wp_die();
    }
}

// Shortcode
add_shortcode('acv_coupons', function() {
    $options = get_option('acv_options', array());
    $coupons = isset($options['coupons']) ? $options['coupons'] : array();
    if (empty($coupons)) {
        return '<p>No coupons configured yet. <a href="' . admin_url('options-general.php?page=acv-settings') . '">Set up now</a>.</p>';
    }

    $html = '<div id="acv-vault" style="max-width: 600px; margin: 20px 0;">
        <h3>Exclusive Deals & Coupons</h3>';
    foreach ($coupons as $coupon) {
        $clicks = isset($coupon['clicks']) ? $coupon['clicks'] : 0;
        $html .= '<div style="border: 1px solid #ddd; margin: 10px 0; padding: 15px; border-radius: 8px;">
            <h4>' . esc_html($coupon['name']) . '</h4>
            <p><strong>Code:</strong> <code>' . esc_html($coupon['code']) . '</code></p>
            <p><a href="' . esc_url($coupon['url']) . '" class="button button-primary acv-track" data-id="' . esc_attr($coupon['affiliate_id']) . '" target="_blank">Grab Deal (Tracked: ' . $clicks . ')</a></p>
        </div>';
    }
    $html .= '</div>';
    return $html;
});

// Frontend JS (inline for single file)
add_action('wp_footer', function() {
    if (!is_admin() && has_shortcode(get_post()->post_content, 'acv_coupons')) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.acv-track').click(function(e) {
                e.preventDefault();
                var btn = $(this);
                var id = btn.data('id');
                $.post(acv_ajax.ajax_url, {
                    action: 'acv_track_click',
                    nonce: acv_ajax.nonce,
                    affiliate_id: id
                }, function() {
                    window.open(btn.attr('href'), '_blank');
                });
            });
        });
        </script>
        <?php
    }
});

AffiliateCouponVault::get_instance();

// Pro upsell check (simulate pro)
if (!defined('AFFILIATE_COUPON_VAULT_PRO')) {
    define('AFFILIATE_COUPON_VAULT_PRO', false);
}