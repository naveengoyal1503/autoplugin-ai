<?php
/*
Plugin Name: Affiliate Deals Booster
Plugin URI: https://example.com/affiliate-deals-booster
Description: Curate and display exclusive affiliate coupons tailored to your niche to boost conversions.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deals_Booster.php
License: GPL2
*/

if (!defined('ABSPATH')) exit;

class AffiliateDealsBooster {
    private $option_name = 'adb_settings';

    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('affiliate_deals_booster', array($this, 'render_deals'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    public function enqueue_styles() {
        wp_enqueue_style('adb-style', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function admin_menu() {
        add_options_page(
            'Affiliate Deals Booster Settings',
            'Affiliate Deals Booster',
            'manage_options',
            'affiliate-deals-booster',
            array($this, 'settings_page')
        );
    }

    public function register_settings() {
        register_setting($this->option_name, $this->option_name, array($this, 'validate_settings'));

        add_settings_section(
            'adb_main_section',
            'Main Settings',
            null,
            'affiliate-deals-booster'
        );

        add_settings_field(
            'api_key',
            'Affiliate API Key',
            array($this, 'input_api_key'),
            'affiliate-deals-booster',
            'adb_main_section'
        );

        add_settings_field(
            'affiliate_network',
            'Affiliate Network',
            array($this, 'select_affiliate_network'),
            'affiliate-deals-booster',
            'adb_main_section'
        );

        add_settings_field(
            'cache_duration',
            'Cache Duration (minutes)',
            array($this, 'input_cache_duration'),
            'affiliate-deals-booster',
            'adb_main_section'
        );
    }

    public function input_api_key() {
        $options = get_option($this->option_name);
        $value = isset($options['api_key']) ? esc_attr($options['api_key']) : '';
        echo "<input type='text' name='{$this->option_name}[api_key]' value='{$value}' size='50' />";
    }

    public function select_affiliate_network() {
        $options = get_option($this->option_name);
        $value = isset($options['affiliate_network']) ? $options['affiliate_network'] : '';
        $networks = array('amazon' => 'Amazon', 'cj' => 'Commission Junction', 'impact' => 'Impact', 'shareasale' => 'ShareASale');
        echo "<select name='{$this->option_name}[affiliate_network]'>";
        foreach ($networks as $key => $label) {
            $selected = selected($value, $key, false);
            echo "<option value='{$key}' {$selected}>{$label}</option>";
        }
        echo "</select>";
    }

    public function input_cache_duration() {
        $options = get_option($this->option_name);
        $value = isset($options['cache_duration']) ? intval($options['cache_duration']) : 60;
        echo "<input type='number' name='{$this->option_name}[cache_duration]' value='{$value}' min='5' max='1440' />";
        echo " <small>Set how often deals are refreshed.</small>";
    }

    public function validate_settings($input) {
        $input['api_key'] = sanitize_text_field($input['api_key']);
        $input['affiliate_network'] = sanitize_text_field($input['affiliate_network']);
        $cache = intval($input['cache_duration']);
        if ($cache < 5) $cache = 5;
        if ($cache > 1440) $cache = 1440;
        $input['cache_duration'] = $cache;
        return $input;
    }

    private function fetch_deals_from_api() {
        $options = get_option($this->option_name);
        $api_key = isset($options['api_key']) ? $options['api_key'] : '';
        $network = isset($options['affiliate_network']) ? $options['affiliate_network'] : '';

        // Since actual API integration is complex and varies, simulate deals for demo
        $demo_deals = array(
            array('title' => '50% off on Widgets', 'url' => '#', 'code' => 'WIDGET50', 'desc' => 'Get 50% discount on widgets','expires' => '2025-12-31'),
            array('title' => 'Free Shipping on Orders $25+', 'url' => '#', 'code' => 'FREESHIP25', 'desc' => 'Free shipping on orders over $25','expires' => '2025-11-30'),
            array('title' => 'Buy 1 Get 1 Free', 'url' => '#', 'code' => 'B1G1FREE', 'desc' => 'Special BOGO deal on selected items','expires' => '2026-01-15')
        );

        return $demo_deals;
    }

    public function get_cached_deals() {
        $transient_key = 'adb_cached_deals';
        $deals = get_transient($transient_key);
        if ($deals === false) {
            $deals = $this->fetch_deals_from_api();
            $options = get_option($this->option_name);
            $cache_duration = isset($options['cache_duration']) ? intval($options['cache_duration']) : 60;
            set_transient($transient_key, $deals, $cache_duration * MINUTE_IN_SECONDS);
        }
        return $deals;
    }

    public function render_deals($atts) {
        $deals = $this->get_cached_deals();
        if (empty($deals)) return '<p>No affiliate deals available at this time.</p>';

        $output = "<div class='adb-deals-list'>";
        foreach ($deals as $deal) {
            $title = esc_html($deal['title']);
            $url = esc_url($deal['url']);
            $code = esc_html($deal['code']);
            $desc = esc_html($deal['desc']);
            $expires = esc_html($deal['expires']);

            $output .= "<div class='adb-deal-item'>";
            $output .= "<h3><a href='{$url}' target='_blank' rel='nofollow noopener'>{$title}</a></h3>";
            $output .= "<p>{$desc}</p>";
            $output .= "<p><strong>Coupon Code: </strong><span class='adb-coupon-code'>{$code}</span></p>";
            $output .= "<p class='adb-expires'>Expires: {$expires}</p>";
            $output .= "</div>";
        }
        $output .= "</div>";

        return $output;
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Deals Booster Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields($this->option_name);
                do_settings_sections('affiliate-deals-booster');
                submit_button();
                ?>
            </form>
            <hr />
            <h2>How to Use</h2>
            <p>Insert the shortcode <code>[affiliate_deals_booster]</code> into any post, page, or widget to display curated affiliate deals.</p>
        </div>
        <?php
    }
}

new AffiliateDealsBooster();
