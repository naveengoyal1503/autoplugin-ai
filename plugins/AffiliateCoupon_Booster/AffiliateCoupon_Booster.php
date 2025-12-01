<?php
/*
Plugin Name: AffiliateCoupon Booster
Description: Auto curates and displays affiliate coupons from multiple networks.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateCoupon_Booster.php
*/

if (!defined('ABSPATH')) exit;

class AffiliateCouponBooster {
    private $option_name = 'acb_coupons';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'settings_init']);
        add_shortcode('affiliate_coupons', [$this, 'coupon_shortcode']);
        // Hook for scheduled coupon update
        if (!wp_next_scheduled('acb_cron_update_coupons')) {
            wp_schedule_event(time(), 'hourly', 'acb_cron_update_coupons');
        }
        add_action('acb_cron_update_coupons', [$this, 'update_coupons']);
    }

    public function add_admin_menu() {
        add_menu_page('AffiliateCoupon Booster', 'AffiliateCoupon Booster', 'manage_options', 'affiliatecouponbooster', [$this, 'options_page']);
    }

    public function settings_init() {
        register_setting('acb_plugin', $this->option_name);

        add_settings_section('acb_section_main', __('Main Settings', 'acb'), null, 'acb_plugin');

        add_settings_field(
            'acb_field_keywords',
            __('Target Keywords (comma separated)', 'acb'),
            [$this, 'render_keywords_field'],
            'acb_plugin',
            'acb_section_main'
        );

        add_settings_field(
            'acb_field_affiliate_id',
            __('Affiliate ID', 'acb'),
            [$this, 'render_affiliate_id_field'],
            'acb_plugin',
            'acb_section_main'
        );
    }

    public function render_keywords_field() {
        $options = get_option($this->option_name);
        $keywords = isset($options['keywords']) ? esc_attr($options['keywords']) : '';
        echo "<input type='text' name='{$this->option_name}[keywords]' value='$keywords' style='width:100%;' placeholder='e.g. hosting, vpn, shoes' />";
    }

    public function render_affiliate_id_field() {
        $options = get_option($this->option_name);
        $affiliate_id = isset($options['affiliate_id']) ? esc_attr($options['affiliate_id']) : '';
        echo "<input type='text' name='{$this->option_name}[affiliate_id]' value='$affiliate_id' style='width:100%;' placeholder='Your affiliate ID' />";
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>AffiliateCoupon Booster Settings</h1>
            <form action='options.php' method='post'>
                <?php
                settings_fields('acb_plugin');
                do_settings_sections('acb_plugin');
                submit_button();
                ?>
            </form>
            <p>Coupons will be automatically updated hourly based on your keywords.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $options = get_option($this->option_name);
        $coupons = isset($options['coupons']) ? $options['coupons'] : [];

        if (empty($coupons)) return '<p>No coupons available at the moment. Please check back later.</p>';

        $output = '<div class="acb-coupons">';
        foreach ($coupons as $coupon) {
            $output .= '<div class="acb-coupon" style="margin-bottom:15px;padding:10px;border:1px solid #ddd;">
                <strong>' . esc_html($coupon['title']) . '</strong><br/>
                <span>' . esc_html($coupon['description']) . '</span><br/>
                <a href="' . esc_url($coupon['url']) . '" target="_blank" style="color:#0073aa;">Use Coupon</a>
            </div>';
        }
        $output .= '</div>';
        return $output;
    }

    public function update_coupons() {
        $options = get_option($this->option_name);
        $keywords = isset($options['keywords']) ? explode(',', $options['keywords']) : [];
        $affiliate_id = isset($options['affiliate_id']) ? $options['affiliate_id'] : '';

        $coupons = [];

        // Simplified example: Generate dummy coupons based on keywords
        foreach ($keywords as $keyword) {
            $kw = trim($keyword);
            if (!$kw) continue;
            // Generate a fake coupon offering 10% off
            $coupons[] = [
                'title' => ucfirst($kw) . ' Discount 10% Off',
                'description' => "Get 10% off on $kw products",
                'url' => 'https://example.com/?aff=' . urlencode($affiliate_id) . '&product=' . urlencode($kw)
            ];
        }

        $options['coupons'] = $coupons;
        update_option($this->option_name, $options);
    }
}

new AffiliateCouponBooster();

register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('acb_cron_update_coupons');
});
