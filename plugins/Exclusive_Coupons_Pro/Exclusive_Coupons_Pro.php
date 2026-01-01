/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Automatically generates and displays exclusive, trackable coupon codes for affiliate products, boosting conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: exclusive-coupons-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class ExclusiveCouponsPro {
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
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('exclusive-coupons-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('exclusive-coupons-pro', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('exclusive-coupons-pro', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons Pro', 'Coupons Pro', 'manage_options', 'exclusive-coupons-pro', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('exclusive_coupons_pro_options', 'exclusive_coupons_pro_settings');
        add_settings_section('coupons_section', 'Coupon Settings', null, 'exclusive-coupons-pro');
        add_settings_field('affiliate_links', 'Affiliate Links (JSON)', array($this, 'affiliate_links_field'), 'exclusive-coupons-pro', 'coupons_section');
    }

    public function affiliate_links_field() {
        $settings = get_option('exclusive_coupons_pro_settings', array('links' => array()));
        echo '<textarea name="exclusive_coupons_pro_settings[links]" rows="10" cols="50">' . esc_textarea(json_encode($settings['links'], JSON_PRETTY_PRINT)) . '</textarea>';
        echo '<p class="description">Enter affiliate links as JSON array, e.g. [{ "name": "Product", "url": "https://aff.link", "discount": "10%" }]</p>';
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Exclusive Coupons Pro Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('exclusive_coupons_pro_options');
                do_settings_sections('exclusive-coupons-pro');
                submit_button();
                ?>
            </form>
            <h2>Shortcode Usage</h2>
            <p>Use <code>[exclusive_coupon id="1"]</code> to display a coupon. Free version limited to 3 coupons.</p>
            <?php if (!$this->is_pro()) : ?>
            <p><a href="#" class="button button-primary" onclick="alert('Upgrade to Pro for unlimited coupons!')">Upgrade to Pro</a></p>
            <?php endif; ?>
        </div>
        <?php
    }

    private function is_pro() {
        return defined('EXCLUSIVE_COUPONS_PRO') && EXCLUSIVE_COUPONS_PRO;
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $settings = get_option('exclusive_coupons_pro_settings', array('links' => array()));
        $links = $settings['links'];

        if (!isset($links[$atts['id']])) {
            return '<p>Coupon not found.</p>';
        }

        $link = $links[$atts['id']];
        $code = $this->generate_coupon_code($link['name']);
        $tracked_url = add_query_arg('coupon', $code, $link['url']);

        if (!$this->is_pro() && count($links) > 3) {
            return '<p><strong>Pro Feature:</strong> Upgrade for more coupons!</p>';
        }

        ob_start();
        ?>
        <div class="exclusive-coupon">
            <h3><?php echo esc_html($link['name']); ?> - Exclusive <?php echo esc_html($link['discount']); ?> OFF!</h3>
            <p>Your exclusive code: <strong><?php echo $code; ?></strong></p>
            <a href="<?php echo esc_url($tracked_url); ?>" class="coupon-button" target="_blank">Get Deal Now</a>
            <small>Track conversions and boost commissions!</small>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_coupon_code($product_name) {
        return strtoupper(substr(md5($product_name . time() . wp_rand(1000, 9999)), 0, 8));
    }

    public function activate() {
        add_option('exclusive_coupons_pro_settings', array('links' => array()));
    }
}

ExclusiveCouponsPro::get_instance();

// Pro check - define EXCLUSIVE_COUPONS_PRO in wp-config.php for pro features
if (!function_exists('exclusive_coupons_pro_assets')) {
    function exclusive_coupons_pro_assets() {
        // Placeholder for pro assets
    }
}

// Create assets directories and placeholder files
register_activation_hook(__FILE__, function() {
    $upload_dir = plugin_dir_path(__FILE__) . 'assets';
    if (!file_exists($upload_dir)) {
        wp_mkdir_p($upload_dir);
    }
    file_put_contents($upload_dir . '/script.js', '// Pro script placeholder\njQuery(document).ready(function($) { $(".coupon-button").hover(function() { $(this).text("Copy Code!"); }); });');
    file_put_contents($upload_dir . '/style.css', '.exclusive-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; text-align: center; border-radius: 10px; } .coupon-button { background: #0073aa; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block; } .coupon-button:hover { background: #005a87; }');
});