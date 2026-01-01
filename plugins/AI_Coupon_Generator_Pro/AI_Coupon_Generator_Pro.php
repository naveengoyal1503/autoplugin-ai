/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Generator_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Generator Pro
 * Plugin URI: https://example.com/aicouponpro
 * Description: AI-powered coupon generator for WordPress. Create unique promo codes to boost affiliate sales and user engagement.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AICouponGeneratorPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (get_option('ai_coupon_pro_version') !== '1.0.0') {
            $this->install();
        }
    }

    public function install() {
        update_option('ai_coupon_pro_version', '1.0.0');
        update_option('ai_coupon_pro_license', 'free');
        update_option('ai_coupon_limit', 5);
    }

    public function activate() {
        $this->install();
    }

    public function admin_menu() {
        add_options_page(
            'AI Coupon Pro Settings',
            'AI Coupon Pro',
            'manage_options',
            'ai-coupon-pro',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['ai_coupon_submit'])) {
            update_option('ai_coupon_api_key', sanitize_text_field($_POST['api_key']));
            update_option('ai_coupon_brand', sanitize_text_field($_POST['brand']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('ai_coupon_api_key', '');
        $brand = get_option('ai_coupon_brand', 'YourBrand');
        ?>
        <div class="wrap">
            <h1>AI Coupon Generator Pro</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>OpenAI API Key</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" placeholder="sk-..."></td>
                    </tr>
                    <tr>
                        <th>Default Brand</th>
                        <td><input type="text" name="brand" value="<?php echo esc_attr($brand); ?>" class="regular-text"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics, and custom integrations for $49/year.</p>
            <p>Use shortcode: <code>[ai_coupon brand="Amazon" discount="20"]</code></p>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function generate_coupon($brand = '', $discount = 20) {
        $brand = $brand ?: get_option('ai_coupon_brand', 'YourBrand');
        $limit = get_option('ai_coupon_limit', 5);
        $count = get_option('ai_coupon_generated', 0);

        if ($count >= $limit && get_option('ai_coupon_pro_license') !== 'pro') {
            return 'Upgrade to Pro for unlimited coupons!';
        }

        // Simulate AI generation (replace with real OpenAI API call in Pro)
        $codes = array('SAVE20', 'DEAL25', 'DISCOUNT30', 'PROMO15', 'COUPON10');
        $code = $codes[array_rand($codes)];
        update_option('ai_coupon_generated', $count + 1);

        $coupon = "Exclusive $brand Coupon: <strong>$code</strong> - $discount% OFF!";
        return $coupon;
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'brand' => '',
            'discount' => '20',
        ), $atts);

        $coupon = $this->generate_coupon($atts['brand'], $atts['discount']);

        return '<div class="ai-coupon-box">' . $coupon . '<button class="ai-copy-btn">Copy Code</button></div>';
    }
}

new AICouponGeneratorPro();

// Pro check function (simulate license)
function ai_coupon_is_pro() {
    return get_option('ai_coupon_pro_license') === 'pro';
}

// AJAX for copy functionality
add_action('wp_ajax_copy_coupon', 'ai_coupon_ajax_copy');
function ai_coupon_ajax_copy() {
    if (!wp_verify_nonce($_POST['nonce'], 'ai_coupon_nonce')) {
        wp_die();
    }
    echo 'Copied to clipboard!';
    wp_die();
}

// Create assets directories on activation
register_activation_hook(__FILE__, 'ai_coupon_create_assets');
function ai_coupon_create_assets() {
    $upload_dir = plugin_dir_path(__FILE__) . 'assets';
    if (!file_exists($upload_dir)) {
        wp_mkdir_p($upload_dir);
    }
    // Minimal JS
    $js = "jQuery('.ai-copy-btn').click(function(){ navigator.clipboard.writeText(jQuery(this).prev('strong').text()); alert('Copied!'); });";
    file_put_contents($upload_dir . '/script.js', $js);
    // Minimal CSS
    $css = '.ai-coupon-box { background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; border-radius: 5px; text-align: center; font-size: 18px; } .ai-copy-btn { background: #007cba; color: white; border: none; padding: 10px 20px; margin-left: 10px; cursor: pointer; border-radius: 3px; }';
    file_put_contents($upload_dir . '/style.css', $css);
}
?>