/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupons with tracking to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateCouponVault {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('acv_pro_version')) {
            // Pro features here
        }
        wp_register_style('acv-style', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('acv-style');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate_url' => '',
            'description' => 'Get this exclusive deal!',
            'discount' => '20%',
            'code' => ''
        ), $atts);

        if (empty($atts['affiliate_url'])) {
            return '<p>Missing affiliate URL.</p>';
        }

        $unique_id = uniqid('acv_');
        $coupon_code = $atts['code'] ?: 'SAVE' . substr(md5($unique_id), 0, 6);
        $tracking_url = add_query_arg(array('ref' => get_current_user_id() ?: 'visitor', 'coupon' => $coupon_code), $atts['affiliate_url']);

        ob_start();
        ?>
        <div id="<?php echo esc_attr($unique_id); ?>" class="acv-coupon-vault" style="border: 2px dashed #0073aa; padding: 20px; background: #f9f9f9; text-align: center; max-width: 400px;">
            <h3 style="color: #0073aa;">Exclusive Coupon</h3>
            <p><?php echo esc_html($atts['description']); ?></p>
            <div style="font-size: 24px; font-weight: bold; color: #28a745; margin: 10px 0;"><?php echo esc_html($atts['discount']); ?> OFF</div>
            <div style="background: #fff; padding: 10px; border: 1px solid #ddd; font-family: monospace; font-size: 18px; margin: 10px 0;">
                <?php echo esc_html($coupon_code); ?>
            </div>
            <a href="<?php echo esc_url($tracking_url); ?>" target="_blank" class="button button-primary" style="padding: 12px 24px; font-size: 16px;">Redeem Now</a>
            <p style="font-size: 12px; margin-top: 15px;">Generated for you on <?php echo date('Y-m-d H:i'); ?></p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#<?php echo esc_attr($unique_id); ?> .button').click(function() {
                var data = {
                    action: 'acv_track_click',
                    coupon_id: '<?php echo esc_attr($unique_id); ?>'
                };
                $.post(ajaxurl, data);
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        if (!wp_verify_nonce($_POST['nonce'], 'acv_nonce')) {
            wp_die('Security check failed');
        }
        $coupon = array(
            'code' => 'ACV' . wp_rand(1000, 9999),
            'generated' => current_time('mysql')
        );
        wp_send_json_success($coupon);
    }

    public function activate() {
        add_option('acv_version', '1.0.0');
        flush_rewrite_rules();
    }
}

AffiliateCouponVault::get_instance();

// Admin settings page
function acv_admin_menu() {
    add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', 'acv_settings_page');
}
add_action('admin_menu', 'acv_admin_menu');

function acv_settings_page() {
    ?>
    <div class="wrap">
        <h1>Affiliate Coupon Vault Settings</h1>
        <p>Upgrade to Pro for unlimited coupons and analytics! <a href="https://example.com/pro">Get Pro</a></p>
        <form method="post" action="options.php">
            <?php settings_fields('acv_settings'); ?>
            <?php do_settings_sections('acv_settings'); ?>
            <table class="form-table">
                <tr>
                    <th>Pro Status</th>
                    <td><input type="checkbox" name="acv_pro_version" value="1" <?php checked(get_option('acv_pro_version')); ?> disabled> Pro License (Upgrade Required)</td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <h2>Usage</h2>
        <p>Use shortcode: [affiliate_coupon affiliate_url="https://example.com/offer" discount="25%" description="Save big today!"]</p>
    </div>
    <?php
}

register_setting('acv_settings', 'acv_pro_version');

// CSS
add_action('wp_head', function() {
    echo '<style>
    .acv-coupon-vault { animation: pulse 2s infinite; }
    @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.05); } 100% { transform: scale(1); } }
    </style>';
});