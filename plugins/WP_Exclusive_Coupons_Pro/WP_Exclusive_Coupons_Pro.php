/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: WP Exclusive Coupons Pro
 * Plugin URI: https://example.com/wp-exclusive-coupons
 * Description: Automatically generates, manages, and displays exclusive affiliate coupons with tracking to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: wp-exclusive-coupons
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Exclusive_Coupons {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_save_coupon', array($this, 'ajax_save_coupon'));
        add_action('wp_ajax_nopriv_save_coupon', array($this, 'ajax_save_coupon'));
        add_shortcode('exclusive_coupons', array($this, 'coupons_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('wpec_pro_version') !== '1.0') {
            add_action('admin_notices', array($this, 'pro_notice'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('wpec-js', plugin_dir_url(__FILE__) . 'wpec.js', array('jquery'), '1.0', true);
        wp_localize_script('wpec-js', 'wpec_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
        wp_enqueue_style('wpec-css', plugin_dir_url(__FILE__) . 'wpec.css', array(), '1.0');
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons', 'Coupons', 'manage_options', 'wpec', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['wpec_code'])) {
            update_option('wpec_code', sanitize_text_field($_POST['wpec_code']));
            update_option('wpec_affiliate_link', esc_url_raw($_POST['wpec_affiliate_link']));
            echo '<div class="notice notice-success"><p>Coupon saved!</p></div>';
        }
        $code = get_option('wpec_code', 'SAVE20');
        $link = get_option('wpec_affiliate_link', '');
        ?>
        <div class="wrap">
            <h1>WP Exclusive Coupons</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupon Code</th>
                        <td><input type="text" name="wpec_code" value="<?php echo esc_attr($code); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Link</th>
                        <td><input type="url" name="wpec_affiliate_link" value="<?php echo esc_attr($link); ?>" style="width: 100%;" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcode: <code>[exclusive_coupons]</code></p>
            <p><strong>Pro Version:</strong> Unlimited coupons, click tracking, analytics. <a href="https://example.com/pro">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function ajax_save_coupon() {
        if (!wp_verify_nonce($_POST['nonce'], 'wpec_nonce')) {
            wp_die('Security check failed');
        }
        $clicks = get_option('wpec_clicks', 0) + 1;
        update_option('wpec_clicks', $clicks);
        wp_redirect(get_option('wpec_affiliate_link'));
        exit;
    }

    public function coupons_shortcode($atts) {
        $code = get_option('wpec_code', 'SAVE20');
        $link = get_option('wpec_affiliate_link', '#');
        ob_start();
        ?>
        <div id="wpec-coupon" class="wpec-banner">
            <h3>Exclusive Deal: <strong><?php echo esc_html($code); ?></strong></h3>
            <p>Get 20% off! Limited time only.</p>
            <a href="#" class="wpec-btn" data-nonce="<?php echo wp_create_nonce('wpec_nonce'); ?>">Claim Coupon</a>
            <small>Tracked clicks: <span id="wpec-clicks"><?php echo get_option('wpec_clicks', 0); ?></span></small>
        </div>
        <script>
        jQuery('.wpec-btn').click(function(e) {
            e.preventDefault();
            jQuery.post(wpec_ajax.ajax_url, {
                action: 'save_coupon',
                nonce: jQuery(this).data('nonce')
            }, function() {
                window.location = '<?php echo esc_js($link); ?>';
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function pro_notice() {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>WP Exclusive Coupons Pro</strong> for advanced features like unlimited coupons and analytics!</p></div>';
    }

    public function activate() {
        update_option('wpec_clicks', 0);
    }
}

new WP_Exclusive_Coupons();

// Pro upsell placeholder
function wpec_pro_upsell() {
    return 'Upgrade to Pro for more features!';
}
?>