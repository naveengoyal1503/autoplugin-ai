/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: AI-powered coupon generator that creates personalized discount codes and affiliate links for WordPress sites.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AICouponAffiliatePro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon_generator', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-pro', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('AI Coupon Pro Settings', 'AI Coupon Pro', 'manage_options', 'ai-coupon-pro', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('ai_coupon_pro_options', 'ai_coupon_pro_settings');
        add_settings_section('main_section', 'Main Settings', null, 'ai-coupon-pro');
        add_settings_field('api_key', 'OpenAI API Key (Pro)', array($this, 'api_key_field'), 'ai-coupon-pro', 'main_section');
        add_settings_field('affiliate_links', 'Default Affiliate Links', array($this, 'affiliate_links_field'), 'ai-coupon-pro', 'main_section');
        add_settings_field('is_pro', 'Pro Version', array($this, 'is_pro_field'), 'ai-coupon-pro', 'main_section');
    }

    public function api_key_field() {
        $options = get_option('ai_coupon_pro_settings');
        echo '<input type="password" name="ai_coupon_pro_settings[api_key]" value="' . esc_attr($options['api_key'] ?? '') . '" class="regular-text" />';
        echo '<p class="description">Enter your OpenAI API key for AI-generated coupons (Pro feature).</p>';
    }

    public function affiliate_links_field() {
        $options = get_option('ai_coupon_pro_settings');
        echo '<textarea name="ai_coupon_pro_settings[affiliate_links]" rows="5" cols="50">' . esc_textarea($options['affiliate_links'] ?? '') . '</textarea>';
        echo '<p class="description">One affiliate link per line: Merchant|Link|Default Discount %</p>';
    }

    public function is_pro_field() {
        $options = get_option('ai_coupon_pro_settings');
        $is_pro = $options['is_pro'] ?? false;
        echo '<input type="checkbox" name="ai_coupon_pro_settings[is_pro]" value="1" ' . checked(1, $is_pro, false) . ' /> Activate Pro (Enter license key)';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>AI Coupon Affiliate Pro Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('ai_coupon_pro_options');
                do_settings_sections('ai-coupon-pro');
                submit_button();
                ?>
            </form>
            <p><strong>Upgrade to Pro</strong> for AI generation, unlimited coupons, analytics. <a href="https://example.com/pro">Get Pro ($49/year)</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'merchant' => '',
            'category' => 'general'
        ), $atts);

        $options = get_option('ai_coupon_pro_settings');
        $affiliates = explode('\n', $options['affiliate_links'] ?? '');
        $coupons = get_option('ai_coupon_cache', array());
        $cache_key = 'coupon_' . md5($atts['merchant'] . $atts['category']);

        if (!isset($coupons[$cache_key]) || !$options['is_pro']) {
            // Mock AI/simple generation
            $code = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
            $discount = rand(10, 50);
            $link = $this->get_affiliate_link($affiliates, $atts['merchant']);
            $coupons[$cache_key] = array(
                'code' => $code,
                'discount' => $discount . '% OFF',
                'link' => $link,
                'expires' => date('Y-m-d', strtotime('+30 days')),
                'merchant' => $atts['merchant'] ?: 'Partner Brand'
            );
            update_option('ai_coupon_cache', $coupons);
        }

        $coupon = $coupons[$cache_key];
        ob_start();
        ?>
        <div class="ai-coupon-card pro-limited<?php echo !$options['is_pro'] ? ' blur' : ''; ?>" data-merchant="<?php echo esc_attr($atts['merchant']); ?>">
            <h3><?php echo esc_html($coupon['merchant']); ?> Deal</h3>
            <div class="coupon-code"><?php echo esc_html($coupon['code']); ?></div>
            <div class="discount"><?php echo esc_html($coupon['discount']); ?></div>
            <a href="<?php echo esc_url($coupon['link']); ?}" class="affiliate-btn" target="_blank" rel="nofollow">Shop Now & Save</a>
            <div class="expires">Expires: <?php echo esc_html($coupon['expires']); ?></div>
            <?php if (!$options['is_pro']): ?>
            <div class="pro-upgrade">Upgrade to Pro for real-time AI coupons!</div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_affiliate_link($affiliates, $merchant) {
        foreach ($affiliates as $line) {
            $parts = explode('|', trim($line));
            if (count($parts) >= 2 && stripos($parts, $merchant) !== false) {
                return $parts[1];
            }
        }
        return 'https://example.com/affiliate?ref=' . get_bloginfo('url');
    }

    public function activate() {
        add_option('ai_coupon_pro_settings', array());
        add_option('ai_coupon_cache', array());
    }
}

new AICouponAffiliatePro();

// Inline CSS/JS for single file

function ai_coupon_inline_assets() {
    ?>
    <style>
    .ai-coupon-card { border: 2px solid #28a745; border-radius: 10px; padding: 20px; max-width: 300px; text-align: center; background: #f8f9fa; font-family: Arial; }
    .ai-coupon-card.blur { filter: blur(3px); position: relative; }
    .ai-coupon-card.blur::after { content: 'Pro Feature'; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.8); color: white; padding: 10px; border-radius: 5px; }
    .coupon-code { font-size: 2em; font-weight: bold; color: #28a745; margin: 10px 0; }
    .discount { font-size: 1.2em; color: #dc3545; }
    .affiliate-btn { display: inline-block; background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px 0; }
    .affiliate-btn:hover { background: #005a87; }
    .pro-upgrade { background: #ffc107; padding: 10px; border-radius: 5px; margin-top: 10px; }
    </style>
    <script>
    jQuery(document).ready(function($) {
        $('.ai-coupon-card').on('click', function() {
            if ($(this).hasClass('blur')) {
                alert('Upgrade to Pro to unlock!');
            }
        });
    });
    </script>
    <?php
}

add_action('wp_head', 'ai_coupon_inline_assets');

// Clear cache daily
if (!wp_next_scheduled('ai_coupon_clear_cache')) {
    wp_schedule_event(time(), 'daily', 'ai_coupon_clear_cache');
}

add_action('ai_coupon_clear_cache', function() {
    delete_option('ai_coupon_cache');
});
?>