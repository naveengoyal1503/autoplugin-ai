/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate exclusive affiliate coupons with tracking and auto-expiration to maximize commissions.
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
        add_action('wp_ajax_save_coupon', array($this, 'ajax_save_coupon'));
        add_action('wp_ajax_nopriv_save_coupon', array($this, 'ajax_save_coupon'));
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('exclusive-coupons-pro', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        if (get_option('ecp_pro_version')) {
            // Pro features unlocked
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ecp-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ecp-style', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
        wp_localize_script('ecp-script', 'ecp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ecp_nonce')));
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons Pro', 'Coupons Pro', 'manage_options', 'exclusive-coupons-pro', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['save_coupon'])) {
            update_option('ecp_coupons', $_POST['coupons']);
        }
        $coupons = get_option('ecp_coupons', array());
        ?>
        <div class="wrap">
            <h1><?php _e('Manage Exclusive Coupons', 'exclusive-coupons-pro'); ?></h1>
            <form method="post">
                <table class="form-table">
                    <?php foreach ($coupons as $i => $coupon): ?>
                    <tr>
                        <th>Name</th>
                        <td><input type="text" name="coupons[<?php echo $i; ?>][name]" value="<?php echo esc_attr($coupon['name']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Code</th>
                        <td><input type="text" name="coupons[<?php echo $i; ?>][code]" value="<?php echo esc_attr($coupon['code']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Link</th>
                        <td><input type="url" name="coupons[<?php echo $i; ?>][link]" value="<?php echo esc_attr($coupon['link']); ?>" style="width:100%;" /></td>
                    </tr>
                    <tr>
                        <th>Expires (days)</th>
                        <td><input type="number" name="coupons[<?php echo $i; ?>][expires]" value="<?php echo esc_attr($coupon['expires']); ?>" /></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <th>Add New</th>
                        <td>
                            <input type="text" id="new-name" placeholder="Name" />
                            <input type="text" id="new-code" placeholder="Code" />
                            <input type="url" id="new-link" placeholder="Affiliate Link" />
                            <input type="number" id="new-expires" placeholder="Expires (days)" value="30" />
                            <button type="button" id="add-coupon">Add Coupon</button>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save Coupons'); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, click tracking, and analytics for $49/year.</p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#add-coupon').click(function() {
                var coupons = <?php echo json_encode($coupons); ?>;
                var i = coupons.length;
                coupons[i] = {
                    name: $('#new-name').val(),
                    code: $('#new-code').val(),
                    link: $('#new-link').val(),
                    expires: $('#new-expires').val()
                };
                $.post(ecp_ajax.ajax_url, {
                    action: 'save_coupon',
                    coupons: coupons,
                    nonce: ecp_ajax.nonce
                }, function() {
                    location.reload();
                });
            });
        });
        </script>
        <?php
    }

    public function ajax_save_coupon() {
        check_ajax_referer('ecp_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_die();
        update_option('ecp_coupons', $_POST['coupons']);
        wp_send_json_success();
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = get_option('ecp_coupons', array());
        if (!isset($coupons[$atts['id']])) return '';
        $coupon = $coupons[$atts['id']];
        $expired = !empty($coupon['expires']) && (time() - get_option('ecp_coupon_created_' . $atts['id'], 0) > $coupon['expires'] * 86400);
        if ($expired) {
            return '<div class="ecp-expired">Coupon expired!</div>';
        }
        $link = add_query_arg('ecp_coupon', $atts['id'], $coupon['link']);
        return '<div class="ecp-coupon"><h3>' . esc_html($coupon['name']) . '</h3><p>Code: <strong>' . esc_html($coupon['code']) . '</strong></p><a href="' . esc_url($link) . '" class="button button-primary" target="_blank">Get Deal Now</a></div>';
    }

    public function activate() {
        if (!get_option('ecp_coupons')) {
            update_option('ecp_coupons', array(
                array('name' => 'Sample Deal', 'code' => 'SAVE20', 'link' => '#', 'expires' => 30)
            ));
        }
    }
}

ExclusiveCouponsPro::get_instance();

// Pro upsell notice
function ecp_pro_notice() {
    if (!get_option('ecp_pro_version') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Exclusive Coupons Pro</strong> for unlimited coupons and tracking! <a href="https://example.com/pro" target="_blank">Get Pro ($49)</a></p></div>';
    }
}
add_action('admin_notices', 'ecp_pro_notice');

// Free version limit
function ecp_coupon_limit($coupons) {
    if (!get_option('ecp_pro_version') && count($coupons) >= 3) {
        return array_slice($coupons, 0, 3);
    }
    return $coupons;
}
add_filter('option_ecp_coupons', 'ecp_coupon_limit');