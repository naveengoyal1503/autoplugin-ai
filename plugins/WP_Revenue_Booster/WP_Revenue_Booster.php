<?php
/*
Plugin Name: WP Revenue Booster
Description: Automate and optimize monetization strategies for WordPress sites.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('premium_content', array($this, 'premium_content_shortcode'));
        add_shortcode('coupon_section', array($this, 'coupon_section_shortcode'));
        add_filter('the_content', array($this, 'insert_affiliate_links'));
        add_filter('the_content', array($this, 'insert_ad_code'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'WP Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp-revenue-booster',
            array($this, 'admin_page'),
            'dashicons-chart-line'
        );
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post" action="options.php">
                <?php settings_fields('wp_revenue_booster_settings'); ?>
                <?php do_settings_sections('wp-revenue-booster'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Affiliate Link Keywords</th>
                        <td><textarea name="affiliate_keywords" rows="5" cols="50"><?php echo esc_attr(get_option('affiliate_keywords')); ?></textarea></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Ad Code</th>
                        <td><textarea name="ad_code" rows="5" cols="50"><?php echo esc_attr(get_option('ad_code')); ?></textarea></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Coupon Codes</th>
                        <td><textarea name="coupon_codes" rows="5" cols="50"><?php echo esc_attr(get_option('coupon_codes')); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wp-revenue-booster', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function premium_content_shortcode($atts, $content = null) {
        if (is_user_logged_in()) {
            return '<div class="premium-content">' . do_shortcode($content) . '</div>';
        } else {
            return '<div class="premium-content-prompt">Log in to access premium content.</div>';
        }
    }

    public function coupon_section_shortcode() {
        $codes = get_option('coupon_codes');
        $codes = explode('\n', $codes);
        $output = '<div class="coupon-section"><h3>Coupons</h3><ul>';
        foreach ($codes as $code) {
            $output .= '<li>' . esc_html($code) . '</li>';
        }
        $output .= '</ul></div>';
        return $output;
    }

    public function insert_affiliate_links($content) {
        $keywords = get_option('affiliate_keywords');
        $keywords = explode('\n', $keywords);
        foreach ($keywords as $keyword) {
            $parts = explode('|', $keyword);
            if (count($parts) == 2) {
                $content = str_replace($parts, '<a href="' . $parts[1] . '" target="_blank">' . $parts . '</a>', $content);
            }
        }
        return $content;
    }

    public function insert_ad_code($content) {
        $ad_code = get_option('ad_code');
        if (!empty($ad_code)) {
            $content = '<div class="ad-section">' . $ad_code . '</div>' . $content;
        }
        return $content;
    }
}

function wp_revenue_booster_init() {
    new WP_Revenue_Booster();
}

add_action('plugins_loaded', 'wp_revenue_booster_init');

function wp_revenue_booster_register_settings() {
    register_setting('wp_revenue_booster_settings', 'affiliate_keywords');
    register_setting('wp_revenue_booster_settings', 'ad_code');
    register_setting('wp_revenue_booster_settings', 'coupon_codes');
}

add_action('admin_init', 'wp_revenue_booster_register_settings');
?>