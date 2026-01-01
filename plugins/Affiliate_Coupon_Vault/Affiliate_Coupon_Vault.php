/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupon codes with click tracking for higher conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AffiliateCouponVault {
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
        add_action('wp_ajax_acv_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_acv_track_click', array($this, 'track_click'));
        add_shortcode('acv_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('acv_pro_version')) {
            return; // Pro version active
        }
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('acv_settings', 'acv_coupons');
        register_setting('acv_settings', 'acv_api_key');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('acv_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>API Key (Pro)</th>
                        <td><input type="text" name="acv_api_key" value="<?php echo esc_attr(get_option('acv_api_key')); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Coupons (JSON)</th>
                        <td><textarea name="acv_coupons" rows="10" cols="50"><?php echo esc_textarea(get_option('acv_coupons')); ?></textarea><br>
                        Format: {"code":"SAVE20","afflink":"https://aff.link","desc":"20% off"}</td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlimited coupons, analytics & automation. <a href="https://example.com/pro">Get Pro ($49/yr)</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 'default'), $atts);
        $coupons = json_decode(get_option('acv_coupons', '[]'), true);
        if (empty($coupons)) {
            return '<p>No coupons configured. <a href="' . admin_url('options-general.php?page=acv-settings') . '">Set up now</a>.</p>';
        }
        $coupon = $coupons; // Free: first coupon
        if (isset($coupons[$atts['id']])) {
            $coupon = $coupons[$atts['id']];
        }
        if (!$coupon) return '';
        ob_start();
        ?>
        <div class="acv-coupon" data-link="<?php echo esc_url($coupon['afflink']); ?>">
            <h3><?php echo esc_html($coupon['desc']); ?></h3>
            <p><strong>Code: <?php echo esc_html($coupon['code']); ?></strong></p>
            <button class="acv-btn">Get Deal & Track</button>
            <small>Clicks tracked for analytics (Pro).</small>
        </div>
        <style>
        .acv-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; }
        .acv-btn { background: #0073aa; color: white; padding: 10px 20px; border: none; cursor: pointer; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function track_click() {
        if (!wp_verify_nonce($_POST['nonce'], 'acv_nonce')) {
            wp_die('Security check failed');
        }
        $link = sanitize_url($_POST['link']);
        // Pro: Log to DB
        if (get_option('acv_api_key')) {
            error_log('ACV Pro Click: ' . $link);
        }
        wp_redirect($link);
        exit;
    }

    public function activate() {
        add_option('acv_coupons', json_encode(array(
            array('code' => 'SAVE10', 'afflink' => '#', 'desc' => 'Sample 10% Off')
        )));
    }
}

AffiliateCouponVault::get_instance();

// JS file content (base64 encoded or inline, but for single file, add inline)
function acv_add_inline_script() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.acv-btn').click(function() {
            var $container = $(this).closest('.acv-coupon');
            var link = $container.data('link');
            $.post(acv_ajax.ajax_url, {
                action: 'acv_track_click',
                link: link,
                nonce: '<?php echo wp_create_nonce('acv_nonce'); ?>'
            }, function() {
                window.open(link, '_blank');
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'acv_add_inline_script');

// Pro upsell notice
add_action('admin_notices', function() {
    if (!get_option('acv_pro_version') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Affiliate Coupon Vault: Unlock <strong>Pro</strong> for unlimited coupons & analytics! <a href="https://example.com/pro">Upgrade Now</a></p></div>';
    }
});