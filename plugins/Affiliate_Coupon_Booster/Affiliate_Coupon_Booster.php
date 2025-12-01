<?php
/*
Plugin Name: Affiliate Coupon Booster
Description: Create and display affiliate coupon offers with tracking and conversion boosting features.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Booster.php
*/

if (!defined('ABSPATH')) exit;

class AffiliateCouponBooster {
    private $coupons_option = 'acb_coupons_list';

    public function __construct() {
        add_action('admin_menu', [$this, 'acb_add_admin_menu']);
        add_action('admin_init', [$this, 'acb_settings_init']);
        add_shortcode('acb_coupons', [$this, 'acb_coupons_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'acb_enqueue_scripts']);
    }

    public function acb_add_admin_menu() {
        add_menu_page('Affiliate Coupon Booster', 'Coupon Booster', 'manage_options', 'affiliate_coupon_booster', [$this, 'acb_options_page']);
    }

    public function acb_settings_init() {
        register_setting('acb_plugin', $this->coupons_option);
        add_settings_section('acb_plugin_section', __('Coupon Settings', 'acb'), null, 'acb_plugin');

        add_settings_field(
            'acb_coupons_field',
            __('Coupons List (JSON Array)', 'acb'),
            [$this, 'acb_coupons_field_render'],
            'acb_plugin',
            'acb_plugin_section'
        );
    }

    public function acb_coupons_field_render() {
        $coupons = get_option($this->coupons_option, '[]');
        echo '<textarea cols="60" rows="10" name="'.$this->coupons_option.'">'.esc_textarea($coupons).'</textarea>';
        echo '<p class="description">Enter coupons as a JSON array. Example: [{"code":"SAVE20","description":"20% off","url":"https://example.com?affid=123"}]</p>';
    }

    public function acb_options_page() {
        ?>
        <form action='options.php' method='post'>
            <h1>Affiliate Coupon Booster Settings</h1>
            <?php
            settings_fields('acb_plugin');
            do_settings_sections('acb_plugin');
            submit_button();
            ?>
        </form>
        <?php
    }

    public function acb_coupons_shortcode($atts) {
        $coupons_raw = get_option($this->coupons_option, '[]');
        $coupons = json_decode($coupons_raw, true);
        if (!is_array($coupons) || count($coupons) === 0) {
            return '<p>No coupons found.</p>';
        }

        $output = '<div class="acb-coupons">';
        foreach ($coupons as $coupon) {
            $code = isset($coupon['code']) ? esc_html($coupon['code']) : '';
            $desc = isset($coupon['description']) ? esc_html($coupon['description']) : '';
            $url = isset($coupon['url']) ? esc_url($coupon['url']) : '#';

            $output .= '<div class="acb-coupon">';
            $output .= '<p><strong>Coupon: <span class="acb-coupon-code">' . $code . '</span></strong></p>';
            $output .= '<p class="acb-coupon-desc">' . $desc . '</p>';
            $output .= '<p><a href="' . $url . '" target="_blank" rel="nofollow noopener noreferrer" class="acb-coupon-link" data-code="' . $code . '">Get Deal</a></p>';
            $output .= '</div>';
        }
        $output .= '</div>';

        return $output;
    }

    public function acb_enqueue_scripts() {
        wp_enqueue_style('acb-style', plugin_dir_url(__FILE__) . 'acb-style.css', [], '1.0');
        wp_enqueue_script('acb-script', plugin_dir_url(__FILE__) . 'acb-script.js', ['jquery'], '1.0', true);
    }
}

new AffiliateCouponBooster();

// Minimal inline CSS and JS for self-containment
add_action('wp_head', function() {
    echo "<style>.acb-coupons {border:1px solid #ccc; padding:10px; max-width:400px; background:#f9f9f9;} .acb-coupon {margin-bottom:15px; border-bottom:1px dashed #ddd; padding-bottom:10px;} .acb-coupon-code {font-family: monospace; color:#d6336c;} .acb-coupon-link {background:#d6336c; color:#fff; padding:6px 12px; text-decoration:none; border-radius:4px;} .acb-coupon-link:hover {background:#b52a5e;}</style>";
});

// Track coupon code clicks with a simple console log (extendable)
add_action('wp_footer', function() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('.acb-coupon-link').forEach(function(link) {
        link.addEventListener('click', function() {
          var code = this.getAttribute('data-code');
          console.log('Coupon clicked:', code);
          // Extend here to add AJAX tracking or analytics events
        });
      });
    });
    </script>
    <?php
});
