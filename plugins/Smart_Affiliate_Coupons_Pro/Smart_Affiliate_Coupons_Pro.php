/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupons Pro
 * Plugin URI: https://example.com/smart-affiliate-coupons
 * Description: Automatically generates and displays personalized affiliate coupon codes, tracks clicks, and boosts conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateCoupons {
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
        add_shortcode('sac_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_sac_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_sac_track_click', array($this, 'track_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sac_pro_version')) {
            // Pro features
        }
        load_plugin_textdomain('smart-affiliate-coupons', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sac-script', plugin_dir_url(__FILE__) . 'sac-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sac-script', 'sac_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sac_nonce')));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate_url' => '',
            'coupon_code' => 'SAVE20',
            'description' => 'Get 20% off with this exclusive coupon!',
            'button_text' => 'Reveal Coupon & Shop',
            'pro' => false
        ), $atts);

        if (empty($atts['affiliate_url'])) {
            return '<p>Missing affiliate URL.</p>';
        }

        ob_start();
        ?>
        <div class="sac-coupon-box" data-url="<?php echo esc_url($atts['affiliate_url']); ?>" data-code="<?php echo esc_attr($atts['coupon_code']); ?>">
            <p><?php echo esc_html($atts['description']); ?></p>
            <button class="sac-reveal-btn" id="sac-btn-<?php echo uniqid(); ?>"><?php echo esc_html($atts['button_text']); ?></button>
            <div class="sac-coupon-revealed" style="display:none;">
                <strong>Coupon: <span class="sac-code"><?php echo esc_html($atts['coupon_code']); ?></span></strong>
                <a href="#" class="sac-shop-btn" style="display:none;">Shop Now (Tracked)</a>
            </div>
        </div>
        <style>
        .sac-coupon-box { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9; }
        .sac-reveal-btn { background: #0073aa; color: white; padding: 10px 20px; border: none; cursor: pointer; }
        .sac-shop-btn { background: #00a32a; color: white; padding: 10px 20px; text-decoration: none; display: inline-block; margin-top: 10px; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function track_click() {
        check_ajax_referer('sac_nonce', 'nonce');
        $url = sanitize_url($_POST['url']);
        $code = sanitize_text_field($_POST['code']);
        // Log click (free version limits, pro unlimited)
        if (!get_option('sac_pro_version') && $this->get_click_count_today() >= 100) {
            wp_die('Upgrade to Pro for unlimited tracking.');
        }
        update_option('sac_clicks_' . date('Y-m-d'), (get_option('sac_clicks_' . date('Y-m-d')) ?: 0) + 1);
        wp_redirect($url);
        exit;
    }

    private function get_click_count_today() {
        return get_option('sac_clicks_' . date('Y-m-d'), 0);
    }

    public function activate() {
        add_option('sac_pro_version', false);
    }
}

SmartAffiliateCoupons::get_instance();

// Pro check function (simulate pro upgrade)
function sac_is_pro() {
    return get_option('sac_pro_version');
}

// JS file content would be enqueued, but for single file, inline it
add_action('wp_footer', function() { if (!wp_script_is('sac-script', 'enqueued')) return; ?>
<script>
jQuery(document).ready(function($) {
    $('.sac-reveal-btn').click(function(e) {
        e.preventDefault();
        var $box = $(this).closest('.sac-coupon-box');
        $box.find('.sac-coupon-revealed').show();
        $box.find('.sac-reveal-btn').hide();
    });
    $(document).on('click', '.sac-shop-btn', function(e) {
        e.preventDefault();
        var $box = $(this).closest('.sac-coupon-box');
        var url = $box.data('url');
        var code = $box.data('code');
        $.post(sac_ajax.ajax_url, {
            action: 'sac_track_click',
            nonce: sac_ajax.nonce,
            url: url,
            code: code
        }, function() {
            window.open(url, '_blank');
        });
    });
});
</script>
<?php });

// Admin page for settings
add_action('admin_menu', function() {
    add_options_page('Smart Affiliate Coupons', 'SAC Pro', 'manage_options', 'sac-pro', function() {
        if (isset($_POST['sac_pro_key'])) {
            update_option('sac_pro_version', true);
            echo '<div class="notice notice-success"><p>Pro activated!</p></div>';
        }
        echo '<div class="wrap"><h1>SAC Pro Settings</h1><form method="post"><p><label>Pro Key: <input type="text" name="sac_pro_key" placeholder="Enter pro key"></label> <input type="submit" value="Activate Pro" class="button-primary"></p></form><p><strong>Pro Features:</strong> Unlimited tracking, analytics dashboard, custom branding.</p></div>';
    });
});