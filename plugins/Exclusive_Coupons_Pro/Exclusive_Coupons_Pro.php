/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Automatically generates and displays exclusive, personalized coupon codes for your WordPress site visitors, boosting affiliate conversions and site revenue.
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
        load_plugin_textdomain('exclusive-coupons-pro', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('exclusive-coupons-pro', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('exclusive-coupons-pro', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page(
            'Exclusive Coupons Pro',
            'Coupons Pro',
            'manage_options',
            'exclusive-coupons-pro',
            array($this, 'settings_page')
        );
    }

    public function admin_init() {
        register_setting('exclusive_coupons_group', 'exclusive_coupons_options');
        add_settings_section('exclusive_coupons_section', 'Coupon Settings', null, 'exclusive_coupons');
        add_settings_field('coupons_list', 'Coupons List (JSON format: {"brand":"discount"})', array($this, 'coupons_field'), 'exclusive_coupons', 'exclusive_coupons_section');
        add_settings_field('pro_nag', 'Upgrade to Pro', array($this, 'pro_nag'), 'exclusive_coupons', 'exclusive_coupons_section');
    }

    public function coupons_field() {
        $options = get_option('exclusive_coupons_options');
        $coupons = isset($options['coupons']) ? $options['coupons'] : '{}';
        echo '<textarea name="exclusive_coupons_options[coupons]" rows="10" cols="50">' . esc_textarea($coupons) . '</textarea>';
        echo '<p class="description">Enter coupons as JSON, e.g. {"Amazon":"10%OFF","Shopify":"SAVE20"}</p>';
    }

    public function pro_nag() {
        echo '<p><strong>Pro Features:</strong> Unlimited coupons, analytics, custom designs, API integrations. <a href="https://example.com/pro" target="_blank">Upgrade Now ($49/year)</a></p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Exclusive Coupons Pro Settings', 'exclusive-coupons-pro'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('exclusive_coupons_group');
                do_settings_sections('exclusive_coupons');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('brand' => ''), $atts);
        $options = get_option('exclusive_coupons_options');
        $coupons = json_decode($options['coupons'] ?? '{}', true);
        $code = '';
        if (!empty($atts['brand']) && isset($coupons[$atts['brand']])) {
            $code = $coupons[$atts['brand']];
        } else {
            $codes = array_values($coupons);
            $code = $codes[array_rand($codes)] ?? 'SAVE10';
        }
        $unique_code = $code . '-' . wp_generate_uuid4() . substr(md5(auth()->user_id ?? ''), 0, 4);
        ob_start();
        ?>
        <div id="exclusive-coupon" class="exclusive-coupon-pro" data-code="<?php echo esc_attr($unique_code); ?>">
            <h3>Your Exclusive Coupon: <span class="coupon-code"><?php echo esc_html($unique_code); ?></span></h3>
            <p>Copy and use at checkout! Limited time offer.</p>
            <button class="copy-btn">Copy Code</button>
            <div class="pro-upsell">Pro: Track usage & analytics</div>
        </div>
        <script>
        jQuery('.copy-btn').click(function() {
            navigator.clipboard.writeText(jQuery(this).siblings('.coupon-code').text());
            jQuery(this).text('Copied!');
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('exclusive_coupons_options', array('coupons' => '{"Amazon":"10%OFF","Shopify":"SAVE20"}'));
    }
}

ExclusiveCouponsPro::get_instance();

// Pro upsell hook
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Exclusive Coupons Pro:</strong> Unlock pro features for <a href="https://example.com/pro">$49/year</a> - Unlimited coupons, analytics & more!</p></div>';
});

// Assets placeholder - create assets/script.js and assets/style.css in plugin folder
?>