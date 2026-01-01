/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Affiliate_Coupon_Generator.php
*/
<?php
/**
 * Plugin Name: AI Affiliate Coupon Generator
 * Plugin URI: https://example.com/ai-affiliate-coupon-generator
 * Description: Automatically generates personalized affiliate coupons and promo codes for your WordPress site, boosting conversions with AI-powered unique discounts.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-affiliate-coupon-generator
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIAffiliateCouponGenerator {
    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon_generator', array($this, 'coupon_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }

    public function activate() {
        add_option('ai_coupon_api_key', '');
        add_option('ai_coupon_affiliate_links', json_encode(array()));
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function init() {
        $this->create_coupon_post_type();
    }

    private function create_coupon_post_type() {
        register_post_type('ai_coupon', array(
            'labels' => array(
                'name' => 'AI Coupons',
                'singular_name' => 'AI Coupon'
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
            'menu_icon' => 'dashicons-cart',
            'rewrite' => array('slug' => 'coupons')
        ));
    }

    public function admin_menu() {
        add_menu_page(
            'AI Coupon Generator',
            'AI Coupons',
            'manage_options',
            'ai-coupon-generator',
            array($this, 'admin_page'),
            'dashicons-tickets-alt',
            30
        );
    }

    public function admin_page() {
        if (isset($_POST['ai_coupon_generate'])) {
            $this->generate_coupon();
        }
        include $this->get_template('admin-page.php');
    }

    private function generate_coupon() {
        $niche = sanitize_text_field($_POST['niche']);
        $affiliate_link = esc_url_raw($_POST['affiliate_link']);
        $discount = intval($_POST['discount']);

        // Simple AI-like generation (deterministic for demo)
        $code = strtoupper(substr(md5($niche . time()), 0, 8));
        $expiry = date('Y-m-d', strtotime('+30 days'));

        $coupon_data = array(
            'post_title' => $niche . ' - ' . $code . ' (' . $discount . '% OFF)',
            'post_content' => '<p><strong>Promo Code:</strong> ' . $code . '</p><p><strong>Discount:</strong> ' . $discount . '%</p><p><strong>Expires:</strong> ' . $expiry . '</p><p><a href="' . $affiliate_link . '" target="_blank" rel="nofollow">Shop Now & Save!</a></p>',
            'post_status' => 'publish',
            'post_type' => 'ai_coupon'
        );

        $post_id = wp_insert_post($coupon_data);
        if ($post_id) {
            set_transient('ai_coupon_success', 'Coupon generated successfully!', 30);
        }
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        if (!$atts['id']) return '';

        $post = get_post($atts['id']);
        if (!$post || $post->post_type !== 'ai_coupon') return '';

        ob_start();
        echo '<div class="ai-coupon-box">';
        echo apply_filters('the_content', $post->post_content);
        echo '</div>';
        return ob_get_clean();
    }

    public function enqueue_scripts() {
        wp_enqueue_style('ai-coupon-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function admin_enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_ai-coupon-generator') return;
        wp_enqueue_style('ai-coupon-admin-style', plugin_dir_url(__FILE__) . 'admin-style.css', array(), '1.0.0');
    }

    private function get_template($file) {
        $template = plugin_dir_path(__FILE__) . 'templates/' . $file;
        if (file_exists($template)) {
            ob_start();
            include $template;
            return ob_get_clean();
        }
        return '';
    }
}

new AIAffiliateCouponGenerator();

// Inline CSS for frontend
add_action('wp_head', function() {
    echo '<style>
    .ai-coupon-box { border: 2px solid #007cba; padding: 20px; background: #f9f9f9; border-radius: 10px; text-align: center; margin: 20px 0; }
    .ai-coupon-box strong { color: #007cba; font-size: 1.2em; }
    .ai-coupon-box a { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
    </style>';
});

// Pro upsell notice
add_action('admin_notices', function() {
    if (get_option('ai_coupon_pro_activated')) return;
    echo '<div class="notice notice-info"><p>Upgrade to <strong>AI Affiliate Coupon Generator Pro</strong> for unlimited coupons, real AI integration, and analytics! <a href="https://example.com/pro" target="_blank">Get Pro ($49/year)</a></p></div>';
});