/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: WP Exclusive Coupons Pro
 * Plugin URI: https://example.com/wp-exclusive-coupons
 * Description: Generate exclusive affiliate coupons with tracking and auto-expiry to maximize commissions.
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('wpec_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        wp_register_style('wpec-admin', plugin_dir_url(__FILE__) . 'admin.css', array(), '1.0');
        wp_register_script('wpec-admin', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0', true);
        load_plugin_textdomain('wp-exclusive-coupons', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function admin_menu() {
        add_menu_page(
            'Exclusive Coupons',
            'Coupons Pro',
            'manage_options',
            'wpec-coupons',
            array($this, 'admin_page'),
            'dashicons-tickets-alt',
            30
        );
    }

    public function admin_page() {
        if (isset($_POST['wpec_save'])) {
            update_option('wpec_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('wpec_coupons', '[]');
        ?>
        <div class="wrap">
            <h1>WP Exclusive Coupons Pro</h1>
            <p><strong>Free:</strong> Basic coupons. <a href="#" onclick="alert('Upgrade to Pro for analytics & unlimited coupons!')">Upgrade to Pro ($49/yr)</a></p>
            <form method="post">
                <textarea name="coupons" rows="20" cols="80" placeholder='[{"code":"SAVE20","afflink":"https://affiliate.com/?coupon=SAVE20","desc":"20% off","expiry":"2026-12-31"}]'><?php echo esc_textarea($coupons); ?></textarea>
                <p class="submit"><input type="submit" name="wpec_save" class="button-primary" value="Save Coupons"></p>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[wpec_coupon id="0"]</code> (Free: up to 3 coupons)</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $coupons = json_decode(get_option('wpec_coupons', '[]'), true);
        if (!isset($atts['id']) || !isset($coupons[$atts['id']])) {
            return 'Invalid coupon.';
        }
        $coupon = $coupons[$atts['id']];
        $today = date('Y-m-d');
        if ($today > $coupon['expiry']) {
            return '<p class="wpec-expired">Coupon expired!</p>';
        }
        $click_id = uniqid('wpec_');
        $track_link = add_query_arg('wpec', $click_id, $coupon['afflink']);
        ob_start();
        ?>
        <div class="wpec-coupon" style="border:2px dashed #0073aa; padding:20px; margin:20px 0; background:#f9f9f9;">
            <h3><?php echo esc_html($coupon['desc']); ?></h3>
            <p><strong>Code:</strong> <code><?php echo esc_html($coupon['code']); ?></code></p>
            <p><a href="<?php echo esc_url($track_link); ?>" target="_blank" class="button button-large" style="background:#0073aa;color:white;padding:10px 20px;text-decoration:none;">Get Deal & Copy Code</a></p>
            <small>Expires: <?php echo esc_html($coupon['expiry']); ?></small>
        </div>
        <script>
        jQuery(function($){
            $('.wpec-coupon a').click(function(){ trackCoupon('<?php echo $click_id; ?>'); });
        });
        function trackCoupon(id){ /* Pro: Send to analytics */ console.log('Tracked:', id); }
        </script>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('wpec_coupons')) {
            update_option('wpec_coupons', json_encode(array(
                array('code' => 'WELCOME10', 'afflink' => '#', 'desc' => '10% Off First Purchase', 'expiry' => '2026-06-30')
            )));
        }
    }
}

new WP_Exclusive_Coupons();

// Pro teaser
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Unlock <strong>WP Exclusive Coupons Pro</strong>: Unlimited coupons, click tracking, analytics! <a href="#">Upgrade Now</a></p></div>';
});