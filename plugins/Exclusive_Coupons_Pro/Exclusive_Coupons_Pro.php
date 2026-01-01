/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Automatically generates and displays exclusive, personalized coupon codes for your WordPress site visitors, boosting affiliate conversions and reader loyalty.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class ExclusiveCouponsPro {
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
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_shortcode('exclusive_coupons', array($this, 'coupons_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('exclusive-coupons', plugin_dir_url(__FILE__) . 'coupons.js', array('jquery'), '1.0.0', true);
        wp_localize_script('exclusive-coupons', 'ecp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ecp_nonce')));
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate_url' => '',
            'brands' => 'Amazon,Shopify,WooCommerce',
            'discount' => '10-20%'
        ), $atts);

        $brands = explode(',', $atts['brands']);
        $output = '<div id="ecp-container" data-affiliate-url="' . esc_attr($atts['affiliate_url']) . '"><h3>Grab Your Exclusive Coupons!</h3>';
        foreach ($brands as $brand) {
            $output .= '<div class="ecp-coupon" data-brand="' . esc_attr(trim($brand)) . '" data-discount="' . esc_attr($atts['discount']) . '"><button class="generate-btn">Get ' . esc_html(trim($brand)) . ' Coupon</button><span class="coupon-code"></span><a class="use-coupon" target="_blank" style="display:none;">Use Now</a></div>';
        }
        $output .= '</div>';
        return $output;
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('ecp_nonce', 'nonce');

        $brand = sanitize_text_field($_POST['brand']);
        $discount = sanitize_text_field($_POST['discount']);
        $user_ip = $_SERVER['REMOTE_ADDR'];
        $coupon_code = strtoupper(substr(md5($user_ip . $brand . time()), 0, 8));

        // Simulate affiliate tracking
        set_transient('ecp_coupon_' . $user_ip . '_' . $coupon_code, true, HOUR_IN_SECONDS);

        wp_send_json_success(array('code' => $coupon_code, 'message' => "Exclusive $discount off at $brand!"));
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons Pro', 'Coupons Pro', 'manage_options', 'exclusive-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        echo '<div class="wrap"><h1>Exclusive Coupons Pro Settings</h1><form method="post" action="options.php">';
        settings_fields('ecp_options');
        do_settings_sections('ecp_options');
        submit_button();
        echo '</form></div>';
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

ExclusiveCouponsPro::get_instance();

// Inline JS for simplicity (self-contained)
function ecp_inline_scripts() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('.generate-btn').click(function() {
            var $btn = $(this);
            var $container = $btn.closest('.ecp-coupon');
            var brand = $container.data('brand');
            var discount = $container.data('discount');
            var affiliateUrl = $('#ecp-container').data('affiliate-url');

            $.post(ecp_ajax.ajax_url, {
                action: 'generate_coupon',
                nonce: ecp_ajax.nonce,
                brand: brand,
                discount: discount
            }, function(response) {
                if (response.success) {
                    $container.find('.coupon-code').text(response.data.code);
                    var useUrl = affiliateUrl ? affiliateUrl + '?coupon=' + response.data.code : '#';
                    $container.find('.use-coupon').attr('href', useUrl).show();
                    $btn.hide();
                }
            });
        });
    });
    </script>
    <style>
    #ecp-container { max-width: 600px; margin: 20px 0; }
    .ecp-coupon { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .generate-btn { background: #0073aa; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 3px; }
    .coupon-code { font-size: 24px; font-weight: bold; color: #28a745; margin-left: 10px; }
    .use-coupon { margin-left: 10px; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 3px; }
    </style>
    <?php
}
add_action('wp_footer', 'ecp_inline_scripts');