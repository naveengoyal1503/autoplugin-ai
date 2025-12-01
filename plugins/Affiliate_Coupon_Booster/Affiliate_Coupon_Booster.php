<?php
/*
Plugin Name: Affiliate Coupon Booster
Description: Automated coupon aggregator and affiliate link manager to increase affiliate conversions.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Booster.php
*/

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateCouponBooster {

    public function __construct() {
        add_action('admin_menu', array($this, 'acb_add_admin_page'));
        add_action('admin_init', array($this, 'acb_settings_init'));
        add_shortcode('affiliate_coupons', array($this, 'acb_display_coupons'));
        add_action('wp_enqueue_scripts', array($this, 'acb_enqueue_scripts'));
    }

    public function acb_add_admin_page() {
        add_menu_page(
            'Affiliate Coupons',
            'Affiliate Coupons',
            'manage_options',
            'affiliate-coupon-booster',
            array($this, 'acb_admin_page'),
            'dashicons-tickets',
            58
        );
    }

    public function acb_settings_init() {
        register_setting('acbSettingsGroup', 'acb_coupons');

        add_settings_section(
            'acb_section_main',
            'Manage Coupons',
            null,
            'affiliate-coupon-booster'
        );

        add_settings_field(
            'acb_field_coupons',
            'Coupons Data (JSON array)',
            array($this, 'acb_field_coupons_render'),
            'affiliate-coupon-booster',
            'acb_section_main'
        );
    }

    public function acb_field_coupons_render() {
        $data = get_option('acb_coupons', '[]');
        echo '<textarea name="acb_coupons" rows="10" cols="60" placeholder="[ {\"title\": \"10% Off\", \"code\": \"SAVE10\", \"link\": \"https://affiliate.example.com/product?ref=123\"}, {...} ]">'.esc_textarea($data).'</textarea>';
        echo '<p>Enter a JSON array of coupons with fields: title, code, link (affiliate link). Example:<br>[{"title":"10% Off","code":"SAVE10","link":"https://affiliate.example.com/product?ref=123"}]</p>';
    }

    public function acb_admin_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Booster</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('acbSettingsGroup');
                do_settings_sections('affiliate-coupon-booster');
                submit_button();
                ?>
            </form>
            <h2>Usage</h2>
            <p>Place the shortcode <code>[affiliate_coupons]</code> in any post or page to display your coupons attractively linked to your affiliate URLs.</p>
        </div>
        <?php
    }

    public function acb_display_coupons() {
        $coupons_json = get_option('acb_coupons', '[]');
        $coupons = json_decode($coupons_json, true);

        if (empty($coupons) || !is_array($coupons)) {
            return '<p>No coupons available currently.</p>';
        }

        $output = '<div class="acb-coupon-list" style="display:flex; flex-wrap:wrap; gap:15px;">';

        foreach ($coupons as $coupon) {
            $title = isset($coupon['title']) ? esc_html($coupon['title']) : 'Coupon';
            $code = isset($coupon['code']) ? esc_html($coupon['code']) : '';
            $link = isset($coupon['link']) ? esc_url($coupon['link']) : '#';

            $output .= '<div class="acb-coupon" style="border:1px solid #ddd; padding:15px; width:250px; box-shadow:0 3px 6px rgba(0,0,0,0.1);">';
            $output .= '<h3 style="margin-top:0;">'. $title .'</h3>';
            if ($code) {
                $output .= '<p><strong>Code:</strong> <span class="acb-coupon-code" style="user-select:all; background:#f7f7f7; padding:3px 6px; border-radius:3px; cursor:pointer;" title="Click to copy">'. $code .'</span></p>';
            }
            $output .= '<a href="'. $link .'" target="_blank" rel="nofollow noopener" class="acb-coupon-link" style="display:inline-block; background:#0073aa; color:#fff; padding:8px 12px; text-decoration:none; border-radius:4px;">Use Coupon</a>';
            $output .= '</div>';
        }

        $output .= '</div>';
        $output .= '<script>document.addEventListener("DOMContentLoaded", function(){
            var codes = document.querySelectorAll(".acb-coupon-code");
            codes.forEach(function(codeElem){
                codeElem.addEventListener("click", function(){
                    navigator.clipboard.writeText(codeElem.textContent).then(function(){
                        alert("Coupon code copied: " + codeElem.textContent);
                    });
                });
            });
        });</script>';

        return $output;
    }

    public function acb_enqueue_scripts() {
        // Minimal inline styles are used to ease self-containment
    }
}

new AffiliateCouponBooster();