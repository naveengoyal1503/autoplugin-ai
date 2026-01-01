/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Create exclusive affiliate coupon sections to boost commissions. Track clicks and generate custom promo codes.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AffiliateCouponVault {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_acv_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_acv_track_click', array($this, 'track_click'));
        add_shortcode('acv_coupons', array($this, 'coupons_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('acv_db_version') != '1.0') {
            $this->create_table();
        }
        flush_rewrite_rules();
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acv_coupons';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            affiliate_url varchar(500) NOT NULL,
            code varchar(100) DEFAULT '',
            description text,
            clicks int DEFAULT 0,
            active tinyint(1) DEFAULT 1,
            created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        update_option('acv_db_version', '1.0');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 10), $atts);
        global $wpdb;
        $table_name = $wpdb->prefix . 'acv_coupons';
        $coupons = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE active = 1 ORDER BY created DESC LIMIT %d", $atts['limit']));
        ob_start();
        echo '<div class="acv-coupons">';
        foreach ($coupons as $coupon) {
            echo '<div class="acv-coupon">';
            echo '<h3>' . esc_html($coupon->title) . '</h3>';
            if ($coupon->code) echo '<div class="acv-code">Code: <strong>' . esc_html($coupon->code) . '</strong></div>';
            echo '<p>' . esc_html($coupon->description) . '</p>';
            echo '<a href="' . esc_url($coupon->affiliate_url) . '" class="acv-btn" data-id="' . $coupon->id . '">Get Deal (' . $coupon->clicks . ' uses)</a>';
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    public function track_click() {
        if (!isset($_POST['coupon_id'])) wp_die();
        global $wpdb;
        $table_name = $wpdb->prefix . 'acv_coupons';
        $wpdb->query($wpdb->prepare("UPDATE $table_name SET clicks = clicks + 1 WHERE id = %d", intval($_POST['coupon_id'])));
        wp_die();
    }

    public function activate() {
        $this->create_table();
        // Insert sample coupon
        global $wpdb;
        $table_name = $wpdb->prefix . 'acv_coupons';
        $wpdb->insert($table_name, array(
            'title' => 'Sample 20% Off Deal',
            'affiliate_url' => 'https://example.com/affiliate-link',
            'code' => 'SAVE20',
            'description' => 'Exclusive coupon for our readers!'
        ));
    }
}

AffiliateCouponVault::get_instance();

// Admin menu
if (is_admin()) {
    add_action('admin_menu', function() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', 'acv_admin_page');
    });

    function acv_admin_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acv_coupons';
        if (isset($_POST['add_coupon'])) {
            $wpdb->insert($table_name, array(
                'title' => sanitize_text_field($_POST['title']),
                'affiliate_url' => esc_url_raw($_POST['url']),
                'code' => sanitize_text_field($_POST['code']),
                'description' => sanitize_textarea_field($_POST['desc']),
                'active' => isset($_POST['active']) ? 1 : 0
            ));
            echo '<div class="notice notice-success"><p>Coupon added!</p></div>';
        }
        $coupons = $wpdb->get_results("SELECT * FROM $table_name");
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault</h1>
            <form method="post">
                <table class="form-table">
                    <tr><th>Title</th><td><input type="text" name="title" required style="width:300px;"></td></tr>
                    <tr><th>Affiliate URL</th><td><input type="url" name="url" required style="width:300px;"></td></tr>
                    <tr><th>Coupon Code</th><td><input type="text" name="code" style="width:200px;"></td></tr>
                    <tr><th>Description</th><td><textarea name="desc" rows="3" style="width:300px;"></textarea></td></tr>
                    <tr><th>Active</th><td><input type="checkbox" name="active" checked></td></tr>
                </table>
                <p><input type="submit" name="add_coupon" class="button-primary" value="Add Coupon"></p>
            </form>
            <h2>Your Coupons</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>ID</th><th>Title</th><th>Code</th><th>Clicks</th><th>Active</th></tr></thead>
                <tbody>
        <?php foreach ($coupons as $c) : ?>
                    <tr><td><?php echo $c->id; ?></td><td><?php echo esc_html($c->title); ?></td><td><?php echo esc_html($c->code); ?></td><td><?php echo $c->clicks; ?></td><td><?php echo $c->active ? 'Yes' : 'No'; ?></td></tr>
        <?php endforeach; ?>
                </tbody>
            </table>
            <p><strong>Shortcode:</strong> <code>[acv_coupons limit="10"]</code></p>
        </div>
        <?php
    }
}

// Simple JS for tracking
add_action('wp_footer', function() { ?>
<script>
jQuery(document).ready(function($) {
    $('.acv-btn').click(function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        $.post(acv_ajax.ajax_url, {action: 'acv_track_click', coupon_id: id}, function() {
            window.location = $(this).attr('href');
        }.bind(this));
    });
});
</script>
<?php });