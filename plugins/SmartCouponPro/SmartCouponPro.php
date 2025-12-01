<?php
/*
Plugin Name: SmartCouponPro
Plugin URI: https://yoursite.com/smartcouponpro
Description: Create AI-powered smart coupons and dynamic deals tailored to visitor behavior.
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=SmartCouponPro.php
License: GPL2
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class SmartCouponPro {
    public function __construct() {
        add_shortcode('smartcouponpro_display', array($this, 'display_coupon')); 
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_scp_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_scp_track_click', array($this, 'track_click'));
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        register_uninstall_hook(__FILE__, array('SmartCouponPro', 'uninstall_plugin'));
    }

    // Enqueue JS for tracking clicks
    public function enqueue_scripts() {
        wp_enqueue_script('scp-script', plugin_dir_url(__FILE__) . 'scp-script.js', array('jquery'), '1.0', true);
        wp_localize_script('scp-script', 'scp_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    // Display coupon shortcode function
    public function display_coupon($atts) {
        $atts = shortcode_atts(array(
            'id' => 'default',
        ), $atts, 'smartcouponpro_display');

        // Simulate AI-powered offer selection based on user behavior (mock example)
        $offers = array(
            'default' => array(
                'code' => 'SAVE10',
                'desc' => 'Save 10% on your next purchase!',
                'url' => 'https://example.com/shop?ref=scp'
            ),
            'vip' => array(
                'code' => 'VIP20',
                'desc' => 'Exclusive 20% VIP discount!',
                'url' => 'https://example.com/vip-shop?ref=scp'
            )
        );

        // For simplicity, randomly assign visitor a coupon offer; in real case use behavioral data
        $offer_key = (rand(0, 1) === 1) ? 'vip' : 'default';
        $offer = $offers[$offer_key];

        ob_start();
        ?>
        <div class="scp-coupon-box" style="border:1px solid #ddd;padding:15px;margin:15px 0;background:#f9f9f9;max-width:300px;">
            <h3 style="margin-top:0;">Exclusive Offer</h3>
            <p><?php echo esc_html($offer['desc']); ?></p>
            <button class="scp-copy-code" data-code="<?php echo esc_attr($offer['code']); ?>" data-url="<?php echo esc_url($offer['url']); ?>">Copy Code & Shop</button>
            <small style="display:block;margin-top:10px;color:#666;">Clicking the button will copy the coupon code and redirect you.</small>
        </div>
        <?php
        return ob_get_clean();
    }

    // Track clicks with AJAX
    public function track_click() {
        if (isset($_POST['code'])) {
            $code = sanitize_text_field($_POST['code']);
            $count_key = 'scp_click_count_' . $code;
            $count = (int)get_option($count_key, 0);
            update_option($count_key, $count + 1);
            wp_send_json_success(array('new_count' => $count + 1));
        } else {
            wp_send_json_error('No coupon code provided');
        }
    }

    // Activation hook
    public function activate_plugin() {
        // Initialize default data or options if needed
    }

    // Uninstall hook
    public static function uninstall_plugin() {
        global $wpdb;
        // Delete all click count options
        $codes = array('SAVE10', 'VIP20');
        foreach ($codes as $code) {
            delete_option('scp_click_count_' . $code);
        }
    }
}

new SmartCouponPro();

// JavaScript embedded output to handle copy and redirect, inline for self-contained
add_action('wp_footer', function() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($){
        $(document).on('click', '.scp-copy-code', function(e){
            e.preventDefault();
            var code = $(this).data('code');
            var url = $(this).data('url');
            // Copy code to clipboard
            navigator.clipboard.writeText(code).then(function() {
                // Track click via AJAX
                $.post(scp_ajax.ajaxurl, {action:'scp_track_click', code: code});
                // Redirect after short delay
                setTimeout(function(){
                    window.location.href = url;
                }, 300);
            });
        });
    });
    </script>
    <?php
});