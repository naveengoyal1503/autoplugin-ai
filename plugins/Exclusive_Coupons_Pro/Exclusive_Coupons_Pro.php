/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate exclusive affiliate coupons with tracking and limits for higher conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: exclusive-coupons-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ECP_VERSION', '1.0.0');
define('ECP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ECP_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Pro check (simulate with option; in real, check license)
function ecp_is_pro() {
    return get_option('ecp_pro_activated', false);
}

// Admin menu
add_action('admin_menu', 'ecp_admin_menu');
function ecp_admin_menu() {
    add_menu_page(
        'Exclusive Coupons',
        'Coupons Pro',
        'manage_options',
        'exclusive-coupons',
        'ecp_admin_page',
        'dashicons-tickets-alt',
        30
    );
}

// Admin page
function ecp_admin_page() {
    if (isset($_POST['ecp_save_coupon'])) {
        ecp_save_coupon();
    }
    if (isset($_POST['ecp_activate_pro'])) {
        update_option('ecp_pro_activated', true);
        echo '<div class="notice notice-success"><p>Pro activated! (Demo)</p></div>';
    }
    $coupons = get_option('ecp_coupons', array());
    ?>
    <div class="wrap">
        <h1>Exclusive Coupons Pro</h1>
        <?php if (!ecp_is_pro()): ?>
        <div class="notice notice-info">
            <p>Upgrade to Pro for unlimited coupons & analytics! <form method="post"><input type="submit" name="ecp_activate_pro" value="Activate Pro (Demo)" class="button-primary"></form></p>
        </div>
        <?php endif; ?>
        <h2>Add New Coupon</h2>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>Code</th>
                    <td><input type="text" name="code" required class="regular-text"></td>
                </tr>
                <tr>
                    <th>Affiliate Link</th>
                    <td><input type="url" name="link" required class="regular-text"></td>
                </tr>
                <tr>
                    <th>Discount (%)</th>
                    <td><input type="number" name="discount" step="0.01" class="small-text"></td>
                </tr>
                <?php if (ecp_is_pro()): ?>
                <tr>
                    <th>Max Uses</th>
                    <td><input type="number" name="max_uses" value="1" class="small-text"></td>
                </tr>
                <tr>
                    <th>Expiry Date</th>
                    <td><input type="datetime-local" name="expiry"></td>
                </tr>
                <?php endif; ?>
            </table>
            <p><input type="submit" name="ecp_save_coupon" value="Add Coupon" class="button-primary"></p>
        </form>
        <h2>Your Coupons (<?php echo count($coupons); ?>)</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>Code</th><th>Link</th><th>Discount</th><th>Uses</th><th>Shortcode</th></tr></thead>
            <tbody>
            <?php foreach ($coupons as $id => $c): 
                $uses = get_option("ecp_uses_{$id}", 0);
                $max_uses = ecp_is_pro() ? ($c['max_uses'] ?? 1) : 999;
                $expired = ecp_is_pro() && isset($c['expiry']) && strtotime($c['expiry']) < time();
            ?>
                <tr <?php echo $expired ? 'style="opacity:0.5"' : ''; ?>>
                    <td><?php echo esc_html($c['code']); ?></td>
                    <td><?php echo esc_html($c['link']); ?></td>
                    <td><?php echo esc_html($c['discount'] ?? 'N/A'); ?>%</td>
                    <td><?php echo $uses; ?>/<?php echo $max_uses; ?><?php echo $expired ? ' (Expired)' : ''; ?></td>
                    <td>[ecp id="<?php echo $id; ?>"]</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function ecp_save_coupon() {
    if (!current_user_can('manage_options')) return;
    $coupons = get_option('ecp_coupons', array());
    $id = count($coupons);
    $coupons[$id] = array(
        'code' => sanitize_text_field($_POST['code']),
        'link' => esc_url_raw($_POST['link']),
        'discount' => floatval($_POST['discount']),
        'max_uses' => ecp_is_pro() ? intval($_POST['max_uses']) : 999,
        'expiry' => ecp_is_pro() ? sanitize_text_field($_POST['expiry']) : ''
    );
    update_option('ecp_coupons', $coupons);
    echo '<div class="notice notice-success"><p>Coupon added!</p></div>';
}

// Shortcode
add_shortcode('ecp', 'ecp_shortcode');
function ecp_shortcode($atts) {
    $atts = shortcode_atts(array('id' => 0), $atts);
    $id = intval($atts['id']);
    $coupons = get_option('ecp_coupons', array());
    if (!isset($coupons[$id])) return 'Invalid coupon.';
    $c = $coupons[$id];
    $uses_key = "ecp_uses_{$id}";
    $uses = intval(get_option($uses_key, 0));
    $max_uses = ecp_is_pro() ? ($c['max_uses'] ?? 1) : 999;
    $expired = ecp_is_pro() && isset($c['expiry']) && strtotime($c['expiry']) < time();
    
    if ($expired || $uses >= $max_uses) {
        return '<div class="ecp-expired" style="padding:10px;background:#ffebee;color:#c62828;border:1px solid #f44336;border-radius:4px;">Coupon expired or max uses reached.</div>';
    }
    
    $output = '<div class="ecp-coupon" style="padding:15px;background:#e8f5e8;border:2px dashed #4caf50;border-radius:8px;text-align:center;font-family:Arial,sans-serif;">
        <h3 style="margin:0 0 10px;color:#2e7d32;">Exclusive Deal! <span style="color:#ff9800;font-size:1.2em;">'.esc_html($c['discount']).'% OFF</span></h3>
        <div style="font-size:1.5em;font-weight:bold;color:#2e7d32;margin:10px 0;">'.esc_html($c['code']).'</div>
        <a href="'.esc_url($c['link']).'" target="_blank" style="display:inline-block;padding:12px 24px;background:#4caf50;color:white;text-decoration:none;border-radius:6px;font-weight:bold;">Redeem Now</a>
        <p style="margin:10px 0 0;font-size:0.9em;color:#666;">Limited uses available!</p>
    </div>';
    
    return $output;
}

// Track use on link click (client-side simulation; server-side via AJAX in pro)
add_action('wp_footer', 'ecp_track_script');
function ecp_track_script() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.ecp-coupon a').on('click', function() {
            var id = $(this).closest('.ecp-coupon').data('id') || '0';
            // In pro: AJAX to track
            console.log('Coupon redeemed: ' + id);
        });
    });
    </script>
    <?php
}

