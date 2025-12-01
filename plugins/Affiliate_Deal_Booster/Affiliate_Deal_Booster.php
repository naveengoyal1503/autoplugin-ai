/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Booster.php
*/
<?php
/**
 * Plugin Name: Affiliate Deal Booster
 * Description: Auto-fetch and display affiliate coupon deals tailored to your niche to boost affiliate commission.
 * Version: 1.0
 * Author: PluginDev
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AffiliateDealBooster {
    private $option_name = 'adb_options';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_shortcode('affiliate_deals', [$this, 'render_deals']);
        add_action('wp_enqueue_scripts', [$this, 'register_scripts']);
    }

    public function add_admin_page() {
        add_options_page(
            'Affiliate Deal Booster',
            'Affiliate Deal Booster',
            'manage_options',
            'affiliate-deal-booster',
            [$this, 'admin_page_html']
        );
    }

    public function register_settings() {
        register_setting($this->option_name, $this->option_name, [$this, 'validate_options']);
        add_settings_section('adb_main_section', 'Settings', null, 'affiliate-deal-booster');

        add_settings_field(
            'keyword',
            'Target Keyword or Niche',
            [$this, 'keyword_field_html'],
            'affiliate-deal-booster',
            'adb_main_section'
        );

        add_settings_field(
            'affiliate_network',
            'Affiliate Network API Key',
            [$this, 'affiliate_key_field_html'],
            'affiliate-deal-booster',
            'adb_main_section'
        );
    }

    public function keyword_field_html() {
        $options = get_option($this->option_name);
        $val = isset($options['keyword']) ? esc_attr($options['keyword']) : '';
        echo "<input type='text' name='{$this->option_name}[keyword]' value='$val' size='50' />";
        echo "<p class='description'>Enter keywords matching your niche to fetch relevant deals.</p>";
    }

    public function affiliate_key_field_html() {
        $options = get_option($this->option_name);
        $val = isset($options['affiliate_network']) ? esc_attr($options['affiliate_network']) : '';
        echo "<input type='text' name='{$this->option_name}[affiliate_network]' value='$val' size='50' />";
        echo "<p class='description'>Enter your affiliate network API key (optional for basic use).</p>";
    }

    public function validate_options($input) {
        $output = [];
        if (isset($input['keyword'])) {
            $output['keyword'] = sanitize_text_field($input['keyword']);
        }
        if (isset($input['affiliate_network'])) {
            $output['affiliate_network'] = sanitize_text_field($input['affiliate_network']);
        }
        return $output;
    }

    public function admin_page_html() {
        ?>
        <div class="wrap">
            <h1>Affiliate Deal Booster Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields($this->option_name);
                do_settings_sections('affiliate-deal-booster');
                submit_button();
                ?>
            </form>
            <h2>Usage</h2>
            <p>Add shortcode <code>[affiliate_deals]</code> to any post or page to display the latest affiliate coupon deals based on your configured niche keyword.</p>
        </div>
        <?php
    }

    public function register_scripts() {
        wp_register_style('adb_style', plugin_dir_url(__FILE__) . 'style.css');
        wp_enqueue_style('adb_style');
    }

    public function fetch_deals() {
        $options = get_option($this->option_name);
        $keyword = isset($options['keyword']) ? $options['keyword'] : '';
        if (!$keyword) {
            return [];
        }

        // For demonstration, use a static sample data.
        // Real implementation would call APIs like Rakuten, CJ, or similar.

        $sample_deals = [
            [
                'title' => '50% Off on Electronics',
                'description' => 'Grab 50% discount on all electronics at Shop.com',
                'affiliate_url' => 'https://shop.com?affid=12345',
                'expiry' => '2025-12-31'
            ],
            [
                'title' => '20% Off Sports Gear',
                'description' => 'Save 20% on sports gear at SportyStore',
                'affiliate_url' => 'https://sportystore.com?ref=affiliate',
                'expiry' => '2025-11-30'
            ],
            [
                'title' => 'Buy 1 Get 1 Free Books',
                'description' => 'Exclusive BOGO offer on books at ReadMore',
                'affiliate_url' => 'https://readmore.com?affiliate=abc',
                'expiry' => '2025-12-15'
            ]
        ];

        // Filter sample deals by keyword presence (simple simulation)
        $filtered = array_filter($sample_deals, function ($deal) use ($keyword) {
            return stripos($deal['title'], $keyword) !== false || stripos($deal['description'], $keyword) !== false;
        });

        return $filtered ? $filtered : $sample_deals;
    }

    public function render_deals() {
        $deals = $this->fetch_deals();
        if (empty($deals)) {
            return '<p>No affiliate deals found for your niche yet.</p>';
        }

        $output = '<div class="adb-deals-container" style="border:1px solid #ccc; padding:10px;">';
        foreach ($deals as $deal) {
            $title = esc_html($deal['title']);
            $desc = esc_html($deal['description']);
            $url = esc_url($deal['affiliate_url']);
            $expiry = esc_html($deal['expiry']);
            $output .= "<div class='adb-deal' style='margin-bottom:15px;'>";
            $output .= "<h3><a href='$url' target='_blank' rel='nofollow noopener'>$title</a></h3>";
            $output .= "<p>$desc</p>";
            $output .= "<small>Expires: $expiry</small>";
            $output .= "</div>";
        }
        $output .= '</div>';

        return $output;
    }
}

new AffiliateDealBooster();
