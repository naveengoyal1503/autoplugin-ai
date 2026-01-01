/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Generate exclusive affiliate coupons, track usage, and boost conversions with auto-expiring codes.
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        wp_register_style('acv-admin', false);
        wp_enqueue_style('acv-admin');
        $acv_css = '
        #acv-coupons th, #acv-coupons td { padding: 10px; border: 1px solid #ddd; }
        .acv-coupon-code { font-family: monospace; background: #f0f0f0; padding: 5px; }
        .acv-pro-upsell { background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; margin: 20px 0; }
        ';
        wp_add_inline_style('acv-admin', $acv_css);
    }

    public function admin_menu() {
        add_menu_page(
            'Affiliate Coupons',
            'Coupon Vault',
            'manage_options',
            'affiliate-coupon-vault',
            array($this, 'admin_page'),
            'dashicons-tickets-alt',
            30
        );
    }

    public function admin_page() {
        if (isset($_POST['acv_submit'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['acv_coupons']));
            echo '<div class="notice notice-success"><p>Coupons updated!</p></div>';
        }
        $coupons = get_option('acv_coupons', "Code: SAVE20|Affiliate Link: https://example.com/aff?ref=123|Expiry: 2026-02-01|Uses: 0\nCode: DISCOUNT10|Affiliate Link: https://example.com/prod|Expiry: 2026-01-15|Uses: 0");
        echo '<div class="wrap"><h1>Affiliate Coupon Vault</h1>';
        echo '<div class="acv-pro-upsell"><strong>Go Pro!</strong> Unlock unlimited coupons, analytics, auto-expiry, and email capture for $49/year. <a href="https://example.com/pro" target="_blank">Upgrade Now</a></div>';
        echo '<form method="post"><table id="acv-coupons"><tr><th>Code</th><th>Affiliate Link</th><th>Expiry</th><th>Uses</th></tr>';
        $lines = explode('\n', $coupons);
        foreach ($lines as $line) {
            if (trim($line)) {
                $parts = explode('|', $line);
                echo '<tr>';
                echo '<td><input type="text" name="parts[]" value="' . esc_attr($parts ?? '') . '" class="acv-coupon-code"></td>';
                echo '<td><input type="url" name="parts[]" value="' . esc_attr($parts[1] ?? '') . '"></td>';
                echo '<td><input type="date" name="parts[]" value="' . esc_attr($parts[2] ?? '') . '"></td>';
                echo '<td><input type="number" name="parts[]" value="' . esc_attr($parts[3] ?? '0') . '" readonly></td>';
                echo '</tr>';
            }
        }
        echo '</table><p><textarea name="acv_coupons" style="width:100%;height:200px;display:none;">' . esc_textarea($coupons) . '</textarea></p>';
        echo '<p><input type="submit" name="acv_submit" class="button-primary" value="Save Coupons"></p></form></div>';
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = explode('\n', get_option('acv_coupons', ''));
        if (isset($coupons[$atts['id']])) {
            $parts = explode('|', $coupons[$atts['id']]);
            if (count($parts) >= 3) {
                $code = trim($parts);
                $link = trim($parts[1]);
                $expiry = trim($parts[2]);
                $uses = intval($parts[3] ?? 0);
                if (strtotime($expiry) > time() && $uses < 100) { // Free limit: 100 uses
                    $new_uses = $uses + 1;
                    $new_line = "{$code}|{$link}|{$expiry}|{$new_uses}";
                    $lines = $coupons;
                    $lines[$atts['id']] = $new_line;
                    update_option('acv_coupons', implode('\n', $lines));
                    return '<div style="border:2px dashed #007cba;padding:20px;text-align:center;"><strong>Exclusive Coupon:</strong><br><span class="acv-coupon-code">' . esc_html($code) . '</span><br><a href="' . esc_url($link) . '" target="_blank" class="button">Redeem Now & Track Conversion</a><br><small>Expires: ' . esc_html(date('M j, Y', strtotime($expiry))) . ' | Used: ' . $new_uses . '/100 (Pro: Unlimited)</small></div>';
                } else {
                    return '<div style="border:2px solid #dc3232;padding:20px;text-align:center;color:#dc3232;"><strong>Coupon Expired or Maxed Out</strong><br>Upgrade to Pro for unlimited use and auto-regeneration.</div>';
                }
            }
        }
        return '<p>No coupon found.</p>';
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', "Code: SAVE20|Affiliate Link: https://example.com/aff?ref=123|Expiry: 2026-02-01|Uses: 0\nCode: DISCOUNT10|Affiliate Link: https://example.com/prod|Expiry: 2026-01-15|Uses: 0");
        }
    }
}

AffiliateCouponVault::get_instance();