/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Affiliate_Coupon_Pro.php
*/
<?php
/**
 * Plugin Name: AI Affiliate Coupon Pro
 * Plugin URI: https://example.com/ai-affiliate-coupon-pro
 * Description: AI-powered plugin that generates and displays personalized affiliate coupons to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-affiliate-coupon-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIAffiliateCouponPro {
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
        add_shortcode('ai_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_generate_coupon', array($this, 'ajax_generate_coupon'));
    }

    public function init() {
        load_plugin_textdomain('ai-affiliate-coupon-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            $this->create_table();
        }
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_coupons';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            affiliate_url varchar(500) NOT NULL,
            code varchar(100) DEFAULT '',
            discount varchar(50) DEFAULT '',
            expires datetime DEFAULT NULL,
            uses int DEFAULT 0,
            max_uses int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'ai-coupon.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-coupon-js', 'ai_coupon_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'ai-coupon.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('AI Affiliate Coupons', 'AI Coupons', 'manage_options', 'ai-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['add_coupon'])) {
            $this->add_coupon($_POST);
        }
        $coupons = $this->get_coupons();
        include 'admin-page.php';
    }

    private function add_coupon($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_coupons';
        $wpdb->insert(
            $table_name,
            array(
                'title' => sanitize_text_field($data['title']),
                'affiliate_url' => esc_url_raw($data['url']),
                'code' => $this->generate_coupon_code(),
                'discount' => sanitize_text_field($data['discount']),
                'expires' => !empty($data['expires']) ? $data['expires'] : null,
                'max_uses' => intval($data['max_uses']),
            )
        );
    }

    private function generate_coupon_code($length = 10) {
        return substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
    }

    private function get_coupons() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . 'ai_coupons');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupon = $this->get_coupon($atts['id']);
        if (!$coupon) return '';
        ob_start();
        ?>
        <div class="ai-coupon" data-id="<?php echo $coupon->id; ?>">
            <h3><?php echo esc_html($coupon->title); ?></h3>
            <p>Code: <strong><?php echo esc_html($coupon->code); ?></strong></p>
            <p>Discount: <?php echo esc_html($coupon->discount); ?></p>
            <a href="<?php echo esc_url($coupon->affiliate_url . '?coupon=' . $coupon->code); ?>" class="ai-coupon-btn" target="_blank">Get Deal</a>
            <button class="generate-new">New Coupon</button>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_coupon($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . 'ai_coupons WHERE id = %d', $id));
    }

    public function ajax_generate_coupon() {
        // Simulate AI generation
        $code = $this->generate_coupon_code();
        wp_send_json_success(array('code' => $code));
    }
}

AIAffiliateCouponPro::get_instance();

// Admin page template
$admin_page_content = '<div class="wrap">
<h1>AI Affiliate Coupons</h1>
<form method="post">
<table class="form-table">
<tr><th>Title</th><td><input type="text" name="title" required></td></tr>
<tr><th>Affiliate URL</th><td><input type="url" name="url" style="width:400px;" required></td></tr>
<tr><th>Discount</th><td><input type="text" name="discount" placeholder="20% OFF"></td></tr>
<tr><th>Expires</th><td><input type="datetime-local" name="expires"></td></tr>
<tr><th>Max Uses</th><td><input type="number" name="max_uses"></td></tr>
</table>
<input type="submit" name="add_coupon" value="Add Coupon" class="button-primary">
</form>
<h2>Coupons</h2>
<table class="wp-list-table widefat fixed striped">
<thead><tr><th>ID</th><th>Title</th><th>Code</th><th>URL</th><th>Actions</th></tr></thead>
<tbody>';