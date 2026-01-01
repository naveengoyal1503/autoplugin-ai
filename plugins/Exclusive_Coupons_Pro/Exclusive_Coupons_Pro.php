/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate exclusive affiliate coupons with auto-expiration and tracking.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class ExclusiveCouponsPro {
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_shortcode('exclusive_coupons', [$this, 'coupons_shortcode']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function init() {
        $this->create_table();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ecp-script', plugin_dir_url(__FILE__) . 'ecp.js', ['jquery'], '1.0.0', true);
        wp_enqueue_style('ecp-style', plugin_dir_url(__FILE__) . 'ecp.css', [], '1.0.0');
    }

    public function admin_menu() {
        add_menu_page('Exclusive Coupons', 'Coupons Pro', 'manage_options', 'exclusive-coupons', [$this, 'admin_page']);
    }

    public function admin_page() {
        if (isset($_POST['add_coupon'])) {
            $this->add_coupon($_POST);
        }
        echo '<div class="wrap"><h1>Manage Exclusive Coupons</h1><form method="post">';
        echo '<table class="form-table"><tr><th>Code</th><td><input type="text" name="code" required></td></tr>';
        echo '<tr><th>Affiliate Link</th><td><input type="url" name="link" required style="width:100%"></td></tr>';
        echo '<tr><th>Expires (days)</th><td><input type="number" name="expires" value="30" min="1"></td></tr>';
        echo '<tr><th>Description</th><td><textarea name="desc"></textarea></td></tr>';
        echo '<p><input type="submit" name="add_coupon" class="button-primary" value="Add Coupon"></p></form>';

        $coupons = $this->get_coupons();
        echo '<table class="wp-list-table widefat striped"><thead><tr><th>Code</th><th>Link</th><th>Expires</th><th>Uses</th><th>Actions</th></tr></thead><tbody>';
        foreach ($coupons as $c) {
            $expired = $c->expires < current_time('timestamp');
            echo '<tr><td>' . esc_html($c->code) . '</td><td>' . esc_html($c->link) . '</td><td>' . ($expired ? 'Expired' : date('Y-m-d', $c->expires)) . '</td><td>' . $c->uses . '</td>';
            echo '<td><a href="?page=exclusive-coupons&delete=' . $c->id . '">Delete</a></td></tr>';
        }
        echo '</tbody></table>';

        if (isset($_GET['delete'])) {
            $this->delete_coupon($_GET['delete']);
        }
        echo '<p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics & integrations for $49/year!</p></div>';
    }

    public function add_coupon($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'exclusive_coupons';
        $wpdb->insert($table, [
            'code' => sanitize_text_field($data['code']),
            'link' => esc_url_raw($data['link']),
            'expires' => time() + (intval($data['expires']) * 86400),
            'desc' => sanitize_textarea_field($data['desc']),
            'uses' => 0
        ]);
    }

    public function get_coupons() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "exclusive_coupons ORDER BY id DESC");
    }

    public function delete_coupon($id) {
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'exclusive_coupons', ['id' => intval($id)]);
    }

    public function coupons_shortcode($atts) {
        $coupons = $this->get_coupons();
        $output = '<div class="ecp-coupons">';
        foreach ($coupons as $c) {
            if ($c->expires > current_time('timestamp')) {
                $output .= '<div class="ecp-coupon">';
                $output .= '<h3>' . esc_html($c->code) . '</h3>';
                $output .= '<p>' . esc_html($c->desc) . '</p>';
                $output .= '<a href="' . esc_url($c->link . (strpos($c->link, '?') === false ? '?' : '&') . 'ref=' . get_bloginfo('url')) . '" class="ecp-btn" onclick="trackUse(' . $c->id . ')" target="_blank">Redeem Now</a>';
                $output .= '</div>';
            }
        }
        $output .= '</div>';
        return $output;
    }

    public function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'exclusive_coupons';
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            code varchar(50) NOT NULL,
            link text NOT NULL,
            expires bigint(20) NOT NULL,
            desc text,
            uses int(11) DEFAULT 0,
            PRIMARY KEY (id)
        ) $charset;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function activate() {
        $this->create_table();
    }
}

new ExclusiveCouponsPro();

// Free version limits: 5 coupons
add_action('admin_notices', function() {
    $count = $GLOBALS['wpdb']->get_var("SELECT COUNT(*) FROM " . $GLOBALS['wpdb']->prefix . "exclusive_coupons");
    if ($count > 5) {
        echo '<div class="notice notice-warning"><p>Exclusive Coupons Pro: Upgrade to Pro for unlimited coupons!</p></div>';
    }
});

// Simple JS for tracking
function trackUse($id) {
    fetch(ajaxurl, {method: 'POST', body: new FormData().append('action', 'track_coupon').append('id', $id)});
}

add_action('wp_ajax_track_coupon', function() {
    global $wpdb;
    $wpdb->query($wpdb->prepare("UPDATE " . $wpdb->prefix . "exclusive_coupons SET uses = uses + 1 WHERE id = %d", $_POST['id']));
});

// CSS and JS placeholders (in real plugin, add files)
function ecp_css() { ?><style>.ecp-coupons { display: grid; gap: 20px; }.ecp-coupon { border: 2px solid #0073aa; padding: 20px; border-radius: 8px; text-align: center; }.ecp-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; }</style><?php }
add_action('wp_head', 'ecp_css');

// Note: Add ecp.js for AJAX tracking in production.