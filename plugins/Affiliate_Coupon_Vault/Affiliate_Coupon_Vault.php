/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with personalized discount codes to boost conversions and commissions.
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
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
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

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('affiliate_coupon_vault_options', 'affiliate_coupon_vault_settings');
        add_settings_section('main_section', 'Main Settings', null, 'affiliate-coupon-vault');
        add_settings_field('coupons', 'Coupons', array($this, 'coupons_field'), 'affiliate-coupon-vault', 'main_section');
    }

    public function coupons_field() {
        $settings = get_option('affiliate_coupon_vault_settings', array());
        $coupons = isset($settings['coupons']) ? $settings['coupons'] : array(
            array('name' => 'Sample Product', 'code' => 'SAVE20', 'afflink' => '#', 'desc' => '20% off')
        );
        echo '<div id="coupons-container">';
        foreach ($coupons as $index => $coupon) {
            echo '<div class="coupon-row">';
            echo '<input type="text" name="affiliate_coupon_vault_settings[coupons][' . $index . '][name]" value="' . esc_attr($coupon['name']) . '" placeholder="Product Name" />';
            echo '<input type="text" name="affiliate_coupon_vault_settings[coupons][' . $index . '][code]" value="' . esc_attr($coupon['code']) . '" placeholder="Discount Code" />';
            echo '<input type="url" name="affiliate_coupon_vault_settings[coupons][' . $index . '][afflink]" value="' . esc_attr($coupon['afflink']) . '" placeholder="Affiliate Link" />';
            echo '<textarea name="affiliate_coupon_vault_settings[coupons][' . $index . '][desc]" placeholder="Description">' . esc_textarea($coupon['desc']) . '</textarea>';
            echo '<button type="button" class="remove-coupon">Remove</button>';
            echo '</div>';
        }
        echo '</div>';
        echo '<button type="button" id="add-coupon">Add Coupon</button>';
        echo '<script>var couponIndex = ' . count($coupons) . ';</script>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('affiliate_coupon_vault_options');
                do_settings_sections('affiliate_coupon_vault');
                submit_button();
                ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics, auto-expiry, and premium integrations for $49/year!</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $settings = get_option('affiliate_coupon_vault_settings', array());
        $coupons = isset($settings['coupons']) ? $settings['coupons'] : array();
        if (!isset($coupons[$atts['id']])) {
            return '';
        }
        $coupon = $coupons[$atts['id']];
        $unique_code = $coupon['code'] . '-' . uniqid();
        ob_start();
        ?>
        <div class="affiliate-coupon-vault" data-unique="<?php echo esc_attr($unique_code); ?>">
            <h3><?php echo esc_html($coupon['name']); ?></h3>
            <p><?php echo esc_html($coupon['desc']); ?></p>
            <div class="coupon-code"><?php echo esc_html($unique_code); ?></div>
            <a href="<?php echo esc_url($coupon['afflink']); ?}" class="coupon-button" target="_blank">Get Deal Now (Affiliate Link)</a>
            <small>Exclusive coupon generated for you!</small>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('affiliate_coupon_vault_settings', array());
    }
}

AffiliateCouponVault::get_instance();

// Inline CSS
add_action('wp_head', function() { ?>
<style>
.affiliate-coupon-vault { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; border-radius: 10px; text-align: center; }
.coupon-code { background: #ffeb3b; font-size: 24px; font-weight: bold; padding: 10px; margin: 10px 0; display: block; }
.coupon-button { background: #0073aa; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; }
.coupon-button:hover { background: #005a87; }
#coupons-container .coupon-row { border: 1px solid #ddd; padding: 10px; margin: 10px 0; }
#coupons-container input, #coupons-container textarea { width: 20%; margin: 5px; }
#add-coupon, .remove-coupon { background: #0073aa; color: white; border: none; padding: 5px 10px; cursor: pointer; }
</style>
<?php });

// Inline JS
add_action('wp_footer', function() { ?>
<script>
jQuery(document).ready(function($) {
    $('#add-coupon').click(function() {
        var html = '<div class="coupon-row">' +
            '<input type="text" name="affiliate_coupon_vault_settings[coupons][' + couponIndex + '][name]" placeholder="Product Name" />' +
            '<input type="text" name="affiliate_coupon_vault_settings[coupons][' + couponIndex + '][code]" placeholder="Discount Code" />' +
            '<input type="url" name="affiliate_coupon_vault_settings[coupons][' + couponIndex + '][afflink]" placeholder="Affiliate Link" />' +
            '<textarea name="affiliate_coupon_vault_settings[coupons][' + couponIndex + '][desc]" placeholder="Description"></textarea>' +
            '<button type="button" class="remove-coupon">Remove</button>' +
            '</div>';
        $('#coupons-container').append(html);
        couponIndex++;
    });
    $(document).on('click', '.remove-coupon', function() {
        $(this).closest('.coupon-row').remove();
    });
});
</script>
<?php });