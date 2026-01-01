/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupon_Manager.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupon Manager
 * Plugin URI: https://example.com/smart-affiliate-coupon-manager
 * Description: Generate trackable affiliate coupons, manage promo codes, and boost affiliate conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartAffiliateCouponManager {
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
        add_shortcode('sacm_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('sacm-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('sacm-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_scripts($hook) {
        if (strpos($hook, 'sacm') !== false) {
            wp_enqueue_script('sacm-admin', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
        }
    }

    public function admin_menu() {
        add_menu_page(
            'Smart Affiliate Coupons',
            'Affiliate Coupons',
            'manage_options',
            'sacm-coupons',
            array($this, 'admin_page'),
            'dashicons-cart',
            30
        );
    }

    public function admin_page() {
        if (isset($_POST['sacm_save'])) {
            update_option('sacm_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('sacm_coupons', '[]');
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Coupon Manager</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupons (JSON format)</th>
                        <td>
                            <textarea name="coupons" rows="10" cols="50" class="large-text"><?php echo esc_textarea($coupons); ?></textarea>
                            <p class="description">Enter coupons as JSON array: [{ "code": "SAVE10", "afflink": "https://aff.link", "desc": "10% off", "uses": 0 }]</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save Coupons', 'primary', 'sacm_save'); ?>
            </form>
            <h2>Shortcode</h2>
            <p>Use <code>[sacm_coupon id="0"]</code> to display coupon. IDs start from 0.</p>
            <p><strong>Pro Features:</strong> Analytics, unlimited coupons, custom designs. <a href="#pro">Upgrade to Pro</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = json_decode(get_option('sacm_coupons', '[]'), true);
        if (!isset($coupons[$atts['id']])) return 'Coupon not found.';

        $coupon = $coupons[$atts['id']];
        $coupon['uses']++;
        $coupons[$atts['id']] = $coupon;
        update_option('sacm_coupons', wp_json_encode($coupons));

        ob_start();
        ?>
        <div class="sacm-coupon" data-id="<?php echo esc_attr($atts['id']); ?>">
            <h3><?php echo esc_html($coupon['desc']); ?></h3>
            <p><strong>Code:</strong> <code><?php echo esc_html($coupon['code']); ?></code></p>
            <p>Used: <?php echo esc_html($coupon['uses']); ?> times</p>
            <a href="<?php echo esc_url($coupon['afflink']); ?}" class="button sacm-btn" target="_blank">Get Deal & Track</a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('sacm_coupons')) {
            update_option('sacm_coupons', wp_json_encode(array(
                array('code' => 'WELCOME20', 'afflink' => '#', 'desc' => '20% Off First Purchase', 'uses' => 0)
            )));
        }
    }
}

SmartAffiliateCouponManager::get_instance();

/* Pro Upsell Notice */
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    $screen = get_current_screen();
    if ($screen->id == 'toplevel_page_sacm-coupons') {
        echo '<div class="notice notice-info"><p><strong>Go Pro!</strong> Unlock unlimited coupons, analytics, and more for $49/year. <a href="https://example.com/pro">Learn More</a></p></div>';
    }
});

/* Inline CSS/JS for self-contained */
function sacm_inline_assets() {
    ?>
    <style>
    .sacm-coupon { border: 2px solid #0073aa; padding: 20px; border-radius: 8px; background: #f9f9f9; text-align: center; max-width: 400px; margin: 20px auto; }
    .sacm-coupon h3 { color: #0073aa; margin: 0 0 10px; }
    .sacm-coupon code { background: #fff; padding: 5px 10px; border-radius: 4px; color: #d63638; }
    .sacm-btn { background: #0073aa; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block; }
    .sacm-btn:hover { background: #005a87; }
    </style>
    <script>
    jQuery(document).ready(function($) {
        $('.sacm-btn').on('click', function() {
            $(this).closest('.sacm-coupon').addClass('sacm-used');
            gtag('event', 'coupon_click', { 'coupon_id': $(this).closest('.sacm-coupon').data('id') });
        });
    });
    </script>
    <?php
}
add_action('wp_head', 'sacm_inline_assets');
add_action('admin_head', 'sacm_inline_assets');