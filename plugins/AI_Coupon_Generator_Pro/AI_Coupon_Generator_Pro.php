/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Generator_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Generator Pro
 * Plugin URI: https://example.com/ai-coupon-generator
 * Description: Automatically generates and displays personalized, trackable coupon codes for affiliate marketing.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-coupon-generator
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponGenerator {
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
        add_shortcode('ai_coupon_generator', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-generator', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate' => '',
            'discount' => '10%',
            'expires' => '+30 days',
            'product' => 'Featured Product'
        ), $atts);

        $code = $this->generate_coupon_code($atts['affiliate']);
        $expires = date('Y-m-d', strtotime($atts['expires']));
        $tracking_id = uniqid('coupon_');

        ob_start();
        ?>
        <div class="ai-coupon-container">
            <h3>Exclusive Deal: <strong><?php echo esc_html($atts['product']); ?></strong></h3>
            <div class="coupon-code"><strong><?php echo esc_html($code); ?></strong></div>
            <p>Save <strong><?php echo esc_html($atts['discount']); ?></strong> - Expires: <?php echo esc_html($expires); ?></p>
            <a href="#" class="copy-coupon" data-code="<?php echo esc_attr($code); ?>" data-tracking="<?php echo esc_attr($tracking_id); ?>">Copy Code</a>
            <a href="<?php echo esc_url($this->get_affiliate_link($atts['affiliate'], $tracking_id)); ?>" class="shop-now" target="_blank">Shop Now & Save</a>
            <div class="coupon-stats" data-tracking="<?php echo esc_attr($tracking_id); ?>"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_coupon_code($affiliate = '') {
        $prefix = !empty($affiliate) ? strtoupper(substr($affiliate, 0, 3)) : 'SAVE';
        return $prefix . wp_rand(1000, 9999);
    }

    private function get_affiliate_link($affiliate, $tracking) {
        // Placeholder - replace with actual affiliate link logic
        $base_links = array(
            'amazon' => 'https://amazon.com/deal?tag=yourtag',
            'shopify' => 'https://shopify.com/deal',
            'default' => 'https://example.com/deal'
        );
        $link = isset($base_links[$affiliate]) ? $base_links[$affiliate] : $base_links['default'];
        return add_query_arg('ref', $tracking, $link);
    }

    public function admin_menu() {
        add_options_page(
            'AI Coupon Generator Settings',
            'AI Coupons',
            'manage_options',
            'ai-coupon-settings',
            array($this, 'settings_page')
        );
    }

    public function admin_init() {
        register_setting('ai_coupon_settings', 'ai_coupon_options');
        add_settings_section('ai_coupon_main', 'Main Settings', null, 'ai-coupon');
        add_settings_field('pro_version', 'Pro Version Active', array($this, 'pro_field'), 'ai-coupon', 'ai_coupon_main');
    }

    public function pro_field() {
        $options = get_option('ai_coupon_options', array());
        echo '<input type="checkbox" name="ai_coupon_options[pro]" ' . checked(1, $options['pro'] ?? 0, false) . ' disabled> <p>Upgrade to Pro for unlimited features.</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>AI Coupon Generator Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('ai_coupon_settings');
                do_settings_sections('ai-coupon');
                submit_button();
                ?>
            </form>
            <p><strong>Pro Features:</strong> Unlimited coupons, analytics dashboard, email capture, premium templates. <a href="https://example.com/pro" target="_blank">Upgrade Now ($49/year)</a></p>
        </div>
        <?php
    }

    public function activate() {
        update_option('ai_coupon_pro_nag', true);
        flush_rewrite_rules();
    }
}

AICouponGenerator::get_instance();

// Pro nag
add_action('admin_notices', function() {
    if (get_option('ai_coupon_pro_nag') && !get_option('ai_coupon_options.pro')) {
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Coupon Generator Pro</strong> for unlimited coupons and tracking. <a href="options-general.php?page=ai-coupon-settings">Upgrade now!</a></p></div>';
    }
});

// Assets (inline for single file)
add_action('wp_head', function() {
    if (is_singular() && has_shortcode(get_post()->post_content, 'ai_coupon_generator')) {
        ?>
        <style>
        .ai-coupon-container { background: #fffbd5; border: 2px dashed #f4c400; padding: 20px; border-radius: 10px; text-align: center; max-width: 400px; margin: 20px auto; }
        .coupon-code { font-size: 2em; color: #d63031; background: #fff; padding: 10px; border-radius: 5px; display: block; margin: 10px 0; }
        .copy-coupon, .shop-now { display: inline-block; padding: 10px 20px; margin: 10px; background: #00b140; color: white; text-decoration: none; border-radius: 5px; }
        .copy-coupon:hover, .shop-now:hover { background: #009a32; }
        .coupon-stats { font-size: 0.8em; color: #666; margin-top: 10px; }
        </style>
        <script>
        jQuery(document).ready(function($) {
            $('.copy-coupon').click(function(e) {
                e.preventDefault();
                var code = $(this).data('code');
                navigator.clipboard.writeText(code).then(function() {
                    $(this).text('Copied!');
                    var tracking = $(this).data('tracking');
                    $.post(ajaxurl || '', {action: 'ai_coupon_copy', tracking: tracking});
                }.bind(this));
            });
        });
        </script>
        <?php
    }
});