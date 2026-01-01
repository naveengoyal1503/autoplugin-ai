/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates, manages, and displays personalized affiliate coupons with tracking to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
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
        add_action('wp_ajax_acv_delete_coupon', array($this, 'ajax_delete_coupon'));
        add_shortcode('acv_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-frontend', plugin_dir_url(__FILE__) . 'acv.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('acv-frontend', plugin_dir_url(__FILE__) . 'acv.css', array(), '1.0.0');
        wp_localize_script('acv-frontend', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        $coupons = get_option('acv_coupons', array());
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault</h1>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics, custom designs for $49/year!</p>
            <form id="acv-form">
                <table class="form-table">
                    <tr>
                        <th>Code</th>
                        <td><input type="text" name="code" required /></td>
                    </tr>
                    <tr>
                        <th>Description</th>
                        <td><textarea name="description" required></textarea></td>
                    </tr>
                    <tr>
                        <th>Affiliate Link</th>
                        <td><input type="url" name="link" required /></td>
                    </tr>
                    <tr>
                        <th>Discount</th>
                        <td><input type="text" name="discount" placeholder="e.g., 20% OFF" /></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" class="button-primary" value="Add Coupon" /></p>
            </form>
            <h2>Your Coupons (Max 5 Free)</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Code</th><th>Description</th><th>Link</th><th>Actions</th></tr></thead>
                <tbody id="coupons-list">
                    <?php foreach ($coupons as $index => $coupon): ?>
                    <tr>
                        <td><?php echo esc_html($coupon['code']); ?></td>
                        <td><?php echo esc_html($coupon['description']); ?></td>
                        <td><?php echo esc_html($coupon['link']); ?></td>
                        <td><button class="button" onclick="acvDelete(<?php echo $index; ?>)" style="color:red;">Delete</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p><em>Use shortcode <code>[acv_coupon id="X"]</code> to display coupons. Upgrade for widgets & blocks!</em></p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#acv-form').submit(function(e) {
                e.preventDefault();
                var data = $(this).serialize() + '&action=acv_save_coupon';
                $.post(acv_ajax.ajax_url, data, function(resp) {
                    if (resp.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + resp.data);
                    }
                });
            });
        });
        function acvDelete(id) {
            jQuery.post(acv_ajax.ajax_url, {action: 'acv_delete_coupon', id: id}, function(resp) {
                if (resp.success) location.reload();
            });
        }
        </script>
        <?php
    }

    public function ajax_save_coupon() {
        if (!current_user_can('manage_options')) wp_die();
        $coupons = get_option('acv_coupons', array());
        if (count($coupons) >= 5) {
            wp_send_json_error('Upgrade to Pro for unlimited coupons!');
            return;
        }
        $coupons[] = array(
            'code' => sanitize_text_field($_POST['code']),
            'description' => sanitize_textarea_field($_POST['description']),
            'link' => esc_url_raw($_POST['link']),
            'discount' => sanitize_text_field($_POST['discount']),
            'clicks' => 0
        );
        update_option('acv_coupons', $coupons);
        wp_send_json_success();
    }

    public function ajax_delete_coupon() {
        if (!current_user_can('manage_options')) wp_die();
        $id = intval($_POST['id']);
        $coupons = get_option('acv_coupons', array());
        if (isset($coupons[$id])) {
            array_splice($coupons, $id, 1);
            update_option('acv_coupons', $coupons);
        }
        wp_send_json_success();
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $id = intval($atts['id']);
        $coupons = get_option('acv_coupons', array());
        if (!isset($coupons[$id])) return '';
        $coupon = $coupons[$id];
        $clicks = intval($coupon['clicks']);
        return '<div class="acv-coupon"><h3>' . esc_html($coupon['code']) . '</h3><p>' . esc_html($coupon['description']) . ' <strong>' . esc_html($coupon['discount']) . '</strong></p><a href="' . esc_url($coupon['link']) . '" class="button acv-btn" onclick="return acvTrack(' . $id . ');">Redeem Now</a><small>Clicks: ' . $clicks . '</small></div>';
    }

    public function activate() {
        if (!get_option('acv_coupons')) update_option('acv_coupons', array());
    }

    public function deactivate() {}
}

AffiliateCouponVault::get_instance();

// Frontend JS and CSS (inline for single file)
function acv_inline_assets() {
    ?>
    <style>
    .acv-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9; border-radius: 10px; }
    .acv-coupon h3 { color: #0073aa; margin: 0 0 10px; }
    .acv-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; }
    .acv-btn:hover { background: #005a87; }
    </style>
    <script type="text/javascript">
    function acvTrack(id) {
        fetch(acv_ajax.ajax_url, {method: 'POST', body: new URLSearchParams({action: 'acv_track_click', id: id})});
        return true;
    }
    </script>
    <?php
}
add_action('wp_footer', 'acv_inline_assets');

add_action('wp_ajax_acv_track_click', function() {
    $id = intval($_POST['id']);
    $coupons = get_option('acv_coupons', array());
    if (isset($coupons[$id])) {
        $coupons[$id]['clicks']++;
        update_option('acv_coupons', $coupons);
    }
});

add_action('wp_ajax_nopriv_acv_track_click', function() {
    $id = intval($_POST['id']);
    $coupons = get_option('acv_coupons', array());
    if (isset($coupons[$id])) {
        $coupons[$id]['clicks']++;
        update_option('acv_coupons', $coupons);
    }
});