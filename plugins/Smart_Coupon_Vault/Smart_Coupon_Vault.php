/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Smart Coupon Vault
 * Plugin URI: https://example.com/smart-coupon-vault
 * Description: AI-powered coupon aggregator for affiliate marketing.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartCouponVault {
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
        add_shortcode('smart_coupon_vault', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_scv_fetch_coupons', array($this, 'ajax_fetch_coupons'));
        add_action('wp_ajax_nopriv_scv_fetch_coupons', array($this, 'ajax_fetch_coupons'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_options_page('Smart Coupon Vault', 'Coupon Vault', 'manage_options', 'smart-coupon-vault', array($this, 'settings_page'));
            add_action('admin_init', array($this, 'settings_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('scv-frontend', plugin_dir_url(__FILE__) . 'scv.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('scv-frontend', plugin_dir_url(__FILE__) . 'scv.css', array(), '1.0.0');
        wp_localize_script('scv-frontend', 'scv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('scv_nonce')));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => 'all',
            'limit' => 10
        ), $atts);

        ob_start();
        ?>
        <div id="scv-container" data-category="<?php echo esc_attr($atts['category']); ?>" data-limit="<?php echo esc_attr($atts['limit']); ?>">
            <div class="scv-loading">Loading coupons...</div>
            <div class="scv-coupons"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_fetch_coupons() {
        check_ajax_referer('scv_nonce', 'nonce');
        $category = sanitize_text_field($_POST['category'] ?? 'all');
        $limit = intval($_POST['limit'] ?? 10);

        // Demo coupons (replace with real API integration in Pro)
        $coupons = $this->get_demo_coupons($category, $limit);

        wp_send_json_success($coupons);
    }

    private function get_demo_coupons($category, $limit) {
        $demo_coupons = array(
            array('code' => 'SAVE20', 'desc' => '20% off on hosting', 'afflink' => 'https://example.com/aff?code=SAVE20', 'expires' => '2026-12-31'),
            array('code' => 'WP50', 'desc' => '$50 off WordPress themes', 'afflink' => 'https://example.com/aff?code=WP50', 'expires' => '2026-06-30'),
            array('code' => 'DEAL10', 'desc' => '10% off plugins', 'afflink' => 'https://example.com/aff?code=DEAL10', 'expires' => '2026-03-31'),
        );

        if ($category !== 'all') {
            $demo_coupons = array_filter($demo_coupons, function($c) use ($category) {
                return stripos($c['desc'], $category) !== false;
            });
        }

        return array_slice($demo_coupons, 0, $limit);
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('scv_settings');
                do_settings_sections('scv_settings');
                submit_button();
                ?>
            </form>
            <p><strong>Upgrade to Pro</strong> for AI coupon fetching, analytics, and unlimited sources. <a href="https://example.com/pro">Get Pro ($49/year)</a></p>
        </div>
        <?php
    }

    public function settings_init() {
        register_setting('scv_settings', 'scv_api_key');
        add_settings_section('scv_section', 'API Settings', null, 'scv_settings');
        add_settings_field('scv_api_key', 'Affiliate API Key', array($this, 'api_key_field'), 'scv_settings', 'scv_section');
    }

    public function api_key_field() {
        $key = get_option('scv_api_key');
        echo '<input type="text" name="scv_api_key" value="' . esc_attr($key) . '" class="regular-text" />';
        echo '<p class="description">Enter your affiliate network API key (Pro feature).</p>
        <p><em>Free version uses demo data. Pro unlocks real-time fetching.</em></p>';
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

SmartCouponVault::get_instance();

// Inline JS and CSS for single-file
add_action('wp_head', function() {
    echo '<script>var scvData = []; jQuery(document).ready(function($) { $(".scv-container").each(function() { var $cont = $(this); $.post(scv_ajax.ajax_url, {action: "scv_fetch_coupons", category: $cont.data("category"), limit: $cont.data("limit"), nonce: scv_ajax.nonce}, function(resp) { if(resp.success) { var html = ""; resp.data.forEach(function(c) { html += "<div class=\'scv-coupon\'><strong>" + c.code + "</strong><br>" + c.desc + "<br><a href=\'" + c.afflink + "\' target=\'_blank\' class=\'scv-btn\'>Get Deal</a><small>Expires: " + c.expires + "</small></div>"; }); $cont.find(".scv-coupons").html(html); $cont.find(".scv-loading").hide(); } }); }); });</script>';
    echo '<style>.scv-container { max-width: 600px; margin: 20px 0; } .scv-coupon { background: #f9f9f9; padding: 15px; margin: 10px 0; border-left: 4px solid #0073aa; } .scv-btn { background: #0073aa; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; } .scv-btn:hover { background: #005a87; }</style>';
});

// Track clicks for analytics (Pro teaser)
add_action('wp_footer', function() {
    if (current_user_can('manage_options')) {
        echo '<script>console.log("Smart Coupon Vault: Upgrade to Pro for click tracking & analytics!");</script>';
    }
});