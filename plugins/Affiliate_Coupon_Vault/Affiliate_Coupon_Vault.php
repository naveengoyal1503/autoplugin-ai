/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupons with tracking to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_acv_save_coupon', array($this, 'ajax_save_coupon'));
        add_shortcode('acv_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('acv-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.css', array(), '1.0.0');
    }

    public function admin_enqueue($hook) {
        if ('toplevel_page_acv-settings' !== $hook) return;
        wp_enqueue_script('acv-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('acv-admin', plugin_dir_url(__FILE__) . 'assets/admin.css', array(), '1.0.0');
        wp_localize_script('acv-admin', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_menu() {
        add_menu_page(
            'Affiliate Coupon Vault',
            'Coupon Vault',
            'manage_options',
            'acv-settings',
            array($this, 'settings_page'),
            'dashicons-cart',
            30
        );
    }

    public function settings_page() {
        $coupons = get_option('acv_coupons', array());
        include plugin_dir_path(__FILE__) . 'templates/settings.php';
    }

    public function ajax_save_coupon() {
        check_ajax_referer('acv_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_die();

        $coupons = get_option('acv_coupons', array());
        $id = sanitize_text_field($_POST['id']);
        $coupon = array(
            'code' => sanitize_text_field($_POST['code']),
            'affiliate_link' => esc_url_raw($_POST['affiliate_link']),
            'description' => sanitize_textarea_field($_POST['description']),
            'discount' => sanitize_text_field($_POST['discount']),
            'expires' => sanitize_text_field($_POST['expires'])
        );

        if ('new' === $id) {
            $id = 'coupon_' . time();
            $coupons[$id] = $coupon;
        } else {
            $coupons[$id] = $coupon;
        }

        update_option('acv_coupons', $coupons);
        wp_send_json_success(array('id' => $id));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = get_option('acv_coupons', array());
        if (!isset($coupons[$atts['id']])) return '';

        $coupon = $coupons[$atts['id']];
        $click_id = uniqid();
        $track_link = add_query_arg(array('acv_click' => $click_id, 'ref' => 'vault'), $coupon['affiliate_link']);

        ob_start();
        ?>
        <div class="acv-coupon-box">
            <h3><?php echo esc_html($coupon['code']); ?> - <?php echo esc_html($coupon['discount']); ?> OFF</h3>
            <p><?php echo esc_html($coupon['description']); ?></p>
            <p><strong>Expires:</strong> <?php echo esc_html($coupon['expires']); ?></p>
            <a href="<?php echo esc_url($track_link); ?>" class="acv-button" target="_blank" rel="nofollow">Get Deal Now</a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', array());
        }
        flush_rewrite_rules();
    }
}

AffiliateCouponVault::get_instance();

// Pro upgrade nag
function acv_pro_nag() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Upgrade to <strong>Affiliate Coupon Vault Pro</strong> for unlimited coupons and analytics! <a href="https://example.com/pro" target="_blank">Learn More</a></p></div>';
}
add_action('admin_notices', 'acv_pro_nag');

// Create assets directories on activation
register_activation_hook(__FILE__, function() {
    $assets = array('assets', 'templates');
    foreach ($assets as $dir) {
        $path = plugin_dir_path(__FILE__) . $dir;
        if (!file_exists($path)) {
            wp_mkdir_p($path);
        }
    }
    // Create basic CSS
    $css = ".acv-coupon-box { border: 2px solid #007cba; padding: 20px; border-radius: 10px; background: #f9f9f9; text-align: center; } .acv-button { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }";
    file_put_contents(plugin_dir_path(__FILE__) . 'assets/frontend.css', $css);
    // Create admin CSS
    $admin_css = "#acv-form { max-width: 500px; } .acv-field { margin-bottom: 15px; }";
    file_put_contents(plugin_dir_path(__FILE__) . 'assets/admin.css', $admin_css);
    // Create JS files
    $frontend_js = "jQuery(document).ready(function($) { $('.acv-button').on('click', function() { $(this).text('Redirecting...'); }); });";
    file_put_contents(plugin_dir_path(__FILE__) . 'assets/frontend.js', $frontend_js);
    $admin_js = "jQuery(document).ready(function($) { $('#acv-save').click(function(e) { e.preventDefault(); $.post(acv_ajax.ajax_url, { action: 'acv_save_coupon', nonce: acv_ajax.nonce, id: $('#coupon-id').val(), code: $('#coupon-code').val(), affiliate_link: $('#affiliate-link').val(), description: $('#description').val(), discount: $('#discount').val(), expires: $('#expires').val() }, function(res) { if (res.success) { alert('Saved! Shortcode: [acv_coupon id="' + res.data.id + '"]'); } }); }); });";
    file_put_contents(plugin_dir_path(__FILE__) . 'assets/admin.js', $admin_js);
    // Create settings template
    $template = '<div class="wrap"><h1>Affiliate Coupon Vault</h1><form id="acv-form"><input type="hidden" id="coupon-id" value="new"><div class="acv-field"><label>Coupon Code:</label><input type="text" id="coupon-code" required></div><div class="acv-field"><label>Affiliate Link:</label><input type="url" id="affiliate-link" required></div><div class="acv-field"><label>Description:</label><textarea id="description"></textarea></div><div class="acv-field"><label>Discount:</label><input type="text" id="discount" placeholder="50% OFF"></div><div class="acv-field"><label>Expires:</label><input type="date" id="expires"></div><button type="button" id="acv-save" class="button-primary">Save Coupon</button></form><h2>Your Coupons</h2><ul id="acv-list"><?php $coupons = get_option(\'acv_coupons\', array()); foreach($coupons as $id => $c) { echo "<li>" . esc_html($c[\'code\']) . " <small>[acv_coupon id=\"$id\"]</small></li>"; } ?></ul><p><em>Pro: Unlimited coupons, click tracking & analytics.</em></p></div>';
    file_put_contents(plugin_dir_path(__FILE__) . 'templates/settings.php', $template);
});