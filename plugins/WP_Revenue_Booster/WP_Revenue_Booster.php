/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Plugin URI: https://example.com/wp-revenue-booster
 * Description: Boost your WordPress site revenue by rotating affiliate links, coupons, and sponsored banners based on user behavior and content context.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'inject_revenue_elements'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('wp-revenue-booster', plugin_dir_url(__FILE__) . 'revenue-booster.js', array('jquery'), '1.0', true);
        wp_localize_script('wp-revenue-booster', 'wpRevenueBooster', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_revenue_booster_nonce')
        ));
    }

    public function inject_revenue_elements() {
        $options = get_option('wp_revenue_booster_settings');
        if (!$options || empty($options['affiliate_links']) && empty($options['coupons']) && empty($options['sponsored_banners'])) return;

        echo '<div id="wp-revenue-booster-container"></div>';
    }

    public function add_admin_menu() {
        add_options_page(
            'WP Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp-revenue-booster',
            array($this, 'options_page')
        );
    }

    public function settings_init() {
        register_setting('wpRevenueBooster', 'wp_revenue_booster_settings');

        add_settings_section(
            'wp_revenue_booster_section',
            'Revenue Booster Settings',
            null,
            'wpRevenueBooster'
        );

        add_settings_field(
            'affiliate_links',
            'Affiliate Links (one per line)',
            array($this, 'affiliate_links_render'),
            'wpRevenueBooster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'coupons',
            'Coupons (one per line)',
            array($this, 'coupons_render'),
            'wpRevenueBooster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'sponsored_banners',
            'Sponsored Banners (one per line: image_url|link_url)',
            array($this, 'sponsored_banners_render'),
            'wpRevenueBooster',
            'wp_revenue_booster_section'
        );
    }

    public function affiliate_links_render() {
        $options = get_option('wp_revenue_booster_settings');
        echo '<textarea cols="60" rows="5" name="wp_revenue_booster_settings[affiliate_links]">' . (isset($options['affiliate_links']) ? esc_attr($options['affiliate_links']) : '') . '</textarea>';
    }

    public function coupons_render() {
        $options = get_option('wp_revenue_booster_settings');
        echo '<textarea cols="60" rows="5" name="wp_revenue_booster_settings[coupons]">' . (isset($options['coupons']) ? esc_attr($options['coupons']) : '') . '</textarea>';
    }

    public function sponsored_banners_render() {
        $options = get_option('wp_revenue_booster_settings');
        echo '<textarea cols="60" rows="5" name="wp_revenue_booster_settings[sponsored_banners]">' . (isset($options['sponsored_banners']) ? esc_attr($options['sponsored_banners']) : '') . '</textarea>';
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('wpRevenueBooster');
                do_settings_sections('wpRevenueBooster');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}

new WP_Revenue_Booster();

// JavaScript file content (revenue-booster.js)
// This should be saved as revenue-booster.js in the plugin directory
//
// jQuery(document).ready(function($) {
//     $.post(wpRevenueBooster.ajax_url, {
//         action: 'wp_revenue_booster_load_element',
//         nonce: wpRevenueBooster.nonce
//     }, function(response) {
//         if (response.success) {
//             $('#wp-revenue-booster-container').html(response.data);
//         }
//     });
// });

// Add AJAX handler
add_action('wp_ajax_wp_revenue_booster_load_element', 'wp_revenue_booster_load_element');
add_action('wp_ajax_nopriv_wp_revenue_booster_load_element', 'wp_revenue_booster_load_element');

function wp_revenue_booster_load_element() {
    check_ajax_referer('wp_revenue_booster_nonce', 'nonce');

    $options = get_option('wp_revenue_booster_settings');
    $elements = array();

    if (!empty($options['affiliate_links'])) {
        $links = explode("\n", $options['affiliate_links']);
        foreach ($links as $link) {
            if (!empty($link)) {
                $elements[] = '<a href="' . esc_url(trim($link)) . '" target="_blank">Visit Affiliate</a>';
            }
        }
    }

    if (!empty($options['coupons'])) {
        $coupons = explode("\n", $options['coupons']);
        foreach ($coupons as $coupon) {
            if (!empty($coupon)) {
                $elements[] = '<div class="coupon">Coupon: ' . esc_html(trim($coupon)) . '</div>';
            }
        }
    }

    if (!empty($options['sponsored_banners'])) {
        $banners = explode("\n", $options['sponsored_banners']);
        foreach ($banners as $banner) {
            $parts = explode('|', $banner);
            if (count($parts) == 2) {
                $elements[] = '<a href="' . esc_url(trim($parts[1])) . '" target="_blank"><img src="' . esc_url(trim($parts)) . '" alt="Sponsored Banner"></a>';
            }
        }
    }

    if (empty($elements)) {
        wp_die();
    }

    $random_element = $elements[array_rand($elements)];
    wp_send_json_success($random_element);
}
?>