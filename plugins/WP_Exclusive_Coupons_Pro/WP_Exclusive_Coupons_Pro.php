/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: WP Exclusive Coupons Pro
 * Plugin URI: https://example.com/wp-exclusive-coupons
 * Description: Generate exclusive affiliate coupons with tracking and limits.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: wp-exclusive-coupons
 */

if (!defined('ABSPATH')) exit;

class WPExclusiveCoupons {
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('wp-exclusive-coupons', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('wpec-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('wpec-js', 'wpec_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('wpec_nonce')));
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons', 'Coupons Pro', 'manage_options', 'wp-exclusive-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['wpec_save'])) {
            update_option('wpec_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('wpec_coupons', '');
        ?>
        <div class="wrap">
            <h1>WP Exclusive Coupons Pro</h1>
            <form method="post">
                <textarea name="coupons" rows="10" cols="50" placeholder="Code|AffiliateLink|Uses|Expiry (YYYY-MM-DD)"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="description">Format each line: CODE|https://affiliate.link|5|2026-12-31 (Uses: 0 for unlimited)</p>
                <p><input type="submit" name="wpec_save" class="button-primary" value="Save Coupons"></p>
            </form>
            <h2>Shortcode</h2>
            <p>Use <code>[exclusive_coupon code="YOURCODE"]</code> to display coupon button.</p>
            <?php if (!class_exists('WPECPro')) { ?>
            <div class="notice notice-info"><p><strong>Pro Version:</strong> Unlimited coupons, analytics, emails. <a href="#" onclick="alert('Upgrade to Pro for $49/year')">Upgrade Now</a></p></div>
            <?php } ?>
        </div>
        <?php
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('wpec_nonce', 'nonce');
        $code = sanitize_text_field($_POST['code']);
        $coupons = explode("\n", get_option('wpec_coupons', ''));
        foreach ($coupons as $line) {
            $parts = explode('|', trim($line));
            if (count($parts) === 4 && $parts === $code) {
                $uses = (int)$parts[2];
                $expiry = $parts[3];
                if (($uses == 0 || $uses > 0) && strtotime($expiry) > time()) {
                    if ($uses > 0) {
                        $parts[2] = $uses - 1;
                        $coupons[array_search($line, $coupons)] = implode('|', $parts);
                        update_option('wpec_coupons', implode("\n", $coupons));
                    }
                    wp_send_json_success(array('url' => $parts[1], 'code' => $code));
                }
            }
        }
        wp_send_json_error('Invalid or expired coupon');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('code' => ''), $atts);
        if (empty($atts['code'])) return '';
        ob_start();
        ?>
        <div class="wpec-coupon">
            <button class="wpec-btn" data-code="<?php echo esc_attr($atts['code']); ?>">Get Exclusive <?php echo esc_html($atts['code']); ?> Coupon</button>
            <div class="wpec-status"></div>
        </div>
        <style>
        .wpec-coupon { margin: 20px 0; }
        .wpec-btn { background: #0073aa; color: white; padding: 10px 20px; border: none; cursor: pointer; }
        .wpec-btn:disabled { background: #ccc; }
        </style>
        <script>
        jQuery('.wpec-btn[data-code="<?php echo esc_js($atts['code']); ?>"]').click(function() {
            var btn = jQuery(this), status = btn.siblings('.wpec-status');
            btn.prop('disabled', true).text('Redeeming...');
            jQuery.post(wpec_ajax.ajax_url, {action: 'generate_coupon', code: btn.data('code'), nonce: wpec_ajax.nonce}, function(res) {
                if (res.success) {
                    status.html('<a href="' + res.data.url + '" target="_blank">Click for ' + res.data.code + ' (One-time use!)</a>');
                } else {
                    status.html('<span style="color:red;">Coupon invalid or expired</span>');
                    btn.prop('disabled', false).text('Try Again');
                }
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('wpec_coupons')) {
            update_option('wpec_coupons', "DEMO20|https://example-affiliate.com/?coupon=DEMO20|10|2026-06-30");
        }
    }
}

WPExclusiveCoupons::get_instance();

class WPECPro {
    // Pro stub - extend in premium version
}