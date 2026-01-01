/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: AI-powered coupon and affiliate link generator for WordPress blogs. Earn commissions with personalized deals.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-coupon-affiliate-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponAffiliatePro {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
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
        load_plugin_textdomain('ai-coupon-affiliate-pro', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
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

    public function admin_init() {
        register_setting('ai_coupon_settings', 'ai_coupon_options');
        add_settings_section('ai_coupon_main', 'Main Settings', null, 'ai_coupon');
        add_settings_field('affiliate_links', 'Affiliate Links (JSON)', array($this, 'affiliate_links_field'), 'ai_coupon', 'ai_coupon_main');
        add_settings_field('pro_key', 'Pro License Key', array($this, 'pro_key_field'), 'ai_coupon', 'ai_coupon_main');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>AI Coupon Affiliate Pro</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('ai_coupon_settings');
                do_settings_sections('ai_coupon');
                submit_button();
                ?>
            </form>
            <p><strong>Upgrade to Pro</strong> for AI generation, unlimited coupons, and analytics. <a href="https://example.com/pro" target="_blank">Get Pro ($49/year)</a></p>
        </div>
        <?php
    }

    public function affiliate_links_field() {
        $options = get_option('ai_coupon_options', array('links' => '{"amazon":"https://amazon.com/?tag=YOURTAG","shopify":"https://shopify.com/?ref=YOURREF"}'));
        echo '<textarea name="ai_coupon_options[links]" rows="10" cols="50">' . esc_textarea($options['links']) . '</textarea><p class="description">JSON format: {"store":"affiliate_url"}</p>';
    }

    public function pro_key_field() {
        $options = get_option('ai_coupon_options', array());
        echo '<input type="text" name="ai_coupon_options[pro_key]" value="' . esc_attr($options['pro_key'] ?? '') . '" />';
        echo '<p class="description">Enter Pro key to unlock premium features.</p>';
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'store' => 'amazon',
            'discount' => '20%',
            'expires' => date('Y-m-d', strtotime('+30 days')),
        ), $atts);

        $options = get_option('ai_coupon_options', array('links' => '{}'));
        $links = json_decode($options['links'], true);

        if (!isset($links[$atts['store']])) {
            return '<p>Coupon store not configured.</p>';
        }

        $code = 'SAVE' . $atts['discount'] . wp_generate_password(4, false) . rand(100,999);
        $aff_link = $links[$atts['store']];

        // Pro check
        $is_pro = !empty($options['pro_key']);

        ob_start();
        ?>
        <div class="ai-coupon-box pro-<?php echo $is_pro ? 'active' : 'locked'; ?>">
            <h3>Exclusive Deal: <?php echo esc_html($atts['discount']); ?> OFF!</h3>
            <p><strong>Code:</strong> <span class="coupon-code"><?php echo esc_html($code); ?></span></p>
            <p>Expires: <?php echo esc_html($atts['expires']); ?></p>
            <?php if (!$is_pro) { ?>
                <p class="pro-teaser">Upgrade to Pro for AI-generated unique coupons & tracking!</p>
            <?php } else { ?>
                <p>Clicks tracked: <span class="coupon-clicks">0</span></p>
            <?php } ?>
            <a href="<?php echo esc_url($aff_link); ?>" class="coupon-btn" target="_blank" data-store="<?php echo esc_attr($atts['store']); ?>">Shop Now & Save</a>
        </div>
        <script>
        jQuery(function($) {
            $('.coupon-btn').click(function() {
                if (<?php echo $is_pro ? 'true' : 'false'; ?>) {
                    // AJAX to track click (Pro feature)
                    $.post(ajaxurl, {action: 'track_coupon_click', store: $(this).data('store')});
                }
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('ai_coupon_options')) {
            update_option('ai_coupon_options', array('links' => '{"amazon":"https://amazon.com/?tag=YOURTAG"}'));
        }
    }
}

AICouponAffiliatePro::get_instance();

// AJAX handler for Pro tracking
add_action('wp_ajax_track_coupon_click', function() {
    if (current_user_can('manage_options')) {
        $store = sanitize_text_field($_POST['store']);
        $clicks = get_option('ai_coupon_clicks_' . $store, 0) + 1;
        update_option('ai_coupon_clicks_' . $store, $clicks);
        wp_die();
    }
});

// Create assets directories if needed
$upload_dir = wp_upload_dir();
$assets_dir = plugin_dir_path(__FILE__) . 'assets/';
if (!file_exists($assets_dir)) {
    wp_mkdir_p($assets_dir);
}

// Minimal CSS
file_put_contents($assets_dir . 'style.css', ".ai-coupon-box { border: 2px solid #28a745; padding: 20px; border-radius: 10px; background: #f8fff9; text-align: center; margin: 20px 0; } .coupon-code { font-size: 24px; color: #dc3545; font-weight: bold; } .coupon-btn { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; } .pro-locked { opacity: 0.7; } .pro-teaser { color: #ffc107; font-weight: bold; }");

// Minimal JS
file_put_contents($assets_dir . 'script.js', "console.log('AI Coupon Pro loaded');");