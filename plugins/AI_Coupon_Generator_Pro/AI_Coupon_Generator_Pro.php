/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Generator_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Generator Pro
 * Plugin URI: https://example.com/ai-coupon-generator
 * Description: AI-powered coupon generator for WordPress. Create, manage, and display personalized coupons with affiliate links.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponGenerator {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'ai-coupon.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'ai-coupon.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('AI Coupon Generator', 'AI Coupons', 'manage_options', 'ai-coupon-generator', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('ai_coupon_settings', 'ai_coupon_options');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>AI Coupon Generator Pro</h1>
            <form method="post" action="options.php">
                <?php settings_fields('ai_coupon_settings'); ?>
                <?php do_settings_sections('ai_coupon_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Affiliate Links</th>
                        <td><textarea name="ai_coupon_options[affiliates]" rows="5" cols="50"><?php echo esc_textarea(get_option('ai_coupon_options')['affiliates'] ?? ''); ?></textarea><br><small>One per line: Product Name|Affiliate URL|Discount %</small></td>
                    </tr>
                    <tr>
                        <th>Pro License Key</th>
                        <td><input type="text" name="ai_coupon_options[pro_key]" value="<?php echo esc_attr(get_option('ai_coupon_options')['pro_key'] ?? ''); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[ai_coupon]</code> or <code>[ai_coupon category="tech"]</code></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('category' => ''), $atts);
        $options = get_option('ai_coupon_options', array());
        $affiliates = explode("\n", $options['affiliates'] ?? '');
        $coupons = array();

        foreach ($affiliates as $aff) {
            if (trim($aff)) {
                list($name, $url, $discount) = explode('|', trim($aff), 3);
                if (empty($atts['category']) || stripos($name, $atts['category']) !== false) {
                    $code = 'SAVE' . rand(10, 99) . strtoupper(substr(md5($name), 0, 4));
                    $coupons[] = array(
                        'name' => trim($name),
                        'url' => trim($url),
                        'discount' => trim($discount),
                        'code' => $code
                    );
                }
            }
        }

        if (empty($coupons)) {
            return '<p>No coupons available.</p>';
        }

        $html = '<div class="ai-coupon-container">';
        foreach ($coupons as $coupon) {
            $html .= '<div class="ai-coupon-item">';
            $html .= '<h3>' . esc_html($coupon['name']) . '</h3>';
            $html .= '<p><strong>' . esc_html($coupon['discount']) . ' OFF</strong> - Code: <code>' . esc_html($coupon['code']) . '</code></p>';
            $html .= '<a href="' . esc_url($coupon['url']) . '" class="ai-coupon-btn" target="_blank">Get Deal</a>';
            $html .= '</div>';
        }
        $html .= '</div>';

        if (empty($options['pro_key'])) {
            $html .= '<p><a href="https://example.com/pro" target="_blank">Upgrade to Pro for AI generation & analytics</a></p>';
        }

        return $html;
    }

    public function activate() {
        add_option('ai_coupon_options', array());
    }
}

new AICouponGenerator();

// Inline CSS
add_action('wp_head', function() { ?>
<style>
.ai-coupon-container { max-width: 600px; margin: 20px 0; }
.ai-coupon-item { background: #f9f9f9; padding: 20px; margin: 10px 0; border-radius: 8px; border-left: 5px solid #0073aa; }
.ai-coupon-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
.ai-coupon-btn:hover { background: #005a87; }
</style>
<?php });

// Inline JS
add_action('wp_footer', function() { ?>
<script>
jQuery(document).ready(function($) {
    $('.ai-coupon-btn').on('click', function() {
        $(this).text('Copied! Shop now');
    });
});
</script>
<?php });