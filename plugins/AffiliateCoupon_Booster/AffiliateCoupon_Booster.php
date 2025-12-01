/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateCoupon_Booster.php
*/
<?php
/**
 * Plugin Name: AffiliateCoupon Booster
 * Description: Curates and displays affiliate coupons with expiration countdowns and conversion tracking.
 * Version: 1.0
 * Author: PluginDev
 */

if (!defined('ABSPATH')) exit;

class AffiliateCouponBooster {

    public function __construct() {
        add_shortcode('affiliate_coupons', array($this, 'render_coupons'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_acb_track_click', array($this, 'track_click')); 
        add_action('wp_ajax_nopriv_acb_track_click', array($this, 'track_click'));
    }

    public function enqueue_assets() {
        wp_enqueue_style('acb-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_enqueue_script('acb-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), false, true);
        wp_localize_script('acb-script', 'acb_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('acb_nonce')
        ));
    }

    private function get_coupons() {
        // For demo, static coupons
        return array(
            array('id'=>1, 'title'=>'Save 20% on Shoes', 'code'=>'SHOE20', 'url'=>'https://example.com/shoes?aff=123', 'expiry'=>'2025-12-10'),
            array('id'=>2, 'title'=>'30% Off Electronics', 'code'=>'ELEC30', 'url'=>'https://example.com/electronics?aff=123', 'expiry'=>'2025-12-05'),
            array('id'=>3, 'title'=>'Free Shipping on Orders $50+', 'code'=>'FREESHIP', 'url'=>'https://example.com/freeship?aff=123', 'expiry'=>'2025-12-20')
        );
    }

    public function render_coupons() {
        $coupons = $this->get_coupons();
        ob_start();
        echo '<div class="acb-coupons-container">';
        foreach ($coupons as $coupon) {
            $expiry = strtotime($coupon['expiry']);
            $now = current_time('timestamp');
            $days_left = max(0, ceil(($expiry - $now) / DAY_IN_SECONDS));
            echo '<div class="acb-coupon" data-coupon-id="' . esc_attr($coupon['id']) . '">';
            echo '<h3>' . esc_html($coupon['title']) . '</h3>';
            echo '<p>Code: <strong>' . esc_html($coupon['code']) . '</strong></p>';
            if ($days_left > 0) {
                echo '<p>Expires in <span class="acb-days-left" data-expiry="'.esc_attr($coupon['expiry']).'">' . $days_left . ' days</span></p>';
            } else {
                echo '<p class="acb-expired">Expired</p>';
            }
            echo '<a href="#" class="acb-use-coupon" data-url="' . esc_url($coupon['url']) . '">Use Coupon</a>';
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    public function track_click() {
        check_ajax_referer('acb_nonce', 'nonce');
        $coupon_id = intval($_POST['coupon_id']);
        if ($coupon_id <= 0) {
            wp_send_json_error('Invalid coupon ID');
        }
        $count_key = 'acb_coupon_clicks_' . $coupon_id;
        $count = get_option($count_key, 0);
        update_option($count_key, $count + 1);
        wp_send_json_success('Click tracked');
    }
}

new AffiliateCouponBooster();

// Inline style for demonstration
add_action('wp_head', function () {
    echo '<style>
        .acb-coupons-container { max-width: 600px; margin: 20px auto; font-family: Arial,sans-serif; }
        .acb-coupon { border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px; background:#f9f9f9; }
        .acb-coupon h3 { margin: 0 0 5px 0; font-size: 1.2em; }
        .acb-coupon p { margin: 5px 0; }
        .acb-use-coupon { display: inline-block; margin-top: 10px; padding: 8px 12px; background: #28a745; color: white; text-decoration: none; border-radius: 3px; }
        .acb-use-coupon:hover { background: #218838; }
        .acb-expired { color: #dc3545; font-weight: bold; }
    </style>';
});

// Inline JS for demonstration
add_action('wp_footer', function () {
    ?>
    <script>
    jQuery(document).ready(function($){
        $('.acb-use-coupon').on('click', function(e){
            e.preventDefault();
            var url = $(this).data('url');
            var couponDiv = $(this).closest('.acb-coupon');
            var couponId = couponDiv.data('coupon-id');

            $.post(acb_ajax.ajax_url, {
                action: 'acb_track_click',
                coupon_id: couponId,
                nonce: acb_ajax.nonce
            });

            window.open(url, '_blank');
        });

        // Countdown update every day (optional enhancement)
        function updateCountdowns() {
            $('.acb-days-left').each(function() {
                var expiry = new Date($(this).data('expiry')); 
                var now = new Date();
                var diffTime = expiry.getTime() - now.getTime();
                var diffDays = Math.ceil(diffTime / (1000 * 3600 * 24));
                if (diffDays <= 0) {
                    $(this).text('Expired');
                    $(this).closest('.acb-coupon').find('.acb-use-coupon').hide();
                } else {
                    $(this).text(diffDays + ' days');
                }
            });
        }

        updateCountdowns();
        // Could setInterval for real-time but daily is sufficient
    });
    </script>
    <?php
});