/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generate, manage, and display exclusive affiliate coupons to boost conversions and revenue.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateCouponVault {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('acv_coupons', array($this, 'coupons_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page(
            'Affiliate Coupon Vault',
            'Coupon Vault',
            'manage_options',
            'affiliate-coupon-vault',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        if (isset($_POST['acv_save'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', "Coupon1|20% OFF|amazon.com/product1|Your Affiliate Link\nCoupon2|Buy 1 Get 1|shopify.com/deal|Your Affiliate Link");
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th scope="row">Coupons (Format: Name|Discount|Brand|Affiliate Link)</th>
                        <td><textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button('Save Coupons', 'primary', 'acv_save'); ?>
            </form>
            <p>Use shortcode <code>[acv_coupons]</code> to display coupons on any page/post.</p>
            <p><strong>Upgrade to Pro</strong> for unlimited coupons, auto-expiration, analytics, and API integrations!</p>
        </div>
        <?php
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts);
        $coupons_str = get_option('acv_coupons', '');
        if (empty($coupons_str)) return '<p>No coupons configured. <a href="' . admin_url('options-general.php?page=affiliate-coupon-vault') . '">Set up now</a>.</p>';

        $coupons = explode('\n', $coupons_str);
        $html = '<div class="acv-vault">';
        shuffle($coupons);
        $count = 0;
        foreach ($coupons as $coupon) {
            if ($count >= $atts['limit']) break;
            $parts = explode('|', trim($coupon));
            if (count($parts) == 4) {
                $html .= '<div class="acv-coupon">';
                $html .= '<h3>' . esc_html($parts) . '</h3>';
                $html .= '<p>' . esc_html($parts[1]) . ' at ' . esc_html($parts[2]) . '</p>';
                $html .= '<a href="' . esc_url($parts[3]) . '" target="_blank" class="acv-button" rel="nofollow">Grab Deal</a>';
                $html .= '</div>';
                $count++;
            }
        }
        $html .= '</div>';
        $html .= '<p class="acv-pro-upsell"><strong>Pro:</strong> More coupons, tracking & automation! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p>';
        return $html;
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', "Welcome|50% OFF First Month|YourBrand|https://youraffiliatelink.com\nExclusive|Free Trial|PartnerSite|https://partnerlink.com");
        }
    }
}

// Create style.css content (inline for single file)
add_action('wp_head', function() { ?>
<style>
.acv-vault { display: flex; flex-wrap: wrap; gap: 15px; padding: 20px; background: #f9f9f9; border-radius: 8px; }
.acv-coupon { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); flex: 1 1 300px; text-align: center; }
.acv-coupon h3 { color: #e74c3c; margin: 0 0 10px; }
.acv-button { display: inline-block; background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; }
.acv-button:hover { background: #219a52; }
.acv-pro-upsell { text-align: center; margin-top: 20px; padding: 15px; background: #3498db; color: white; border-radius: 5px; }
</style>
<?php });

// Create script.js content (inline)
add_action('wp_footer', function() { ?>
<script>jQuery(document).ready(function($) { $('.acv-coupon').on('mouseenter', function() { $(this).addClass('hover'); }).on('mouseleave', function() { $(this).removeClass('hover'); }); });</script>
<?php });

AffiliateCouponVault::get_instance();

// Pro upsell nag
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault:</strong> Unlock Pro features like analytics and unlimited coupons! <a href="https://example.com/pro">Learn more</a></p></div>';
});