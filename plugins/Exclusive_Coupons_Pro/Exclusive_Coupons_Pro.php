/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate exclusive affiliate coupons with tracking and monetization features.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: exclusive-coupons-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class ExclusiveCouponsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('ecp_pro_version')) {
            add_action('wp_ajax_ecp_track_click', array($this, 'track_click'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ecp-frontend', plugin_dir_url(__FILE__) . 'ecp.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ecp-frontend', 'ecp_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_menu_page('Exclusive Coupons', 'Coupons', 'manage_options', 'exclusive-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['ecp_save_coupon'])) {
            update_option('ecp_coupon_' . sanitize_key($_POST['code']), array(
                'code' => sanitize_text_field($_POST['code']),
                'description' => sanitize_textarea_field($_POST['description']),
                'affiliate_link' => esc_url_raw($_POST['affiliate_link']),
                'expiry' => sanitize_text_field($_POST['expiry']),
                'uses' => intval($_POST['uses'])
            ));
            echo '<div class="notice notice-success"><p>Coupon saved!</p></div>';
        }
        echo '<div class="wrap"><h1>Manage Exclusive Coupons</h1><form method="post">';
        echo '<table class="form-table">';
        echo '<tr><th>Code</th><td><input type="text" name="code" required /></td></tr>';
        echo '<tr><th>Description</th><td><textarea name="description"></textarea></td></tr>';
        echo '<tr><th>Affiliate Link</th><td><input type="url" name="affiliate_link" required /></td></tr>';
        echo '<tr><th>Expiry Date</th><td><input type="date" name="expiry" required /></td></tr>';
        echo '<tr><th>Max Uses</th><td><input type="number" name="uses" value="0" /></td></tr>';
        echo '</table><p><input type="submit" name="ecp_save_coupon" class="button-primary" value="Save Coupon" /></p></form>';

        $coupons = get_option('ecp_coupons', array());
        echo '<h2>Active Coupons</h2><ul>';
        foreach ($coupons as $key => $coupon) {
            if (strtotime($coupon['expiry']) > current_time('timestamp') && $coupon['uses'] > 0) {
                echo '<li>' . esc_html($coupon['code']) . ' - <a href="' . esc_url($coupon['affiliate_link']) . '" target="_blank">Link</a> (' . $coupon['uses'] . ' uses left)</li>';
            }
        }
        echo '</ul></div>';
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('code' => ''), $atts);
        $coupon = get_option('ecp_coupon_' . sanitize_key($atts['code']));
        if (!$coupon || strtotime($coupon['expiry']) < current_time('timestamp')) {
            return '<p>This coupon has expired.</p>';
        }
        ob_start();
        echo '<div class="ecp-coupon" data-code="' . esc_attr($atts['code']) . '">';
        echo '<h3>Exclusive Coupon: ' . esc_html($coupon['code']) . '</h3>';
        echo '<p>' . esc_html($coupon['description']) . '</p>';
        echo '<a href="#" class="ecp-reveal" data-link="' . esc_url($coupon['affiliate_link']) . '">Reveal & Use Coupon</a>';
        echo '</div>';
        return ob_get_clean();
    }

    public function track_click() {
        // Pro feature: Track clicks
        wp_die('Pro feature');
    }

    public function activate() {
        add_option('ecp_coupons', array());
    }
}

new ExclusiveCouponsPro();

// Freemium upsell notice
function ecp_upsell_notice() {
    if (!get_option('ecp_pro_version') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Exclusive Coupons Pro</strong> for click tracking, unlimited coupons, and analytics! <a href="https://example.com/pro" target="_blank">Get Pro ($49/year)</a></p></div>';
    }
}
add_action('admin_notices', 'ecp_upsell_notice');

// JS file content would be enqueued, but for single file, inline it
add_action('wp_head', function() {
    if (!get_option('ecp_pro_version')) return;
    ?><script>jQuery(document).ready(function($) {
        $('.ecp-reveal').click(function(e) {
            e.preventDefault();
            var link = $(this).data('link');
            $.post(ecp_ajax.ajaxurl, {action: 'ecp_track_click', code: $(this).closest('.ecp-coupon').data('code')}, function() {
                window.open(link, '_blank');
            });
        });
    });</script><?php
});