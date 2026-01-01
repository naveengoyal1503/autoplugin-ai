/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate and manage exclusive, trackable coupon codes for your audience, boosting affiliate conversions and site engagement with personalized discounts.
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
define('ECP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ECP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Pro check (simulate license - in real, use API)
function ecp_is_pro() {
    return get_option('ecp_pro_activated', false);
}

// Admin menu
add_action('admin_menu', 'ecp_admin_menu');
function ecp_admin_menu() {
    add_menu_page(
        'Exclusive Coupons Pro',
        'Coupons Pro',
        'manage_options',
        'exclusive-coupons-pro',
        'ecp_admin_page',
        'dashicons-tickets-alt',
        30
    );
}

// Admin page
function ecp_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php _e('Exclusive Coupons Pro', 'exclusive-coupons-pro'); ?></h1>
        <?php
        if (isset($_POST['ecp_save_coupon'])) {
            ecp_save_coupon();
        }
        if (isset($_POST['ecp_activate_pro'])) {
            update_option('ecp_pro_activated', true);
            echo '<div class="notice notice-success"><p>Pro activated! (Demo)</p></div>';
        }
        $coupons = get_option('ecp_coupons', array());
        ?>
        <h2>Create New Coupon</h2>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>Code</th>
                    <td><input type="text" name="code" required style="width:200px;" /></td>
                </tr>
                <tr>
                    <th>Description</th>
                    <td><input type="text" name="description" required style="width:300px;" /></td>
                </tr>
                <tr>
                    <th>Affiliate Link</th>
                    <td><input type="url" name="link" required style="width:400px;" /></td>
                </tr>
                <tr>
                    <th>Discount</th>
                    <td><input type="text" name="discount" style="width:100px;" /> % off</td>
                </tr>
            </table>
            <?php submit_button('Save Coupon'); ?>
            <input type="hidden" name="ecp_save_coupon" value="1" />
        </form>
        <?php if (!ecp_is_pro()) : ?>
        <h2>Go Pro</h2>
        <form method="post">
            <p>Unlock unlimited coupons & analytics for $49/year.</p>
            <?php submit_button('Activate Pro (Demo)', 'secondary', 'ecp_activate_pro'); ?>
            <input type="hidden" name="ecp_activate_pro" value="1" />
        </form>
        <?php endif; ?>
        <h2>Coupons (<?php echo count($coupons); ?>/<?php echo ecp_is_pro() ? 'Unlimited' : '5 (Free)'; ?>)</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>Code</th><th>Description</th><th>Link</th><th>Uses</th><th>Shortcode</th></tr></thead>
            <tbody>
        <?php foreach ($coupons as $id => $coupon) : 
            $uses = get_option("ecp_uses_{$id}", 0);
        ?>
                <tr>
                    <td><?php echo esc_html($coupon['code']); ?></td>
                    <td><?php echo esc_html($coupon['description']); ?></td>
                    <td><a href="<?php echo esc_url($coupon['link']); ?>" target="_blank">View</a></td>
                    <td><?php echo $uses; ?></td>
                    <td>[ecp_coupon id="<?php echo $id; ?>"]</td>
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
    if (!ecp_is_pro() && count($coupons) >= 5) {
        wp_die('Upgrade to Pro for more coupons!');
    }
    $id = uniqid();
    $coupons[$id] = array(
        'code' => sanitize_text_field($_POST['code']),
        'description' => sanitize_text_field($_POST['description']),
        'link' => esc_url_raw($_POST['link']),
        'discount' => sanitize_text_field($_POST['discount']),
        'created' => current_time('mysql')
    );
    update_option('ecp_coupons', $coupons);
    wp_redirect(admin_url('admin.php?page=exclusive-coupons-pro&saved=1'));
    exit;
}

