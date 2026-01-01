/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate exclusive affiliate coupons with tracking and monetization features.
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
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('exclusive-coupons-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('exclusive-coupons-pro', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('exclusive-coupons-pro', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
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

    public function admin_enqueue_scripts($hook) {
        if ('settings_page' !== get_current_screen()->id) return;
        wp_enqueue_style('exclusive-coupons-pro-admin', plugin_dir_url(__FILE__) . 'admin-style.css');
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('ecp_api_key', sanitize_text_field($_POST['api_key']));
            update_option('ecp_coupons', maybe_serialize($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('ecp_api_key', '');
        $coupons = maybe_unserialize(get_option('ecp_coupons', array()));
        ?>
        <div class="wrap">
            <h1>Exclusive Coupons Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>API Key (Pro)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Coupons</th>
                        <td>
                            <?php foreach ($coupons as $index => $coupon): ?>
                            <p>
                                <label>Code <?php echo $index+1; ?>: <input type="text" name="coupons[<?php echo $index; ?>][code]" value="<?php echo esc_attr($coupon['code']); ?>" /></label>
                                <label>Link: <input type="url" name="coupons[<?php echo $index; ?>][link]" value="<?php echo esc_attr($coupon['link']); ?>" /></label>
                                <label>Expires: <input type="date" name="coupons[<?php echo $index; ?>][expires]" value="<?php echo esc_attr($coupon['expires']); ?>" /></label>
                                <label>Uses: <input type="number" name="coupons[<?php echo $index; ?>][uses]" value="<?php echo esc_attr($coupon['uses']); ?>" /></label>
                            </p>
                            <?php endforeach; ?>
                            <p><input type="button" id="add-coupon" value="Add Coupon" class="button" /></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#add-coupon').click(function() {
                var index = $('.coupon-row').length;
                $('.form-table td:last').append(
                    '<p class="coupon-row">' +
                    '<label>Code: <input type="text" name="coupons[' + index + '][code]" /></label>' +
                    '<label>Link: <input type="url" name="coupons[' + index + '][link]" /></label>' +
                    '<label>Expires: <input type="date" name="coupons[' + index + '][expires]" /></label>' +
                    '<label>Uses: <input type="number" name="coupons[' + index + '][uses]" /></label>' +
                    '</p>'
                );
            });
        });
        </script>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = maybe_unserialize(get_option('ecp_coupons', array()));
        if (!isset($coupons[$atts['id']])) return 'Coupon not found.';

        $coupon = $coupons[$atts['id']];
        $uses_key = 'ecp_uses_' . $atts['id'];
        $uses = get_option($uses_key, 0);
        $now = current_time('Y-m-d');

        if ($uses >= $coupon['uses'] || $now > $coupon['expires']) {
            return '<div class="expired-coupon">Coupon expired!</div>';
        }

        $nonce = wp_create_nonce('use_coupon_' . $atts['id']);
        ob_start();
        ?>
        <div class="exclusive-coupon" data-id="<?php echo $atts['id']; ?>" data-nonce="<?php echo $nonce; ?>">
            <h3>Exclusive Coupon: <strong><?php echo esc_html($coupon['code']); ?></strong></h3>
            <p><a href="<?php echo esc_url($coupon['link']); ?>" target="_blank" class="coupon-link">Get Deal Now</a></p>
            <p class="coupon-uses">Uses left: <span class="uses-count"><?php echo intval($coupon['uses'] - $uses); ?></span></p>
        </div>
        <script>
        jQuery('.exclusive-coupon .coupon-link').click(function(e) {
            e.preventDefault();
            var $this = jQuery(this).closest('.exclusive-coupon');
            jQuery.post(ajaxurl, {
                action: 'track_coupon_use',
                id: $this.data('id'),
                nonce: $this.data('nonce')
            }, function() {
                location.href = '<?php echo esc_js($coupon['link']); ?>';
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function ajax_track_coupon_use() {
        check_ajax_referer('use_coupon_' . $_POST['id'], 'nonce');
        $id = intval($_POST['id']);
        $uses_key = 'ecp_uses_' . $id;
        $uses = get_option($uses_key, 0) + 1;
        update_option($uses_key, $uses);
        wp_die();
    }

    public function activate() {
        if (!get_option('ecp_coupons')) {
            update_option('ecp_coupons', array(array(
                'code' => 'WELCOME10',
                'link' => 'https://example.com',
                'expires' => date('Y-m-d', strtotime('+30 days')),
                'uses' => 100
            )));
        }
    }

    public function deactivate() {
        // Cleanup if needed
    }
}

ExclusiveCouponsPro::get_instance();

add_action('wp_ajax_track_coupon_use', array(ExclusiveCouponsPro::get_instance(), 'ajax_track_coupon_use'));

// Pro upsell notice
function ecp_pro_notice() {
    if (!get_option('ecp_dismiss_pro')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Exclusive Coupons Pro</strong> for unlimited coupons, analytics dashboard, and API integrations! <a href="https://example.com/pro" target="_blank">Get Pro</a> | <a href="?ecp_dismiss=1">Dismiss</a></p></div>';
    }
}
add_action('admin_notices', 'ecp_pro_notice');

if (isset($_GET['ecp_dismiss'])) {
    update_option('ecp_dismiss_pro', 1);
}