/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Automatically fetches, curates, and displays exclusive affiliate coupons with personalized promo codes to boost conversions and commissions.
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
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('exclusive_coupons', array($this, 'coupons_shortcode'));
        add_action('wp_ajax_ecp_dismiss_premium', array($this, 'dismiss_premium_notice'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('exclusive-coupons-pro', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        if (is_admin()) {
            add_action('admin_notices', array($this, 'premium_notice'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('ecp-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('ecp-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ecp-script', 'ecp_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
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

    public function settings_page() {
        if (isset($_POST['ecp_save'])) {
            update_option('ecp_api_key', sanitize_text_field($_POST['ecp_api_key']));
            update_option('ecp_categories', sanitize_text_field($_POST['ecp_categories']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('ecp_api_key', '');
        $categories = get_option('ecp_categories', 'fashion,tech,travel');
        ?>
        <div class="wrap">
            <h1>Exclusive Coupons Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>API Key (Premium)</th>
                        <td><input type="text" name="ecp_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Categories</th>
                        <td><input type="text" name="ecp_categories" value="<?php echo esc_attr($categories); ?>" class="regular-text" placeholder="fashion,tech,travel" /></td>
                    </tr>
                </table>
                <?php submit_button('Save Settings', 'primary', 'ecp_save'); ?>
            </form>
            <div class="ecp-premium-upsell">
                <h2>Go Pro for Live Coupons!</h2>
                <p>Unlock real-time coupon APIs, analytics, and unlimited displays. <a href="https://example.com/pricing" target="_blank">Upgrade Now ($49/year)</a></p>
            </div>
        </div>
        <?php
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array(
            'count' => 5,
            'category' => ''
        ), $atts);

        $coupons = $this->get_sample_coupons($atts['count'], $atts['category']);
        ob_start();
        ?>
        <div class="ecp-coupons-grid">
            <?php foreach ($coupons as $coupon): ?>
            <div class="ecp-coupon-card">
                <h3><?php echo esc_html($coupon['title']); ?></h3>
                <p class="ecp-discount"><?php echo esc_html($coupon['discount']); ?> OFF</p>
                <p>Code: <strong><?php echo esc_html($coupon['code']); ?></strong></p>
                <p>Expires: <?php echo esc_html($coupon['expires']); ?></p>
                <a href="<?php echo esc_url($coupon['link']); ?}" class="ecp-btn" target="_blank">Shop Now & Save</a>
                <small>Affiliate link</small>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_sample_coupons($count, $category) {
        $sample_coupons = array(
            array(
                'title' => '50% Off Premium Themes',
                'discount' => '50%',
                'code' => 'EXCLUSIVE50',
                'expires' => '2026-02-01',
                'link' => 'https://example.com/theme?aff=123',
                'category' => 'tech'
            ),
            array(
                'title' => '20% Off Fashion Essentials',
                'discount' => '20%',
                'code' => 'BLOG20',
                'expires' => '2026-01-15',
                'link' => 'https://example.com/fashion?aff=123',
                'category' => 'fashion'
            ),
            array(
                'title' => 'Free Shipping on Travel Gear',
                'discount' => 'Free Ship',
                'code' => 'TRAVELFREE',
                'expires' => '2026-01-31',
                'link' => 'https://example.com/travel?aff=123',
                'category' => 'travel'
            ),
            array(
                'title' => '30% Off WordPress Plugins',
                'discount' => '30%',
                'code' => 'WP30',
                'expires' => '2026-01-20',
                'link' => 'https://example.com/plugins?aff=123',
                'category' => 'tech'
            ),
            array(
                'title' => '15% Off Wellness Products',
                'discount' => '15%',
                'code' => 'WELLNESS15',
                'expires' => '2026-02-10',
                'link' => 'https://example.com/wellness?aff=123',
                'category' => 'health'
            )
        );

        $filtered = array_filter($sample_coupons, function($c) use ($category) {
            return empty($category) || $c['category'] === $category;
        });

        return array_slice(array_values($filtered), 0, $count);
    }

    public function premium_notice() {
        if (!get_option('ecp_dismissed_premium')) {
            echo '<div class="notice notice-info is-dismissible" id="ecp-premium-notice">
                <p>Supercharge <strong>Exclusive Coupons Pro</strong> with Premium: Live APIs, Analytics & More! <a href="https://example.com/pricing" target="_blank">Get 20% Off Now</a></p>
            </div>';
        }
    }

    public function dismiss_premium_notice() {
        update_option('ecp_dismissed_premium', 1);
        wp_die();
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

ExclusiveCouponsPro::get_instance();

// Prevent direct access to plugin files
function ecp_protect_files() {
    if (!defined('ABSPATH')) exit;
}
ecp_protect_files();

// CSS
/* Add to style.css or inline */
/*
.ecp-coupons-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
.ecp-coupon-card { border: 1px solid #ddd; padding: 20px; border-radius: 8px; background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
.ecp-discount { font-size: 2em; color: #e74c3c; font-weight: bold; }
.ecp-btn { background: #3498db; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
.ecp-btn:hover { background: #2980b9; }
*/

// JS
/* Add to script.js */
/*
jQuery(document).ready(function($) {
    $('#ecp-premium-notice .notice-dismiss').on('click', function() {
        $.post(ecp_ajax.ajax_url, { action: 'ecp_dismiss_premium' });
    });
});
*/
?>