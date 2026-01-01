/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: AI-powered coupon and affiliate manager that generates personalized coupons, tracks clicks, and boosts conversions.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
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
        add_shortcode('ai_coupon_section', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
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
        register_setting('ai_coupon_pro_options', 'ai_coupon_pro_settings');
        add_settings_section('ai_coupon_main', 'Main Settings', null, 'ai_coupon_pro');
        add_settings_field('affiliate_links', 'Affiliate Links (JSON)', array($this, 'affiliate_links_field'), 'ai_coupon_pro', 'ai_coupon_main');
        add_settings_field('pro_upgrade', 'Upgrade to Pro', array($this, 'pro_upgrade_field'), 'ai_coupon_pro', 'ai_coupon_main');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>AI Coupon Affiliate Pro</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('ai_coupon_pro_options');
                do_settings_sections('ai_coupon_pro');
                submit_button();
                ?>
            </form>
            <div class="pro-notice notice-warning"><p><strong>Pro Features:</strong> Unlimited coupons, analytics, custom AI prompts. <a href="https://example.com/pro" target="_blank">Upgrade Now ($49/year)</a></p></div>
        </div>
        <?php
    }

    public function affiliate_links_field() {
        $settings = get_option('ai_coupon_pro_settings', array('affiliates' => '[]'));
        echo '<textarea name="ai_coupon_pro_settings[affiliates]" rows="10" cols="50">' . esc_textarea($settings['affiliates']) . '</textarea><p>Enter JSON array of affiliates: {"name":"Brand","link":"https://aff.link","discount":"10%"}</p>';
    }

    public function pro_upgrade_field() {
        echo '<p><a href="https://example.com/pro" class="button button-primary" target="_blank">Upgrade to Pro</a> for advanced features.</p>';
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts);
        $settings = get_option('ai_coupon_pro_settings', array('affiliates' => '[]'));
        $affiliates = json_decode($settings['affiliates'], true) ?: array();

        // Simple AI-like generation: rotate and personalize coupons
        $coupons = array();
        $users = wp_get_current_user();
        $user_id = $users->ID ?: 'guest';
        for ($i = 0; $i < min($atts['limit'], count($affiliates)); $i++) {
            $aff = $affiliates[$i % count($affiliates)];
            $code = strtoupper(substr(md5($user_id . time() . $i), 0, 8));
            $coupons[] = array(
                'name' => $aff['name'],
                'code' => $code,
                'discount' => $aff['discount'],
                'link' => $aff['link'] . '?coupon=' . $code,
                'clicks' => get_option('ai_coupon_clicks_' . $i, 0)
            );
        }

        ob_start();
        ?>
        <div class="ai-coupon-section">
            <h3>Exclusive AI-Generated Coupons</h3>
            <?php foreach ($coupons as $coupon): ?>
            <div class="coupon-item">
                <h4><?php echo esc_html($coupon['name']); ?> - <?php echo esc_html($coupon['discount']); ?> OFF</h4>
                <p>Code: <strong><?php echo esc_html($coupon['code']); ?></strong></p>
                <a href="<?php echo esc_url(add_query_arg('ref', 'wpplugin', $coupon['link'])); ?>" class="coupon-btn" data-coupon-id="<?php echo $i; ?>">Grab Deal & Track</a>
                <span class="clicks"><?php echo $coupon['clicks']; ?> clicks</span>
            </div>
            <?php endforeach; ?>
            <p><em>Pro: Unlimited + Analytics. <a href="https://example.com/pro" target="_blank">Upgrade</a></em></p>
        </div>
        <script>
        jQuery('.coupon-btn').click(function(e) {
            var id = jQuery(this).data('coupon-id');
            jQuery.post(ajaxurl, {action: 'track_coupon_click', id: id});
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('ai_coupon_pro_settings', array('affiliates' => json_encode(array(
            array('name' => 'Sample Brand', 'link' => 'https://example.com/aff', 'discount' => '20%'),
            array('name' => 'Demo Store', 'link' => 'https://example.com/deal', 'discount' => '15%')
        ))));
    }

    public function deactivate() {
        // Cleanup optional
    }
}

// AJAX handler
add_action('wp_ajax_track_coupon_click', 'ai_coupon_track_click');
add_action('wp_ajax_nopriv_track_coupon_click', 'ai_coupon_track_click');
function ai_coupon_track_click() {
    $id = intval($_POST['id']);
    $clicks = get_option('ai_coupon_clicks_' . $id, 0) + 1;
    update_option('ai_coupon_clicks_' . $id, $clicks);
    wp_die();
}

AICouponAffiliatePro::get_instance();

// Dummy assets (in real plugin, create folders)
// Create assets/script.js and assets/style.css manually or via build
?>