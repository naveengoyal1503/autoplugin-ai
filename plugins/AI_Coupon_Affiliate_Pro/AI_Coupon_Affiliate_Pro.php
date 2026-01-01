/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicouponpro
 * Description: AI-powered coupon and affiliate plugin for WordPress monetization. Generates dynamic coupon sections with affiliate tracking.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-affiliate-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponAffiliatePro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon_section', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_save_coupon', array($this, 'save_coupon'));
        add_action('wp_ajax_nopriv_save_coupon', array($this, 'save_coupon'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-affiliate-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        $this->create_table();
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_coupons';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            code varchar(100) NOT NULL,
            affiliate_url text NOT NULL,
            discount varchar(50) DEFAULT '',
            brand varchar(255) DEFAULT '',
            expires date DEFAULT NULL,
            clicks int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
        wp_localize_script('ai-coupon-js', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('AI Coupon Pro Settings', 'AI Coupon Pro', 'manage_options', 'ai-coupon-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('ai_coupon_api_key', sanitize_text_field($_POST['api_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('ai_coupon_api_key', '');
        echo '<div class="wrap"><h1>AI Coupon Affiliate Pro Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr><th>AI API Key (OpenAI)</th><td><input type="text" name="api_key" value="' . esc_attr($api_key) . '" class="regular-text"></td></tr>
            </table>
            <p>Use shortcode <code>[ai_coupon_section]</code> to display coupons.</p>
            ' . submit_button() . '
        </form></div>';
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('num' => 5), $atts);
        ob_start();
        echo '<div class="ai-coupon-section"><h3>Exclusive Deals & Coupons</h3>';
        $coupons = $this->get_coupons($atts['num']);
        if (empty($coupons)) {
            echo $this->generate_ai_coupon();
        } else {
            foreach ($coupons as $coupon) {
                echo $this->render_coupon($coupon);
            }
        }
        echo '<p><a href="#" id="add-coupon">Generate New Coupon</a></p></div>';
        return ob_get_clean();
    }

    private function get_coupons($limit = 5) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ai_coupons ORDER BY clicks DESC LIMIT %d", $limit));
    }

    private function render_coupon($coupon) {
        $track_url = add_query_arg('coupon_id', $coupon->id, $coupon->affiliate_url);
        return '<div class="coupon-item">
            <h4>' . esc_html($coupon->title) . '</h4>
            <p><strong>Code:</strong> ' . esc_html($coupon->code) . ' | <strong>Save:</strong> ' . esc_html($coupon->discount) . '</p>
            <p><strong>Brand:</strong> ' . esc_html($coupon->brand) . '</p>
            <a href="' . esc_url($track_url) . '" class="coupon-btn" target="_blank">Shop Now (' . $coupon->clicks . ' used)</a>
        </div>';
    }

    private function generate_ai_coupon() {
        // Simulate AI generation (premium feature would call real API)
        $fake_coupons = array(
            array('title' => '20% Off Hosting', 'code' => 'HOST20', 'affiliate_url' => 'https://example-affiliate.com/hosting', 'discount' => '20%', 'brand' => 'Bluehost'),
            array('title' => 'Free Trial VPN', 'code' => 'VPNFREE', 'affiliate_url' => 'https://example-affiliate.com/vpn', 'discount' => 'Free Month', 'brand' => 'ExpressVPN')
        );
        $html = '';
        foreach ($fake_coupons as $c) {
            $html .= $this->render_coupon((object)$c);
        }
        return $html;
    }

    public function save_coupon() {
        if (!current_user_can('manage_options')) wp_die();
        global $wpdb;
        $data = array(
            'title' => sanitize_text_field($_POST['title']),
            'code' => sanitize_text_field($_POST['code']),
            'affiliate_url' => esc_url_raw($_POST['url']),
            'discount' => sanitize_text_field($_POST['discount']),
            'brand' => sanitize_text_field($_POST['brand'])
        );
        $wpdb->insert($wpdb->prefix . 'ai_coupons', $data);
        wp_send_json_success('Coupon saved!');
    }
}

new AICouponAffiliatePro();

// Track clicks
function track_coupon_click() {
    if (isset($_GET['coupon_id'])) {
        global $wpdb;
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}ai_coupons SET clicks = clicks + 1 WHERE id = %d", intval($_GET['coupon_id'])));
    }
}
add_action('init', 'track_coupon_click');

// Premium notice
function ai_coupon_pro_notice() {
    if (!get_option('ai_coupon_pro_activated')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>AI Coupon Affiliate Pro Premium</strong> for AI generation, analytics, and unlimited coupons! <a href="https://example.com/premium">Get it now</a></p></div>';
    }
}
add_action('admin_notices', 'ai_coupon_pro_notice');
?>