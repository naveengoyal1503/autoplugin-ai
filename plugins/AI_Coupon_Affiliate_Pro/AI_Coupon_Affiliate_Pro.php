/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: AI-powered coupon and affiliate manager for WordPress. Generate, track, and monetize coupons with ease.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponAffiliatePro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon_box', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('AI Coupon Pro Settings', 'AI Coupon Pro', 'manage_options', 'ai-coupon-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('ai_coupon_api_key', sanitize_text_field($_POST['api_key']));
            update_option('ai_coupon_affiliate_id', sanitize_text_field($_POST['affiliate_id']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('ai_coupon_api_key', '');
        $affiliate_id = get_option('ai_coupon_affiliate_id', '');
        ?>
        <div class="wrap">
            <h1>AI Coupon Affiliate Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>OpenAI API Key (Pro)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" placeholder="sk-..."></td>
                    </tr>
                    <tr>
                        <th>Your Affiliate ID</th>
                        <td><input type="text" name="affiliate_id" value="<?php echo esc_attr($affiliate_id); ?>" class="regular-text"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock AI generation, unlimited coupons, analytics. <a href="https://example.com/pro" target="_blank">Get Pro ($49/year)</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'niche' => 'general',
            'count' => 3
        ), $atts);

        $coupons = get_transient('ai_coupon_cache_' . md5($atts['niche']));
        if (false === $coupons) {
            $coupons = $this->generate_coupons($atts['niche'], $atts['count']);
            set_transient('ai_coupon_cache_' . md5($atts['niche']), $coupons, HOUR_IN_SECONDS);
        }

        $output = '<div class="ai-coupon-container">';
        foreach ($coupons as $coupon) {
            $aff_link = $coupon['link'] . (strpos($coupon['link'], '?') ? '&' : '?') . 'aff=' . get_option('ai_coupon_affiliate_id', 'yourid');
            $output .= '<div class="coupon-item">';
            $output .= '<h4>' . esc_html($coupon['title']) . '</h4>';
            $output .= '<p>Code: <strong>' . esc_html($coupon['code']) . '</strong></p>';
            $output .= '<p>Save: ' . esc_html($coupon['discount']) . '</p>';
            $output .= '<a href="' . esc_url($aff_link) . '" class="coupon-btn" target="_blank">Shop Now & Save</a>';
            $output .= '</div>';
        }
        $output .= '</div>';
        $output .= '<p><small>Powered by <a href="https://example.com/pro" target="_blank">AI Coupon Pro</a></small></p>';

        return $output;
    }

    private function generate_coupons($niche, $count) {
        // Free version: Static demo coupons
        $demo_coupons = array(
            array('title' => 'Amazon 20% Off Electronics', 'code' => 'SAVE20', 'discount' => '20%', 'link' => 'https://amazon.com'),
            array('title' => 'Shopify $10 Credit', 'code' => 'WP10', 'discount' => '$10', 'link' => 'https://shopify.com'),
            array('title' => 'Hostinger 75% Off', 'code' => 'HOST75', 'discount' => '75%', 'link' => 'https://hostinger.com')
        );

        // Pro: AI generation (demo placeholder)
        $api_key = get_option('ai_coupon_api_key');
        if ($api_key) {
            // Simulate AI call
            return $demo_coupons; // Replace with real OpenAI API call
        }

        return array_slice($demo_coupons, 0, $count);
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AICouponAffiliatePro();

// Inline CSS/JS for single-file

function ai_coupon_inline_assets() {
    ?>
    <style>
    .ai-coupon-container { max-width: 600px; margin: 20px 0; }
    .coupon-item { background: #f9f9f9; padding: 20px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #007cba; }
    .coupon-btn { background: #ff6600; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
    .coupon-btn:hover { background: #e55a00; }
    </style>
    <script>
    jQuery(document).ready(function($) {
        $('.coupon-btn').on('click', function() {
            // Track clicks (Pro analytics)
            console.log('Coupon clicked!');
        });
    });
    </script>
    <?php
}

add_action('wp_head', 'ai_coupon_inline_assets');