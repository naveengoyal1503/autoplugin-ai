/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Monetizer.php
*/
<?php
/**
 * Plugin Name: AI Content Monetizer
 * Plugin URI: https://example.com/ai-content-monetizer
 * Description: Automatically generates affiliate-optimized product review content with personalized coupon codes and monetization blocks.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentMonetizer {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon_review', array($this, 'coupon_review_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (isset($_POST['aicm_generate'])) {
            $this->generate_content();
        }
    }

    public function admin_menu() {
        add_options_page('AI Content Monetizer', 'AI Content Monetizer', 'manage_options', 'ai-content-monetizer', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['product_name'])) {
            $product = sanitize_text_field($_POST['product_name']);
            $affiliate_link = esc_url($_POST['affiliate_link']);
            $coupon_code = strtoupper(substr(md5($product . time()), 0, 8));
            $content = $this->generate_review_content($product, $affiliate_link, $coupon_code);
            echo '<div class="notice notice-success"><p>Content generated! Use shortcode: [ai_coupon_review product="' . $product . '" coupon="' . $coupon_code . '" link="' . $affiliate_link . '"]</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Content Monetizer</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Product Name</th>
                        <td><input type="text" name="product_name" class="regular-text" placeholder="e.g., Wireless Headphones" required /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Link</th>
                        <td><input type="url" name="affiliate_link" class="regular-text" placeholder="https://affiliate-link.com" required /></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" name="aicm_generate" class="button-primary" value="Generate Review Content" /></p>
            </form>
            <h2>Usage</h2>
            <p>Insert the generated shortcode into any post or page to display monetized content with coupon.</p>
            <p><strong>Upgrade to Pro</strong> for unlimited generations and advanced features: <a href="https://example.com/pro">Get Pro</a></p>
        </div>
        <?php
    }

    private function generate_review_content($product, $link, $coupon) {
        $content = "<h3>Why You'll Love the " . esc_html($product) . "</h3>
        <p>This amazing " . esc_html($product) . " offers top-tier performance at an unbeatable price. Perfect for everyday use!</p>
        <blockquote><strong>Exclusive Coupon:</strong> Use code <code>" . esc_html($coupon) . "</code> for 20% off!</blockquote>
        <p><a href=\"\" . esc_url($link) . \"\" class=\"button\" target=\"_blank\">Buy Now & Save <span class=\"coupon\">" . esc_html($coupon) . "</span></a></p>
        <p><em>Affiliate disclosure: We earn from qualifying purchases.</em></p>";
        return $content;
    }

    public function coupon_review_shortcode($atts) {
        $atts = shortcode_atts(array(
            'product' => 'Product',
            'coupon' => 'SAVE20',
            'link' => '#',
        ), $atts);

        $product = esc_html($atts['product']);
        $coupon = esc_html($atts['coupon']);
        $link = esc_url($atts['link']);

        return "<div class=\"ai-coupon-review\"><h3>" . $product . " Deal</h3><p>Exclusive: <strong>" . $coupon . "</strong> saves 20%!</p><a href=\"\" . $link . \"\" class=\"button button-primary\" target=\"_blank\">Claim Deal</a></div>";
    }

    public function activate() {
        add_option('aicm_pro', 'free');
        flush_rewrite_rules();
    }
}

new AIContentMonetizer();

// Enqueue styles
function aicm_styles() {
    wp_enqueue_style('aicm-style', plugin_dir_url(__FILE__) . 'style.css');
}
add_action('wp_enqueue_scripts', 'aicm_styles');

// Pro upsell notice
function aicm_admin_notice() {
    if (!get_option('aicm_dismissed_notice', false) && get_option('aicm_pro') === 'free') {
        echo '<div class="notice notice-info is-dismissible"><p>Upgrade to <strong>AI Content Monetizer Pro</strong> for unlimited AI content generation! <a href="https://example.com/pro">Learn More</a></p></div>';
    }
}
add_action('admin_notices', 'aicm_admin_notice');