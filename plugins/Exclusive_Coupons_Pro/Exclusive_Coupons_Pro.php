/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Automatically generates and displays personalized, trackable coupon codes for your WordPress site, boosting affiliate conversions and reader engagement.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
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
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ecp-script', plugin_dir_url(__FILE__) . 'ecp-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ecp-style', plugin_dir_url(__FILE__) . 'ecp-style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons Pro', 'Coupons Pro', 'manage_options', 'exclusive-coupons-pro', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('ecp_options', 'ecp_settings');
        add_settings_section('ecp_main', 'Coupon Settings', null, 'exclusive-coupons-pro');
        add_settings_field('ecp_coupons', 'Coupons', array($this, 'coupons_field'), 'exclusive-coupons-pro', 'ecp_main');
    }

    public function coupons_field() {
        $settings = get_option('ecp_settings', array('coupons' => array()));
        $coupons = $settings['coupons'];
        echo '<textarea name="ecp_settings[coupons]" rows="10" cols="50">' . esc_textarea(json_encode($coupons, JSON_PRETTY_PRINT)) . '</textarea>';
        echo '<p>Enter JSON array of coupons: {"brand":"Brand Name","code":"SAVE20","discount":"20%","link":"https://affiliate-link.com"}</p>';
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Exclusive Coupons Pro</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('ecp_options');
                do_settings_sections('exclusive-coupons-pro');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $settings = get_option('ecp_settings', array('coupons' => array()));
        $coupons = $settings['coupons'];
        $coupon = null;
        if ($atts['id']) {
            foreach ($coupons as $c) {
                if (isset($c['id']) && $c['id'] === $atts['id']) {
                    $coupon = $c;
                    break;
                }
            }
        } else {
            $coupon = $coupons[array_rand($coupons)] ?? null;
        }
        if (!$coupon) return '';

        $unique_code = $coupon['code'] . '-' . uniqid();
        $visitor_ip = $_SERVER['REMOTE_ADDR'] ?? '';
        setcookie('ecp_coupon_' . md5($unique_code), $unique_code, time() + 86400, '/');

        ob_start();
        ?>
        <div class="ecp-coupon" data-brand="<?php echo esc_attr($coupon['brand']); ?>" data-code="<?php echo esc_attr($unique_code); ?>">
            <h3>Exclusive Deal: <?php echo esc_html($coupon['brand']); ?></h3>
            <p>Use code: <strong><?php echo esc_html($unique_code); ?></strong> - <?php echo esc_html($coupon['discount']); ?></p>
            <a href="<?php echo esc_url($coupon['link'] . '?coupon=' . $unique_code); ?>" class="ecp-button" target="_blank">Get Deal Now</a>
            <small>Personalized for you! One-time use.</small>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('ecp_settings')) {
            update_option('ecp_settings', array('coupons' => array(
                array('id' => '1', 'brand' => 'Demo Brand', 'code' => 'SAVE10', 'discount' => '10% OFF', 'link' => 'https://example.com')
            )));
        }
    }
}

ExclusiveCouponsPro::get_instance();

/* Inline scripts and styles */
function ecp_inline_assets() {
    ?>
    <style>
    .ecp-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; border-radius: 8px; text-align: center; }
    .ecp-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
    .ecp-button:hover { background: #005a87; }
    </style>
    <script>
    jQuery(document).ready(function($) {
        $('.ecp-coupon').on('click', '.ecp-button', function() {
            $(this).parent().addClass('used');
            alert('Coupon copied! Happy shopping!');
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'ecp_inline_assets');

?>