/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Description: Automate coupon distribution, affiliate tracking, and ad optimization.
 * Version: 1.0
 * Author: RevenueBoost Team
 */

define('WP_REVENUE_BOOSTER_VERSION', '1.0');

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('revenue_booster', array($this, 'shortcode_handler'));
        add_action('wp_ajax_track_conversion', array($this, 'track_conversion'));
        add_action('wp_ajax_nopriv_track_conversion', array($this, 'track_conversion'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'WP Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp-revenue-booster',
            array($this, 'admin_page'),
            'dashicons-chart-line',
            6
        );
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post" action="options.php">
                <?php settings_fields('wp_revenue_booster'); ?>
                <?php do_settings_sections('wp_revenue_booster'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Affiliate Tracking ID</th>
                        <td><input type="text" name="affiliate_tracking_id" value="<?php echo esc_attr(get_option('affiliate_tracking_id')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Ad Placement Zone</th>
                        <td><input type="text" name="ad_placement_zone" value="<?php echo esc_attr(get_option('ad_placement_zone')); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('wp-revenue-booster-js', plugins_url('/js/revenue-booster.js', __FILE__), array('jquery'), WP_REVENUE_BOOSTER_VERSION, true);
        wp_localize_script('wp-revenue-booster-js', 'revenueBoosterAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php')
        ));
    }

    public function shortcode_handler($atts) {
        $atts = shortcode_atts(array(
            'coupon' => '',
            'affiliate_link' => '',
            'ad_code' => ''
        ), $atts, 'revenue_booster');

        $output = '';
        if (!empty($atts['coupon'])) {
            $output .= '<div class="revenue-booster-coupon">Coupon: ' . esc_html($atts['coupon']) . '</div>';
        }
        if (!empty($atts['affiliate_link'])) {
            $output .= '<a href="' . esc_url($atts['affiliate_link']) . '?ref=' . esc_attr(get_option('affiliate_tracking_id')) . '" target="_blank">Visit Affiliate</a>';
        }
        if (!empty($atts['ad_code'])) {
            $output .= '<div class="revenue-booster-ad">' . wp_kses_post($atts['ad_code']) . '</div>';
        }
        return $output;
    }

    public function track_conversion() {
        if (isset($_POST['conversion_type'])) {
            $conversion_type = sanitize_text_field($_POST['conversion_type']);
            $data = array(
                'conversion_type' => $conversion_type,
                'timestamp' => current_time('mysql')
            );
            // In a real plugin, you'd save to the database
            wp_die('Conversion tracked: ' . $conversion_type);
        }
    }
}

function wp_revenue_booster_init() {
    new WP_Revenue_Booster();
}
add_action('plugins_loaded', 'wp_revenue_booster_init');

// Activation hook
register_activation_hook(__FILE__, function() {
    add_option('affiliate_tracking_id', 'default');
    add_option('ad_placement_zone', 'default');
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    delete_option('affiliate_tracking_id');
    delete_option('ad_placement_zone');
});
?>