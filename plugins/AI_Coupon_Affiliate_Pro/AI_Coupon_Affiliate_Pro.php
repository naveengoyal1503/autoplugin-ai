/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: Generate and manage exclusive AI-powered coupon codes with affiliate tracking for WordPress sites.
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
        add_shortcode('ai_coupon_generator', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->create_table();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'ai-coupon.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'ai-coupon.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_menu_page('AI Coupons', 'AI Coupons', 'manage_options', 'ai-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['generate_coupon'])) {
            $this->generate_coupon();
        }
        include plugin_dir_path(__FILE__) . 'admin.html';
    }

    private function generate_coupon() {
        global $wpdb;
        $code = wp_generate_uuid4();
        $affiliate_link = sanitize_text_field($_POST['affiliate_link']);
        $discount = sanitize_text_field($_POST['discount']);
        $wpdb->insert(
            $wpdb->prefix . 'ai_coupons',
            array(
                'code' => $code,
                'link' => $affiliate_link,
                'discount' => $discount,
                'uses' => 0,
                'created' => current_time('mysql')
            )
        );
        echo '<div class="notice notice-success"><p>Coupon generated: ' . $code . '</p></div>';
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        global $wpdb;
        $coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ai_coupons WHERE id = %d", $atts['id']));
        if (!$coupon) return '';

        ob_start();
        echo '<div class="ai-coupon-box">';
        echo '<h3>Exclusive Deal: ' . esc_html($coupon->discount) . ' OFF</h3>';
        echo '<p>Use code: <strong>' . esc_html($coupon->code) . '</strong></p>';
        echo '<a href="' . esc_url($coupon->link) . '" class="ai-coupon-btn" target="_blank">Shop Now & Save</a>';
        echo '</div>';
        return ob_get_clean();
    }

    private function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'ai_coupons';
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            code varchar(255) NOT NULL,
            link text NOT NULL,
            discount varchar(100) NOT NULL,
            uses int DEFAULT 0,
            created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function activate() {
        $this->create_table();
    }
}

new AICouponAffiliatePro();

// Pro upsell notice
function ai_coupon_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>AI Coupon Pro:</strong> Unlock unlimited coupons, AI generation, analytics & more! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
}
add_action('admin_notices', 'ai_coupon_pro_notice');

// Frontend JS (inline for single file)
function ai_coupon_inline_js() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.ai-coupon-btn').on('click', function() {
            $(this).text('Copied! Shop Now');
            // Track click for affiliate
            gtag('event', 'coupon_click', {'coupon_id': $(this).data('id')});
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'ai_coupon_inline_js');

// Basic CSS
function ai_coupon_inline_css() {
    ?>
    <style>
    .ai-coupon-box { background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0; }
    .ai-coupon-btn { background: #e17055; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; }
    .ai-coupon-btn:hover { background: #d63031; }
    </style>
    <?php
}
add_action('wp_head', 'ai_coupon_inline_css');

// List coupons shortcode
add_shortcode('ai_coupons_list', function() {
    global $wpdb;
    $coupons = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ai_coupons ORDER BY created DESC LIMIT 5");
    ob_start();
    echo '<ul class="ai-coupons-list">';
    foreach ($coupons as $coupon) {
        echo '<li><strong>' . esc_html($coupon->code) . '</strong> - ' . esc_html($coupon->discount) . ' <a href="' . esc_url($coupon->link) . '" target="_blank">Use</a></li>';
    }
    echo '</ul>';
    return ob_get_clean();
});