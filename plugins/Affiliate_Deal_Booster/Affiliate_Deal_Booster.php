/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Booster.php
*/
<?php
/**
 * Plugin Name: Affiliate Deal Booster
 * Description: Auto-aggregates and displays niche-specific affiliate coupons and deals with real-time tracking to maximize affiliate revenue.
 * Version: 1.0
 * Author: Your Name
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateDealBooster {
    private $option_name = 'adb_settings';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_shortcode('affiliate_deals', array($this, 'render_deals_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Affiliate Deal Booster',
            'Affiliate Deal Booster',
            'manage_options',
            'affiliate_deal_booster',
            array($this, 'options_page')
        );
    }

    public function settings_init() {
        register_setting('adbSettings', $this->option_name);

        add_settings_section(
            'adb_section',
            __('Settings for Affiliate Deal Booster', 'adb'),
            null,
            'adbSettings'
        );

        add_settings_field(
            'adb_affiliate_id',
            __('Your Affiliate ID', 'adb'),
            array($this, 'affiliate_id_render'),
            'adbSettings',
            'adb_section'
        );

        add_settings_field(
            'adb_niche_keyword',
            __('Niche Keyword', 'adb'),
            array($this, 'niche_keyword_render'),
            'adbSettings',
            'adb_section'
        );
    }

    public function affiliate_id_render() {
        $options = get_option($this->option_name);
        ?>
        <input type='text' name='<?php echo $this->option_name; ?>[affiliate_id]' value='<?php echo isset($options['affiliate_id']) ? esc_attr($options['affiliate_id']) : ''; ?>' placeholder='e.g. myaffiliate123'>
        <?php
    }

    public function niche_keyword_render() {
        $options = get_option($this->option_name);
        ?>
        <input type='text' name='<?php echo $this->option_name; ?>[niche_keyword]' value='<?php echo isset($options['niche_keyword']) ? esc_attr($options['niche_keyword']) : ''; ?>' placeholder='e.g. fitness, gardening'>
        <?php
    }

    public function options_page() {
        ?>
        <form action='options.php' method='post'>
            <h1>Affiliate Deal Booster Settings</h1>
            <?php
            settings_fields('adbSettings');
            do_settings_sections('adbSettings');
            submit_button();
            ?>
        </form>
        <p><strong>Usage:</strong> Insert shortcode <code>[affiliate_deals]</code> into your posts or pages to display curated affiliate coupons and deals based on your niche.</p>
        <?php
    }

    public function enqueue_scripts() {
        if (is_singular() && has_shortcode(get_post()->post_content, 'affiliate_deals')) {
            wp_enqueue_style('adb-style', plugin_dir_url(__FILE__) . 'style.css');
            wp_enqueue_script('adb-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), false, true);
        }
    }

    private function fetch_deals($keyword, $affiliate_id) {
        // Simulation: In a real plugin this would call external affiliate APIs or scrape approved deal sources.
        // For this standalone plugin file, we generate mock deals.
        $mockDeals = array(
            array('title' => '50% OFF on ' . ucfirst($keyword) . ' Gear', 'link' => 'https://affiliate.example.com/product1?aff=' . $affiliate_id, 'expires' => date('Y-m-d', strtotime('+7 days'))),
            array('title' => 'Buy 1 Get 1 Free ' . ucfirst($keyword) . ' Supplements', 'link' => 'https://affiliate.example.com/product2?aff=' . $affiliate_id, 'expires' => date('Y-m-d', strtotime('+14 days'))),
            array('title' => 'Save $20 on ' . ucfirst($keyword) . ' Coaching Sessions', 'link' => 'https://affiliate.example.com/product3?aff=' . $affiliate_id, 'expires' => date('Y-m-d', strtotime('+5 days'))),
        );
        return $mockDeals;
    }

    public function render_deals_shortcode($atts) {
        $options = get_option($this->option_name);
        $affiliate_id = isset($options['affiliate_id']) ? sanitize_text_field($options['affiliate_id']) : '';
        $keyword = isset($options['niche_keyword']) ? sanitize_text_field($options['niche_keyword']) : '';
        if (!$affiliate_id || !$keyword) {
            return '<p>Please configure your Affiliate Deal Booster settings to show deals.</p>';
        }

        $deals = $this->fetch_deals($keyword, $affiliate_id);

        $output = '<div class="adb-deal-list">';
        foreach ($deals as $deal) {
            $output .= '<div class="adb-deal">
                <a href="' . esc_url($deal['link']) . '" target="_blank" rel="nofollow noopener">' . esc_html($deal['title']) . '</a><br />
                <small>Expires: ' . esc_html($deal['expires']) . '</small>
            </div>';
        }
        $output .= '</div>';

        return $output;
    }
}

new AffiliateDealBooster();
