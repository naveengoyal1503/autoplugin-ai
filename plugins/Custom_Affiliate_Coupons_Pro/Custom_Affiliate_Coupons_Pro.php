/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Custom_Affiliate_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Custom Affiliate Coupons Pro
 * Plugin URI: https://example.com/custom-affiliate-coupons
 * Description: Generate and manage exclusive custom coupon codes for affiliate products to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class CustomAffiliateCouponsPro {
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_shortcode('affiliate_coupon', [$this, 'coupon_shortcode']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function init() {
        wp_register_style('cacp-admin-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_register_script('cacp-admin-script', plugin_dir_url(__FILE__) . 'script.js', ['jquery'], '1.0.0', true);
    }

    public function admin_menu() {
        add_menu_page('Affiliate Coupons', 'Affiliate Coupons', 'manage_options', 'cacp-coupons', [$this, 'admin_page']);
    }

    public function admin_page() {
        if (isset($_POST['save_coupon'])) {
            $this->save_coupon();
        }
        $coupons = get_option('cacp_coupons', []);
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    private function save_coupon() {
        $coupons = get_option('cacp_coupons', []);
        $id = sanitize_text_field($_POST['coupon_id']);
        $coupons[$id] = [
            'affiliate_link' => esc_url_raw($_POST['affiliate_link']),
            'code' => sanitize_text_field($_POST['code']),
            'description' => sanitize_textarea_field($_POST['description']),
            'expires' => sanitize_text_field($_POST['expires']),
            'image' => esc_url_raw($_POST['image'])
        ];
        update_option('cacp_coupons', $coupons);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(['id' => ''], $atts);
        if (empty($atts['id'])) return '';
        $coupons = get_option('cacp_coupons', []);
        if (!isset($coupons[$atts['id']])) return '';
        $coupon = $coupons[$atts['id']];
        $output = '<div class="cacp-coupon-box">';
        if (!empty($coupon['image'])) {
            $output .= '<img src="' . esc_url($coupon['image']) . '" alt="Coupon Image">';
        }
        $output .= '<h3>Exclusive Deal: ' . esc_html($coupon['code']) . '</h3>';
        $output .= '<p>' . esc_html($coupon['description']) . '</p>';
        $output .= '<a href="' . esc_url($coupon['affiliate_link']) . '" target="_blank" class="cacp-button">Get Deal Now (Affiliate Link)</a>';
        if (!empty($coupon['expires'])) {
            $output .= '<p class="expires">Expires: ' . esc_html($coupon['expires']) . '</p>';
        }
        $output .= '</div>';
        return $output;
    }

    public function activate() {
        if (!get_option('cacp_coupons')) {
            update_option('cacp_coupons', []);
        }
    }
}

new CustomAffiliateCouponsPro();

// Inline CSS for frontend
function cacp_styles() {
    echo '<style>
    .cacp-coupon-box { border: 2px solid #007cba; padding: 20px; border-radius: 10px; text-align: center; background: #f9f9f9; max-width: 400px; margin: 20px auto; }
    .cacp-coupon-box img { max-width: 100%; height: auto; }
    .cacp-coupon-box h3 { color: #007cba; margin: 10px 0; }
    .cacp-button { display: inline-block; background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; }
    .cacp-button:hover { background: #005a87; }
    .expires { font-size: 0.9em; color: #666; margin-top: 10px; }
    </style>';
}
add_action('wp_head', 'cacp_styles');

// Sample admin page template (embedded)
// Note: In a real single-file plugin, this would be echoed or included similarly
function cacp_admin_page_template() { ?>
<div class="wrap">
    <h1>Custom Affiliate Coupons Pro</h1>
    <form method="post">
        <table class="form-table">
            <tr>
                <th>Coupon ID</th>
                <td><input type="text" name="coupon_id" value="<?php echo isset($_POST['coupon_id']) ? esc_attr($_POST['coupon_id']) : ''; ?>" required /></td>
            </tr>
            <tr>
                <th>Affiliate Link</th>
                <td><input type="url" name="affiliate_link" style="width: 100%;" required /></td>
            </tr>
            <tr>
                <th>Coupon Code</th>
                <td><input type="text" name="code" style="width: 100%;" required /></td>
            </tr>
            <tr>
                <th>Description</th>
                <td><textarea name="description" rows="3" style="width: 100%;"><?php echo isset($_POST['description']) ? esc_textarea($_POST['description']) : ''; ?></textarea></td>
            </tr>
            <tr>
                <th>Expires</th>
                <td><input type="date" name="expires" /></td>
            </tr>
            <tr>
                <th>Image URL</th>
                <td><input type="url" name="image" style="width: 100%;" /></td>
            </tr>
        </table>
        <?php submit_button('Save Coupon'); ?>
    </form>
    <h2>Existing Coupons</h2>
    <ul><?php
        $coupons = get_option('cacp_coupons', []);
        foreach ($coupons as $id => $c) {
            echo '<li><strong>' . esc_html($id) . '</strong>: ' . esc_html($c['code']) . ' <small><a href="' . esc_url($c['affiliate_link']) . '" target="_blank">Link</a></small></li>';
        }
    ?></ul>
    <p><strong>Usage:</strong> Use shortcode <code>[affiliate_coupon id="your-id"]</code> to display coupon.</p>
</div><?php
}
// Override admin_page to use this template
// (In class, echo the template content)