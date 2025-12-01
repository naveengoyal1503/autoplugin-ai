<?php
/*
Plugin Name: WP Revenue Booster
Description: Automate ad placement, affiliate link management, and coupon distribution.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_footer', array($this, 'display_ads'));
        add_action('the_content', array($this, 'insert_affiliate_links'));
        add_shortcode('coupons', array($this, 'display_coupons'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'WP Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp-revenue-booster',
            array($this, 'admin_page'),
            'dashicons-chart-bar'
        );
    }

    public function admin_page() {
        if (isset($_POST['save_settings'])) {
            update_option('revenue_booster_ads', $_POST['ads']);
            update_option('revenue_booster_affiliates', $_POST['affiliates']);
            update_option('revenue_booster_coupons', $_POST['coupons']);
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
        $ads = get_option('revenue_booster_ads', '');
        $affiliates = get_option('revenue_booster_affiliates', '');
        $coupons = get_option('revenue_booster_coupons', '');
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post">
                <h2>Ad Code</h2>
                <textarea name="ads" rows="5" cols="50"><?php echo esc_textarea($ads); ?></textarea>
                <h2>Affiliate Links (JSON format: {"keyword":"link"})</h2>
                <textarea name="affiliates" rows="5" cols="50"><?php echo esc_textarea($affiliates); ?></textarea>
                <h2>Coupons (JSON format: [{"code":"CODE1","description":"10% off"}])</h2>
                <textarea name="coupons" rows="5" cols="50"><?php echo esc_textarea($coupons); ?></textarea>
                <p><input type="submit" name="save_settings" class="button button-primary" value="Save Settings"></p>
            </form>
        </div>
        <?php
    }

    public function display_ads() {
        $ads = get_option('revenue_booster_ads', '');
        echo $ads;
    }

    public function insert_affiliate_links($content) {
        $affiliates = get_option('revenue_booster_affiliates', '');
        if ($affiliates) {
            $links = json_decode($affiliates, true);
            if (is_array($links)) {
                foreach ($links as $keyword => $link) {
                    $content = str_replace($keyword, '<a href="' . esc_url($link) . '" target="_blank">' . $keyword . '</a>', $content);
                }
            }
        }
        return $content;
    }

    public function display_coupons() {
        $coupons = get_option('revenue_booster_coupons', '');
        if ($coupons) {
            $coupons = json_decode($coupons, true);
            if (is_array($coupons)) {
                $output = '<div class="revenue-booster-coupons"><h3>Coupons</h3><ul>';
                foreach ($coupons as $coupon) {
                    $output .= '<li><strong>' . esc_html($coupon['code']) . '</strong>: ' . esc_html($coupon['description']) . '</li>';
                }
                $output .= '</ul></div>';
                return $output;
            }
        }
        return '';
    }
}

new WP_Revenue_Booster();
?>