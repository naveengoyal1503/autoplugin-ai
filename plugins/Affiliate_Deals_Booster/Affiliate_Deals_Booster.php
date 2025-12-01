<?php
/*
Plugin Name: Affiliate Deals Booster
Description: Aggregate and display exclusive affiliate coupons and deals to boost conversions.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deals_Booster.php
*/

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateDealsBooster {
    private $option_name = 'adb_deals';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'settings_init']);
        add_shortcode('affiliate_deals', [$this, 'render_deals_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function add_admin_menu() {
        add_menu_page('Affiliate Deals Booster', 'Affiliate Deals Booster', 'manage_options', 'affiliate_deals_booster', [$this, 'options_page']);
    }

    public function settings_init() {
        register_setting('affiliateDealsBooster', $this->option_name);

        add_settings_section(
            'adb_section',
            __('Manage Affiliate Deals', 'affiliate-deals-booster'),
            null,
            'affiliateDealsBooster'
        );

        add_settings_field(
            'adb_deals_field',
            __('Deals JSON Data', 'affiliate-deals-booster'),
            [$this, 'deals_field_render'],
            'affiliateDealsBooster',
            'adb_section'
        );
    }

    public function deals_field_render() {
        $options = get_option($this->option_name, '{}');
        echo '<textarea cols="60" rows="10" name="' . esc_attr($this->option_name) . '">' . esc_textarea($options) . '</textarea>';
        echo '<p class="description">Enter deals as JSON array. Each deal: {"title": "string", "url": "string", "discount": "string", "expiry": "YYYY-MM-DD"}</p>';
    }

    public function options_page() {
        ?>
        <form action='options.php' method='post'>
            <h2>Affiliate Deals Booster</h2>
            <?php
            settings_fields('affiliateDealsBooster');
            do_settings_sections('affiliateDealsBooster');
            submit_button();
            ?>
        </form>
        <?php
    }

    public function render_deals_shortcode() {
        $raw = get_option($this->option_name, '[]');
        $deals = json_decode($raw, true);

        if (!is_array($deals) || empty($deals)) {
            return '<p>No affiliate deals available currently.</p>';
        }

        $today = date('Y-m-d');
        $output = '<ul class="affiliate-deals-list">';
        foreach ($deals as $deal) {
            if (empty($deal['title']) || empty($deal['url'])) {
                continue;
            }
            // Filter expired
            if (!empty($deal['expiry']) && $deal['expiry'] < $today) {
                continue;
            }

            $discount = !empty($deal['discount']) ? ' - <strong>' . esc_html($deal['discount']) . '</strong>' : '';
            $output .= '<li><a href="' . esc_url($deal['url']) . '" target="_blank" rel="nofollow noopener">' . esc_html($deal['title']) . '</a>' . $discount . '</li>';
        }
        $output .= '</ul>';

        return $output;
    }

    public function enqueue_scripts() {
        wp_add_inline_style('wp-block-library', '.affiliate-deals-list { list-style-type: disc; margin-left: 20px; font-family: Arial, sans-serif; } .affiliate-deals-list li { margin-bottom: 8px; }');
    }
}

new AffiliateDealsBooster();
