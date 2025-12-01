/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupon_Aggregator.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupon Aggregator
 * Description: Aggregates affiliate coupons from multiple platforms and displays them with automatic updates for higher conversions.
 * Version: 1.0
 * Author: Your Name
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SmartAffiliateCouponAggregator {
    private $coupons_option = 'saca_coupons_data';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_page'));
        add_action('admin_post_saca_save_coupons', array($this, 'save_coupons')); // handle form submission
        add_shortcode('saca_coupons', array($this, 'render_coupons_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    public function add_admin_page() {
        add_menu_page(
            'Smart Coupon Aggregator',
            'Coupon Aggregator',
            'manage_options',
            'smart-coupon-aggregator',
            array($this, 'admin_page_html'),
            'dashicons-tickets-alt',
            100
        );
    }

    public function admin_page_html() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $coupons = get_option($this->coupons_option, array());
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Coupon Aggregator</h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="saca_save_coupons">
                <?php wp_nonce_field('saca_save_coupons_verify'); ?>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="coupons_data">Coupons JSON Data</label></th>
                            <td>
                                <textarea id="coupons_data" name="coupons_data" rows="15" cols="80" placeholder='[{"title":"10% Off", "code":"SAVE10", "url":"https://affiliatelink.com/product123", "expiry":"2025-12-31"}]'><?php echo esc_textarea(json_encode($coupons, JSON_PRETTY_PRINT)); ?></textarea>
                                <p class="description">Enter coupon data in JSON format. Each coupon needs "title", "code", "url", and optional "expiry" (YYYY-MM-DD).</p>
                                <p class="description">Example: [{"title":"10% OFF", "code":"SAVE10", "url":"https://affiliate.example.com/?ref=123", "expiry":"2025-12-31"}]</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <?php submit_button('Save Coupons'); ?>
            </form>
            <hr>
            <h2>Shortcode Usage</h2>
            <p>Use the shortcode <code>[saca_coupons]</code> in any post or page to display the coupons table.</p>
        </div>
        <?php
    }

    public function save_coupons() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized user');
        }

        check_admin_referer('saca_save_coupons_verify');

        $coupons_json = isset($_POST['coupons_data']) ? wp_unslash(trim($_POST['coupons_data'])) : '';
        $coupons = json_decode($coupons_json, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($coupons)) {
            wp_redirect(add_query_arg('saca_error', 'invalid_json', admin_url('admin.php?page=smart-coupon-aggregator')));
            exit;
        }

        // Basic validation of each coupon
        foreach ($coupons as $coupon) {
            if (!isset($coupon['title'], $coupon['code'], $coupon['url'])) {
                wp_redirect(add_query_arg('saca_error', 'missing_fields', admin_url('admin.php?page=smart-coupon-aggregator')));
                exit;
            }
        }

        update_option($this->coupons_option, $coupons);
        wp_redirect(add_query_arg('saca_message', 'saved', admin_url('admin.php?page=smart-coupon-aggregator')));
        exit;
    }

    public function render_coupons_shortcode() {
        $coupons = get_option($this->coupons_option, array());
        if (empty($coupons)) {
            return '<p>No coupons available at the moment.</p>';
        }

        $output = '<table class="saca-coupons-table" style="width:100%;border-collapse:collapse;">';
        $output .= '<thead><tr><th style="border:1px solid #ddd;padding:8px;text-align:left;">Coupon</th><th style="border:1px solid #ddd;padding:8px;text-align:left;">Code</th><th style="border:1px solid #ddd;padding:8px;text-align:left;">Expires</th><th style="border:1px solid #ddd;padding:8px;text-align:left;">Get Deal</th></tr></thead><tbody>';

        $today = current_time('Y-m-d');

        foreach ($coupons as $coupon) {
            $expired = false;
            if (!empty($coupon['expiry']) && $coupon['expiry'] < $today) {
                $expired = true;
            }

            $title = esc_html($coupon['title']);
            $code = esc_html($coupon['code']);
            $expiry = !empty($coupon['expiry']) ? esc_html($coupon['expiry']) : 'Never';
            $url = esc_url($coupon['url']);

            $row_style = $expired ? 'style="color:#aaa;text-decoration:line-through;"' : '';
            $button = $expired ? 'Expired' : '<a class="saca-button" href="' . $url . '" target="_blank" rel="nofollow noopener" onclick="navigator.clipboard.writeText(\'' . $code . '\');alert(\'Coupon code copied: ' . $code . '\');">Use Code</a>';

            $output .= "<tr $row_style><td style='border:1px solid #ddd;padding:8px;'>$title</td><td style='border:1px solid #ddd;padding:8px;'><code>$code</code></td><td style='border:1px solid #ddd;padding:8px;'>$expiry</td><td style='border:1px solid #ddd;padding:8px;'>$button</td></tr>";
        }

        $output .= '</tbody></table>';
        return $output;
    }

    public function enqueue_styles() {
        wp_add_inline_style('wp-block-library', 
            ".saca-coupons-table {border: 1px solid #ddd; border-radius: 4px; margin-top: 20px; font-family: Arial, sans-serif;}"
            .".saca-button {background-color: #0073aa; color: white; padding: 6px 12px; text-decoration: none; border-radius: 3px; transition: background-color 0.3s;}"
            .".saca-button:hover {background-color: #005177;}"
        );
    }
}

new SmartAffiliateCouponAggregator();
