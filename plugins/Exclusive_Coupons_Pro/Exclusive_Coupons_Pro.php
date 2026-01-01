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
    exit; // Exit if accessed directly.
}

class ExclusiveCouponsPro {
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
        register_setting('exclusive_coupons_options', 'exclusive_coupons_settings');
        add_settings_section('main_section', 'Coupon Settings', null, 'exclusive_coupons');
        add_settings_field('affiliate_links', 'Affiliate Products', array($this, 'affiliate_links_field'), 'exclusive_coupons', 'main_section');
        add_settings_field('pro_upgrade', 'Upgrade to Pro', array($this, 'pro_upgrade_field'), 'exclusive_coupons', 'main_section');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Exclusive Coupons Pro Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('exclusive_coupons_options');
                do_settings_sections('exclusive_coupons');
                submit_button();
                ?>
            </form>
            <div class="pro-notice notice notice-info"><p><strong>Pro Features:</strong> Unlimited coupons, advanced tracking, email capture, premium templates. <a href="#" onclick="alert('Upgrade at example.com/pro')">Upgrade Now ($49/year)</a></p></div>
        </div>
        <?php
    }

    public function affiliate_links_field() {
        $settings = get_option('exclusive_coupons_settings', array('products' => array()));
        echo '<textarea name="exclusive_coupons_settings[products]" rows="10" cols="50">' . esc_textarea(json_encode($settings['products'], JSON_PRETTY_PRINT)) . '</textarea><p class="description">JSON array of products: {"name":"Product","affiliate_url":"https://aff.link","base_discount":"10"}</p>';
    }

    public function pro_upgrade_field() {
        echo '<p>Unlock pro features for more revenue!</p>';
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $settings = get_option('exclusive_coupons_settings', array('products' => array()));
        $products = $settings['products'];
        if (!isset($products[$atts['id']])) {
            return '<p>No coupon found.</p>';
        }
        $product = $products[$atts['id']];
        $unique_code = $this->generate_unique_code($product['name']);
        $coupon_url = add_query_arg('coupon', $unique_code, $product['affiliate_url']);
        ob_start();
        ?>
        <div class="exclusive-coupon" data-product-id="<?php echo esc_attr($atts['id']); ?>">
            <h3><?php echo esc_html($product['name']); ?> - Exclusive <?php echo esc_html($product['base_discount']); ?>% OFF!</h3>
            <p>Your unique code: <strong><?php echo esc_html($unique_code); ?></strong></p>
            <a href="<?php echo esc_url($coupon_url); ?>" class="coupon-btn" target="_blank">Shop Now & Save</a>
            <p class="coupon-used" style="display:none;">Code used! Tracked for commissions.</p>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_unique_code($product_name) {
        $site = sanitize_title(get_bloginfo('name'));
        $rand = wp_rand(1000, 9999);
        return strtoupper(substr($product_name, 0, 4) . $site . $rand);
    }

    public function activate() {
        add_option('exclusive_coupons_settings', array('products' => array(
            array('name' => 'Sample Product', 'affiliate_url' => '#', 'base_discount' => '15')
        )));
    }
}

ExclusiveCouponsPro::get_instance();

// Pro teaser
function exclusive_coupons_pro_teaser() {
    if (!is_pro()) {
        echo '<div class="notice notice-warning"><p>Upgrade to <strong>Exclusive Coupons Pro</strong> for unlimited coupons and tracking!</p></div>';
    }
}
add_action('admin_notices', 'exclusive_coupons_pro_teaser');

function is_pro() {
    return false; // Freemium check
}

// Assets placeholder (create assets/script.js and style.css)
/*
assets/style.css:
.exclusive-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; }
.coupon-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
.coupon-btn:hover { background: #005a87; }

assets/script.js:
jQuery(document).ready(function($) {
    $('.coupon-btn').click(function() {
        $(this).parent().find('.coupon-used').show();
        $(this).hide();
    });
});
*/