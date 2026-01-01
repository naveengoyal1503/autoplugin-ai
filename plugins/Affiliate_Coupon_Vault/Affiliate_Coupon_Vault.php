/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons to boost conversions and commissions.
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
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('aff-coupon-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('aff-coupon-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate' => 'default',
            'code' => 'SAVE10',
            'link' => 'https://affiliate-link.com',
            'expiry' => date('Y-m-d', strtotime('+30 days')),
            'description' => 'Get 10% off your purchase!'
        ), $atts);

        $output = '<div class="aff-coupon-vault">';
        $output .= '<div class="coupon-code">' . esc_html($atts['code']) . '</div>';
        $output .= '<p>' . esc_html($atts['description']) . '</p>';
        $output .= '<a href="' . esc_url($atts['link']) . '" class="coupon-btn" target="_blank">Redeem Now</a>';
        $output .= '<small>Expires: ' . esc_html($atts['expiry']) . '</small>';
        $output .= '</div>';

        return $output;
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

    public function admin_init() {
        register_setting('aff_coupon_settings', 'aff_coupon_options');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('aff_coupon_settings');
                do_settings_sections('aff_coupon_settings');
                ?>
                <table class="form-table">
                    <tr>
                        <th>Default Affiliate Link</th>
                        <td><input type="url" name="aff_coupon_options[default_link]" value="<?php echo esc_attr(get_option('aff_coupon_options')['default_link'] ?? ''); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics, auto-generation, and custom branding for $49/year!</p>
        </div>
        <?php
    }

    public function activate() {
        add_option('aff_coupon_pro', false);
    }
}

new AffiliateCouponVault();

// Pro upsell notice
function aff_coupon_admin_notice() {
    if (!get_option('aff_coupon_pro')) {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Upgrade for unlimited coupons and analytics! <a href="https://example.com/pro">Get Pro Now</a></p></div>';
    }
}
add_action('admin_notices', 'aff_coupon_admin_notice');

// Assets (base64 or inline for single file)
/*
<style>
.aff-coupon-vault { border: 2px dashed #0073aa; padding: 20px; text-align: center; background: #f9f9f9; border-radius: 10px; max-width: 300px; }
.coupon-code { font-size: 2em; font-weight: bold; color: #0073aa; background: white; padding: 10px; border-radius: 5px; display: inline-block; margin-bottom: 10px; }
.coupon-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
.coupon-btn:hover { background: #005a87; }
</style>
<script>
jQuery(document).ready(function($) {
    $('.coupon-btn').on('click', function() {
        $(this).text('Copied! Redeem at link.');
        var code = $(this).siblings('.coupon-code').text();
        navigator.clipboard.writeText(code);
    });
});
</script>
*/

// Inline CSS and JS for single file

function aff_coupon_inline_assets() {
    if (is_admin()) return;
    ?>
    <style>
    .aff-coupon-vault { border: 2px dashed #0073aa; padding: 20px; text-align: center; background: #f9f9f9; border-radius: 10px; max-width: 300px; margin: 20px auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .coupon-code { font-size: 2.5em; font-weight: bold; color: #0073aa; background: white; padding: 15px 25px; border-radius: 8px; display: inline-block; margin-bottom: 15px; letter-spacing: 3px; font-family: monospace; }
    .aff-coupon-vault p { margin: 10px 0; font-size: 1.1em; }
    .coupon-btn { background: linear-gradient(135deg, #0073aa, #00a0d2); color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; display: inline-block; font-weight: bold; transition: all 0.3s; box-shadow: 0 4px 15px rgba(0,115,170,0.3); }
    .coupon-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,115,170,0.4); background: linear-gradient(135deg, #005a87, #0073aa); }
    .aff-coupon-vault small { color: #666; display: block; margin-top: 10px; }
    </style>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('.coupon-btn').on('click', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var code = $btn.closest('.aff-coupon-vault').find('.coupon-code').text();
            navigator.clipboard.writeText(code).then(function() {
                $btn.text('Copied! Visit link to redeem.');
                setTimeout(function() {
                    window.open($btn.attr('href'), '_blank');
                }, 1000);
            });
        });
    });
    </script>
    <?php
}
add_action('wp_head', 'aff_coupon_inline_assets');