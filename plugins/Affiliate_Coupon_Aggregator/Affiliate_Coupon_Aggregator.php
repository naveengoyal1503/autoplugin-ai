/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Aggregator.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Aggregator
 * Description: Aggregates and displays affiliate coupons from multiple stores with shortcode.
 * Version: 1.0
 * Author: Plugin Generator
 */

if (!defined('ABSPATH')) exit;

class AffiliateCouponAggregator {
    private $coupons_option = 'aca_coupons_data';
    private $nonce = 'aca_nonce_action';

    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_post_save_coupons', array($this, 'save_coupons_data'));
        add_shortcode('affiliate_coupons', array($this, 'render_coupons'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    public function admin_menu() {
        add_menu_page('Affiliate Coupon Aggregator', 'Coupon Aggregator', 'manage_options', 'aca_settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) return;
        $coupons = get_option($this->coupons_option, array());
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Aggregator Settings</h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field($this->nonce); ?>
                <input type="hidden" name="action" value="save_coupons">
                <label for="coupons_data">Enter coupons JSON (array of objects with keys: title, code, url, description):</label><br>
                <textarea id="coupons_data" name="coupons_data" rows="15" cols="70" style="font-family: monospace;"><?php echo esc_textarea(json_encode($coupons)); ?></textarea><br><br>
                <input type="submit" class="button button-primary" value="Save Coupons">
            </form>
            <h2>Usage</h2>
            <p>Add shortcode <code>[affiliate_coupons]</code> wherever you want to display coupons.</p>
        </div>
        <?php
    }

    public function save_coupons_data() {
        if (!current_user_can('manage_options') || !check_admin_referer($this->nonce)) {
            wp_die('Permission denied');
        }
        $raw = isset($_POST['coupons_data']) ? wp_unslash($_POST['coupons_data']) : '';
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            wp_redirect(admin_url('admin.php?page=aca_settings&error=invalid_json'));
            exit;
        }
        // Basic validation of keys
        foreach ($decoded as $c) {
            if (!isset($c['title']) || !isset($c['code']) || !isset($c['url'])) {
                wp_redirect(admin_url('admin.php?page=aca_settings&error=missing_fields'));
                exit;
            }
        }
        update_option($this->coupons_option, $decoded);
        wp_redirect(admin_url('admin.php?page=aca_settings&updated=1'));
        exit;
    }

    public function render_coupons($atts) {
        $coupons = get_option($this->coupons_option, array());
        if (empty($coupons)) {
            return '<p>No coupons available at the moment.</p>';
        }
        $output = '<div class="aca-coupons-list">';
        foreach ($coupons as $coupon) {
            $title = esc_html($coupon['title']);
            $code = esc_html($coupon['code']);
            $url = esc_url($coupon['url']);
            $desc = isset($coupon['description']) ? esc_html($coupon['description']) : '';
            $output .= "<div class='aca-coupon-item' style='border:1px solid #ddd;padding:10px;margin-bottom:10px;'>";
            $output .= "<h3 style='margin:0 0 5px 0;'>$title</h3>";
            if ($desc) {
                $output .= "<p style='margin:0 0 5px 0;'>$desc</p>";
            }
            $output .= "<p><strong>Coupon Code: </strong><code>$code</code></p>";
            $output .= "<p><a href='$url' target='_blank' rel='nofollow noopener' style='background:#0073aa;color:#fff;padding:5px 10px;text-decoration:none;border-radius:3px;'>Use Coupon</a></p>";
            $output .= "</div>";
        }
        $output .= '</div>';
        return $output;
    }

    public function enqueue_styles() {
        wp_register_style('aca-style', false);
        wp_enqueue_style('aca-style');
    }
}

new AffiliateCouponAggregator();
