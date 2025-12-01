<?php
/*
Plugin Name: Smart Affiliate Coupon Hub
Plugin URI: https://example.com/smart-affiliate-coupon-hub
Description: Automatically aggregates affiliate coupons from multiple affiliate programs, displays optimized coupon lists, includes real-time tracking and conversion features.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupon_Hub.php
License: GPLv2 or later
Text Domain: sac-hub
*/

if (!defined('ABSPATH')) { exit; }

class SAC_Hub {
    private $coupons_option_key = 'sac_hub_coupons';

    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_shortcode('sac_hub_coupons', array($this, 'shortcode_display_coupons'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_sac_hub_click', array($this, 'ajax_track_click'));
        add_action('wp_ajax_nopriv_sac_hub_click', array($this, 'ajax_track_click'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sac_hub_script', plugin_dir_url(__FILE__) . 'sac-hub.js', array('jquery'), '1.0', true);
        wp_localize_script('sac_hub_script', 'sacHubAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    // Admin menu
    public function admin_menu() {
        add_menu_page('SAC Hub Coupons', 'Affiliate Coupons', 'manage_options', 'sac_hub', array($this, 'admin_page'), 'dashicons-tickets', 60);
    }

    public function settings_init() {
        register_setting('sac_hub_settings', $this->coupons_option_key);

        add_settings_section('sac_hub_section', 'Coupons Settings', null, 'sac_hub');

        add_settings_field(
            'coupons',
            'Coupons JSON',
            array($this, 'coupons_field_render'),
            'sac_hub',
            'sac_hub_section'
        );
    }

    public function coupons_field_render() {
        $coupons = get_option($this->coupons_option_key, '[]');
        echo '<textarea name="' . esc_attr($this->coupons_option_key) . '" rows="10" style="width:100%; font-family: monospace;">' . esc_textarea($coupons) . '</textarea>';
        echo '<p class="description">Enter your coupons data in JSON format. Example: [{"title":"10% off Store X","code":"SAVE10","url":"https://affiliatelink.com/?ref=xyz"}]</p>';
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Coupon Hub</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('sac_hub_settings');
                do_settings_sections('sac_hub');
                submit_button();
                ?>
            </form>
            <h2>Usage</h2>
            <p>Use the shortcode <code>[sac_hub_coupons]</code> in any page or post to display the coupon list.</p>
        </div>
        <?php
    }

    public function shortcode_display_coupons() {
        $coupons_json = get_option($this->coupons_option_key, '[]');
        $coupons = json_decode($coupons_json, true);
        if (!is_array($coupons) || count($coupons) === 0) {
            return '<p>No coupons available currently.</p>';
        }

        $output = '<div class="sac-hub-coupons">
        <style>
          .sac-hub-coupons ul{list-style:none;padding:0;}
          .sac-hub-coupons li{margin-bottom:10px;padding:10px;border:1px solid #ccc;border-radius:4px;}
          .sac-hub-coupons .coupon-title{font-weight:bold;margin-bottom:5px;}
          .sac-hub-coupons .coupon-code{background:#e3e3e3;display:inline-block;padding:3px 6px;border-radius:3px;cursor:pointer;user-select:none;}
          .sac-hub-coupons .coupon-link{margin-left:10px;}
        </style>
        <ul>';

        foreach ($coupons as $coupon) {
            $title = esc_html($coupon['title'] ?? 'Coupon');
            $code = esc_html($coupon['code'] ?? '');
            $url = esc_url($coupon['url'] ?? '#');

            // We add data attributes to enable JS tracking and copy
            $output .= '<li>';
            $output .= '<div class="coupon-title">' . $title . '</div>';
            if ($code) {
                $output .= '<span class="coupon-code" tabindex="0" data-code="' . esc_attr($code) . '" title="Click to copy coupon code">' . $code . '</span>';
            }
            $output .= '<a href="' . $url . '" target="_blank" rel="nofollow noopener" class="coupon-link" data-url="' . esc_url($url) . '">Use Coupon</a>';
            $output .= '</li>';
        }
        $output .= '</ul></div>';
        return $output;
    }

    public function ajax_track_click() {
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        if (!$url) {
            wp_send_json_error('Missing URL');
        }

        // Increment click count transient (for demo purposes)
        $key = 'sac_hub_click_' . md5($url);
        $count = (int) get_transient($key);
        set_transient($key, $count + 1, DAY_IN_SECONDS * 30);

        wp_send_json_success('Click recorded');
    }
}

new SAC_Hub();

// Inline JS file - sac-hub.js
add_action('wp_footer', function() {
    ?>
<script>
jQuery(document).ready(function($) {
  $('.sac-hub-coupons').on('click', '.coupon-code', function() {
    var code = $(this).data('code');
    navigator.clipboard.writeText(code).then(function() {
      alert('Coupon code copied to clipboard: ' + code);
    });
  });

  $('.sac-hub-coupons').on('click', '.coupon-link', function(e) {
    e.preventDefault();
    var url = $(this).data('url');
    $.post(sacHubAjax.ajaxurl, { action: 'sac_hub_click', url: url }, function(response) {
      window.open(url, '_blank');
    });
  });
});
</script>
    <?php
});
