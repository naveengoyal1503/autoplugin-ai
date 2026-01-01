/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons from popular networks to boost your commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupons', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'network' => 'amazon',
            'category' => 'electronics',
            'limit' => 5
        ), $atts);

        $coupons = $this->generate_coupons($atts['network'], $atts['category'], intval($atts['limit']));
        ob_start();
        ?>
        <div class="affiliate-coupon-vault">
            <h3>Exclusive Deals for You!</h3>
            <?php foreach ($coupons as $coupon): ?>
                <div class="coupon-item">
                    <h4><?php echo esc_html($coupon['title']); ?></h4>
                    <p>Code: <strong><?php echo esc_html($coupon['code']); ?></strong></p>
                    <p>Discount: <?php echo esc_html($coupon['discount']); ?></p>
                    <a href="<?php echo esc_url($coupon['link']); ?}" target="_blank" class="coupon-btn" rel="nofollow">Shop Now & Save</a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_coupons($network, $category, $limit) {
        // Demo coupons - Premium version integrates real APIs
        $demo_coupons = array(
            array(
                'title' => '50% Off Wireless Headphones',
                'code' => 'SAVE50',
                'discount' => '50%',
                'link' => 'https://example.com/affiliate-amazon-headphones?ref=yourid'
            ),
            array(
                'title' => '20% Off Laptops',
                'code' => 'LAPTOP20',
                'discount' => '20%',
                'link' => 'https://example.com/affiliate-amazon-laptop?ref=yourid'
            ),
            array(
                'title' => 'Free Shipping on Electronics',
                'code' => 'FREESHIP',
                'discount' => 'Free Shipping',
                'link' => 'https://example.com/affiliate-amazon-electronics?ref=yourid'
            )
        );
        return array_slice($demo_coupons, 0, $limit);
    }

    public function admin_menu() {
        add_options_page(
            'Affiliate Coupon Vault',
            'Coupon Vault',
            'manage_options',
            'affiliate-coupon-vault',
            array($this, 'settings_page')
        );
    }

    public function admin_init() {
        register_setting('affiliate_coupon_vault', 'acv_api_keys');
        register_setting('affiliate_coupon_vault', 'acv_affiliate_ids');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('affiliate_coupon_vault');
                do_settings_sections('affiliate_coupon_vault');
                ?>
                <table class="form-table">
                    <tr>
                        <th>Amazon Affiliate ID</th>
                        <td><input type="text" name="acv_affiliate_ids[amazon]" value="<?php echo esc_attr(get_option('acv_affiliate_ids')['amazon'] ?? ''); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Premium</strong> for real-time API integration, analytics, and more networks!</p>
        </div>
        <?php
    }

    public function activate() {
        add_option('acv_affiliate_ids', array());
    }
}

new AffiliateCouponVault();

// Inline CSS
add_action('wp_head', function() { ?>
<style>
.affiliate-coupon-vault { max-width: 600px; margin: 20px 0; }
.coupon-item { background: #f9f9f9; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #0073aa; }
.coupon-btn { background: #ff6600; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block; }
.coupon-btn:hover { background: #e65c00; }
</style>
<?php });

// Inline JS
add_action('wp_footer', function() { ?>
<script>
jQuery(document).ready(function($) {
    $('.coupon-btn').on('click', function() {
        // Track clicks for premium analytics
        console.log('Coupon clicked!');
    });
});
</script>
<?php });