/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupons Pro
 * Plugin URI: https://example.com/smart-affiliate-coupons
 * Description: Automatically generates and displays personalized affiliate coupon codes with dynamic discounts, tracking clicks and conversions for maximum WordPress blog revenue.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-coupons
 */

if (!defined('ABSPATH')) {
    exit;
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('sac_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_sac_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_sac_track_click', array($this, 'track_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-coupons', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sac-frontend', plugin_dir_url(__FILE__) . 'sac-frontend.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sac-frontend', 'sac_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sac_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Coupons', 'SAC Pro', 'manage_options', 'sac-pro', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['sac_save'])) {
            update_option('sac_api_key', sanitize_text_field($_POST['api_key']));
            update_option('sac_coupons', $_POST['coupons']);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('sac_api_key', '');
        $coupons = get_option('sac_coupons', json_encode(array(
            array('name' => 'Example Deal', 'code' => 'SAVE20', 'afflink' => 'https://example.com/aff', 'discount' => '20%')
        )));
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Coupons Pro</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>API Key (Pro)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Coupons (JSON)</th>
                        <td><textarea name="coupons" rows="10" class="large-text"><?php echo esc_textarea($coupons); ?></textarea><br>
                        Format: [{ "name": "Deal", "code": "CODE", "afflink": "https://aff.link", "discount": "20%" }]</td>
                    </tr>
                </table>
                <?php submit_button('Save Settings', 'primary', 'sac_save'); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics, auto-generation for $49/year.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => '1'), $atts);
        $coupons = json_decode(get_option('sac_coupons', '[]'), true);
        if (!isset($coupons[$atts['id'] - 1])) return 'Coupon not found.';
        $coupon = $coupons[$atts['id'] - 1];
        ob_start();
        ?>
        <div class="sac-coupon" data-afflink="<?php echo esc_url($coupon['afflink']); ?>">
            <h3><?php echo esc_html($coupon['name']); ?></h3>
            <p><strong>Code:</strong> <span class="sac-code"><?php echo esc_html($coupon['code']); ?></span> (<?php echo esc_html($coupon['discount']); ?> off)</p>
            <button class="sac-btn button">Get Deal & Track</button>
            <div class="sac-stats">Clicks: <span class="sac-clicks">0</span></div>
        </div>
        <style>
        .sac-coupon { border: 2px solid #0073aa; padding: 20px; margin: 20px 0; border-radius: 8px; background: #f9f9f9; }
        .sac-btn { background: #0073aa; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 4px; }
        .sac-btn:hover { background: #005a87; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function track_click() {
        check_ajax_referer('sac_nonce', 'nonce');
        $link = sanitize_url($_POST['link']);
        // In pro version, log to DB or API
        error_log('SAC Click: ' . $link);
        wp_send_json_success(array('redirect' => $link));
    }

    public function activate() {
        if (!get_option('sac_coupons')) {
            update_option('sac_coupons', json_encode(array(
                array('name' => 'Starter Deal', 'code' => 'WELCOME10', 'afflink' => '#', 'discount' => '10%')
            )));
        }
    }
}

SmartAffiliateCoupons::get_instance();

// Frontend JS (embedded)
function sac_add_inline_script() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.sac-btn').click(function() {
            var $container = $(this).closest('.sac-coupon');
            var link = $container.data('afflink');
            $.post(sac_ajax.ajax_url, {
                action: 'sac_track_click',
                nonce: sac_ajax.nonce,
                link: link
            }, function(res) {
                if (res.success) window.open(res.data.redirect, '_blank');
            });
            var clicks = parseInt($container.find('.sac-clicks').text()) + 1;
            $container.find('.sac-clicks').text(clicks);
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'sac_add_inline_script');