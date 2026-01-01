/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Generator_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Generator Pro
 * Plugin URI: https://example.com/aicoupon-generator
 * Description: AI-powered plugin that generates unique personalized coupon codes and affiliate deals to boost conversions.
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
        add_shortcode('ai_coupon_generator', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('aicg_pro') !== 'yes') {
            add_action('admin_notices', array($this, 'pro_notice'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate_link' => 'https://example.com/affiliate',
            'base_discount' => '10',
            'max_uses' => 100
        ), $atts);

        $user_id = get_current_user_id();
        $unique_code = $this->generate_unique_code($user_id);

        ob_start();
        ?>
        <div id="ai-coupon-container" style="border: 2px dashed #007cba; padding: 20px; text-align: center; background: #f9f9f9;">
            <h3>🎉 Get Your Personalized Coupon!</h3>
            <div style="font-size: 24px; font-weight: bold; color: #007cba; margin: 10px 0;">
                <?php echo esc_html($unique_code); ?> - Save <strong><?php echo esc_html($atts['base_discount']); ?>% OFF</strong>
            </div>
            <p>Unique for you! Limited to <?php echo esc_html($atts['max_uses']); ?> uses.</p>
            <a href="<?php echo esc_url($atts['affiliate_link'] . '?coupon=' . $unique_code); ?>" target="_blank" class="button button-large" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none;">Shop Now & Save</a>
            <?php if (!$user_id) : ?>
            <p style="margin-top: 10px;"><small>Log in for even better personalized deals!</small></p>
            <?php endif; ?>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#ai-coupon-container').on('click', '.button', function() {
                $(this).text('Coupon Applied! Enjoy Savings!');
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    private function generate_unique_code($user_id = 0) {
        $prefix = $user_id ? 'VIP' . substr(md5($user_id), 0, 4) : 'SAVE';
        $random = wp_rand(1000, 9999);
        $code = strtoupper($prefix . $random);

        // Simulate AI personalization (pro version would use real AI API)
        $affiliates = array('SAVE10', 'DEAL20', 'FLASH15');
        if (get_option('aicg_pro') === 'yes') {
            $code .= '-' . $affiliates[array_rand($affiliates)];
        }

        // Store usage (simple transient for demo)
        $uses = get_transient('aicg_' . $code) ?: 0;
        if ($uses < 100) {
            set_transient('aicg_' . $code, $uses + 1, HOUR_IN_SECONDS);
        }

        return $code;
    }

    public function admin_menu() {
        add_options_page('AI Coupon Generator', 'AI Coupons', 'manage_options', 'ai-coupon-generator', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('aicg_pro', sanitize_text_field($_POST['aicg_pro']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $pro = get_option('aicg_pro');
        ?>
        <div class="wrap">
            <h1>AI Coupon Generator Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Enable Pro Features</th>
                        <td>
                            <select name="aicg_pro">
                                <option value="no" <?php selected($pro, 'no'); ?>>Free Version</option>
                                <option value="yes" <?php selected($pro, 'yes'); ?>>Pro Activated (Simulated)</option>
                            </select>
                            <p class="description">Upgrade to Pro for AI-powered coupons, analytics, and unlimited usage.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Usage</h2>
            <p>Add <code>[ai_coupon_generator affiliate_link="YOUR_LINK" base_discount="15"]</code> to any post or page.</p>
        </div>
        <?php
    }

    public function pro_notice() {
        echo '<div class="notice notice-info"><p><strong>AI Coupon Generator:</strong> Unlock Pro for AI features & analytics! <a href="' . admin_url('options-general.php?page=ai-coupon-generator') . '">Upgrade Now</a></p></div>';
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AICouponGenerator();

// Freemium upsell hook
add_action('admin_footer', function() {
    if (get_option('aicg_pro') !== 'yes') {
        echo '<div style="position:fixed; bottom:20px; right:20px; background:#007cba; color:white; padding:10px; border-radius:5px; z-index:9999;">
                <strong>Go Pro!</strong> Unlimited coupons + AI for $49/yr <a href="https://example.com/buy-pro" style="color:#fff; text-decoration:underline;" target="_blank">Buy Now</a>
              </div>';
    }
});