/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and manages exclusive affiliate coupons, tracks clicks, and boosts conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
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
        load_plugin_textdomain('affiliate-coupon-vault');
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('affiliate_coupon_vault_options', 'affiliate_coupon_vault_settings');
        add_settings_section('coupons_section', 'Coupons', null, 'affiliate-coupon-vault');
        add_settings_field('coupons', 'Coupons', array($this, 'coupons_field'), 'affiliate-coupon-vault', 'coupons_section');
    }

    public function coupons_field() {
        $settings = get_option('affiliate_coupon_vault_settings', array());
        $coupons = isset($settings['coupons']) ? $settings['coupons'] : array();
        echo '<table class="form-table">
                <tr>
                    <th>Add Coupon</th>
                    <td>
                        <input type="text" name="affiliate_coupon_vault_settings[coupons][][code]" placeholder="Coupon Code" /><br>
                        <input type="text" name="affiliate_coupon_vault_settings[coupons][][affiliate_url]" placeholder="Affiliate URL" /><br>
                        <input type="text" name="affiliate_coupon_vault_settings[coupons][][description]" placeholder="Description" /><br>
                        <button type="button" class="button add-coupon">Add Another</button>
                    </td>
                </tr>
              </table>';
        echo '<ul id="existing-coupons">';
        foreach ($coupons as $index => $coupon) {
            echo '<li>
                    Code: <input type="text" name="affiliate_coupon_vault_settings[coupons][' . $index . '][code]" value="' . esc_attr($coupon['code']) . '" />
                    URL: <input type="text" name="affiliate_coupon_vault_settings[coupons][' . $index . '][affiliate_url]" value="' . esc_attr($coupon['affiliate_url']) . '" />
                    Desc: <input type="text" name="affiliate_coupon_vault_settings[coupons][' . $index . '][description]" value="' . esc_attr($coupon['description']) . '" />
                    <button type="button" class="button remove-coupon">Remove</button>
                  </li>';
        }
        echo '</ul>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('affiliate_coupon_vault_options');
                do_settings_sections('affiliate-coupon-vault');
                submit_button();
                ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics, auto-expiration. <a href="https://example.com/pro">Get Pro ($49/year)</a></p>
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
        $click_id = uniqid();
        return '<div class="affiliate-coupon-vault" data-click-id="' . $click_id . '">
                    <h3>Exclusive Deal: ' . esc_html($coupon['description']) . '</h3>
                    <p><strong>Code:</strong> <span class="coupon-code">' . esc_html($coupon['code']) . '</span></p>
                    <a href="' . esc_url($coupon['affiliate_url']) . '" class="coupon-button" target="_blank" rel="nofollow">Get Deal & Track Click</a>
                    <script>console.log("Coupon ' . $click_id . ' displayed");</script>
               </div>';
    }

    public function activate() {
        if (!get_option('affiliate_coupon_vault_settings')) {
            update_option('affiliate_coupon_vault_settings', array('coupons' => array()));
        }
    }
}

AffiliateCouponVault::get_instance();

// Admin JS
if (is_admin()) {
    add_action('admin_footer', function() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('.add-coupon').click(function() {
                var index = $('#existing-coupons li').length;
                $('#existing-coupons').append(
                    '<li>Code: <input type="text" name="affiliate_coupon_vault_settings[coupons][' + index + '][code]" />
                     URL: <input type="text" name="affiliate_coupon_vault_settings[coupons][' + index + '][affiliate_url]" />
                     Desc: <input type="text" name="affiliate_coupon_vault_settings[coupons][' + index + '][description]" />
                     <button type="button" class="button remove-coupon">Remove</button></li>'
                );
            });
            $(document).on('click', '.remove-coupon', function() {
                $(this).parent().remove();
            });
        });
        </script>
        <?php
    });
}

// Frontend JS and CSS placeholders (create assets/script.js and assets/style.css manually or inline)
function acv_inline_scripts() {
    if (!is_admin()) {
        ?>
        <script>jQuery(document).ready(function($) {
            $('.coupon-button').click(function() {
                var clickId = $(this).closest('.affiliate-coupon-vault').data('click-id');
                console.log('Tracked click: ' + clickId);
                // Pro: Send to analytics endpoint
            });
        });</script>
        <style>
        .affiliate-coupon-vault { border: 2px solid #007cba; padding: 20px; margin: 20px 0; background: #f9f9f9; border-radius: 5px; }
        .coupon-code { font-size: 1.5em; color: #007cba; font-weight: bold; }
        .coupon-button { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
        .coupon-button:hover { background: #005a87; }
        </style>
        <?php
    }
}
add_action('wp_footer', 'acv_inline_scripts');