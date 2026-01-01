/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Automatically generates and displays exclusive, personalized coupon codes for your WordPress site visitors, boosting affiliate conversions and site engagement.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: exclusive-coupons-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class ExclusiveCouponsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_generate_coupon', array($this, 'ajax_generate_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('ecp_api_key')) {
            // Premium check
        }
        load_plugin_textdomain('exclusive-coupons-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ecp-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ecp-style', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
        wp_localize_script('ecp-script', 'ecp_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons Pro', 'Coupons Pro', 'manage_options', 'exclusive-coupons-pro', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['ecp_save'])) {
            update_option('ecp_coupons', sanitize_textarea_field($_POST['coupons']));
            update_option('ecp_api_key', sanitize_text_field($_POST['api_key']));
            echo '<div class="notice notice-success"><p>Saved!</p></div>';
        }
        $coupons = get_option('ecp_coupons', "Brand1:20OFF\nBrand2:SAVE10");
        $api_key = get_option('ecp_api_key', '');
        ?>
        <div class="wrap">
            <h1>Exclusive Coupons Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupons (Brand:Code)</th>
                        <td><textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Premium API Key</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" /></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" name="ecp_save" class="button-primary" value="Save Changes" /></p>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[exclusive_coupon]</code></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('brand' => ''), $atts);
        ob_start();
        ?>
        <div id="ecp-coupon" data-brand="<?php echo esc_attr($atts['brand']); ?>" class="ecp-container">
            <p>Generate your exclusive coupon!</p>
            <button id="ecp-generate">Get Coupon</button>
            <div id="ecp-code" style="display:none;"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        $coupons = explode("\n", get_option('ecp_coupons', ''));
        $code = '';
        foreach ($coupons as $line) {
            $parts = explode(':', trim($line), 2);
            if (count($parts) == 2 && ($parts == $_POST['brand'] || empty($_POST['brand']))) {
                $code = $parts[1];
                break;
            }
        }
        if (!$code) {
            $code = 'SAVE' . wp_rand(1000, 9999);
        }
        wp_send_json_success(array('code' => $code));
    }

    public function activate() {
        update_option('ecp_coupons', "YourBrand:20OFF\nShopNow:SAVE15");
    }
}

new ExclusiveCouponsPro();

// Assets would be created as script.js and style.css in /assets/
// script.js example:
/*
jQuery(document).ready(function($) {
    $('#ecp-generate').click(function() {
        var $container = $(this).closest('.ecp-container');
        var brand = $container.data('brand');
        $.post(ecp_ajax.ajax_url, {action: 'generate_coupon', brand: brand}, function(res) {
            if (res.success) {
                $('#ecp-code', $container).html('<strong>Your code: ' + res.data.code + '</strong>').show();
            }
        });
    });
});
*/
// style.css example:
/*
.ecp-container { border: 1px solid #ddd; padding: 20px; margin: 20px 0; }
#ecp-generate { background: #0073aa; color: white; padding: 10px 20px; border: none; cursor: pointer; }
*/