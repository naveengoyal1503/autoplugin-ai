/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupons with custom promo codes to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_shortcode('affiliate_coupon', [$this, 'coupon_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_menu', [$this, 'admin_menu']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', [$this, 'admin_init']);
        }
    }

    public function activate() {
        add_option('acv_coupons', []);
    }

    public function enqueue_scripts() {
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'style.css', [], '1.0');
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'script.js', ['jquery'], '1.0', true);
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', [$this, 'settings_page']);
    }

    public function admin_init() {
        register_setting('acv_settings', 'acv_coupons');
        add_settings_section('acv_main', 'Coupons', null, 'acv-settings');
        add_settings_field('acv_coupons_list', 'Add Coupon', [$this, 'coupons_field'], 'acv-settings', 'acv_main');
    }

    public function coupons_field() {
        $coupons = get_option('acv_coupons', []);
        echo '<table class="form-table"><tr><th>Add New Coupon</th><td>';
        echo '<input type="text" name="acv_coupons[title][]" placeholder="Coupon Title" /><br>';
        echo '<input type="text" name="acv_coupons[afflink][]" placeholder="Affiliate Link" /><br>';
        echo '<input type="text" name="acv_coupons[code][]" placeholder="Promo Code (e.g., SAVE20)" /><br>';
        echo '<input type="number" name="acv_coupons[discount][]" placeholder="Discount %" step="0.01" /><br>';
        echo '<button type="button" class="button add-coupon">Add Coupon</button></td></tr>';
        echo '<tr><th>Existing Coupons</th><td>';
        foreach ($coupons as $index => $coupon) {
            echo '<div class="coupon-row">';
            echo '<input type="hidden" name="acv_coupons[title][' . $index . ']" value="' . esc_attr($coupon['title']) . '" />';
            echo esc_html($coupon['title']) . ' | ';
            echo '<a href="' . esc_url($coupon['afflink']) . '" target="_blank">' . esc_html($coupon['afflink']) . '</a> | Code: ' . esc_html($coupon['code']) . ' (' . $coupon['discount'] . '%) ';
            echo '<button type="button" class="button delete-coupon">Delete</button><br>';
            echo '</div>';
        }
        echo '</td></tr></table>';
        echo '<script> jQuery(".add-coupon").click(function(){jQuery(this).before("<div><input type=\"text\" name=\"acv_coupons[title][]\" placeholder=\"Title\" /><input type=\"text\" name=\"acv_coupons[afflink][]\" placeholder=\"Link\" /><input type=\"text\" name=\"acv_coupons[code][]\" placeholder=\"Code\" /><input type=\"number\" name=\"acv_coupons[discount][]\" placeholder=\"Discount\" /><br></div>");}); jQuery(".delete-coupon").click(function(){jQuery(this).parent().remove();}); </script>';
    }

    public function settings_page() {
        ?><div class="wrap"><h1>Affiliate Coupon Vault Settings</h1><form method="post" action="options.php"> <?php
        settings_fields('acv_settings'); do_settings_sections('acv-settings'); submit_button(); ?></form></div><?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(['id' => 0], $atts);
        $coupons = get_option('acv_coupons', []);
        if (!isset($coupons[$atts['id']])) return '';
        $coupon = $coupons[$atts['id']];
        $unique_code = $coupon['code'] . '-' . uniqid();

        ob_start();
        ?>
        <div class="acv-coupon" data-afflink="<?php echo esc_url($coupon['afflink']); ?>">
            <h3><?php echo esc_html($coupon['title']); ?></h3>
            <p><strong>Code: <span class="coupon-code"><?php echo esc_html($unique_code); ?></strong></span></p>
            <p><strong><?php echo $coupon['discount']; ?>% OFF!</strong> Limited time.</p>
            <a href="#" class="button acv-copy-code">Copy Code</a>
            <a href="<?php echo esc_url($coupon['afflink']); ?>" class="button acv-claim" target="_blank">Claim Deal</a>
        </div>
        <style>
        .acv-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; text-align: center; }
        .coupon-code { background: #fff; padding: 5px 10px; font-family: monospace; font-size: 1.2em; color: #d63638; }
        .acv-copy-code, .acv-claim { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; margin: 0 10px; border-radius: 5px; }
        .acv-claim:hover { background: #005a87; }
        </style>
        <script>jQuery(function($){ $('.acv-copy-code').click(function(e){e.preventDefault(); var code = $(this).siblings('.coupon-code').text(); navigator.clipboard.writeText(code).then(function(){$(this).text('Copied!');}.bind(this)); }); $('.acv-claim').click(function(){ gtag && gtag('event', 'claim_coupon'); }); });</script>
        <?php
        return ob_get_clean();
    }
}

new AffiliateCouponVault();

// Pro teaser
function acv_pro_teaser() { if (!get_option('acv_pro')) echo '<div class="notice notice-info"><p>Upgrade to <strong>Affiliate Coupon Vault Pro</strong> for unlimited coupons, analytics & more! <a href="https://example.com/pro" target="_blank">Get Pro</a></p></div>'; }
add_action('admin_notices', 'acv_pro_teaser');

// Minified CSS and JS would be in separate files, but inline for single-file
/* style.css content here if needed */
/* script.js content here if needed */