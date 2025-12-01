<?php
/*
Plugin Name: WP Revenue Booster
Description: Boost your WordPress site's revenue with smart affiliate link management, coupon display, and sponsored content automation.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_footer', array($this, 'inject_coupon_bar'));
        add_filter('the_content', array($this, 'inject_sponsored_content'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'WP Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp-revenue-booster',
            array($this, 'admin_page'),
            'dashicons-chart-line'
        );
    }

    public function admin_page() {
        if (isset($_POST['save_settings'])) {
            update_option('wp_revenue_booster_coupons', $_POST['coupons']);
            update_option('wp_revenue_booster_sponsored', $_POST['sponsored']);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $coupons = get_option('wp_revenue_booster_coupons', '');
        $sponsored = get_option('wp_revenue_booster_sponsored', '');
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post">
                <h2>Coupon Manager</h2>
                <textarea name="coupons" rows="5" cols="50"><?php echo esc_textarea($coupons); ?></textarea>
                <p>Enter one coupon per line (format: Brand - Code - Description)</p>
                <h2>Sponsored Content</h2>
                <textarea name="sponsored" rows="5" cols="50"><?php echo esc_textarea($sponsored); ?></textarea>
                <p>Enter sponsored content HTML</p>
                <input type="submit" name="save_settings" class="button button-primary" value="Save Settings">
            </form>
        </div>
        <?php
    }

    public function inject_coupon_bar() {
        $coupons = get_option('wp_revenue_booster_coupons', '');
        if (empty($coupons)) return;
        $coupons_array = explode('\n', $coupons);
        if (count($coupons_array) > 0) {
            echo '<div id="wp-revenue-coupon-bar" style="background:#f0f0f0;padding:10px;text-align:center;position:fixed;bottom:0;left:0;width:100%;z-index:9999;display:none;">
                <strong>Exclusive Coupons:</strong> ';
            foreach ($coupons_array as $coupon) {
                $parts = explode(' - ', $coupon);
                if (count($parts) >= 3) {
                    echo '<span style="margin:0 10px;">' . esc_html($parts) . ': <strong>' . esc_html($parts[1]) . '</strong> (' . esc_html($parts[2]) . ')</span>';
                }
            }
            echo '<button onclick="this.parentElement.style.display=\'none\'" style="float:right;">×</button></div>';
            echo '<script>document.addEventListener("DOMContentLoaded", function(){document.getElementById("wp-revenue-coupon-bar").style.display="block";});</script>';
        }
    }

    public function inject_sponsored_content($content) {
        $sponsored = get_option('wp_revenue_booster_sponsored', '');
        if (empty($sponsored) || !is_single()) return $content;
        return $content . '<div class="wp-revenue-sponsored" style="background:#f9f9f9;padding:15px;margin:20px 0;border:1px dashed #ccc;">' . $sponsored . '</div>';
    }

    public function enqueue_styles() {
        wp_enqueue_style('wp-revenue-booster', plugins_url('style.css', __FILE__));
    }
}

new WP_Revenue_Booster();
?>