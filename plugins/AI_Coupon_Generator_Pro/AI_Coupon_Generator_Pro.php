/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Generator_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Generator Pro
 * Plugin URI: https://example.com/aicouponpro
 * Description: AI-powered coupon generator for WordPress monetization. Create personalized coupons, track usage, and integrate affiliates.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AICouponGeneratorPro {
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_shortcode('ai_coupon', [$this, 'coupon_shortcode']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function init() {
        if (get_option('aicoupon_pro') !== 'pro') {
            add_action('admin_notices', [$this, 'pro_nag']);
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('aicoupon-js', plugin_dir_url(__FILE__) . 'aicoupon.js', ['jquery'], '1.0.0', true);
        wp_enqueue_style('aicoupon-css', plugin_dir_url(__FILE__) . 'aicoupon.css', [], '1.0.0');
    }

    public function admin_menu() {
        add_options_page('AI Coupon Pro', 'AI Coupons', 'manage_options', 'ai-coupon-pro', [$this, 'admin_page']);
    }

    public function admin_page() {
        if (isset($_POST['generate_coupon'])) {
            update_option('ai_coupon_code', sanitize_text_field($_POST['coupon_code']));
            update_option('ai_coupon_desc', sanitize_text_field($_POST['coupon_desc']));
            update_option('ai_affiliate_link', esc_url_raw($_POST['affiliate_link']));
        }
        ?>
        <div class="wrap">
            <h1>AI Coupon Generator Pro</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupon Code</th>
                        <td><input type="text" name="coupon_code" value="<?php echo get_option('ai_coupon_code', 'SAVE20'); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Description</th>
                        <td><textarea name="coupon_desc" class="large-text"><?php echo get_option('ai_coupon_desc', '20% off your first purchase!'); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Affiliate Link</th>
                        <td><input type="url" name="affiliate_link" value="<?php echo get_option('ai_affiliate_link', ''); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button('Generate & Save Coupon'); ?>
            </form>
            <p>Use shortcode: <code>[ai_coupon]</code></p>
            <?php if (get_option('aicoupon_pro') !== 'pro') : ?>
                <div class="notice notice-warning"><p>Upgrade to Pro for unlimited coupons, analytics, and AI generation!</p></div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(['id' => ''], $atts);
        $code = get_option('ai_coupon_code', 'SAVE20');
        $desc = get_option('ai_coupon_desc', 'Exclusive deal!');
        $link = get_option('ai_affiliate_link', '');
        $uses = get_option('ai_coupon_uses', 0);

        ob_start();
        ?>
        <div id="ai-coupon" class="ai-coupon-pro" data-uses="<?php echo $uses; ?>">
            <h3>Exclusive Coupon</h3>
            <div class="coupon-code"><?php echo esc_html($code); ?></div>
            <p><?php echo esc_html($desc); ?></p>
            <?php if ($link) : ?>
                <a href="<?php echo esc_url($link); ?>" class="coupon-btn" target="_blank">Get Deal Now</a>
            <?php endif; ?>
            <small>Used <?php echo $uses; ?> times</small>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#ai-coupon .coupon-code').click(function() {
                navigator.clipboard.writeText('<?php echo esc_js($code); ?>');
                $(this).text('Copied!');
                $.post(ajaxurl, {action: 'ai_coupon_use', nonce: '<?php echo wp_create_nonce('ai_coupon'); ?>'});
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function pro_nag() {
        echo '<div class="notice notice-info"><p>AI Coupon Generator: <strong>Upgrade to Pro</strong> for advanced AI features and remove this notice!</p></div>';
    }

    public function activate() {
        add_option('ai_coupon_code', 'SAVE20');
        add_option('ai_coupon_desc', '20% off first purchase!');
    }
}

// AJAX handler
add_action('wp_ajax_ai_coupon_use', function() {
    check_ajax_referer('ai_coupon', 'nonce');
    $uses = get_option('ai_coupon_uses', 0) + 1;
    update_option('ai_coupon_uses', $uses);
    wp_die();
});

new AICouponGeneratorPro();

// Inline CSS
add_action('wp_head', function() {
    echo '<style>
    .ai-coupon-pro { background: #fff; border: 2px dashed #007cba; padding: 20px; text-align: center; max-width: 400px; margin: 20px auto; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    .coupon-code { font-size: 2em; font-weight: bold; color: #007cba; cursor: pointer; background: #f0f8ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .coupon-btn { background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0; }
    .coupon-btn:hover { background: #218838; }
    </style>';
});

// Freemium check - simulate pro
if (!get_option('aicoupon_pro')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-upgrade"><p><strong>AI Coupon Pro:</strong> Unlock unlimited coupons for $49/year! <a href="https://example.com/pro">Buy Now</a></p></div>';
    });
}
?>