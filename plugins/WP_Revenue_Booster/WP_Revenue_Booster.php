/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Description: Maximize revenue by rotating and optimizing affiliate links, ads, and sponsored content.
 * Version: 1.0
 * Author: RevenueBoost Team
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'inject_revenue_elements'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('wp-revenue-booster', plugin_dir_url(__FILE__) . 'revenue-booster.js', array(), '1.0', true);
        wp_localize_script('wp-revenue-booster', 'wpRevenueBooster', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_revenue_booster_nonce')
        ));
    }

    public function inject_revenue_elements() {
        $options = get_option('wp_revenue_booster_options');
        if (!$options) return;

        $elements = array();
        if (!empty($options['affiliate_links'])) {
            $elements[] = $this->get_random_affiliate_link($options['affiliate_links']);
        }
        if (!empty($options['ads'])) {
            $elements[] = $this->get_random_ad($options['ads']);
        }
        if (!empty($options['sponsored_content'])) {
            $elements[] = $this->get_random_sponsored_content($options['sponsored_content']);
        }

        if (!empty($elements)) {
            $selected = $elements[array_rand($elements)];
            echo '<div class="wp-revenue-booster-element">' . $selected . '</div>';
        }
    }

    private function get_random_affiliate_link($links) {
        $link = $links[array_rand($links)];
        return '<a href="' . esc_url($link['url']) . '" target="_blank" rel="nofollow">' . esc_html($link['text']) . '</a>';
    }

    private function get_random_ad($ads) {
        $ad = $ads[array_rand($ads)];
        return '<div class="wp-revenue-booster-ad">' . $ad['code'] . '</div>';
    }

    private function get_random_sponsored_content($contents) {
        $content = $contents[array_rand($contents)];
        return '<div class="wp-revenue-booster-sponsored">' . $content['content'] . '</div>';
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
        register_setting('wpRevenueBooster', 'wp_revenue_booster_options');

        add_settings_section(
            'wp_revenue_booster_section',
            'Revenue Booster Settings',
            null,
            'wpRevenueBooster'
        );

        add_settings_field(
            'affiliate_links',
            'Affiliate Links',
            array($this, 'affiliate_links_render'),
            'wpRevenueBooster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'ads',
            'Ad Codes',
            array($this, 'ads_render'),
            'wpRevenueBooster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'sponsored_content',
            'Sponsored Content',
            array($this, 'sponsored_content_render'),
            'wpRevenueBooster',
            'wp_revenue_booster_section'
        );
    }

    public function affiliate_links_render() {
        $options = get_option('wp_revenue_booster_options');
        $links = isset($options['affiliate_links']) ? $options['affiliate_links'] : array();
        echo '<textarea name="wp_revenue_booster_options[affiliate_links]" rows="5" cols="50">' . json_encode($links) . '</textarea>';
    }

    public function ads_render() {
        $options = get_option('wp_revenue_booster_options');
        $ads = isset($options['ads']) ? $options['ads'] : array();
        echo '<textarea name="wp_revenue_booster_options[ads]" rows="5" cols="50">' . json_encode($ads) . '</textarea>';
    }

    public function sponsored_content_render() {
        $options = get_option('wp_revenue_booster_options');
        $contents = isset($options['sponsored_content']) ? $options['sponsored_content'] : array();
        echo '<textarea name="wp_revenue_booster_options[sponsored_content]" rows="5" cols="50">' . json_encode($contents) . '</textarea>';
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form action='options.php' method='post'>
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
?>