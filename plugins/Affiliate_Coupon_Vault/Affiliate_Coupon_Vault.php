/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupons with click tracking for higher conversions.
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
        if (null == self::$instance) {
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
        if (get_option('acv_pro_version')) {
            // Pro features here
        }
        $this->create_table();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acv_clicks';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            coupon_id varchar(50) NOT NULL,
            affiliate_link text NOT NULL,
            ip varchar(45) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function activate() {
        $this->create_table();
        add_option('acv_db_version', '1.0');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 'default',
            'title' => 'Exclusive Coupon',
            'discount' => '20% OFF',
            'affiliate_link' => '#',
            'image' => '',
            'button_text' => 'Get Coupon'
        ), $atts);

        $coupon_id = sanitize_text_field($atts['id']);
        $tracked_link = add_query_arg('acv_coupon', $coupon_id, $atts['affiliate_link']);

        $output = '<div class="acv-coupon" data-coupon-id="' . esc_attr($coupon_id) . '">';
        if ($atts['image']) {
            $output .= '<img src="' . esc_url($atts['image']) . '" alt="Coupon" style="max-width:100%;">';
        }
        $output .= '<h3>' . esc_html($atts['title']) . '</h3>';
        $output .= '<div class="acv-discount">' . esc_html($atts['discount']) . '</div>';
        $output .= '<a href="#" class="acv-button">' . esc_html($atts['button_text']) . '</a>';
        $output .= '<div class="acv-reveal" style="display:none;"><a href="' . esc_url($tracked_link) . '" target="_blank" class="acv-link">Reveal & Shop</a></div>';
        $output .= '</div>';
        return $output;
    }

    public function track_click() {
        check_ajax_referer('acv_nonce', 'nonce');
        global $wpdb;
        $coupon_id = sanitize_text_field($_POST['coupon_id']);
        $affiliate_link = esc_url_raw($_POST['affiliate_link']);
        $ip = $_SERVER['REMOTE_ADDR'];

        $wpdb->insert(
            $wpdb->prefix . 'acv_clicks',
            array(
                'coupon_id' => $coupon_id,
                'affiliate_link' => $affiliate_link,
                'ip' => $ip
            )
        );

        wp_send_json_success(array('link' => $affiliate_link));
    }
}

// Enqueue JS inline for single file
add_action('wp_footer', function() {
    if (!wp_script_is('acv-script', 'enqueued')) return;
    ?>
    <script>jQuery(document).ready(function($) {
        $('.acv-button').click(function(e) {
            e.preventDefault();
            var $container = $(this).closest('.acv-coupon');
            var couponId = $container.data('coupon-id');
            var link = $container.find('.acv-link').attr('href');
            $.post(acv_ajax.ajax_url, {
                action: 'acv_track_click',
                nonce: acv_ajax.nonce,
                coupon_id: couponId,
                affiliate_link: link
            }, function(response) {
                if (response.success) {
                    $container.find('.acv-reveal').show();
                    $container.find('.acv-button').hide();
                }
            });
        });
    });</script>
    <style>
    .acv-coupon { border: 2px solid #007cba; padding: 20px; text-align: center; border-radius: 10px; background: #f9f9f9; margin: 20px 0; }
    .acv-discount { font-size: 24px; color: #007cba; font-weight: bold; margin: 10px 0; }
    .acv-button { background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; }
    .acv-button:hover { background: #005a87; }
    .acv-reveal { margin-top: 15px; }
    .acv-link { background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; }
    </style>
    <?php
});

AffiliateCouponVault::get_instance();