// Shortcode
add_shortcode('ecp_coupon', 'ecp_coupon_shortcode');
function ecp_coupon_shortcode($atts) {
    $atts = shortcode_atts(array('id' => ''), $atts);
    $coupons = get_option('ecp_coupons', array());
    if (!isset($coupons[$atts['id']])) {
        return 'Coupon not found.';
    }
    $coupon = $coupons[$atts['id']];
    $id = $atts['id'];
    $uses = get_option("ecp_uses_{$id}", 0);
    
    // Track use
    if (!wp_doing_ajax()) {
        update_option("ecp_uses_{$id}", $uses + 1);
    }
    
    $pro_style = ecp_is_pro() ? 'border:2px solid gold; padding:20px; background:#fff3cd;' : '';
    
    ob_start();
    ?>
    <div class="ecp-coupon" style="max-width:400px; <?php echo $pro_style; ?>">
        <h3><?php echo esc_html($coupon['description']); ?></h3>
        <div style="font-size:2em; font-weight:bold; color:#d63384; margin:10px 0;">
            <?php echo esc_html($coupon['code']); ?>
        </div>
        <p><strong><?php echo esc_html($coupon['discount']); ?> off</strong> - Exclusive for our readers!</p>
        <p>Used <?php echo $uses; ?> times</p>
        <a href="<?php echo esc_url($coupon['link']); ?><?php echo strpos($coupon['link'], '?') === false ? '?ref=ecp' : '&ref=ecp'; ?>" 
           class="button button-large" style="background:#0073aa; color:white; text-decoration:none; padding:10px 20px;" 
           target="_blank">Get Deal Now</a>
    </div>
    <?php
    return ob_get_clean();
}

// Gutenberg block
add_action('init', 'ecp_register_block');
function ecp_register_block() {
    if (!function_exists('register_block_type')) return;
    wp_register_script(
        'ecp-block',
        ECP_PLUGIN_URL . 'block.js',
        array('wp-blocks', 'wp-element', 'wp-editor'),
        ECP_VERSION
    );
    register_block_type('ecp/coupon', array(
        'editor_script' => 'ecp-block',
        'render_callback' => 'ecp_coupon_shortcode',
        'attributes' => array(
            'id' => array('type' => 'string'),
        ),
    ));
}

// Widget
add_action('widgets_init', 'ecp_register_widget');
function ecp_register_widget() {
    register_widget('ECP_Widget');
}
class ECP_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct('ecp_widget', 'Exclusive Coupon');
    }
    public function widget($args, $instance) {
        $coupons = get_option('ecp_coupons', array());
        if (empty($coupons)) return;
        $id = !empty($instance['coupon_id']) ? $instance['coupon_id'] : key($coupons);
        echo do_shortcode('[ecp_coupon id="' . esc_attr($id) . '"]');
    }
    public function form($instance) {
        $coupons = get_option('ecp_coupons', array());
        ?>
        <p>
            <label>Coupon:</label>
            <select name="<?php echo $this->get_field_name('coupon_id'); ?>">
        <?php foreach ($coupons as $id => $c) : ?>
                <option value="<?php echo $id; ?>" <?php selected($instance['coupon_id'] ?? '', $id); ?>><?php echo esc_html($c['code']); ?></option>
        <?php endforeach; ?>
            </select>
        </p>
        <?php
    }
    public function update($new, $old) {
        $instance = array();
        $instance['coupon_id'] = sanitize_text_field($new['coupon_id']);
        return $instance;
    }
}

// Enqueue styles
add_action('wp_enqueue_scripts', 'ecp_enqueue_styles');
function ecp_enqueue_styles() {
    wp_enqueue_style('ecp-styles', ECP_PLUGIN_URL . 'style.css', array(), ECP_VERSION);
}

// Freemium upsell notice
add_action('admin_notices', 'ecp_upsell_notice');
function ecp_upsell_notice() {
    if (ecp_is_pro() || !current_user_can('manage_options')) return;
    $coupons = get_option('ecp_coupons', array());
    if (count($coupons) < 5) return;
    echo '<div class="notice notice-info"><p>Upgrade to <strong>Exclusive Coupons Pro</strong> for unlimited coupons and analytics! <a href="' . admin_url('admin.php?page=exclusive-coupons-pro') . '">Go Pro</a></p></div>';
}

// Create assets folders (simulate)
register_activation_hook(__FILE__, 'ecp_create_assets');
function ecp_create_assets() {
    // In real plugin, create style.css and block.js
}