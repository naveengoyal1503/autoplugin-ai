/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Plugin URI: https://example.com/wp-revenue-booster
 * Description: Automatically optimizes ad, affiliate, and coupon placements for maximum revenue.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('wp_head', array($this, 'add_tracking_code'));
        add_action('the_content', array($this, 'inject_optimized_content'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function add_tracking_code() {
        echo '<!-- WP Revenue Booster Tracking -->';
    }

    public function inject_optimized_content($content) {
        $settings = get_option('wp_revenue_booster_settings');
        $ad_code = isset($settings['ad_code']) ? $settings['ad_code'] : '';
        $affiliate_link = isset($settings['affiliate_link']) ? $settings['affiliate_link'] : '';
        $coupon_code = isset($settings['coupon_code']) ? $settings['coupon_code'] : '';

        if (!empty($ad_code)) {
            $content = $this->insert_after_paragraph($ad_code, 2, $content);
        }
        if (!empty($affiliate_link)) {
            $content = $this->insert_after_paragraph('<p><a href="' . esc_url($affiliate_link) . '" target="_blank">Check out this offer</a></p>', 4, $content);
        }
        if (!empty($coupon_code)) {
            $content = $this->insert_after_paragraph('<p>Use coupon: <strong>' . esc_html($coupon_code) . '</strong> for a special discount!</p>', 6, $content);
        }

        return $content;
    }

    private function insert_after_paragraph($insertion, $paragraph_id, $content) {
        $closing_p = '</p>';
        $paragraphs = explode($closing_p, $content);
        foreach ($paragraphs as $index => $paragraph) {
            if (trim($paragraph)) {
                $paragraphs[$index] .= $closing_p;
            }
            if ($paragraph_id == $index + 1) {
                $paragraphs[$index] .= $insertion;
            }
        }
        return implode('', $paragraphs);
    }

    public function add_admin_menu() {
        add_options_page(
            'WP Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp_revenue_booster',
            array($this, 'options_page')
        );
    }

    public function settings_init() {
        register_setting('wp_revenue_booster', 'wp_revenue_booster_settings');
        add_settings_section(
            'wp_revenue_booster_section',
            'Revenue Booster Settings',
            null,
            'wp_revenue_booster'
        );
        add_settings_field(
            'ad_code',
            'Ad Code',
            array($this, 'ad_code_render'),
            'wp_revenue_booster',
            'wp_revenue_booster_section'
        );
        add_settings_field(
            'affiliate_link',
            'Affiliate Link',
            array($this, 'affiliate_link_render'),
            'wp_revenue_booster',
            'wp_revenue_booster_section'
        );
        add_settings_field(
            'coupon_code',
            'Coupon Code',
            array($this, 'coupon_code_render'),
            'wp_revenue_booster',
            'wp_revenue_booster_section'
        );
    }

    public function ad_code_render() {
        $settings = get_option('wp_revenue_booster_settings');
        echo '<textarea name="wp_revenue_booster_settings[ad_code]" rows="4" cols="50">' . (isset($settings['ad_code']) ? esc_textarea($settings['ad_code']) : '') . '</textarea>';
    }

    public function affiliate_link_render() {
        $settings = get_option('wp_revenue_booster_settings');
        echo '<input type="text" name="wp_revenue_booster_settings[affiliate_link]" value="' . (isset($settings['affiliate_link']) ? esc_url($settings['affiliate_link']) : '') . '" size="50" />';
    }

    public function coupon_code_render() {
        $settings = get_option('wp_revenue_booster_settings');
        echo '<input type="text" name="wp_revenue_booster_settings[coupon_code]" value="' . (isset($settings['coupon_code']) ? esc_attr($settings['coupon_code']) : '') . '" size="20" />';
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('wp_revenue_booster');
                do_settings_sections('wp_revenue_booster');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}

new WP_Revenue_Booster();
?>