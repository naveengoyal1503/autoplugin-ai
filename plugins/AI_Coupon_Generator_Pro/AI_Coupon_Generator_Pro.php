/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Generator_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Generator Pro
 * Plugin URI: https://example.com/ai-coupon-generator
 * Description: Automatically generates unique, personalized coupon codes for your WordPress site. Boost affiliate earnings with custom discounts.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponGenerator {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
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
        wp_enqueue_script('jquery');
    }

    public function admin_menu() {
        add_options_page(
            'AI Coupon Generator',
            'AI Coupons',
            'manage_options',
            'ai-coupon-generator',
            array($this, 'admin_page')
        );
    }

    public function admin_init() {
        register_setting('ai_coupon_settings', 'ai_coupon_options');
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('ai_coupon_options', $_POST['ai_coupon_options']);
        }
        $options = get_option('ai_coupon_options', array('prefix' => 'SAVE', 'discount' => '20'));
        ?>
        <div class="wrap">
            <h1>AI Coupon Generator Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupon Prefix</th>
                        <td><input type="text" name="ai_coupon_options[prefix]" value="<?php echo esc_attr($options['prefix']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Default Discount %</th>
                        <td><input type="number" name="ai_coupon_options[discount]" value="<?php echo esc_attr($options['discount']); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use <code>[ai_coupon]</code> shortcode on any page/post to display a unique coupon.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate' => '',
            'product' => ''
        ), $atts);

        $options = get_option('ai_coupon_options', array('prefix' => 'SAVE', 'discount' => '20'));
        $code = strtoupper(wp_generate_uuid4());
        $code = substr($code, 0, 8);
        $code = $options['prefix'] . $code;
        $discount = $options['discount'];

        // Store coupon temporarily
        set_transient('ai_coupon_' . md5($code), array(
            'code' => $code,
            'discount' => $discount,
            'affiliate' => $atts['affiliate'],
            'product' => $atts['product'],
            'used' => 0
        ), 3600);

        ob_start();
        ?>
        <div id="ai-coupon" style="border: 2px dashed #0073aa; padding: 20px; text-align: center; background: #f9f9f9;">
            <h3>Exclusive Coupon for You!</h3>
            <div style="font-size: 2em; color: #0073aa; font-weight: bold; margin: 10px 0;"><?php echo esc_html($code); ?></div>
            <p>Save <strong><?php echo esc_html($discount); ?>% OFF</strong> on <?php echo esc_html($atts['product'] ?: 'selected products'); ?></p>
            <?php if ($atts['affiliate']) : ?>
            <p><a href="<?php echo esc_url($atts['affiliate']); ?>" target="_blank" class="button button-primary" style="padding: 10px 20px;">Shop Now & Save</a></p>
            <?php endif; ?>
            <small>This is your unique code - generated just for you!</small>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#ai-coupon').on('click', function() {
                navigator.clipboard.writeText('<?php echo esc_js($code); ?>');
                $(this).append('<p style="color: green;">Copied to clipboard!</p>');
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        // Create default options on activation
        if (!get_option('ai_coupon_options')) {
            update_option('ai_coupon_options', array('prefix' => 'SAVE', 'discount' => '20'));
        }
    }
}

AICouponGenerator::get_instance();

// Premium upsell notice (remove for production)
add_action('admin_notices', function() {
    if (current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>AI Coupon Generator Pro</strong> for unlimited coupons, analytics, and AI-powered personalization! Visit <a href="https://example.com/pro">example.com/pro</a></p></div>';
    }
});