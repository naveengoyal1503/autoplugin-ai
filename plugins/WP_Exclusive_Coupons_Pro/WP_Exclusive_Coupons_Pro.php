/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: WP Exclusive Coupons Pro
 * Plugin URI: https://example.com/wp-exclusive-coupons
 * Description: Automatically generates and manages exclusive affiliate coupons to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: wp-exclusive-coupons
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Exclusive_Coupons {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('wp_exclusive_coupons', array($this, 'coupons_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('wp-exclusive-coupons', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function activate() {
        add_option('wp_coupon_pro_version', '1.0.0');
        add_option('wp_coupon_coupons', array());
    }

    public function admin_menu() {
        add_options_page(
            'Exclusive Coupons',
            'Coupons Pro',
            'manage_options',
            'wp-exclusive-coupons',
            array($this, 'admin_page')
        );
    }

    public function admin_init() {
        register_setting('wp_coupon_options', 'wp_coupon_coupons');
        if (isset($_POST['submit'])) {
            update_option('wp_coupon_coupons', sanitize_text_field($_POST['coupons']));
        }
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('WP Exclusive Coupons Pro', 'wp-exclusive-coupons'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('wp_coupon_options'); ?>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Coupons JSON', 'wp-exclusive-coupons'); ?></th>
                        <td>
                            <textarea name="coupons" rows="10" cols="50" class="large-text"><?php echo esc_textarea(get_option('wp_coupon_coupons', '[]')); ?></textarea>
                            <p class="description"><?php _e('Enter coupons as JSON array: [{"brand":"Amazon","code":"SAVE20","link":"https://aff.link","desc":"20% off"}]', 'wp-exclusive-coupons'); ?></p>
                            <?php if (get_option('wp_coupon_pro_key') !== 'pro') { ?>
                            <p><strong>Upgrade to Pro for unlimited coupons & analytics! <a href="#">Get Pro</a></strong></p>
                            <?php } ?>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wp-coupons-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts);
        $coupons_json = get_option('wp_coupon_coupons', '[]');
        $coupons = json_decode($coupons_json, true);
        if (!is_array($coupons) || empty($coupons)) {
            return '<p>No coupons available. <a href="' . admin_url('options-general.php?page=wp-exclusive-coupons') . '">Add some in settings</a>.</p>';
        }
        $limit = min((int)$atts['limit'], count($coupons));
        $output = '<div class="wp-coupons-container">';
        for ($i = 0; $i < $limit; $i++) {
            if (isset($coupons[$i])) {
                $coupon = $coupons[$i];
                $output .= '<div class="coupon-card">';
                $output .= '<h3>' . esc_html($coupon['brand'] ?? 'Brand') . '</h3>';
                $output .= '<p><strong>Code:</strong> ' . esc_html($coupon['code'] ?? '') . '</p>';
                $output .= '<p>' . esc_html($coupon['desc'] ?? '') . '</p>';
                $output .= '<a href="' . esc_url($coupon['link'] ?? '') . '" class="coupon-btn" target="_blank">Get Deal</a>';
                $output .= '</div>';
            }
        }
        $output .= '</div>';
        if (get_option('wp_coupon_pro_key') !== 'pro') {
            $output .= '<p class="pro-upsell"><strong>Pro: Unlimited coupons + tracking!</strong></p>';
        }
        return $output;
    }
}

WP_Exclusive_Coupons::get_instance();

// Pro check
function is_wp_coupon_pro() {
    return get_option('wp_coupon_pro_key') === 'pro';
}

/*
 * Frontend CSS (inline for single file)
 */
function wp_coupon_add_inline_style() {
    if (!is_wp_coupon_pro()) {
        ?>
        <style>
        .wp-coupons-container { display: flex; flex-wrap: wrap; gap: 20px; }
        .coupon-card { border: 1px solid #ddd; padding: 20px; border-radius: 8px; flex: 1 1 300px; background: #f9f9f9; }
        .coupon-card h3 { color: #333; margin-top: 0; }
        .coupon-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; }
        .coupon-btn:hover { background: #005a87; }
        .pro-upsell { text-align: center; background: #fff3cd; padding: 10px; border-radius: 4px; margin-top: 20px; }
        </style>
        <?php
    }
}
add_action('wp_head', 'wp_coupon_add_inline_style');

?>