/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupon_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupon Pro
 * Plugin URI: https://example.com/smart-affiliate-coupon-pro
 * Description: Automatically generates and displays personalized affiliate coupons with dynamic discounts to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-coupon-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateCouponPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('sac_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_sac_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_sac_generate_coupon', array($this, 'ajax_generate_coupon'));
    }

    public function init() {
        if (get_option('sac_pro_version')) {
            // Pro features
        }
        load_plugin_textdomain('smart-affiliate-coupon-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sac-script', plugin_dir_url(__FILE__) . 'sac-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sac-script', 'sac_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sac_nonce')));
        wp_enqueue_style('sac-style', plugin_dir_url(__FILE__) . 'sac-style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Coupon Pro', 'SAC Pro', 'manage_options', 'sac-pro', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['sac_save'])) {
            update_option('sac_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('sac_coupons', 'Amazon:10%,ExampleStore:15%');
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Coupon Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupons (Format: Store:Discount% | AffiliateLink)</th>
                        <td><textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button('Save Coupons', 'primary', 'sac_save'); ?>
            </form>
            <p><strong>Upgrade to Pro:</strong> Unlimited coupons, analytics, auto-rotation. <a href="#">Buy Now ($49/year)</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('store' => ''), $atts);
        ob_start();
        ?>
        <div id="sac-coupon" class="sac-coupon-container" data-store="<?php echo esc_attr($atts['store']); ?>">
            <div class="sac-coupon-code">GENERATING COUPON...</div>
            <div class="sac-discount">Discount: <span class="sac-discount-percent"></span></div>
            <a href="#" class="sac-copy-btn">Copy Code</a>
            <a href="#" class="sac-affiliate-link" target="_blank">Shop Now</a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('sac_nonce', 'nonce');
        $store = sanitize_text_field($_POST['store']);
        $coupons = explode('|', get_option('sac_coupons', ''));
        $selected = '';
        foreach ($coupons as $coupon) {
            $parts = explode(':', $coupon);
            if (count($parts) >= 2 && trim($parts) === $store) {
                $selected = $coupon;
                break;
            }
        }
        if (!$selected) {
            $selected = $coupons[array_rand($coupons)] ?? 'Amazon:10%||https://amazon.com/?tag=yourtag';
        }
        $parts = explode('|', $selected);
        $store_data = explode(':', $parts);
        $discount = $store_data[1] ?? '10%';
        $link = $parts[1] ?? 'https://example.com';
        $code = substr(md5($store . time()), 0, 8);
        wp_send_json_success(array('code' => $code, 'discount' => $discount, 'link' => $link));
    }
}

new SmartAffiliateCouponPro();

// Inline CSS
add_action('wp_head', function() { ?>
<style>
.sac-coupon-container { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9; border-radius: 10px; }
.sac-coupon-code { font-size: 24px; font-weight: bold; color: #007cba; margin-bottom: 10px; }
.sac-discount { font-size: 18px; margin-bottom: 15px; }
.sac-copy-btn, .sac-affiliate-link { display: inline-block; padding: 10px 20px; margin: 5px; text-decoration: none; border-radius: 5px; }
.sac-copy-btn { background: #28a745; color: white; }
.sac-affiliate-link { background: #007cba; color: white; }
.sac-copy-btn:hover, .sac-affiliate-link:hover { opacity: 0.8; }
</style>
<?php });

// JS
add_action('wp_footer', function() { ?>
<script>
jQuery(document).ready(function($) {
    $('.sac-coupon-container').each(function() {
        var $container = $(this);
        var store = $container.data('store');
        $.post(sac_ajax.ajaxurl, {
            action: 'sac_generate_coupon',
            nonce: sac_ajax.nonce,
            store: store
        }, function(response) {
            if (response.success) {
                $container.find('.sac-coupon-code').text(response.data.code);
                $container.find('.sac-discount-percent').text(response.data.discount);
                $container.find('.sac-affiliate-link').attr('href', response.data.link);
            }
        });
    });

    $(document).on('click', '.sac-copy-btn', function(e) {
        e.preventDefault();
        var code = $(this).closest('.sac-coupon-container').find('.sac-coupon-code').text();
        navigator.clipboard.writeText(code).then(function() {
            alert('Coupon code copied!');
        });
    });
});
</script>
<?php });