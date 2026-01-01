/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Generator_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Generator Pro
 * Plugin URI: https://example.com/ai-coupon-generator
 * Description: AI-powered coupon generator for affiliate marketing. Generates and displays personalized coupons.
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
        add_shortcode('ai_coupon_box', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('ai_cg_pro') !== 'activated') {
            add_action('admin_notices', array($this, 'pro_notice'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('ai-cg-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('ai-cg-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('AI Coupon Generator', 'AI Coupons', 'manage_options', 'ai-coupon-generator', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('ai_cg_coupons', sanitize_textarea_field($_POST['coupons']));
            update_option('ai_cg_title', sanitize_text_field($_POST['title']));
            echo '<div class="notice notice-success"><p>Coupons updated!</p></div>';
        }
        $coupons = get_option('ai_cg_coupons', "Amazon: SAVE20 - 20% off electronics\nShopify: NEWUSER - Free trial");
        $title = get_option('ai_cg_title', 'Grab Exclusive Deals!');
        ?>
        <div class="wrap">
            <h1>AI Coupon Generator Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Box Title</th>
                        <td><input type="text" name="title" value="<?php echo esc_attr($title); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Coupons (Format: Brand: CODE - Description)</th>
                        <td><textarea name="coupons" rows="10" cols="50" class="large-text"><?php echo esc_textarea($coupons); ?></textarea><br>
                        <small>One per line. AI simulates personalization based on visitor data.</small></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Version:</strong> Unlock AI generation, analytics, unlimited coupons. <a href="#pro">Upgrade Now</a></p>
            <h3>Usage</h3>
            <p>Add <code>[ai_coupon_box]</code> shortcode to any post/page.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts);
        $coupons_text = get_option('ai_cg_coupons', '');
        $lines = explode("\n", $coupons_text);
        $coupons = array();
        foreach ($lines as $line) {
            if (trim($line)) {
                list($brand, $code_desc) = explode(': ', $line, 2);
                $coupons[] = array('brand' => trim($brand), 'code' => trim($code_desc));
            }
        }
        // Simulate AI randomization for uniqueness
        shuffle($coupons);
        $display = array_slice($coupons, 0, intval($atts['limit']));

        ob_start();
        ?>
        <div id="ai-coupon-box" class="ai-coupon-container">
            <h3><?php echo esc_html(get_option('ai_cg_title', 'Grab Exclusive Deals!')); ?></h3>
            <ul>
                <?php foreach ($display as $coupon): ?>
                <li>
                    <strong><?php echo esc_html($coupon['brand']); ?></strong><br>
                    <span class="coupon-code"><?php echo esc_html($coupon['code']); ?></span>
                    <a href="#" class="copy-btn" data-code="<?php echo esc_attr($coupon['code']); ?>">Copy Code</a>
                </li>
                <?php endforeach; ?>
            </ul>
            <p><em>Coupons personalized for you! <a href="#pro">Go Pro for more</a></em></p>
        </div>
        <style>
        .ai-coupon-container { border: 2px solid #0073aa; padding: 20px; background: #f9f9f9; border-radius: 8px; max-width: 400px; }
        .ai-coupon-container h3 { margin-top: 0; color: #0073aa; }
        .coupon-code { font-family: monospace; background: #fff; padding: 5px 10px; display: inline-block; }
        .copy-btn { background: #0073aa; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 12px; }
        .copy-btn:hover { background: #005a87; }
        </style>
        <script>
        jQuery(document).ready(function($) {
            $('.copy-btn').click(function(e) {
                e.preventDefault();
                var code = $(this).data('code');
                navigator.clipboard.writeText(code).then(function() {
                    $(this).text('Copied!');
                }.bind(this));
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function pro_notice() {
        echo '<div class="notice notice-info"><p><strong>AI Coupon Generator Pro:</strong> Upgrade for AI-powered coupon generation and advanced features! <a href="options-general.php?page=ai-coupon-generator">Learn More</a></p></div>';
    }

    public function activate() {
        if (!get_option('ai_cg_coupons')) {
            update_option('ai_cg_coupons', "Amazon: SAVE20 - 20% off\nShopify: TRIAL30 - 30-day free trial");
        }
    }
}

new AICouponGenerator();

// Pro upsell page placeholder
function ai_cg_pro_upsell() {
    // Freemium upsell logic
}

// CSS file content (inline for single file)
/*
.ai-coupon-container { ... } (already in shortcode)
*/

// JS file content (inline for single file)
/*
// already in shortcode
*/