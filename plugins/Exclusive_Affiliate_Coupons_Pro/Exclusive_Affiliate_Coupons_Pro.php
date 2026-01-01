/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Affiliate_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Affiliate Coupons Pro
 * Plugin URI: https://example.com/exclusive-affiliate-coupons
 * Description: Automatically generates and displays personalized affiliate coupon codes with tracking to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class ExclusiveAffiliateCoupons {
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
        add_shortcode('eac_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_eac_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_eac_track_click', array($this, 'track_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('eac_pro') !== 'yes') {
            add_action('admin_notices', array($this, 'pro_notice'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('eac-script', plugin_dir_url(__FILE__) . 'eac.js', array('jquery'), '1.0.0', true);
        wp_localize_script('eac-script', 'eac_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('eac_nonce')));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate' => '',
            'code' => 'SAVE10',
            'discount' => '10%',
            'link' => '#',
            'limit' => 100
        ), $atts);

        $used = get_option('eac_used_' . sanitize_key($atts['code']), 0);
        if ($used >= $atts['limit'] && get_option('eac_pro') !== 'yes') {
            return '<p>Coupon limit reached. <a href="https://example.com/pro">Upgrade to Pro</a> for unlimited uses.</p>';
        }

        ob_start();
        ?>
        <div class="eac-coupon" data-affiliate="<?php echo esc_attr($atts['affiliate']); ?>" data-code="<?php echo esc_attr($atts['code']); ?>">
            <h3>Exclusive Coupon: <strong><?php echo esc_html($atts['code']); ?></strong> - <?php echo esc_html($atts['discount']); ?> Off!</h3>
            <a href="<?php echo esc_url($atts['link']); ?}" class="eac-button" target="_blank">Get Deal Now</a>
            <span class="eac-uses"><?php echo $used; ?>/<?php echo $atts['limit']; ?> used</span>
        </div>
        <?php
        return ob_get_clean();
    }

    public function track_click() {
        check_ajax_referer('eac_nonce', 'nonce');
        $code = sanitize_text_field($_POST['code']);
        $used = (int) get_option('eac_used_' . $code, 0);
        $used++;
        update_option('eac_used_' . $code, $used);
        wp_die(json_encode(array('success' => true, 'used' => $used)));
    }

    public function pro_notice() {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Exclusive Affiliate Coupons Pro</strong> for unlimited coupons and analytics! <a href="https://example.com/pro">Get Pro</a></p></div>';
    }

    public function activate() {
        add_option('eac_used_SAVE10', 0);
    }
}

ExclusiveAffiliateCoupons::get_instance();

// Pro check simulation
function eac_is_pro() { return get_option('eac_pro') === 'yes'; }

// JS file content (embedded for single file)
/*
jQuery(document).ready(function($) {
    $('.eac-button').click(function(e) {
        e.preventDefault();
        var $coupon = $(this).closest('.eac-coupon');
        var code = $coupon.data('code');
        $.post(eac_ajax.ajax_url, {
            action: 'eac_track_click',
            nonce: eac_ajax.nonce,
            code: code
        }, function(res) {
            if (res.success) {
                $coupon.find('.eac-uses').text(res.used + '/100 used');
            }
        });
        window.open($(this).attr('href'), '_blank');
    });
});
*/

// Embed JS as inline for single file
add_action('wp_footer', function() {
    if (!is_admin()) {
        ?>
        <script>jQuery(document).ready(function($) { $('.eac-button').click(function(e) { e.preventDefault(); var $coupon = $(this).closest('.eac-coupon'); var code = $coupon.data('code'); $.post(eac_ajax.ajax_url, { action: 'eac_track_click', nonce: eac_ajax.nonce, code: code }, function(res) { if (res.success) { $coupon.find('.eac-uses').text(res.used + '/100 used'); } }); window.open($(this).attr('href'), '_blank'); }); });</script>
        <?php
    }
});