<?php
/*
Plugin Name: Affiliate Deal Booster
Description: Aggregates and displays affiliate coupons with smart tracking to increase affiliate commission revenue.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Booster.php
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AffiliateDealBooster {
    private $option_name = 'adb_coupons';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'settings_init']);
        add_shortcode('aff_deals', [$this, 'render_coupons_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_adb_track_click', [$this, 'track_click']);
        add_action('wp_ajax_nopriv_adb_track_click', [$this, 'track_click']);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('adb_script', plugin_dir_url(__FILE__) . 'adb_script.js', ['jquery'], '1.0', true);
        wp_localize_script('adb_script', 'adbAjax', ['ajaxurl' => admin_url('admin-ajax.php')]);
    }

    public function add_admin_menu() {
        add_menu_page('Affiliate Deal Booster', 'Affiliate Deals', 'manage_options', 'affiliate-deal-booster', [$this, 'options_page']);
    }

    public function settings_init() {
        register_setting('adb_settings', $this->option_name);

        add_settings_section(
            'adb_section',
            __('Coupon Deals Settings', 'adb'),
            null,
            'adb_settings'
        );

        add_settings_field(
            'adb_coupons_field',
            __('Coupons JSON', 'adb'),
            [$this, 'coupons_field_render'],
            'adb_settings',
            'adb_section'
        );
    }

    public function coupons_field_render() {
        $coupons = get_option($this->option_name, '[]');
        echo '<textarea cols="60" rows="10" name="'.$this->option_name.'">' . esc_textarea($coupons) . '</textarea>';
        echo '<p class="description">Enter coupon deals as JSON array with objects: id, title, url, discount, expiry</p>';
        echo '<pre>[Example]
[
  {"id": "c1", "title": "10% off Shoes", "url": "https://affiliatesite.com/track/c1", "discount": "10%", "expiry": "2026-12-31"}
]</pre>';
    }

    public function options_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        echo '<form action="options.php" method="post">';
        settings_fields('adb_settings');
        do_settings_sections('adb_settings');
        submit_button();
        echo '</form>';
    }

    public function render_coupons_shortcode() {
        $coupons_json = get_option($this->option_name, '[]');
        $coupons = json_decode($coupons_json, true);
        if (!$coupons || !is_array($coupons)) {
            return '<p>No coupons available.</p>';
        }

        $output = '<div class="adb-coupons">';
        foreach ($coupons as $coupon) {
            $valid = true;
            if (isset($coupon['expiry'])) {
                $expiry = strtotime($coupon['expiry']);
                if ($expiry !== false && $expiry < time()) {
                    $valid = false;
                }
            }
            if (!$valid) continue;

            $id = esc_attr($coupon['id'] ?? uniqid('c_'));
            $title = esc_html($coupon['title'] ?? 'Coupon Deal');
            $url = esc_url($coupon['url'] ?? '#');
            $discount = esc_html($coupon['discount'] ?? '');

            $output .= '<div class="adb-coupon" style="border:1px solid #ccc;padding:10px;margin-bottom:10px;">';
            $output .= '<h3>' . $title . '</h3>';
            if ($discount) {
                $output .= '<strong>Discount:</strong> ' . $discount . '<br/>';
            }
            $output .= '<a href="#" class="adb-aff-link" data-id="' . $id . '" data-url="' . $url . '" target="_blank" rel="nofollow noopener">Get Deal</a>';
            $output .= '</div>';
        }
        $output .= '</div>';
        // Minimal inline styling could be added here or via separate CSS file
        $output .= '<style>.adb-aff-link{background:#0073aa;color:#fff;padding:8px 12px;text-decoration:none;display:inline-block;border-radius:4px;}.adb-aff-link:hover{background:#005177;}</style>';

        return $output;
    }

    public function track_click() {
        if (!isset($_POST['coupon_id'])) {
            wp_send_json_error('No coupon id');
        }
        $coupon_id = sanitize_text_field($_POST['coupon_id']);

        // Implement simple click logging in database or file (for performance, realtime logging should use async method)
        global $wpdb;
        $table = $wpdb->prefix . 'adb_clicks';

        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE coupon_id=%s", $coupon_id));
        if ($exists) {
            $wpdb->query($wpdb->prepare("UPDATE $table SET clicks = clicks + 1, last_click = NOW() WHERE coupon_id=%s", $coupon_id));
        } else {
            $wpdb->insert($table, ['coupon_id' => $coupon_id, 'clicks' => 1, 'last_click' => current_time('mysql')]);
        }

        wp_send_json_success();
    }

    public function install() {
        global $wpdb;

        $table = $wpdb->prefix . 'adb_clicks';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            coupon_id VARCHAR(100) NOT NULL,
            clicks BIGINT(20) DEFAULT 0,
            last_click DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY coupon_id (coupon_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

$adb = new AffiliateDealBooster();
register_activation_hook(__FILE__, [$adb, 'install']);

// JavaScript for AJAX click tracking embedded within PHP to keep single file
add_action('wp_footer', function() {
    ?>
    <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.adb-aff-link').forEach(function(elem) {
            elem.addEventListener('click', function(e) {
                e.preventDefault();
                var couponId = this.getAttribute('data-id');
                var url = this.getAttribute('data-url');

                fetch(adbAjax.ajaxurl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                    body: 'action=adb_track_click&coupon_id=' + encodeURIComponent(couponId)
                }).finally(() => {
                    window.open(url, '_blank');
                });
            });
        });
    });
    </script>
    <?php
});
