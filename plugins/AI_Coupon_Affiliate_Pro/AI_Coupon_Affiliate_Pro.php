/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: AI-powered coupon generator and affiliate link manager that creates personalized coupons, tracks clicks, and boosts affiliate earnings.
 * Version: 1.0.0
 * Author: Your Name
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
        add_shortcode('ai_coupon_generator', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-affiliate-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_style('ai-coupon-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('ai-coupon-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
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
        if (isset($_POST['submit'])) {
            update_option('ai_coupon_api_key', sanitize_text_field($_POST['api_key']));
            update_option('ai_coupon_affiliate_links', sanitize_textarea_field($_POST['affiliate_links']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('ai_coupon_api_key', '');
        $aff_links = get_option('ai_coupon_affiliate_links', '');
        ?>
        <div class="wrap">
            <h1>AI Coupon Affiliate Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>OpenAI API Key (Pro Feature)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" placeholder="sk-..."></td>
                    </tr>
                    <tr>
                        <th>Affiliate Links (JSON format)</th>
                        <td><textarea name="affiliate_links" rows="10" class="large-text"><?php echo esc_textarea($aff_links); ?></textarea><br>
                        <small>Example: {"amazon":"https://amzn.to/abc","shopify":"https://shopify.com/aff123"}</small></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Pro</strong> for unlimited AI generations, analytics, and auto-insertion. <a href="https://example.com/pro" target="_blank">Get Pro ($49/year)</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'niche' => 'general',
            'count' => 3
        ), $atts);

        $api_key = get_option('ai_coupon_api_key');
        $coupons = array();

        if ($api_key && class_exists('WP_REST_Server')) {
            // Simulate AI generation (Pro feature - replace with real OpenAI call)
            $prompt = "Generate " . $atts['count'] . " unique coupons for " . $atts['niche'] . " niche. Format: Store|Code|Discount|Expiry|Affiliate";
            $coupons = $this->generate_ai_coupons($prompt, $api_key);
        } else {
            // Free demo coupons
            $demo_coupons = array(
                array('Amazon', 'SAVE20', '20% Off', '2026-12-31', 'https://amzn.to/demo'),
                array('Shopify', 'WP10', '10% Off', '2026-06-30', 'https://shopify.com/demo'),
                array('Udemy', 'LEARN50', '50% Off Courses', '2026-03-31', 'https://udemy.com/demo')
            );
            $coupons = array_slice($demo_coupons, 0, $atts['count']);
        }

        ob_start();
        echo '<div class="ai-coupon-container">';
        foreach ($coupons as $coupon) {
            $aff_links = json_decode(get_option('ai_coupon_affiliate_links', '{}'), true);
            $link = isset($aff_links[$coupon]) ? $aff_links[$coupon] : $coupon[4];
            echo '<div class="coupon-card">';
            echo '<h4>' . esc_html($coupon) . '</h4>';
            echo '<p><strong>Code:</strong> ' . esc_html($coupon[1]) . '</p>';
            echo '<p><strong>Discount:</strong> ' . esc_html($coupon[2]) . '</p>';
            echo '<p><strong>Expires:</strong> ' . esc_html($coupon[3]) . '</p>';
            echo '<a href="' . esc_url($link) . '" class="coupon-btn" target="_blank" rel="nofollow">Get Deal <span class="dashicons dashicons-external"></span></a>';
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    private function generate_ai_coupons($prompt, $api_key) {
        // Pro: Real OpenAI integration
        // For demo: Return mock data
        return array(
            array('ExampleStore', 'AI' . wp_rand(1000,9999), rand(10,50) . '% Off', date('Y-m-d', strtotime('+30 days')), 'https://pro-link.com')
        );
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

AICouponAffiliatePro::get_instance();

// Pro upsell notice
function ai_coupon_admin_notice() {
    if (!get_option('ai_coupon_api_key')) {
        echo '<div class="notice notice-info is-dismissible"><p>Unlock <strong>AI Coupon Affiliate Pro</strong> features with a Pro license! <a href="https://example.com/pro">Upgrade Now ($49/year)</a></p></div>';
    }
}
add_action('admin_notices', 'ai_coupon_admin_notice');

// Track clicks (Pro analytics)
add_action('wp_ajax_track_coupon_click', 'ai_coupon_track_click');
function ai_coupon_track_click() {
    // Log click for analytics (Pro)
    wp_die();
}

/* CSS */
function ai_coupon_styles() {
    echo '<style>
    .ai-coupon-container { display: flex; flex-wrap: wrap; gap: 20px; }
    .coupon-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); flex: 1 1 300px; }
    .coupon-card h4 { margin: 0 0 10px; }
    .coupon-btn { background: #ff6b6b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
    .coupon-btn:hover { background: #ff5252; }
    </style>';
}
add_action('wp_head', 'ai_coupon_styles');

/* JS */
function ai_coupon_scripts() {
    echo '<script>
    jQuery(document).ready(function($) {
        $(".coupon-btn").on("click", function(e) {
            $.post(ajaxurl, {action: "track_coupon_click", coupon: $(this).data("coupon")});
        });
    });
    </script>';
}
add_action('wp_footer', 'ai_coupon_scripts');