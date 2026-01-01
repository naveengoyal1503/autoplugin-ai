/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: WP Exclusive Coupons Pro
 * Plugin URI: https://example.com/wp-exclusive-coupons
 * Description: Automatically generates and manages exclusive coupon codes for your WordPress site, boosting affiliate conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPExclusiveCoupons {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('wpec_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('wpec_pro') !== 'yes') {
            add_action('admin_notices', array($this, 'pro_notice'));
        }
    }

    public function pro_notice() {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>WP Exclusive Coupons Pro</strong> for unlimited coupons and analytics! <a href="https://example.com/pro" target="_blank">Get Pro</a></p></div>';
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons', 'Coupons', 'manage_options', 'wpec', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('wpec_coupons', sanitize_textarea_field($_POST['coupons']));
            update_option('wpec_limit', intval($_POST['limit']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $coupons = get_option('wpec_coupons', "BrandA:10OFF\nBrandB:DEAL20");
        $limit = get_option('wpec_limit', 3);
        ?>
        <div class="wrap">
            <h1>WP Exclusive Coupons</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupons (Brand:CODE)</th>
                        <td><textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Max Coupons per Page</th>
                        <td><input type="number" name="limit" value="<?php echo $limit; ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Usage: <code>[wpec_coupon]</code> or <code>[wpec_coupon id="1"]</code></p>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wpec-style', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons_str = get_option('wpec_coupons', '');
        $limit = get_option('wpec_limit', 3);
        if (empty($coupons_str)) return '';

        $coupons = explode('\n', $coupons_str);
        $coupons = array_filter(array_map('trim', $coupons));
        if ($atts['id']) {
            $id = intval($atts['id']) - 1;
            if (isset($coupons[$id])) {
                list($brand, $code) = explode(':', $coupons[$id], 2);
                return $this->render_coupon($brand, $code);
            }
        } else {
            shuffle($coupons);
            $coupons = array_slice($coupons, 0, $limit);
            $output = '<div class="wpec-coupons">';
            foreach ($coupons as $coupon) {
                list($brand, $code) = explode(':', $coupon, 2);
                $output .= $this->render_coupon($brand, $code);
            }
            $output .= '</div>';
            return $output;
        }
        return '';
    }

    private function render_coupon($brand, $code) {
        $aff_link = 'https://example.com/affiliate?code=' . $code; // Replace with real affiliate
        return '<div class="wpec-coupon"><strong>' . esc_html($brand) . '</strong> <code>' . esc_html($code) . '</code> <a href="' . esc_url($aff_link) . '" target="_blank" rel="nofollow">Shop Now</a></div>';
    }

    public function activate() {
        if (get_option('wpec_limit') === false) {
            update_option('wpec_limit', 3);
        }
    }
}

new WPExclusiveCoupons();

// Pro check stub
function wpec_is_pro() {
    return get_option('wpec_pro') === 'yes';
}

/* Add style.css content as inline or separate file */
function wpec_add_styles() {
    echo '<style>.wpec-coupons { background: #f9f9f9; padding: 20px; border-radius: 8px; } .wpec-coupon { margin: 10px 0; padding: 15px; background: white; border-left: 4px solid #0073aa; } .wpec-coupon code { background: #eee; padding: 4px 8px; border-radius: 4px; }</style>';
}
add_action('wp_head', 'wpec_add_styles');