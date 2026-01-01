/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate and manage exclusive affiliate coupons for your WordPress site, boosting conversions with personalized discount codes and tracking.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ExclusiveCouponsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        wp_register_style('ecp-admin-style', plugin_dir_url(__FILE__) . 'admin-style.css');
        wp_enqueue_style('ecp-admin-style');
    }

    public function admin_menu() {
        add_menu_page(
            'Exclusive Coupons',
            'Coupons Pro',
            'manage_options',
            'exclusive-coupons',
            array($this, 'admin_page'),
            'dashicons-tickets-alt',
            30
        );
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('ecp_coupons', sanitize_textarea_field($_POST['coupons']));
        }
        $coupons = get_option('ecp_coupons', "Coupon Code|Brand Name|Discount %|Affiliate Link");
        echo '<div class="wrap"><h1>Manage Exclusive Coupons</h1>
        <form method="post">
            <textarea name="coupons" rows="10" cols="80" placeholder="Code|Brand|Discount|Link\nEXAMPLE|BrandX|20%|https://aff.link">' . esc_textarea($coupons) . '</textarea>
            <p>Format: Code|Brand|Discount|Affiliate Link (one per line)</p>
            <p><input type="submit" name="submit" class="button-primary" value="Save Coupons"></p>
        </form>
        <h2>Shortcode Usage</h2>
        <p>Use <code>[exclusive_coupon id="1"]</code> or <code>[exclusive_coupon]</code> for random.</p>
        </div>';
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 'random'), $atts);
        $coupons_str = get_option('ecp_coupons', '');
        if (empty($coupons_str)) return '<p>No coupons configured. <a href="' . admin_url('admin.php?page=exclusive-coupons') . '">Set up now</a>.</p>';

        $coupons = explode("\n", trim($coupons_str));
        $lines = array();
        foreach ($coupons as $line) {
            $parts = explode('|', trim($line), 4);
            if (count($parts) === 4) {
                $lines[] = array(
                    'code' => sanitize_text_field($parts),
                    'brand' => sanitize_text_field($parts[1]),
                    'discount' => sanitize_text_field($parts[2]),
                    'link' => esc_url($parts[3])
                );
            }
        }

        if (empty($lines)) return '<p>No valid coupons found.</p>';

        if ($atts['id'] === 'random') {
            $coupon = $lines[array_rand($lines)];
        } else {
            $id = intval($atts['id']) - 1;
            $coupon = isset($lines[$id]) ? $lines[$id] : $lines;
        }

        $click_id = uniqid('ecp_');
        $track_link = add_query_arg('ecp', $click_id, $coupon['link']);

        ob_start();
        echo '<div class="ecp-coupon" style="border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9;">
                <h3>Exclusive Deal: ' . esc_html($coupon['brand']) . '</h3>
                <p><strong>Code: </strong><code>' . esc_html($coupon['code']) . '</code></p>
                <p><strong>Save: </strong>' . esc_html($coupon['discount']) . ' OFF</p>
                <a href="' . esc_url($track_link) . '" target="_blank" class="button button-large" style="background: #0073aa; color: white; padding: 10px 20px; text-decoration: none;">Grab Deal Now</a>
              </div>';

        // Track clicks
        if (isset($_GET['ecp'])) {
            update_option('ecp_clicks', get_option('ecp_clicks', 0) + 1);
        }

        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('ecp_coupons')) {
            update_option('ecp_coupons', "WELCOME50|ExampleBrand|50% Off|https://your-affiliate-link.com");
        }
    }
}

new ExclusiveCouponsPro();

// Premium upsell notice
function ecp_premium_notice() {
    if (current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Exclusive Coupons Pro Premium</strong> for unlimited coupons, analytics dashboard, and custom branding! <a href="https://example.com/premium" target="_blank">Get it now ($49/year)</a></p></div>';
    }
}
add_action('admin_notices', 'ecp_premium_notice');

// Simple CSS
add_action('admin_head', function() {
    echo '<style>.ecp-coupon { max-width: 400px; } .wrap textarea { width: 100%; font-family: monospace; }</style>';
});

// Widget support
function ecp_register_widget() {
    register_widget('ECP_Widget');
}
add_action('widgets_init', 'ecp_register_widget');

class ECP_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct('ecp_widget', 'Exclusive Coupon');
    }

    public function widget($args, $instance) {
        echo $args['before_widget'];
        echo do_shortcode('[exclusive_coupon]');
        echo $args['after_widget'];
    }

    public function form($instance) {
        echo '<p>Displays a random exclusive coupon. Configure in Coupons Pro menu.</p>';
    }
}
