/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupon codes and deals to boost affiliate commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_shortcode('affiliate_coupons', array($this, 'coupon_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        add_menu_page('Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
        add_action('admin_post_save_coupons', array($this, 'save_coupons'));
    }

    public function activate() {
        add_option('acv_coupons', array(
            array('code' => 'SAVE10', 'description' => '10% off on all products', 'afflink' => '', 'expires' => ''),
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0');
    }

    public function admin_page() {
        $coupons = get_option('acv_coupons', array());
        if (isset($_POST['coupons'])) {
            update_option('acv_coupons', $_POST['coupons']);
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault</h1>
            <p>Upgrade to Pro for auto-expiration and analytics.</p>
            <form method="post">
                <table class="form-table">
                    <?php foreach ($coupons as $index => $coupon): ?>
                    <tr>
                        <th>Code</th>
                        <td><input type="text" name="coupons[<?php echo $index; ?>][code]" value="<?php echo esc_attr($coupon['code']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Description</th>
                        <td><input type="text" name="coupons[<?php echo $index; ?>][description]" value="<?php echo esc_attr($coupon['description']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Link</th>
                        <td><input type="url" name="coupons[<?php echo $index; ?>][afflink]" value="<?php echo esc_attr($coupon['afflink']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Expires (YYYY-MM-DD)</th>
                        <td><input type="date" name="coupons[<?php echo $index; ?>][expires]" value="<?php echo esc_attr($coupon['expires']); ?>" /></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <p><input type="submit" class="button-primary" value="Save Coupons" /></p>
            </form>
            <p><a href="#" class="button" onclick="addCouponRow()">Add New Coupon</a></p>
            <script>
            function addCouponRow() {
                const table = document.querySelector('.form-table');
                const index = table.rows.length / 4;
                table.innerHTML += `
                    <tr><th>Code</th><td><input type="text" name="coupons[${index}][code]" /></td></tr>
                    <tr><th>Description</th><td><input type="text" name="coupons[${index}][description]" /></td></tr>
                    <tr><th>Affiliate Link</th><td><input type="url" name="coupons[${index}][afflink]" /></td></tr>
                    <tr><th>Expires</th><td><input type="date" name="coupons[${index}][expires]" /></td></tr>
                `;
            }
            </script>
        </div>
        <style>
        .wrap { max-width: 800px; }
        .form-table th { width: 150px; }
        </style>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts);
        $coupons = get_option('acv_coupons', array());
        $output = '<div class="acv-coupons">';
        $count = 0;
        foreach ($coupons as $coupon) {
            if ($count >= $atts['limit']) break;
            $expired = !empty($coupon['expires']) && strtotime($coupon['expires']) < time();
            if (!$expired) {
                $output .= '<div class="acv-coupon">';
                $output .= '<h4>' . esc_html($coupon['code']) . '</h4>';
                $output .= '<p>' . esc_html($coupon['description']) . '</p>';
                if (!empty($coupon['afflink'])) {
                    $output .= '<a href="' . esc_url($coupon['afflink']) . '" class="button" target="_blank">Shop Now & Save</a>';
                }
                $output .= '</div>';
                $count++;
            }
        }
        $output .= '</div>';
        return $output;
    }
}

new AffiliateCouponVault();

// Pro teaser
add_action('admin_notices', function() {
    if (!get_option('acv_pro_activated')) {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock auto-expiration, analytics, and custom branding for $49/year! <a href="https://example.com/pro">Upgrade Now</a></p></div>';
    }
});

// Minimal CSS
/*
.acv-coupons { display: flex; flex-wrap: wrap; gap: 20px; }
.acv-coupon { border: 1px solid #ddd; padding: 20px; border-radius: 8px; background: #f9f9f9; flex: 1 1 300px; }
.acv-coupon h4 { color: #0073aa; margin: 0 0 10px; }
.acv-coupon .button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
*/
?>