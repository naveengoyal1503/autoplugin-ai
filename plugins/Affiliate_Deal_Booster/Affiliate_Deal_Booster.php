<?php
/*
Plugin Name: Affiliate Deal Booster
Description: Auto-aggregates and displays niche-specific affiliate coupons and deals to boost conversions.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Booster.php
*/

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateDealBooster {
    private $option_name = 'adb_settings';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_shortcode('affiliate_deals', array($this, 'display_deals_shortcode'));
    }

    public function add_admin_menu() {
        add_menu_page('Affiliate Deal Booster', 'Aff. Deal Booster', 'manage_options', 'affiliate_deal_booster', array($this, 'options_page'), 'dashicons-cart');
    }

    public function settings_init() {
        register_setting('adb_plugin', $this->option_name);

        add_settings_section(
            'adb_plugin_section',
            __('Settings for Affiliate Deal Booster', 'adb'),
            null,
            'adb_plugin'
        );

        add_settings_field(
            'keywords',
            __('Deal Keywords (comma-separated)', 'adb'),
            array($this, 'keywords_render'),
            'adb_plugin',
            'adb_plugin_section'
        );

        add_settings_field(
            'max_deals',
            __('Max Deals to Show', 'adb'),
            array($this, 'max_deals_render'),
            'adb_plugin',
            'adb_plugin_section'
        );

        add_settings_field(
            'aff_id',
            __('Affiliate ID', 'adb'),
            array($this, 'aff_id_render'),
            'adb_plugin',
            'adb_plugin_section'
        );
    }

    public function keywords_render() {
        $options = get_option($this->option_name);
        ?>
        <input type='text' name='<?php echo $this->option_name; ?>[keywords]' value='<?php echo isset($options['keywords']) ? esc_attr($options['keywords']) : ''; ?>' placeholder='e.g. tech,gadgets,software'>
        <p class='description'>Comma separated keywords to find relevant deals</p>
        <?php
    }

    public function max_deals_render() {
        $options = get_option($this->option_name);
        $val = isset($options['max_deals']) ? intval($options['max_deals']) : 5;
        ?>
        <input type='number' name='<?php echo $this->option_name; ?>[max_deals]' value='<?php echo $val; ?>' min='1' max='20'>
        <?php
    }

    public function aff_id_render() {
        $options = get_option($this->option_name);
        ?>
        <input type='text' name='<?php echo $this->option_name; ?>[aff_id]' value='<?php echo isset($options['aff_id']) ? esc_attr($options['aff_id']) : ''; ?>' placeholder='YourAffiliateID'>
        <?php
    }

    public function options_page() {
        ?>
        <form action='options.php' method='post'>
            <h1>Affiliate Deal Booster Settings</h1>
            <?php
            settings_fields('adb_plugin');
            do_settings_sections('adb_plugin');
            submit_button();
            ?>
        </form>
        <?php
    }

    private function get_sample_deals($keywords, $affiliate_id) {
        // In a real plugin, here you would call external affiliate APIs or scrape deals.
        // For demo purposes, returning static dummy deals filtered by keywords.

        $all_deals = array(
            array('title' => '50% off Tech Gadget ABC', 'url' => 'https://affiliateshop.com/product/abc?aff_id=' . $affiliate_id),
            array('title' => 'Save $20 on Software XYZ', 'url' => 'https://affiliateshop.com/product/xyz?aff_id=' . $affiliate_id),
            array('title' => 'Buy one get one free Gadget DEF', 'url' => 'https://affiliateshop.com/product/def?aff_id=' . $affiliate_id),
            array('title' => '30% discount on Laptop Accessories', 'url' => 'https://affiliateshop.com/product/laptop-accessories?aff_id=' . $affiliate_id),
            array('title' => 'Free Shipping on Orders over $50', 'url' => 'https://affiliateshop.com/free-shipping?aff_id=' . $affiliate_id),
            array('title' => 'Exclusive 15% off Home Electronics', 'url' => 'https://affiliateshop.com/home-electronics?aff_id=' . $affiliate_id),
        );

        if (empty($keywords)) {
            return array_slice($all_deals, 0, 5);
        }

        $keywords = array_map('trim', explode(',', strtolower($keywords)));

        $filtered = array();
        foreach ($all_deals as $deal) {
            foreach ($keywords as $kw) {
                if (stripos(strtolower($deal['title']), $kw) !== false) {
                    $filtered[] = $deal;
                    break;
                }
            }
        }

        if (count($filtered) === 0) {
            $filtered = array_slice($all_deals, 0, 5);
        }

        return $filtered;
    }

    public function display_deals_shortcode() {
        $options = get_option($this->option_name);
        $keywords = isset($options['keywords']) ? $options['keywords'] : '';
        $max = isset($options['max_deals']) ? intval($options['max_deals']) : 5;
        $aff_id = isset($options['aff_id']) ? $options['aff_id'] : 'default-aff';

        $deals = $this->get_sample_deals($keywords, $aff_id);
        $deals = array_slice($deals, 0, $max);

        if (empty($deals)) {
            return '<p>No affiliate deals found.</p>';
        }

        $html = '<ul class="affiliate-deal-list" style="list-style:none;padding-left:0;">';
        foreach ($deals as $deal) {
            $title = esc_html($deal['title']);
            $url = esc_url($deal['url']);
            $html .= "<li style='margin-bottom:10px;'><a href='$url' target='_blank' rel='nofollow noopener noreferrer' style='color:#0073aa;text-decoration:none;'>$title</a></li>";
        }
        $html .= '</ul>';
        return $html;
    }
}

new AffiliateDealBooster();
