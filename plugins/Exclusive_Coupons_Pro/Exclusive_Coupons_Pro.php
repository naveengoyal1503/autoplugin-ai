/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Automatically generates and displays exclusive, personalized coupon codes for your WordPress site, boosting affiliate conversions and reader loyalty with custom deals.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: exclusive-coupons-pro
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
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('ecp_api_key') === false) {
            add_option('ecp_api_key', '');
        }
        if (get_option('ecp_coupons') === false) {
            add_option('ecp_coupons', array(
                array('code' => 'SAVE10', 'afflink' => '', 'desc' => '10% off first purchase', 'uses' => 0, 'maxuses' => 100)
            ));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ecp-script', plugin_dir_url(__FILE__) . 'ecp.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ecp-style', plugin_dir_url(__FILE__) . 'ecp.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons Pro', 'Coupons Pro', 'manage_options', 'ecp-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['ecp_save'])) {
            update_option('ecp_api_key', sanitize_text_field($_POST['ecp_api_key']));
            update_option('ecp_coupons', array_map(function($c) {
                return array(
                    'code' => sanitize_text_field($c['code']),
                    'afflink' => esc_url_raw($c['afflink']),
                    'desc' => sanitize_text_field($c['desc']),
                    'uses' => intval($c['uses']),
                    'maxuses' => intval($c['maxuses'])
                );
            }, $_POST['coupons']));
            echo '<div class="notice notice-success"><p>Saved!</p></div>';
        }
        $api_key = get_option('ecp_api_key');
        $coupons = get_option('ecp_coupons');
        ?>
        <div class="wrap">
            <h1>Exclusive Coupons Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>API Key (Premium)</th>
                        <td><input type="text" name="ecp_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <h2>Coupons</h2>
                <?php foreach ($coupons as $i => $coupon): ?>
                <div style="border:1px solid #ccc; margin:10px 0; padding:10px;">
                    <input type="hidden" name="coupons[<?php echo $i; ?>][code]" value="<?php echo esc_attr($coupon['code']); ?>" />
                    <p><label>Code: <input type="text" name="coupons[<?php echo $i; ?>][code]" value="<?php echo esc_attr($coupon['code']); ?>" /></label></p>
                    <p><label>Affiliate Link: <input type="url" name="coupons[<?php echo $i; ?>][afflink]" value="<?php echo esc_attr($coupon['afflink']); ?>" style="width:300px;" /></label></p>
                    <p><label>Description: <input type="text" name="coupons[<?php echo $i; ?>][desc]" value="<?php echo esc_attr($coupon['desc']); ?>" /></label></p>
                    <p><label>Uses: <input type="number" name="coupons[<?php echo $i; ?>][uses]" value="<?php echo esc_attr($coupon['uses']); ?>" /></label> / Max: <input type="number" name="coupons[<?php echo $i; ?>][maxuses]" value="<?php echo esc_attr($coupon['maxuses']); ?>" /></p>
                </div>
                <?php endforeach; ?>
                <p><input type="submit" name="ecp_save" class="button-primary" value="Save Settings" /></p>
            </form>
            <p><strong>Shortcode:</strong> <code>[exclusive_coupon]</code> - Use in posts/pages. Premium unlocks more features.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = get_option('ecp_coupons');
        if (empty($coupons)) return '';
        $coupon = $coupons[array_rand($coupons)];
        if ($coupon['uses'] >= $coupon['maxuses']) return '<p>Coupon expired!</p>';
        $unique_code = $coupon['code'] . '-' . uniqid();
        ob_start();
        ?>
        <div id="ecp-coupon" class="ecp-coupon-box">
            <h3>Exclusive Deal: <?php echo esc_html($coupon['desc']); ?></h3>
            <div class="ecp-code"><?php echo esc_html($unique_code); ?></div>
            <a href="<?php echo esc_url($coupon['afflink']); ?>&coupon=<?php echo urlencode($unique_code); ?>" class="ecp-button" target="_blank">Redeem Now</a>
            <small>Used <?php echo intval($coupon['uses']); ?> / <?php echo intval($coupon['maxuses']); ?> times</small>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#ecp-coupon .ecp-button').click(function() {
                $(this).text('Redeemed!');
                $.post(ajaxurl, {action: 'ecp_track_use', code: '<?php echo esc_js($unique_code); ?>'});
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new ExclusiveCouponsPro();

add_action('wp_ajax_ecp_track_use', function() {
    $code = sanitize_text_field($_POST['code']);
    $coupons = get_option('ecp_coupons');
    foreach ($coupons as &$c) {
        if (strpos($code, $c['code']) === 0) {
            $c['uses']++;
            break;
        }
    }
    update_option('ecp_coupons', $coupons);
    wp_die();
});

/* CSS */
function ecp_add_css() {
    echo '<style>
    .ecp-coupon-box { border: 2px dashed #007cba; padding: 20px; text-align: center; background: #f9f9f9; border-radius: 10px; margin: 20px 0; }
    .ecp-code { font-size: 2em; font-weight: bold; color: #007cba; background: white; padding: 10px; margin: 10px 0; display: inline-block; letter-spacing: 3px; }
    .ecp-button { background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0; }
    .ecp-button:hover { background: #005a87; }
    </style>';
}
add_action('wp_head', 'ecp_add_css');

/* JS */
function ecp_add_js() {
    ?><script>jQuery(document).ready(function($){ /* Inline JS for tracking */ });</script><?php
}

?>