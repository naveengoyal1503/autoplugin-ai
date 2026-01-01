/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: AI-powered coupon generator with affiliate tracking for WordPress.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponAffiliatePro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon_deal', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        // Create table on activation if needed
        global $wpdb;
        $table = $wpdb->prefix . 'ai_coupons';
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            code varchar(100) DEFAULT '',
            affiliate_url varchar(500) DEFAULT '',
            discount text,
            expiry date DEFAULT NULL,
            used int DEFAULT 0,
            PRIMARY KEY (id)
        ) $charset;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function activate() {
        $this->init();
        flush_rewrite_rules();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'ai-coupon.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'ai-coupon.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('AI Coupon Pro', 'AI Coupons', 'manage_options', 'ai-coupon-pro', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['add_coupon'])) {
            global $wpdb;
            $table = $wpdb->prefix . 'ai_coupons';
            $wpdb->insert($table, array(
                'title' => sanitize_text_field($_POST['title']),
                'code' => sanitize_text_field($_POST['code']),
                'affiliate_url' => esc_url_raw($_POST['affiliate_url']),
                'discount' => sanitize_textarea_field($_POST['discount']),
                'expiry' => sanitize_text_field($_POST['expiry'])
            ));
            echo '<div class="notice notice-success"><p>Coupon added!</p></div>';
        }
        echo '<div class="wrap"><h1>Manage Coupons</h1><form method="post">';
        echo '<table class="form-table">';
        echo '<tr><th>Title</th><td><input type="text" name="title" required /></td></tr>';
        echo '<tr><th>Coupon Code</th><td><input type="text" name="code" /></td></tr>';
        echo '<tr><th>Affiliate URL</th><td><input type="url" name="affiliate_url" required /></td></tr>';
        echo '<tr><th>Discount</th><td><textarea name="discount"></textarea></td></tr>';
        echo '<tr><th>Expiry</th><td><input type="date" name="expiry" /></td></tr>';
        echo '</table><p><input type="submit" name="add_coupon" class="button-primary" value="Add Coupon" /></p></form>';

        // List coupons
        global $wpdb;
        $coupons = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ai_coupons ORDER BY id DESC");
        echo '<h2>Existing Coupons</h2><table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>ID</th><th>Title</th><th>Code</th><th>Affiliate URL</th><th>Actions</th></tr></thead><tbody>';
        foreach ($coupons as $coupon) {
            echo '<tr><td>' . $coupon->id . '</td><td>' . esc_html($coupon->title) . '</td><td>' . esc_html($coupon->code) . '</td><td>' . esc_html($coupon->affiliate_url) . '</td>';
            echo '<td><a href="' . add_query_arg('delete_coupon', $coupon->id) . '" onclick="return confirm(\'Delete?\')">Delete</a></td></tr>';
        }
        echo '</tbody></table></div>';

        if (isset($_GET['delete_coupon'])) {
            $wpdb->delete($wpdb->prefix . 'ai_coupons', array('id' => intval($_GET['delete_coupon'])));
            wp_redirect(admin_url('options-general.php?page=ai-coupon-pro'));
            exit;
        }
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        global $wpdb;
        $coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ai_coupons WHERE id = %d", $atts['id']));
        if (!$coupon) return '';

        ob_start();
        echo '<div class="ai-coupon-deal" data-id="' . $coupon->id . '">';
        echo '<h3>' . esc_html($coupon->title) . '</h3>';
        if ($coupon->code) echo '<p><strong>Code:</strong> <span class="coupon-code">' . esc_html($coupon->code) . '</span> <button class="copy-code">Copy</button></p>';
        echo '<p>' . esc_html($coupon->discount) . '</p>';
        echo '<a href="' . esc_url($coupon->affiliate_url) . '" target="_blank" class="coupon-btn" rel="nofollow">Get Deal (Affiliate)</a>';
        if ($coupon->expiry) echo '<p class="expiry">Expires: ' . date('M j, Y', strtotime($coupon->expiry)) . '</p>';
        echo '</div>';
        return ob_get_clean();
    }
}

new AICouponAffiliatePro();

// Pro upsell notice
function ai_coupon_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Upgrade to <strong>AI Coupon Affiliate Pro</strong> for AI-generated coupons, analytics, and premium integrations! <a href="https://example.com/pro" target="_blank">Get Pro ($49/year)</a></p></div>';
}
add_action('admin_notices', 'ai_coupon_pro_notice');

// Dummy JS/CSS - in real plugin, enqueue actual files
/*
Add these as separate files:

ai-coupon.css:
.ai-coupon-deal { border: 2px solid #007cba; padding: 20px; margin: 20px 0; border-radius: 10px; background: #f9f9f9; }
.coupon-code { font-size: 1.5em; color: #e74c3c; }
.copy-code { background: #27ae60; color: white; border: none; padding: 5px 10px; cursor: pointer; }
.coupon-btn { background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }

ai-coupon.js:
jQuery('.copy-code').click(function() {
    navigator.clipboard.writeText(jQuery(this).prev('.coupon-code').text());
    jQuery(this).text('Copied!');
});
*/