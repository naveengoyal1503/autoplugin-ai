/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupon_Tracker.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupon Tracker
 * Plugin URI: https://example.com/smart-affiliate-coupon-tracker
 * Description: Automatically generates and tracks unique affiliate coupon codes for WordPress blogs, boosting conversions with personalized discounts and real-time analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-coupon-tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateCouponTracker {
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
        add_shortcode('sac_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            return;
        }
        $this->load_textdomain();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sac-frontend', plugin_dir_url(__FILE__) . 'sac-frontend.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sac-frontend', plugin_dir_url(__FILE__) . 'sac-frontend.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate Coupon Tracker',
            'Coupon Tracker',
            'manage_options',
            'sac-tracker',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        if (isset($_POST['sac_save'])) {
            update_option('sac_affiliate_id', sanitize_text_field($_POST['affiliate_id']));
            update_option('sac_coupon_prefix', sanitize_text_field($_POST['coupon_prefix']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $affiliate_id = get_option('sac_affiliate_id', '');
        $coupon_prefix = get_option('sac_coupon_prefix', 'SAVE10-');
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Coupon Tracker</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Affiliate ID</th>
                        <td><input type="text" name="affiliate_id" value="<?php echo esc_attr($affiliate_id); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Coupon Prefix</th>
                        <td><input type="text" name="coupon_prefix" value="<?php echo esc_attr($coupon_prefix); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="sac_save" class="button-primary" value="Save Settings" />
                </p>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[sac_coupon affiliate="your-affiliate-link" discount="10"]</code></p>
            <h2>Pro Features (Upgrade for $49/year)</h2>
            <ul>
                <li>Unlimited coupons</li>
                <li>Advanced analytics dashboard</li>
                <li>API integrations</li>
            </ul>
            <a href="https://example.com/pro" class="button button-large button-primary" target="_blank">Upgrade to Pro</a>
        </div>
        <style>
        .sac-pro { background: #0073aa; color: white; padding: 10px; }
        </style>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate' => '',
            'discount' => '10',
            'text' => 'Get ' . $atts['discount'] . '% OFF!'
        ), $atts);

        $prefix = get_option('sac_coupon_prefix', 'SAVE10-');
        $user_id = get_current_user_id() ?: uniqid();
        $coupon_code = $prefix . substr(md5($user_id . time()), 0, 8);

        $aff_url = $atts['affiliate'];
        if (strpos($aff_url, '?') === false) {
            $aff_url .= '?coupon=' . $coupon_code;
        } else {
            $aff_url .= '&coupon=' . $coupon_code;
        }

        // Track usage
        $tracks = get_option('sac_tracks', array());
        $tracks[] = array(
            'code' => $coupon_code,
            'user' => $user_id,
            'time' => current_time('mysql'),
            'ip' => $_SERVER['REMOTE_ADDR']
        );
        if (count($tracks) > 100) { // Free limit
            array_shift($tracks);
        }
        update_option('sac_tracks', $tracks);

        ob_start();
        ?>
        <div class="sac-coupon" data-code="<?php echo esc_attr($coupon_code); ?>">
            <span class="sac-code"><?php echo esc_html($coupon_code); ?></span>
            <a href="<?php echo esc_url($aff_url); ?>" class="sac-button" target="_blank"><?php echo esc_html($atts['text']); ?></a>
            <small>Unique code generated for you!</small>
        </div>
        <script>
        jQuery('.sac-coupon').on('click', '.sac-button', function() {
            // Pro analytics hook
            console.log('Coupon clicked: ' + jQuery(this).closest('.sac-coupon').data('code'));
        });
        </script>
        <style>
        .sac-coupon { border: 2px dashed #0073aa; padding: 20px; text-align: center; margin: 20px 0; background: #f9f9f9; }
        .sac-code { font-size: 24px; font-weight: bold; color: #0073aa; display: block; margin-bottom: 10px; }
        .sac-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        .sac-button:hover { background: #005a87; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('sac_affiliate_id', '');
        add_option('sac_coupon_prefix', 'SAVE10-');
        add_option('sac_tracks', array());
    }

    private function load_textdomain() {
        load_plugin_textdomain('smart-affiliate-coupon-tracker', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
}

SmartAffiliateCouponTracker::get_instance();

// Pro upsell notice
function sac_admin_notice() {
    if (!current_user_can('manage_options')) return;
    $screen = get_current_screen();
    if ($screen->id === 'settings_page_sac-tracker') return;
    ?>
    <div class="notice notice-info">
        <p><strong>Smart Affiliate Coupon Tracker Pro</strong> unlocked: Unlimited coupons & analytics for $49/year. <a href="<?php echo admin_url('options-general.php?page=sac-tracker'); ?>">Upgrade now &raquo;</a></p>
    </div>
    <?php
}
add_action('admin_notices', 'sac_admin_notice');

// Frontend JS (embedded)
function sac_frontend_js() {
    ?><script>jQuery(document).ready(function($){ /* Pro analytics */ });</script><?php
}
add_action('wp_footer', 'sac_frontend_js');