/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Create and manage affiliate coupon sections to boost conversions and earnings.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_acv_save_coupon', array($this, 'save_coupon'));
        add_shortcode('acv_coupons', array($this, 'coupons_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->create_table();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-frontend', plugin_dir_url(__FILE__) . 'acv.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('acv-frontend', plugin_dir_url(__FILE__) . 'acv.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_api_key', sanitize_text_field($_POST['api_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('acv_api_key', '');
        echo '<div class="wrap"><h1>Affiliate Coupon Vault Settings</h1><form method="post"><table class="form-table"><tr><th>API Key (Premium)</th><td><input type="text" name="api_key" value="' . esc_attr($api_key) . '" /></td></tr></table><p class="submit"><input type="submit" name="submit" class="button-primary" value="Save Changes" /></p></form><p>Use shortcode <code>[acv_coupons]</code> to display coupons.</p></div>';
    }

    private function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'acv_coupons';
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            code varchar(100) NOT NULL,
            affiliate_url text NOT NULL,
            description text,
            expiry_date datetime DEFAULT NULL,
            clicks int DEFAULT 0,
            active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function activate() {
        $this->create_table();
    }

    public function save_coupon() {
        if (!current_user_can('manage_options')) wp_die();
        global $wpdb;
        $table = $wpdb->prefix . 'acv_coupons';
        $data = array(
            'title' => sanitize_text_field($_POST['title']),
            'code' => sanitize_text_field($_POST['code']),
            'affiliate_url' => esc_url_raw($_POST['url']),
            'description' => sanitize_textarea_field($_POST['desc']),
            'expiry_date' => !empty($_POST['expiry']) ? $_POST['expiry'] : null,
            'active' => isset($_POST['active']) ? 1 : 0
        );
        $wpdb->insert($table, $data);
        wp_send_json_success('Coupon saved');
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 10), $atts);
        global $wpdb;
        $table = $wpdb->prefix . 'acv_coupons';
        $coupons = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE active = 1 AND (expiry_date IS NULL OR expiry_date > %s) ORDER BY created_at DESC LIMIT %d", current_time('mysql'), $atts['limit']));
        ob_start();
        echo '<div class="acv-coupons">';
        foreach ($coupons as $coupon) {
            echo '<div class="acv-coupon">';
            echo '<h3>' . esc_html($coupon->title) . '</h3>';
            echo '<p><strong>Code:</strong> ' . esc_html($coupon->code) . '</p>';
            if ($coupon->description) echo '<p>' . esc_html($coupon->description) . '</p>';
            echo '<a href="' . esc_url(add_query_arg('acv_coupon', $coupon->id, $coupon->affiliate_url)) . '" class="acv-button" target="_blank">Get Deal (Track clicks)</a>';
            if ($coupon->expiry_date) echo '<p class="acv-expiry">Expires: ' . date('M j, Y', strtotime($coupon->expiry_date)) . '</p>';
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }
}

new AffiliateCouponVault();

// AJAX click tracking
add_action('wp_ajax_nopriv_acv_track_click', 'acv_track_click');
add_action('wp_ajax_acv_track_click', 'acv_track_click');
function acv_track_click() {
    $coupon_id = intval($_GET['coupon_id']);
    global $wpdb;
    $table = $wpdb->prefix . 'acv_coupons';
    $wpdb->query($wpdb->prepare("UPDATE $table SET clicks = clicks + 1 WHERE id = %d", $coupon_id));
    $coupon = $wpdb->get_row($wpdb->prepare("SELECT affiliate_url FROM $table WHERE id = %d", $coupon_id));
    if ($coupon) {
        wp_redirect($coupon->affiliate_url);
        exit;
    }
}

// Enqueue admin assets
function acv_admin_assets($hook) {
    if ($hook !== 'settings_page') return;
    wp_enqueue_script('acv-admin', plugin_dir_url(__FILE__) . 'acv-admin.js', array('jquery'), '1.0.0', true);
    wp_localize_script('acv-admin', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
}
add_action('admin_enqueue_scripts', 'acv_admin_assets');

// CSS
add_action('wp_head', 'acv_inline_css');
function acv_inline_css() {
    echo '<style>.acv-coupons { display: grid; gap: 20px; margin: 20px 0; }.acv-coupon { border: 1px solid #ddd; padding: 20px; border-radius: 8px; background: #f9f9f9; }.acv-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; }.acv-expiry { font-size: 0.9em; color: #666; }</style>';
}

// JS for frontend click tracking
add_action('wp_footer', 'acv_inline_js');
function acv_inline_js() {
    echo '<script>jQuery(document).ready(function($) { $(".acv-button").on("click", function(e) { var href = $(this).attr("href"); if (href.indexOf("acv_coupon=") === -1) { e.preventDefault(); var url = href + (href.indexOf("?") > -1 ? "&" : "?") + "acv_coupon=" + $("input[name=\"acv-coupon-id\"]").val(); window.location = url; } }); });</script>';
}

// Premium teaser
add_action('admin_notices', function() {
    if (!get_option('acv_premium_dismissed')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Affiliate Coupon Vault Pro</strong> for unlimited coupons, analytics dashboard, auto-expiry, and API integrations! <a href="https://example.com/pro" target="_blank">Learn more</a> | <a href="?acv_dismiss=1">Dismiss</a></p></div>';
        if (isset($_GET['acv_dismiss'])) update_option('acv_premium_dismissed', 1);
    }
});