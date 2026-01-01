/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupons Pro
 * Plugin URI: https://example.com/smart-affiliate-coupons
 * Description: Automatically generates and displays personalized affiliate coupon codes with tracking, boosting conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartAffiliateCoupons {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_generate_coupon', array($this, 'ajax_generate_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sac_pro_version')) {
            // Pro features
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sac-script', plugin_dir_url(__FILE__) . 'sac-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sac-script', 'sac_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate_id' => 'default',
            'discount' => '10%',
            'product' => 'Product Name',
            'link' => '#',
        ), $atts);

        ob_start();
        ?>
        <div class="sac-coupon-box">
            <h3><?php echo esc_html($atts['product']); ?> - <?php echo esc_html($atts['discount']); ?> OFF</h3>
            <div id="sac-coupon-<?php echo esc_attr($atts['affiliate_id']); ?>" class="sac-coupon-code">GENERATE COUPON</div>
            <a href="<?php echo esc_url($atts['link']); ?}" target="_blank" class="sac-affiliate-btn" data-affid="<?php echo esc_attr($atts['affiliate_id']); ?>">Shop Now & Save</a>
            <div class="sac-tracking" data-affid="<?php echo esc_attr($atts['affiliate_id']); ?>" style="display:none;"></div>
        </div>
        <style>
        .sac-coupon-box { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9; }
        .sac-coupon-code { background: #fff; font-size: 24px; font-weight: bold; margin: 10px 0; padding: 15px; border: 1px solid #ddd; }
        .sac-affiliate-btn { display: inline-block; background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        .sac-affiliate-btn:hover { background: #005a87; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        $affid = sanitize_text_field($_POST['affid']);
        $code = 'SAVE' . $affid . substr(md5(uniqid()), 0, 8);
        $tracking = $this->track_click($affid);
        wp_send_json_success(array('code' => $code, 'tracking' => $tracking));
    }

    private function track_click($affid) {
        $clicks = get_option('sac_clicks_' . $affid, 0) + 1;
        update_option('sac_clicks_' . $affid, $clicks);
        return $clicks;
    }

    public function activate() {
        add_option('sac_pro_version', false);
    }
}

new SmartAffiliateCoupons();

// Pro upgrade notice
function sac_pro_notice() {
    if (!get_option('sac_pro_version') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Smart Affiliate Coupons Pro</strong> for unlimited coupons and advanced tracking! <a href="https://example.com/pro">Get Pro</a></p></div>';
    }
}
add_action('admin_notices', 'sac_pro_notice');

// JS file content (embedded for single file)
/*
$(document).ready(function() {
    $('.sac-coupon-code').click(function() {
        var box = $(this);
        var affid = box.closest('.sac-coupon-box').find('.sac-affiliate-btn').data('affid');
        $.post(sac_ajax.ajaxurl, {action: 'generate_coupon', affid: affid}, function(res) {
            if (res.success) {
                box.html(res.data.code).css({'color': '#e74c3c', 'cursor': 'default'});
                $('.sac-tracking', box.closest('.sac-coupon-box')).html('Tracked: ' + res.data.tracking + ' clicks').show();
            }
        });
    });
});
*/
?>