// Freemium upsell notice
add_action('admin_notices', 'ecp_upsell_notice');
function ecp_upsell_notice() {
    if (!ecp_is_pro() && current_user_can('manage_options')) {
        echo '<div class="notice notice-warning"><p><strong>Exclusive Coupons Pro:</strong> Unlock unlimited coupons, usage tracking & auto-expiration for $49/year! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
    }
}

// Widget support
add_action('widgets_init', 'ecp_register_widget');
function ecp_register_widget() {
    register_widget('ECP_Widget');
}

class ECP_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct('ecp_widget', 'Exclusive Coupon', array('description' => 'Display a coupon'));
    }
    public function widget($args, $instance) {
        $id = !empty($instance['coupon_id']) ? $instance['coupon_id'] : 0;
        echo do_shortcode('[ecp id="' . $id . '"]');
    }
    public function form($instance) {
        $id = isset($instance['coupon_id']) ? $instance['coupon_id'] : 0;
        echo '<p><label>Coupon ID: <input type="number" class="widefat" name="' . $this->get_field_name('coupon_id') . '" value="' . esc_attr($id) . '"></label></p>';
    }
    public function update($new, $old) {
        return $new;
    }
}

register_activation_hook(__FILE__, 'ecp_activate');
function ecp_activate() {
    add_option('ecp_pro_activated', false);
}
