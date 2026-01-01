/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates, manages, and displays personalized affiliate coupon codes and deals to boost conversions and commissions.
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
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('acv_settings', 'acv_options');
        add_settings_section('acv_main', 'Coupon Settings', null, 'acv');
        add_settings_field('acv_coupons', 'Coupons', array($this, 'coupons_field'), 'acv', 'acv_main');
        add_settings_field('acv_pro_notice', 'Go Pro', array($this, 'pro_notice'), 'acv', 'acv_main');
    }

    public function coupons_field() {
        $options = get_option('acv_options', array('coupons' => array(
            array('code' => 'SAVE10', 'desc' => '10% off on all products', 'afflink' => 'https://affiliate-link.com/?ref=10', 'expires' => ''),
            array('code' => 'WELCOME20', 'desc' => '20% off first purchase', 'afflink' => 'https://affiliate-link.com/?ref=20', 'expires' => '2026-12-31')
        )));
        $coupons = $options['coupons'];
        echo '<div id="acv-coupons-list">';
        foreach ($coupons as $index => $coupon) {
            echo '<div class="acv-coupon-row">';
            echo '<input type="text" name="acv_options[coupons][' . $index . '][code]" value="' . esc_attr($coupon['code']) . '" placeholder="Coupon Code" />';
            echo '<input type="text" name="acv_options[coupons][' . $index . '][desc]" value="' . esc_attr($coupon['desc']) . '" placeholder="Description" />';
            echo '<input type="url" name="acv_options[coupons][' . $index . '][afflink]" value="' . esc_attr($coupon['afflink']) . '" placeholder="Affiliate Link" />';
            echo '<input type="date" name="acv_options[coupons][' . $index . '][expires]" value="' . esc_attr($coupon['expires']) . '" />';
            echo '<button type="button" class="acv-remove-coupon">Remove</button>';
            echo '</div>';
        }
        echo '</div>';
        echo '<button type="button" id="acv-add-coupon">Add Coupon</button>';
        echo '<script>var couponIndex = ' . count($coupons) . ';</script>';
    }

    public function pro_notice() {
        echo '<p><strong>Pro Version:</strong> Unlimited coupons, analytics, auto-expiry, and premium integrations. <a href="https://example.com/pro" target="_blank">Upgrade Now ($49/year)</a></p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('acv_settings');
                do_settings_sections('acv');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts);
        $options = get_option('acv_options', array('coupons' => array()));
        $coupons = $options['coupons'];
        $active_coupons = array_filter($coupons, function($c) {
            return empty($c['expires']) || $c['expires'] > date('Y-m-d');
        });
        $display = array_slice($active_coupons, 0, intval($atts['limit']));
        ob_start();
        echo '<div class="acv-coupons-container">';
        foreach ($display as $coupon) {
            echo '<div class="acv-coupon">';
            echo '<h4>' . esc_html($coupon['code']) . '</h4>';
            echo '<p>' . esc_html($coupon['desc']) . '</p>';
            echo '<a href="' . esc_url($coupon['afflink']) . '" class="acv-button" target="_blank">Get Deal</a>';
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    public function activate() {
        add_option('acv_options', array('coupons' => array()));
    }
}

// Enqueue dummy assets (create empty files in real plugin)
function acv_dummy_assets() {
    wp_register_style('acv-style', 'data:text/css,');
    wp_register_script('acv-script', 'data:text/javascript,/* Pro features locked */', array('jquery'), '1.0.0', true);
}
add_action('wp_enqueue_scripts', 'acv_dummy_assets');
add_action('admin_enqueue_scripts', 'acv_dummy_assets');

// Admin JS
add_action('admin_footer', function() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#acv-add-coupon').click(function() {
            var index = window.couponIndex++;
            $('#acv-coupons-list').append(
                '<div class="acv-coupon-row">' +
                '<input type="text" name="acv_options[coupons][" + index + "][code]" placeholder="Coupon Code" />' +
                '<input type="text" name="acv_options[coupons][" + index + "][desc]" placeholder="Description" />' +
                '<input type="url" name="acv_options[coupons][" + index + "][afflink]" placeholder="Affiliate Link" />' +
                '<input type="date" name="acv_options[coupons][" + index + "][expires]" />' +
                '<button type="button" class="acv-remove-coupon">Remove</button>' +
                '</div>'
            );
        });
        $(document).on('click', '.acv-remove-coupon', function() {
            $(this).closest('.acv-coupon-row').remove();
        });
    });
    </script>
    <style>
    .acv-coupon-row { margin-bottom: 10px; }
    .acv-coupon-row input { margin-right: 10px; width: 150px; }
    .acv-coupons-container { display: grid; gap: 15px; }
    .acv-coupon { border: 1px solid #ddd; padding: 15px; border-radius: 5px; background: #f9f9f9; }
    .acv-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block; }
    </style>
    <?php
});

AffiliateCouponVault::get_instance();