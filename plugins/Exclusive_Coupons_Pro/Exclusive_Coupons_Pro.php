/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Automatically generates, manages, and displays exclusive affiliate coupons with personalized promo codes to boost conversions and monetize your WordPress blog.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ExclusiveCouponsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_generate_promo', array($this, 'ajax_generate_promo'));
        add_action('wp_ajax_nopriv_generate_promo', array($this, 'ajax_generate_promo'));
    }

    public function init() {
        if (get_option('ecp_license') !== 'activated') {
            add_action('admin_notices', array($this, 'pro_notice'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ecp-script', plugin_dir_url(__FILE__) . 'ecp-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ecp-script', 'ecp_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons Pro', 'Coupons Pro', 'manage_options', 'exclusive-coupons-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['ecp_save'])) {
            update_option('ecp_affiliates', sanitize_textarea_field($_POST['affiliates']));
            update_option('ecp_license', sanitize_text_field($_POST['license']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Exclusive Coupons Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Affiliate Links/Codes</th>
                        <td><textarea name="affiliates" rows="10" cols="50"><?php echo esc_textarea(get_option('ecp_affiliates')); ?></textarea><br>
                        <small>One per line: Brand|Promo Code|Affiliate Link|Description</small></td>
                    </tr>
                    <tr>
                        <th>License Key</th>
                        <td><input type="text" name="license" value="<?php echo esc_attr(get_option('ecp_license')); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button('Save Settings', 'primary', 'ecp_save'); ?>
            </form>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $affiliates = explode("\n", get_option('ecp_affiliates', ''));
        $coupons = array();
        foreach ($affiliates as $line) {
            $parts = explode('|', trim($line), 4);
            if (count($parts) === 4) {
                $coupons[] = $parts;
            }
        }
        if (empty($coupons)) return '<p>No coupons configured.</p>';

        $coupon = $coupons[array_rand($coupons)];
        $unique_code = substr(md5(uniqid()), 0, 8);
        ob_start();
        ?>
        <div id="ecp-coupon" style="border: 2px dashed #0073aa; padding: 20px; background: #f9f9f9; text-align: center;">
            <h3><?php echo esc_html($coupon); ?> Exclusive Deal!</h3>
            <p><strong>Promo Code: <span id="promo-code"><?php echo $unique_code; ?></span></strong></p>
            <p><?php echo esc_html($coupon[3]); ?></p>
            <a href="<?php echo esc_url($coupon[2] . '?code=' . $unique_code); ?}" target="_blank" class="button button-primary" style="padding: 10px 20px; font-size: 16px;">Get Deal Now (Affiliate)</a>
            <button id="new-promo" class="button">New Code</button>
        </div>
        <script>
        jQuery('#new-promo').click(function() {
            jQuery.post(ecp_ajax.ajaxurl, {action: 'generate_promo'}, function(code) {
                jQuery('#promo-code').text(code);
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_promo() {
        $code = substr(md5(uniqid()), 0, 8);
        wp_send_json($code);
        wp_die();
    }

    public function pro_notice() {
        if (get_option('ecp_license') !== 'activated') {
            echo '<div class="notice notice-warning"><p><strong>Exclusive Coupons Pro:</strong> Enter license key for premium features or <a href="https://example.com/pricing">upgrade</a>.</p></div>';
        }
    }
}

new ExclusiveCouponsPro();

// Freemium check
function ecp_is_pro() {
    return get_option('ecp_license') === 'activated';
}

/*
Premium features (locked in free):
- Unlimited coupons
- Analytics dashboard
- Custom branding
- Email capture
*/
?>