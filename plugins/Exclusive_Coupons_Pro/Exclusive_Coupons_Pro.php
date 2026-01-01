/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Automatically generates and displays exclusive, personalized coupon codes for your WordPress site visitors, boosting affiliate conversions and engagement.
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
        add_shortcode('exclusive_coupons', array($this, 'coupons_shortcode'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_generate_coupon', array($this, 'ajax_generate_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('ecp_api_key') && get_option('ecp_enabled')) {
            // Pro features would check license here
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ecp-script', plugin_dir_url(__FILE__) . 'ecp.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ecp-script', 'ecp_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array(
            'count' => 3,
            'brands' => 'amazon,shopify,nike',
        ), $atts);

        ob_start();
        echo '<div id="ecp-coupons-container" data-count="' . esc_attr($atts['count']) . '" data-brands="' . esc_attr($atts['brands']) . '">Loading exclusive coupons...</div>';
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        $count = intval($_POST['count'] ?? 3);
        $brands = sanitize_text_field($_POST['brands'] ?? '');
        $coupons = array();

        $sample_coupons = array(
            array('code' => 'SAVE20NOW', 'brand' => 'Amazon', 'discount' => '20% Off', 'link' => 'https://amazon.com/?tag=youraffiliate'),
            array('code' => 'WP50OFF', 'brand' => 'Shopify', 'discount' => '50% Off First Month', 'link' => 'https://shopify.com/?ref=yourref'),
            array('code' => 'RUN15', 'brand' => 'Nike', 'discount' => '15% Off Footwear', 'link' => 'https://nike.com/?aff=youraff'),
        );

        for ($i = 0; $i < $count; $i++) {
            $coupon = $sample_coupons[array_rand($sample_coupons)];
            $coupon['unique_code'] = $coupon['code'] . '-' . wp_generate_uuid4() . substr(md5(uniqid()), 0, 4);
            $coupons[] = $coupon;
        }

        wp_send_json_success($coupons);
    }

    public function activate() {
        add_option('ecp_enabled', true);
        add_option('ecp_version', '1.0.0');
    }
}

new ExclusiveCouponsPro();

// Inline JS for simplicity (self-contained)
function ecp_inline_js() {
    if (is_page() || is_single()) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('#ecp-coupons-container').each(function() {
                var $container = $(this);
                var count = $container.data('count') || 3;
                var brands = $container.data('brands') || '';

                $.post(ecp_ajax.ajax_url, {
                    action: 'generate_coupon',
                    count: count,
                    brands: brands
                }, function(response) {
                    if (response.success) {
                        var html = '';
                        response.data.forEach(function(coupon) {
                            html += '<div class="ecp-coupon" style="border:1px solid #ddd; padding:15px; margin:10px 0; border-radius:5px;"><h4>' + coupon.brand + '</h4><p><strong>Code:</strong> ' + coupon.unique_code + '</p><p>' + coupon.discount + '</p><a href="' + coupon.link + '" target="_blank" style="background:#0073aa; color:white; padding:10px 20px; text-decoration:none; border-radius:3px;">Shop Now & Save</a></div>';
                        });
                        $container.html(html);
                    }
                });
            });
        });
        <?php
    }
}
add_action('wp_footer', 'ecp_inline_js');

// Admin settings page
add_action('admin_menu', function() {
    add_options_page('Exclusive Coupons Pro', 'Coupons Pro', 'manage_options', 'ecp-settings', function() {
        if (isset($_POST['ecp_api_key'])) {
            update_option('ecp_api_key', sanitize_text_field($_POST['ecp_api_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Exclusive Coupons Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Enable Plugin</th>
                        <td><input type="checkbox" name="ecp_enabled" value="1" <?php checked(get_option('ecp_enabled')); ?> /></td>
                    </tr>
                    <tr>
                        <th>Pro API Key (Upgrade for real coupons)</th>
                        <td><input type="text" name="ecp_api_key" value="<?php echo esc_attr(get_option('ecp_api_key')); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, real-time affiliate tracking, custom designs, and premium support for $49/year.</p>
        </div>
        <?php
    });
});