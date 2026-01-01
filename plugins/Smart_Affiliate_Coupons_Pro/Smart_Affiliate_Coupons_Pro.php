/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupons Pro
 * Plugin URI: https://example.com/smart-affiliate-coupons
 * Description: Automatically generates and manages personalized affiliate coupon codes, tracks clicks and conversions, and displays dynamic coupon sections to boost affiliate earnings.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateCoupons {
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
        add_shortcode('sac_coupon_section', array($this, 'coupon_section_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        // Create table on init if not exists
        global $wpdb;
        $table_name = $wpdb->prefix . 'sac_coupons';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            code varchar(100) NOT NULL,
            affiliate_url text NOT NULL,
            discount varchar(50) DEFAULT '',
            brand varchar(100) DEFAULT '',
            clicks int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function activate() {
        $this->init();
    }

    public function enqueue_scripts() {
        wp_enqueue_style('sac-styles', plugin_dir_url(__FILE__) . 'sac-styles.css', array(), '1.0.0');
        wp_enqueue_script('sac-script', plugin_dir_url(__FILE__) . 'sac-script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Coupons', 'SAC Coupons', 'manage_options', 'sac-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['sac_submit'])) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'sac_coupons';
            $wpdb->insert($table_name, array(
                'title' => sanitize_text_field($_POST['title']),
                'code' => sanitize_text_field($_POST['code']),
                'affiliate_url' => esc_url_raw($_POST['affiliate_url']),
                'discount' => sanitize_text_field($_POST['discount']),
                'brand' => sanitize_text_field($_POST['brand']),
            ));
            echo '<div class="notice notice-success"><p>Coupon added!</p></div>';
        }
        echo '<div class="wrap"><h1>Manage Coupons</h1><form method="post">';
        echo '<table class="form-table">';
        echo '<tr><th>Title</th><td><input type="text" name="title" required /></td></tr>';
        echo '<tr><th>Code</th><td><input type="text" name="code" required /></td></tr>';
        echo '<tr><th>Affiliate URL</th><td><input type="url" name="affiliate_url" style="width:100%;" required /></td></tr>';
        echo '<tr><th>Discount</th><td><input type="text" name="discount" placeholder="e.g. 20% OFF" /></td></tr>';
        echo '<tr><th>Brand</th><td><input type="text" name="brand" /></td></tr>';
        echo '</table><p><input type="submit" name="sac_submit" class="button-primary" value="Add Coupon" /></p></form>';

        // List coupons
        global $wpdb;
        $coupons = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . 'sac_coupons' . " ORDER BY created_at DESC");
        if ($coupons) {
            echo '<h2>Existing Coupons</h2><table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>ID</th><th>Title</th><th>Code</th><th>Brand</th><th>Discount</th><th>Clicks</th></tr></thead><tbody>';
            foreach ($coupons as $coupon) {
                echo '<tr><td>' . $coupon->id . '</td><td>' . esc_html($coupon->title) . '</td><td>' . esc_html($coupon->code) . '</td><td>' . esc_html($coupon->brand) . '</td><td>' . esc_html($coupon->discount) . '</td><td>' . $coupon->clicks . '</td></tr>';
            }
            echo '</tbody></table>';
        }
        echo '</div>';
    }

    public function coupon_section_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts);
        global $wpdb;
        $coupons = $wpdb->get_results($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . 'sac_coupons' . " ORDER BY clicks DESC LIMIT %d", $atts['limit']));
        ob_start();
        echo '<div class="sac-coupon-section">';
        foreach ($coupons as $coupon) {
            $track_url = add_query_arg(array('sac_id' => $coupon->id), $coupon->affiliate_url);
            echo '<div class="sac-coupon">';
            echo '<h3>' . esc_html($coupon->title) . '</h3>';
            echo '<p><strong>Code:</strong> <span class="sac-code">' . esc_html($coupon->code) . '</span></p>';
            if ($coupon->brand) echo '<p><strong>Brand:</strong> ' . esc_html($coupon->brand) . '</p>';
            if ($coupon->discount) echo '<p><strong>Discount:</strong> ' . esc_html($coupon->discount) . '</p>';
            echo '<a href="' . esc_url($track_url) . '" class="sac-button" target="_blank">Get Deal (Clicks: ' . $coupon->clicks . ')</a>';
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }
}

// Track clicks
add_action('init', function() {
    if (isset($_GET['sac_id'])) {
        global $wpdb;
        $wpdb->query($wpdb->prepare("UPDATE " . $wpdb->prefix . "sac_coupons SET clicks = clicks + 1 WHERE id = %d", intval($_GET['sac_id'])));
    }
});

SmartAffiliateCoupons::get_instance();

// Inline CSS
add_action('wp_head', function() { ?>
<style>
.sac-coupon-section { max-width: 800px; margin: 20px 0; }
.sac-coupon { background: #f9f9f9; border: 1px solid #ddd; padding: 20px; margin: 10px 0; border-radius: 8px; }
.sac-code { font-family: monospace; background: #fff; padding: 5px 10px; border-radius: 4px; }
.sac-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; }
.sac-button:hover { background: #005a87; }
</style>
<?php });

// Inline JS
add_action('wp_footer', function() { ?>
<script>jQuery(document).ready(function($) { $('.sac-code').click(function() { var code = $(this).text(); navigator.clipboard.writeText(code); $(this).after('<span style="color:green;"> Copied!</span>'); }); });</script>
<?php });