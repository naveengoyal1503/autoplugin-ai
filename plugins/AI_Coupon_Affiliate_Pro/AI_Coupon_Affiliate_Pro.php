/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/ai-coupon-affiliate-pro
 * Description: AI-powered coupon generator for affiliate marketing. Create, track, and display personalized coupons to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-affiliate-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
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
        load_plugin_textdomain('ai-coupon-affiliate-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
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
        add_settings_field('api_key', 'OpenAI API Key (Pro)', array($this, 'api_key_field'), 'ai_coupon', 'ai_coupon_main');
        add_settings_field('affiliate_links', 'Affiliate Links', array($this, 'affiliate_links_field'), 'ai_coupon', 'ai_coupon_main');
    }

    public function api_key_field() {
        $options = get_option('ai_coupon_options');
        echo '<input type="password" name="ai_coupon_options[api_key]" value="' . esc_attr($options['api_key'] ?? '') . '" class="regular-text" placeholder="Pro Feature" />';
        echo '<p class="description">Enter your OpenAI API key for AI-generated coupons (Pro upgrade required).</p>';
    }

    public function affiliate_links_field() {
        $options = get_option('ai_coupon_options');
        echo '<textarea name="ai_coupon_options[affiliate_links]" rows="5" cols="50" class="large-text code">' . esc_textarea($options['affiliate_links'] ?? '') . '</textarea>';
        echo '<p class="description">JSON format: {"brand1": "https://affiliate.link1", "brand2": "https://affiliate.link2"}</p>';
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
            <p><strong>Upgrade to Pro</strong> for AI generation, unlimited coupons, analytics dashboard, and priority support. <a href="https://example.com/pro" target="_blank">Get Pro Now ($49/year)</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'brand' => 'Generic',
            'discount' => '20%',
            'code' => '',
        ), $atts);

        $options = get_option('ai_coupon_options');
        $aff_links = json_decode($options['affiliate_links'] ?? '{}', true);
        $link = $aff_links[$atts['brand']] ?? '#';

        if (empty($atts['code'])) {
            $atts['code'] = 'SAVE' . $atts['discount'] . wp_generate_password(4, false);
        }

        $click_id = uniqid();
        $tracking_url = add_query_arg(array('ref' => 'ai_coupon_pro', 'click_id' => $click_id), $link);

        ob_start();
        ?>
        <div class="ai-coupon-card pro-upgrade-tease">
            <h3>Exclusive Deal: <?php echo esc_html($atts['brand']); ?> - <?php echo esc_html($atts['discount']); ?> OFF!</h3>
            <div class="coupon-code"><?php echo esc_html($atts['code']); ?></div>
            <p><strong>Limited Time!</strong> Click to redeem and shop.</p>
            <a href="<?php echo esc_url($tracking_url); ?>" class="coupon-btn" target="_blank" rel="nofollow">Shop Now & Save</a>
            <small>Coupon tracked by AI Coupon Pro. <span class="pro-badge">Pro Feature</span></small>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('.coupon-btn').click(function() {
                gtag('event', 'coupon_click', {'brand': '<?php echo esc_js($atts['brand']); ?>', 'code': '<?php echo esc_js($atts['code']); ?>'});
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('ai_coupon_options', array());
        // Create assets directories
        $upload_dir = wp_upload_dir();
        $assets_dir = $upload_dir['basedir'] . '/ai-coupon-pro/';
        if (!file_exists($assets_dir)) {
            wp_mkdir_p($assets_dir);
        }
    }

    // Pro Features (commented for free version)
    private function generate_ai_coupon($brand) {
        // Pro: Integrate OpenAI API here
        return 'AI-' . strtoupper(substr($brand, 0, 3)) . wp_generate_password(6, false);
    }

    public function track_clicks() {
        if (isset($_GET['ref']) && $_GET['ref'] === 'ai_coupon_pro') {
            // Log click (Pro analytics)
            error_log('Coupon click: ' . $_GET['click_id']);
        }
    }
}

// Initialize
AICouponAffiliatePro::get_instance();

// Assets (base64 or inline for single file)
function ai_coupon_inline_assets() {
    ?>
    <style>
    .ai-coupon-card { background: linear-gradient(135deg, #ff6b6b, #feca57); color: white; padding: 20px; border-radius: 10px; text-align: center; max-width: 300px; margin: 20px auto; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    .coupon-code { font-size: 2em; font-weight: bold; background: rgba(255,255,255,0.9); color: #333; padding: 15px; border-radius: 5px; margin: 10px 0; letter-spacing: 3px; }
    .coupon-btn { display: inline-block; background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 25px; font-weight: bold; margin-top: 10px; transition: all 0.3s; }
    .coupon-btn:hover { background: #218838; transform: translateY(-2px); }
    .pro-badge { background: #ffd700; color: #333; padding: 2px 6px; border-radius: 3px; font-size: 0.8em; }
    .pro-upgrade-tease::after { content: '✨ Upgrade to Pro for AI-powered unlimited coupons & analytics!'; display: block; margin-top: 10px; font-size: 0.9em; opacity: 0.9; }
    </style>
    <script>
    console.log('AI Coupon Affiliate Pro loaded');
    </script>
    <?php
}
add_action('wp_head', 'ai_coupon_inline_assets');

// Analytics endpoint
add_action('wp_ajax_ai_coupon_track', 'ai_coupon_track_ajax');
add_action('wp_ajax_nopriv_ai_coupon_track', 'ai_coupon_track_ajax');
function ai_coupon_track_ajax() {
    // Pro analytics endpoint
    wp_die();
}

?>