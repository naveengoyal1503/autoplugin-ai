<?php
/*
Plugin Name: Affiliate Deal Booster
Description: Aggregates and displays affiliate coupons and deals dynamically to increase conversions.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Booster.php
*/

if (!defined('ABSPATH')) exit;

class AffiliateDealBooster {
    private $plugin_slug = 'affiliate-deal-booster';
    private $options_key = 'adb_plugin_options';

    public function __construct() {
        add_action('init', array($this, 'register_shortcodes'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'plugin_settings'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('adb_styles', plugins_url('/assets/adb-style.css', __FILE__));
    }

    public function register_shortcodes() {
        add_shortcode('affiliate_deals', array($this, 'render_deals'));
    }

    public function admin_menu() {
        add_options_page('Affiliate Deal Booster', 'Affiliate Deal Booster', 'manage_options', $this->plugin_slug, array($this, 'settings_page'));
    }

    public function plugin_settings() {
        register_setting($this->plugin_slug . '_group', $this->options_key, array($this, 'sanitize_options'));

        add_settings_section('adb_main_section', 'Main Settings', null, $this->plugin_slug);

        add_settings_field('adb_affiliate_id', 'Your Affiliate ID', array($this, 'affiliate_id_field'), $this->plugin_slug, 'adb_main_section');
    }

    public function sanitize_options($input) {
        $output = array();
        if (isset($input['affiliate_id'])) {
            $output['affiliate_id'] = sanitize_text_field($input['affiliate_id']);
        }
        return $output;
    }

    public function affiliate_id_field() {
        $options = get_option($this->options_key);
        $val = isset($options['affiliate_id']) ? esc_attr($options['affiliate_id']) : '';
        echo '<input type="text" name="' . $this->options_key . '[affiliate_id]" value="' . $val . '" size="40" />';
        echo '<p class="description">Enter your primary affiliate ID to append to deals.</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Deal Booster Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields($this->plugin_slug . '_group');
                do_settings_sections($this->plugin_slug);
                submit_button();
                ?>
            </form>
            <h2>How to use</h2>
            <p>Add the shortcode <code>[affiliate_deals]</code> anywhere in your posts or pages to display the latest affiliate deals.</p>
        </div>
        <?php
    }

    public function render_deals($atts) {
        $options = get_option($this->options_key);
        $affiliate_id = isset($options['affiliate_id']) ? $options['affiliate_id'] : '';

        // Example static deals list - in premium, this would be dynamic fetched and cached via API
        $deals = array(
            array('title' => '50% Off on Star Trek Merchandise', 'url' => 'https://example-affiliate.com/deal1', 'code' => 'STAR50'),
            array('title' => 'Buy 1 Get 1 Free on Sci-Fi Books', 'url' => 'https://example-affiliate.com/deal2', 'code' => 'BOOKSBOGO'),
            array('title' => 'Extra 20% Off Gamer Gear', 'url' => 'https://example-affiliate.com/deal3', 'code' => 'GAMER20')
        );

        $output = '<div class="adb-deals-container">';
        $output .= '<ul class="adb-deals-list">';

        foreach ($deals as $deal) {
            $url = esc_url(add_query_arg('aff_id', urlencode($affiliate_id), $deal['url']));
            $title = esc_html($deal['title']);
            $code = esc_html($deal['code']);

            $output .= "<li class='adb-deal'>";
            $output .= "<a href='$url' target='_blank' rel='nofollow noopener'>$title</a>";
            $output .= " <code class='adb-coupon-code'>$code</code>";
            $output .= "</li>";
        }

        $output .= '</ul></div>';

        return $output;
    }
}

new AffiliateDealBooster();

// CSS embedded for basic styling
add_action('wp_head', function() {
    echo '<style>.adb-deals-container{background:#f9f9f9;padding:15px;border:1px solid #ddd;max-width:400px;margin:20px auto;font-family:Arial,sans-serif;}.adb-deals-list{list-style:none;padding:0;margin:0;}.adb-deal{margin-bottom:10px;font-size:14px;}.adb-deal a{color:#0073aa;text-decoration:none;font-weight:bold;}.adb-coupon-code{background:#eee;padding:2px 5px;margin-left:8px;border-radius:3px;font-family:monospace;color:#c7254e;}</style>';
});