/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupons, exclusive deals, and discount codes to boost conversions and commissions.
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affiliate_coupon_vault', array($this, 'coupon_shortcode'));
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
        add_settings_section('main_section', 'Coupon Settings', null, 'affiliate_coupon_vault');
        add_settings_field('coupons', 'Coupons', array($this, 'coupons_field'), 'affiliate_coupon_vault', 'main_section');
    }

    public function coupons_field() {
        $settings = get_option('affiliate_coupon_vault_settings', array('coupons' => array(
            array('code' => 'SAVE10', 'description' => '10% off on all products', 'afflink' => '#', 'image' => ''),
        )));
        $coupons = $settings['coupons'];
        echo '<table id="coupons-table" class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Code</th><th>Description</th><th>Affiliate Link</th><th>Image URL</th><th>Action</th></tr></thead><tbody>';
        foreach ($coupons as $index => $coupon) {
            echo '<tr>';
            echo '<td><input type="text" name="settings[coupons][' . $index . '][code]" value="' . esc_attr($coupon['code']) . '" /></td>';
            echo '<td><input type="text" name="settings[coupons][' . $index . '][description]" value="' . esc_attr($coupon['description']) . '" /></td>';
            echo '<td><input type="url" name="settings[coupons][' . $index . '][afflink]" value="' . esc_attr($coupon['afflink']) . '" /></td>';
            echo '<td><input type="url" name="settings[coupons][' . $index . '][image]" value="' . esc_attr($coupon['image']) . '" /></td>';
            echo '<td><button type="button" class="button remove-coupon">Remove</button></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '<p><button type="button" id="add-coupon" class="button">Add Coupon</button></p>';
        echo '<script>jQuery(document).ready(function($){let index=' . count($coupons) . '; $("#add-coupon").click(function(){let row="<tr><td><input type=\"text\" name=\"settings[coupons]["+index+"][code]\" /></td><td><input type=\"text\" name=\"settings[coupons]["+index+"][description]\" /></td><td><input type=\"url\" name=\"settings[coupons]["+index+"][afflink]\" /></td><td><input type=\"url\" name=\"settings[coupons]["+index+"][image]\" /></td><td><button type=\"button\" class=\"button remove-coupon\">Remove</button></td></tr>"; $("#coupons-table tbody").append(row); index++;}); $(".remove-coupon").click(function(){$(this).closest("tr").remove();});});</script>';
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
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts);
        $settings = get_option('affiliate_coupon_vault_settings', array('coupons' => array()));
        $coupons = $settings['coupons'];
        $limit = min((int)$atts['limit'], count($coupons));
        if (empty($coupons) || $limit < 1) return '';

        $output = '<div class="affiliate-coupon-vault">';
        for ($i = 0; $i < $limit; $i++) {
            if (!isset($coupons[$i])) break;
            $coupon = $coupons[$i];
            $image = !empty($coupon['image']) ? '<img src="' . esc_url($coupon['image']) . '" alt="' . esc_attr($coupon['description']) . '" />' : '';
            $output .= '<div class="coupon-item">'
                     . $image
                     . '<h3>' . esc_html($coupon['code']) . '</h3>'
                     . '<p>' . esc_html($coupon['description']) . '</p>'
                     . '<a href="' . esc_url($coupon['afflink']) . '" target="_blank" class="coupon-button" rel="nofollow">Get Deal</a>'
                     . '</div>';
        }
        $output .= '</div>';
        return $output;
    }

    public function activate() {
        add_option('affiliate_coupon_vault_settings', array('coupons' => array(
            array('code' => 'WELCOME20', 'description' => '20% off first purchase', 'afflink' => 'https://example.com/aff', 'image' => ''),
        )));
    }
}

new AffiliateCouponVault();

/* Pro Notice */
function affiliate_coupon_vault_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Upgrade to <strong>Affiliate Coupon Vault Pro</strong> for unlimited coupons, analytics, and premium integrations! <a href="https://example.com/pro" target="_blank">Get Pro</a></p></div>';
}
add_action('admin_notices', 'affiliate_coupon_vault_pro_notice');

/* Styles */
function affiliate_coupon_vault_styles() {
    echo '<style>.affiliate-coupon-vault{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;margin:20px 0;}.coupon-item{background:#fff;border:2px solid #0073aa;border-radius:10px;padding:20px;text-align:center;box-shadow:0 4px 8px rgba(0,0,0,0.1);}.coupon-item h3{font-size:2em;color:#0073aa;margin:0 0 10px;font-weight:bold;text-transform:uppercase;}.coupon-item p{margin:10px 0;}.coupon-button{display:inline-block;background:#0073aa;color:#fff;padding:12px 24px;text-decoration:none;border-radius:5px;font-weight:bold;transition:background 0.3s;}.coupon-button:hover{background:#005a87;}</style>';
}
add_action('wp_head', 'affiliate_coupon_vault_styles');
add_action('admin_head', 'affiliate_coupon_vault_styles');

/* JS for interactions */
function affiliate_coupon_vault_js() {
    echo '<script>jQuery(document).ready(function($){.coupon-button.click(function(){$(this).html("Copied!");setTimeout(()=>{$(this).html("Get Deal");},2000);});});</script>';
}
add_action('wp_footer', 'affiliate_coupon_vault_js');