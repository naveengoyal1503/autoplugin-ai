<?php
/*
Plugin Name: Affiliate Coupon Hub
Description: Display and manage exclusive coupons linked to affiliate programs to boost conversions.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Hub.php
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AffiliateCouponHub {
    public function __construct() {
        add_action('admin_menu', array($this, 'ach_add_admin_menu'));
        add_action('admin_init', array($this, 'ach_settings_init'));
        add_shortcode('affiliate_coupons', array($this, 'ach_shortcode_display_coupons'));
        add_action('wp_enqueue_scripts', array($this, 'ach_enqueue_scripts'));
    }

    public function ach_add_admin_menu() {
        add_menu_page('Affiliate Coupon Hub', 'Coupon Hub', 'manage_options', 'affiliate_coupon_hub', array($this, 'ach_options_page'), 'dashicons-tickets-alt');
    }

    public function ach_settings_init() {
        register_setting('achPage', 'ach_settings');

        add_settings_section(
            'ach_achPage_section', 
            __('Manage Coupons', 'wordpress'), 
            null, 
            'achPage'
        );

        add_settings_field(
            'ach_coupons', 
            __('Coupons JSON', 'wordpress'), 
            array($this, 'ach_coupons_render'), 
            'achPage', 
            'ach_achPage_section'
        );
    }

    public function ach_coupons_render() {
        $options = get_option('ach_settings');
        ?>
        <textarea cols='60' rows='10' name='ach_settings[ach_coupons]'><?php echo isset($options['ach_coupons']) ? esc_textarea($options['ach_coupons']) : ''; ?></textarea>
        <p>Enter coupons as JSON array. Each coupon needs: <code>title</code>, <code>code</code>, <code>description</code>, <code>affiliate_url</code></p>
        <pre>[
  {
    "title": "10% off Shoes",
    "code": "SHOES10",
    "description": "Get 10% discount on all shoes.",
    "affiliate_url": "https://affiliate.example.com/?product=shoes&ref=123"
  }
]</pre>
        <?php
    }

    public function ach_options_page() {
        ?>
        <form action='options.php' method='post'>
            <h2>Affiliate Coupon Hub</h2>
            <?php
            settings_fields('achPage');
            do_settings_sections('achPage');
            submit_button();
            ?>
        </form>
        <?php
    }

    public function ach_shortcode_display_coupons() {
        $options = get_option('ach_settings');
        if (empty($options['ach_coupons'])) {
            return '<p>No coupons available at the moment.</p>';
        }

        $coupons = json_decode($options['ach_coupons'], true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($coupons)) {
            return '<p>Invalid coupon data.</p>';
        }

        $output = '<div class="ach-coupons">';
        foreach ($coupons as $coupon) {
            $title = esc_html($coupon['title'] ?? '');
            $code = esc_html($coupon['code'] ?? '');
            $desc = esc_html($coupon['description'] ?? '');
            $affiliate_url = esc_url($coupon['affiliate_url'] ?? '#');

            $output .= '<div class="ach-coupon" style="border:1px solid #ccc;padding:10px;margin-bottom:10px;">';
            $output .= '<h3>' . $title . '</h3>';
            $output .= '<p>' . $desc . '</p>';
            $output .= '<p><strong>Coupon Code: </strong><span style="background:#eee;padding:2px 6px;cursor:pointer;" onclick="navigator.clipboard.writeText(\'' . $code . '\');alert(\'Coupon code copied!\')">' . $code . '</span></p>';
            $output .= '<p><a href="' . $affiliate_url . '" target="_blank" rel="nofollow noopener noreferrer" class="button">Shop Now</a></p>';
            $output .= '</div>';
        }
        $output .= '</div>';

        return $output;
    }

    public function ach_enqueue_scripts() {
        wp_enqueue_style('ach-style', plugins_url('/style.css', __FILE__)); // Placeholder if CSS is added
    }
}

new AffiliateCouponHub();