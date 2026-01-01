/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate exclusive affiliate coupons with tracking, expiration, and revenue analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: exclusive-coupons-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define plugin constants
define('ECP_VERSION', '1.0.0');
define('ECP_PATH', plugin_dir_path(__FILE__));
define('ECP_URL', plugin_dir_url(__FILE__));

// Pro check (simulate with license key for freemium)
function ecp_is_pro() {
    return get_option('ecp_pro_license', false);
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
        'dashicons-tickets',
        30
    );
}

// Admin page
function ecp_admin_page() {
    if (!current_user_can('manage_options')) return;
    ?>
    <div class="wrap">
        <h1><?php _e('Exclusive Coupons Pro', 'exclusive-coupons-pro'); ?></h1>
        <?php
        if (isset($_POST['ecp_create_coupon'])) {
            ecp_create_coupon($_POST['code'], $_POST['affiliate_url'], $_POST['expiry'], $_POST['description']);
        }
        if (isset($_POST['ecp_delete_coupon'])) {
            ecp_delete_coupon($_POST['coupon_id']);
        }
        $coupons = get_option('ecp_coupons', array());
        ?>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>Coupon Code</th>
                    <td><input type="text" name="code" required placeholder="SAVE20" /></td>
                </tr>
                <tr>
                    <th>Affiliate URL</th>
                    <td><input type="url" name="affiliate_url" required style="width: 400px;" /></td>
                </tr>
                <tr>
                    <th>Expiry (days)</th>
                    <td><input type="number" name="expiry" value="30" min="1" /></td>
                </tr>
                <tr>
                    <th>Description</th>
                    <td><textarea name="description"></textarea></td>
                </tr>
            </table>
            <p><input type="submit" name="ecp_create_coupon" class="button-primary" value="Create Coupon" /></p>
        </form>
        <h2>Your Coupons</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr><th>ID</th><th>Code</th><th>URL</th><th>Expiry</th><th>Uses</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($coupons as $id => $coupon): ?>
                <tr>
                    <td><?php echo $id; ?></td>
                    <td><?php echo esc_html($coupon['code']); ?></td>
                    <td><?php echo esc_html($coupon['url']); ?></td>
                    <td><?php echo $coupon['expiry'] ? date('Y-m-d', strtotime($coupon['created'] . ' + ' . $coupon['expiry'] . ' days')) : 'Never'; ?></td>
                    <td><?php echo isset($coupon['uses']) ? $coupon['uses'] : 0; ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="coupon_id" value="<?php echo $id; ?>" />
                            <input type="submit" name="ecp_delete_coupon" value="Delete" class="button" onclick="return confirm('Delete?');" />
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!ecp_is_pro()): ?>
        <div class="notice notice-info">
            <p>Upgrade to <strong>Pro</strong> for unlimited coupons, analytics, and auto-sharing! <a href="#pro">Get Pro ($49)</a></p>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

// Create coupon
function ecp_create_coupon($code, $url, $expiry, $desc) {
    $coupons = get_option('ecp_coupons', array());
    $id = time();
    $coupons[$id] = array(
        'code' => sanitize_text_field($code),
        'url' => esc_url_raw($url),
        'expiry' => intval($expiry),
        'description' => sanitize_textarea_field($desc),
        'created' => current_time('mysql'),
        'uses' => 0
    );
    update_option('ecp_coupons', $coupons);
    add_action('admin_notices', function() { echo '<div class="notice notice-success"><p>Coupon created!</p></div>'; });
}

// Delete coupon
function ecp_delete_coupon($id) {
    $coupons = get_option('ecp_coupons', array());
    unset($coupons[$id]);
    update_option('ecp_coupons', $coupons);
    add_action('admin_notices', function() { echo '<div class="notice notice-success"><p>Coupon deleted!</p></div>'; });
}

// Shortcode [ecp_coupon id="1"]
add_shortcode('ecp_coupon', 'ecp_shortcode');
function ecp_shortcode($atts) {
    $atts = shortcode_atts(array('id' => 0), $atts);
    $coupons = get_option('ecp_coupons', array());
    if (!isset($coupons[$atts['id']])) return '';
    $coupon = $coupons[$atts['id']];
    $expired = $coupon['expiry'] && strtotime($coupon['created'] . ' + ' . $coupon['expiry'] . ' days') < current_time('timestamp');
    if ($expired) return '<p><em>This coupon has expired.</em></p>';
    
    ob_start();
    ?>
    <div class="ecp-coupon" style="border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9;">
        <h3>Exclusive Deal: <strong><?php echo esc_html($coupon['code']); ?></strong></h3>
        <p><?php echo esc_html($coupon['description']); ?></p>
        <a href="<?php echo $coupon['url']; ?>&coupon=<?php echo $coupon['code']; ?>" target="_blank" class="button button-large" style="background: #0073aa; color: white; padding: 10px 20px; text-decoration: none;">Redeem Now</a>
        <small style="display: block; margin-top: 10px;">Limited time offer</small>
    </div>
    <script>
    jQuery('.ecp-coupon a').on('click', function() {
        // Track click (Pro feature stub)
        if (typeof gtag !== 'undefined') gtag('event', 'coupon_click', {'coupon_id': '<?php echo $atts['id']; ?>'});
    });
    </script>
    <?php
    return ob_get_clean();
}

// Track usage (simplified, Pro would use AJAX/database)
add_action('wp_ajax_ecp_track_use', 'ecp_track_use');
function ecp_track_use() {
    if (!wp_verify_nonce($_POST['nonce'], 'ecp_nonce')) wp_die();
    $id = intval($_POST['id']);
    $coupons = get_option('ecp_coupons', array());
    if (isset($coupons[$id])) {
        $coupons[$id]['uses'] = ($coupons[$id]['uses'] ?? 0) + 1;
        update_option('ecp_coupons', $coupons);
    }
    wp_die();
}

// Widget support
add_action('widgets_init', function() {
    register_widget('ECP_Widget');
});
class ECP_Widget extends WP_Widget {
    function __construct() {
        parent::__construct('ecp_widget', 'Exclusive Coupon');
    }
    public function widget($args, $instance) {
        $id = !empty($instance['coupon_id']) ? $instance['coupon_id'] : 0;
        echo do_shortcode('[ecp_coupon id="' . $id . '"]');
    }
    public function form($instance) {
        $id = isset($instance['coupon_id']) ? $instance['coupon_id'] : 0;
        echo '<p><label>Coupon ID: <input type="number" name="' . $this->get_field_name('coupon_id') . '" value="' . esc_attr($id) . '" /></label></p>';
    }
    public function update($new, $old) {
        $instance = array();
        $instance['coupon_id'] = intval($new['coupon_id']);
        return $instance;
    }
}

// Enqueue styles
add_action('wp_enqueue_scripts', 'ecp_styles');
function ecp_styles() {
    wp_enqueue_style('ecp-style', ECP_URL . 'style.css', array(), ECP_VERSION);
}

// Create style.css placeholder
add_action('init', function() {
    if (!file_exists(ECP_PATH . 'style.css')) {
        file_put_contents(ECP_PATH . 'style.css', '/* Exclusive Coupons Pro Styles */ .ecp-coupon { max-width: 500px; }');
    }
});

// Freemium upsell notice
add_action('admin_notices', function() {
    if (!ecp_is_pro() && get_option('ecp_coupons')) {
        echo '<div class="notice notice-upgrade notice-info"><p>Unlock Pro features: <a href="#pro">Upgrade Now</a></p></div>';
    }
});