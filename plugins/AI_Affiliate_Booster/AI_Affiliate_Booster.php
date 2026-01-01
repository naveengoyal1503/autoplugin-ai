/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Affiliate_Booster.php
*/
<?php
/**
 * Plugin Name: AI Affiliate Booster
 * Plugin URI: https://example.com/ai-affiliate-booster
 * Description: AI-powered affiliate content generator with custom coupons and recommendations.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIAffiliateBooster {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_affiliate_booster', array($this, 'shortcode_callback'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-affiliate-booster', plugin_dir_url(__FILE__) . 'ai-booster.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-affiliate-booster', 'aiBooster', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_booster_nonce')
        ));
    }

    public function admin_scripts($hook) {
        if ($hook !== 'toplevel_page_ai-affiliate-booster') return;
        wp_enqueue_editor();
    }

    public function admin_menu() {
        add_menu_page(
            'AI Affiliate Booster',
            'AI Affiliate Booster',
            'manage_options',
            'ai-affiliate-booster',
            array($this, 'admin_page'),
            'dashicons-money-alt',
            30
        );
    }

    public function admin_page() {
        if (isset($_POST['generate_content'])) {
            check_admin_referer('ai_booster_generate');
            $keyword = sanitize_text_field($_POST['keyword']);
            $affiliate_link = esc_url_raw($_POST['affiliate_link']);
            $content = $this->generate_ai_content($keyword, $affiliate_link);
            echo '<div class="notice notice-success"><p>Generated Content:</p><pre>' . esc_html($content) . '</pre></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Affiliate Booster</h1>
            <form method="post">
                <?php wp_nonce_field('ai_booster_generate'); ?>
                <p><label>Keyword/Topic: <input type="text" name="keyword" required style="width:300px;"></label></p>
                <p><label>Affiliate Link: <input type="url" name="affiliate_link" required style="width:300px;"></label></p>
                <p><input type="submit" name="generate_content" value="Generate Affiliate Content" class="button-primary"></p>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode <code>[ai_affiliate_booster keyword="product" link="your-link"]</code> on any page/post.</p>
            <p><strong>Pro Features:</strong> Unlimited generations, custom coupons, analytics. <a href="#" onclick="alert('Upgrade to Pro for $49/year')">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function generate_ai_content($keyword, $link) {
        // Simulated AI generation (in Pro, integrate real AI API like OpenAI)
        $templates = array(
            "Discover the best {$keyword}! Get it now with our exclusive discount: <a href=\"{$link}\" target=\"_blank\">Buy Now & Save 20%</a>. This product has revolutionized my workflow.",
            "Top recommendation: {$keyword}. Exclusive coupon code: AFFBOOST20 at checkout. <a href=\"{$link}\" target=\"_blank\">Shop Here</a> and boost your productivity!",
            "Why {$keyword} is a must-have in 2026. Use this link for special deal: <a href=\"{$link}\" target=\"_blank\">Get Discounted Price</a>. Limited time offer!"
        );
        return $templates[array_rand($templates)];
    }

    public function shortcode_callback($atts) {
        $atts = shortcode_atts(array(
            'keyword' => 'product',
            'link' => '#',
            'pro' => 'false'
        ), $atts);

        if ($atts['pro'] === 'false') {
            return '<p><strong>AI Affiliate Booster:</strong> Upgrade to Pro for full AI-generated content with custom coupons!</p>';
        }

        return $this->generate_ai_content($atts['keyword'], $atts['link']);
    }

    public function activate() {
        add_option('ai_affiliate_booster_version', '1.0.0');
        flush_rewrite_rules();
    }
}

new AIAffiliateBooster();

// Pro upsell notice
function ai_booster_admin_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>AI Affiliate Booster Pro:</strong> Unlock unlimited AI generations, coupon creator, and analytics for $49/year. <a href="#" onclick="alert(\'Visit example.com/pricing\')">Learn More</a></p></div>';
}
add_action('admin_notices', 'ai_booster_admin_notice');

// AJAX for frontend generation (Pro feature teaser)
add_action('wp_ajax_ai_booster_generate', 'ai_booster_ajax_generate');
add_action('wp_ajax_nopriv_ai_booster_generate', 'ai_booster_ajax_generate');
function ai_booster_ajax_generate() {
    check_ajax_referer('ai_booster_nonce', 'nonce');
    $keyword = sanitize_text_field($_POST['keyword']);
    $link = esc_url_raw($_POST['link']);
    $content = '(Pro Feature: Full AI content here) ' . esc_html($keyword . ' - ' . $link);
    wp_send_json_success($content);
}
