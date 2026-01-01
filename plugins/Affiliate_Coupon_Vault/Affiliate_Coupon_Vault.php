/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically fetch, display, and track affiliate coupons to boost your commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
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
        add_shortcode('acv_coupons', array($this, 'coupons_shortcode'));
        add_action('wp_ajax_acv_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_acv_track_click', array($this, 'track_click'));
    }

    public function init() {
        // Sample coupon data (in pro, fetch from APIs like CJ Affiliate, ShareASale)
        $this->coupons = array(
            array('code' => 'SAVE20', 'description' => '20% off on hosting', 'afflink' => 'https://example.com/aff?ref=123', 'expires' => '2026-12-31'),
            array('code' => 'HOST10', 'description' => '10% off first year', 'afflink' => 'https://example.com/aff?ref=456', 'expires' => '2026-06-30'),
        );
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts, 'acv_coupons');
        ob_start();
        echo '<div class="acv-coupons">';
        $count = 0;
        foreach ($this->coupons as $coupon) {
            if ($count >= $atts['limit']) break;
            if (strtotime($coupon['expires']) < time()) continue;
            echo '<div class="acv-coupon">';
            echo '<h4>' . esc_html($coupon['description']) . '</h4>';
            echo '<code>' . esc_html($coupon['code']) . '</code>';
            echo '<a href="#" class="acv-track" data-link="' . esc_url($coupon['afflink']) . '" data-id="' . $count . '">Get Deal</a>';
            echo '</div>';
            $count++;
        }
        echo '</div>';
        return ob_get_clean();
    }

    public function track_click() {
        if (!wp_verify_nonce($_POST['nonce'], 'acv_nonce')) {
            wp_die('Security check failed');
        }
        $link = sanitize_url($_POST['link']);
        // In pro, log to dashboard
        update_option('acv_clicks', (int) get_option('acv_clicks', 0) + 1);
        wp_redirect($link);
        exit;
    }
}

// Sample JS file content (save as acv.js in plugin folder)
/*
jQuery(document).ready(function($) {
    $('.acv-track').click(function(e) {
        e.preventDefault();
        var link = $(this).data('link');
        $.post(acv_ajax.ajax_url, {
            action: 'acv_track_click',
            nonce: 'demo_nonce', // Replace with real nonce in pro
            link: link
        }, function() {
            window.location = link;
        });
    });
});
*/

AffiliateCouponVault::get_instance();

// Pro upgrade notice
function acv_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Affiliate Coupon Vault Pro unlocks unlimited coupons, API integrations, and analytics. <a href="https://example.com/pro" target="_blank">Upgrade now</a>!</p></div>';
}
add_action('admin_notices', 'acv_pro_notice');

// Add settings page
add_action('admin_menu', function() {
    add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', function() {
        echo '<h1>Affiliate Coupon Vault Settings</h1><p>Upgrade to Pro for full API setup and dashboard.</p>';
        echo '<p>Total clicks: <strong>' . get_option('acv_clicks', 0) . '</strong></p>';
    });
});