/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupons Pro
 * Plugin URI: https://example.com/smart-affiliate-coupons
 * Description: Automatically generates and displays personalized affiliate coupon codes with click tracking to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-coupons
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateCoupons {
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
        add_action('wp_ajax_track_coupon_click', array($this, 'track_coupon_click'));
        add_action('wp_ajax_nopriv_track_coupon_click', array($this, 'track_coupon_click'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_init', array($this, 'admin_init'));
        }
        load_plugin_textdomain('smart-affiliate-coupons', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sac-ajax', plugin_dir_url(__FILE__) . 'sac-ajax.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sac-ajax', 'sac_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sac_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Coupons', 'Affiliate Coupons', 'manage_options', 'smart-affiliate-coupons', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('sac_options', 'sac_coupons');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Affiliate Coupons Settings', 'smart-affiliate-coupons'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('sac_options'); ?>
                <?php do_settings_sections('sac_options'); ?>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Coupons', 'smart-affiliate-coupons'); ?></th>
                        <td>
                            <?php $coupons = get_option('sac_coupons', array()); ?>
                            <textarea name="sac_coupons" rows="10" cols="50"><?php echo esc_textarea(json_encode($coupons, JSON_PRETTY_PRINT)); ?></textarea>
                            <p class="description"><?php _e('JSON array of coupons: {"code":"CODE","url":"AFFILIATE_URL","desc":"Description","pro":false}', 'smart-affiliate-coupons'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics dashboard, A/B testing for $49/year. <a href="https://example.com/pro" target="_blank">Buy Now</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = get_option('sac_coupons', array());
        if (empty($coupons) || !isset($coupons[$atts['id']])) {
            return '<p>No coupon found.</p>';
        }
        $coupon = $coupons[$atts['id']];
        if ($coupon['pro'] && !defined('SAC_PRO')) {
            return '<p><strong>Pro Feature:</strong> Upgrade to unlock this coupon.</p>';
        }
        ob_start();
        ?>
        <div class="sac-coupon" data-id="<?php echo esc_attr($atts['id']); ?>">
            <h3><?php echo esc_html($coupon['desc']); ?></h3>
            <div class="sac-code"><?php echo esc_html($coupon['code']); ?></div>
            <button class="sac-button" onclick="sacTrackClick('<?php echo esc_attr($atts['id']); ?>')">Get Deal & Track</button>
        </div>
        <style>
        .sac-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; text-align: center; }
        .sac-code { font-size: 2em; font-weight: bold; color: #0073aa; margin: 10px 0; }
        .sac-button { background: #0073aa; color: white; padding: 10px 20px; border: none; cursor: pointer; font-size: 1.1em; }
        .sac-button:hover { background: #005a87; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function track_coupon_click() {
        check_ajax_referer('sac_nonce', 'nonce');
        $id = sanitize_text_field($_POST['id']);
        $coupons = get_option('sac_coupons', array());
        if (isset($coupons[$id])) {
            // Log click (simple count in options for free version)
            $stats = get_option('sac_stats', array());
            $stats[$id] = isset($stats[$id]) ? $stats[$id] + 1 : 1;
            update_option('sac_stats', $stats);
            wp_redirect($coupons[$id]['url']);
            exit;
        }
        wp_die('Invalid coupon');
    }

    public function activate() {
        if (!get_option('sac_coupons')) {
            update_option('sac_coupons', array(
                'demo' => array('code' => 'SAVE20', 'url' => 'https://example.com/affiliate', 'desc' => '20% Off Demo Deal', 'pro' => false)
            ));
        }
    }

    public function deactivate() {}
}

SmartAffiliateCoupons::get_instance();

// Pro check (simulate)
if (file_exists(__DIR__ . '/sac-pro.php')) {
    define('SAC_PRO', true);
    include __DIR__ . '/sac-pro.php';
}

// AJAX JS inline
add_action('wp_footer', function() {
    if (!is_admin()) {
        ?>
        <script>
        function sacTrackClick(id) {
            jQuery.post(sac_ajax.ajax_url, {
                action: 'track_coupon_click',
                id: id,
                nonce: sac_ajax.nonce
            });
        }
        </script>
        <?php
    }
});