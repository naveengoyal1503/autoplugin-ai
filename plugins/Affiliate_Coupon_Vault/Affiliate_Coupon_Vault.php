/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupon codes with tracking, boosting conversions for bloggers and site owners.
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
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('acv_pro_version')) {
            // Pro features here
        }
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'acv-style.css', array(), '1.0.0');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate_id' => 'default',
            'discount' => '10%',
            'product' => 'Featured Product',
            'link' => 'https://affiliate-link.com',
        ), $atts);

        ob_start();
        ?>
        <div class="acv-coupon-container">
            <div class="acv-coupon-code" id="acv-code-<?php echo esc_attr($atts['affiliate_id']); ?>">GENERATING...</div>
            <button class="acv-generate-btn" data-affid="<?php echo esc_attr($atts['affiliate_id']); ?>">Generate Coupon</button>
            <p>Save <strong><?php echo esc_html($atts['discount']); ?></strong> on <?php echo esc_html($atts['product']); ?>!</p>
            <a href="<?php echo esc_url($atts['link']); ?>" class="acv-aff-link" target="_blank" rel="nofollow">Shop Now</a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('acv_nonce', 'nonce');
        $affid = sanitize_text_field($_POST['affid']);
        $code = 'ACV' . wp_generate_uuid4() . substr(md5($affid . time()), 0, 8);
        $tracking_url = add_query_arg(array('coupon' => $code, 'ref' => $affid), $_POST['link']);

        wp_send_json_success(array(
            'code' => $code,
            'tracking_url' => $tracking_url
        ));
    }

    public function activate() {
        add_option('acv_activated', time());
        flush_rewrite_rules();
    }
}

// Enqueue JS
add_action('wp_footer', function() {
    if (is_production()) return; // Simplified for single file
echo '<script>jQuery(document).ready(function($) {
    $(".acv-generate-btn").click(function() {
        var btn = $(this);
        var cont = btn.parent();
        var affid = btn.data("affid");
        $.post(acv_ajax.ajax_url, {
            action: "acv_generate_coupon",
            nonce: acv_ajax.nonce,
            affid: affid,
            link: cont.find(".acv-aff-link").attr("href")
        }, function(res) {
            if (res.success) {
                cont.find(".acv-coupon-code").text(res.data.code);
                cont.find(".acv-aff-link").attr("href", res.data.tracking_url);
            }
        });
    });
});</script>';
    echo '<style>.acv-coupon-container { border: 2px dashed #007cba; padding: 20px; text-align: center; margin: 20px 0; background: #f9f9f9; border-radius: 8px; }.acv-coupon-code { font-size: 24px; font-weight: bold; color: #007cba; margin-bottom: 10px; background: white; padding: 10px; border-radius: 4px; }.acv-generate-btn { background: #007cba; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 4px; }.acv-generate-btn:hover { background: #005a87; }.acv-aff-link { display: inline-block; margin-top: 10px; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 4px; }</style>';
});

AffiliateCouponVault::get_instance();