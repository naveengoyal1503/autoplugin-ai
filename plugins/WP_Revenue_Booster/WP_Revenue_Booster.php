/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Description: Boost your WordPress site's revenue with smart affiliate offers and exclusive coupons.
 * Version: 1.0
 * Author: Cozmo Labs
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('wp_revenue_booster', array($this, 'display_offer'));
        add_action('wp_ajax_save_offer_click', array($this, 'save_offer_click'));
        add_action('wp_ajax_nopriv_save_offer_click', array($this, 'save_offer_click'));
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
        if (isset($_POST['save_offer'])) {
            update_option('wp_revenue_booster_offer', sanitize_text_field($_POST['offer']));
            update_option('wp_revenue_booster_coupon', sanitize_text_field($_POST['coupon']));
            update_option('wp_revenue_booster_affiliate_link', esc_url($_POST['affiliate_link']));
            echo '<div class="notice notice-success"><p>Offer updated!</p></div>';
        }
        $offer = get_option('wp_revenue_booster_offer', '');
        $coupon = get_option('wp_revenue_booster_coupon', '');
        $affiliate_link = get_option('wp_revenue_booster_affiliate_link', '');
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><label for="offer">Smart Offer</label></th>
                        <td><input type="text" name="offer" id="offer" value="<?php echo esc_attr($offer); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="coupon">Coupon Code</label></th>
                        <td><input type="text" name="coupon" id="coupon" value="<?php echo esc_attr($coupon); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="affiliate_link">Affiliate Link</label></th>
                        <td><input type="url" name="affiliate_link" id="affiliate_link" value="<?php echo esc_url($affiliate_link); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="save_offer" class="button-primary" value="Save Offer" />
                </p>
            </form>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('wp-revenue-booster-js', plugin_dir_url(__FILE__) . 'revenue-booster.js', array('jquery'), '1.0', true);
        wp_localize_script('wp-revenue-booster-js', 'wpRevenueBooster', array(
            'ajax_url' => admin_url('admin-ajax.php')
        ));
    }

    public function display_offer($atts) {
        $offer = get_option('wp_revenue_booster_offer', '');
        $coupon = get_option('wp_revenue_booster_coupon', '');
        $affiliate_link = get_option('wp_revenue_booster_affiliate_link', '#');
        if (empty($offer)) return '';
        return '<div class="wp-revenue-booster-offer">
                    <p>' . esc_html($offer) . '</p>
                    <p><strong>Coupon:</strong> <span class="wp-revenue-booster-coupon">' . esc_html($coupon) . '</span></p>
                    <a href="' . esc_url($affiliate_link) . '" target="_blank" class="wp-revenue-booster-affiliate-link" data-offer="' . esc_attr($offer) . '">Get Deal</a>
                </div>';
    }

    public function save_offer_click() {
        if (isset($_POST['offer'])) {
            $offer = sanitize_text_field($_POST['offer']);
            $count = get_option('wp_revenue_booster_clicks_' . $offer, 0);
            update_option('wp_revenue_booster_clicks_' . $offer, $count + 1);
            wp_die('success');
        }
    }
}

new WP_Revenue_Booster();

// JavaScript for tracking clicks
add_action('wp_footer', function() {
    if (is_user_logged_in()) return;
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.wp-revenue-booster-affiliate-link').on('click', function(e) {
            var offer = $(this).data('offer');
            $.post(wpRevenueBooster.ajax_url, {
                action: 'save_offer_click',
                offer: offer
            });
        });
    });
    </script>
    <?php
});
?>