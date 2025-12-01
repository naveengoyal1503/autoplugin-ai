<?php
/*
Plugin Name: AffiliDeals Curator
Plugin URI: https://example.com/affilideals
Description: AI-driven affiliate coupon and deals curator for WordPress sites.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliDeals_Curator.php
License: GPL2
Text Domain: affilideals-curator
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AffiliDealsCurator {
    private $option_name = 'affilideals_settings';
    private $deals_cache_key = 'affilideals_deals_cache';
    private $cache_duration = 3600; // 1 hour

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_shortcode('affilideals_deals', array($this, 'display_deals_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    public function enqueue_styles() {
        wp_register_style('affilideals_style', plugins_url('affilideals.css', __FILE__));
        wp_enqueue_style('affilideals_style');
    }

    public function add_admin_menu() {
        add_options_page('AffiliDeals Curator', 'AffiliDeals Curator', 'manage_options', 'affilideals_curator', array($this, 'options_page'));
    }

    public function settings_init() {
        register_setting('affilideals_group', $this->option_name);

        add_settings_section(
            'affilideals_section',
            __('API & Display Settings', 'affilideals-curator'),
            null,
            'affilideals_group'
        );

        add_settings_field(
            'api_key',
            __('Affiliate API Key', 'affilideals-curator'),
            array($this, 'render_api_key_field'),
            'affilideals_group',
            'affilideals_section'
        );

        add_settings_field(
            'niche_keywords',
            __('Niche Keywords (comma-separated)', 'affilideals-curator'),
            array($this, 'render_niche_keywords_field'),
            'affilideals_group',
            'affilideals_section'
        );

        add_settings_field(
            'deals_count',
            __('Number of Deals to Display', 'affilideals-curator'),
            array($this, 'render_deals_count_field'),
            'affilideals_group',
            'affilideals_section'
        );
    }

    public function render_api_key_field() {
        $options = get_option($this->option_name);
        ?>
        <input type='password' name='<?php echo esc_attr($this->option_name); ?>[api_key]' value='<?php echo isset($options['api_key']) ? esc_attr($options['api_key']) : ''; ?>' style='width:350px;'>
        <p class='description'>Enter your affiliate network API key to fetch deals.</p>
        <?php
    }

    public function render_niche_keywords_field() {
        $options = get_option($this->option_name);
        ?>
        <input type='text' name='<?php echo esc_attr($this->option_name); ?>[niche_keywords]' value='<?php echo isset($options['niche_keywords']) ? esc_attr($options['niche_keywords']) : ''; ?>' style='width:350px;'>
        <p class='description'>Comma-separated keywords to tailor deals (e.g. fitness, gadgets).</p>
        <?php
    }

    public function render_deals_count_field() {
        $options = get_option($this->option_name);
        $count = isset($options['deals_count']) ? intval($options['deals_count']) : 5;
        ?>
        <input type='number' name='<?php echo esc_attr($this->option_name); ?>[deals_count]' value='<?php echo $count; ?>' min='1' max='20' style='width:50px;'>
        <p class='description'>Set how many deals to display on the site.</p>
        <?php
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>AffiliDeals Curator Settings</h1>
            <form action='options.php' method='post'>
                <?php
                settings_fields('affilideals_group');
                do_settings_sections('affilideals_group');
                submit_button();
                ?>
            </form>
            <hr>
            <h2>Usage</h2>
            <p>Insert the shortcode <code>[affilideals_deals]</code> in posts, pages, or widgets to display curated affiliate coupons and deals tailored to your niche.</p>
        </div>
        <?php
    }

    /**
     * Runs a pseudo AI-powered deal fetch based on niche keywords.
     * For demo, this uses static sample deals.
     */
    private function fetch_deals() {
        $options = get_option($this->option_name);
        $keywords = isset($options['niche_keywords']) ? explode(',', $options['niche_keywords']) : array();
        $keywords = array_map('trim', $keywords);
        $count = isset($options['deals_count']) ? intval($options['deals_count']) : 5;

        // For demonstration, static deals database
        $all_deals = array(
            array('title' => '50% Off Running Shoes', 'link' => 'https://affiliate.example.com/deal1', 'description' => 'Get 50% off select running shoes for your fitness goals.'),
            array('title' => 'Buy 1 Get 1 Free Protein Powder', 'link' => 'https://affiliate.example.com/deal2', 'description' => 'Limited time offer on premium protein powders.'),
            array('title' => '30% Off Wireless Earbuds', 'link' => 'https://affiliate.example.com/deal3', 'description' => 'Top-rated wireless earbuds at discounted prices.'),
            array('title' => '25% Off Smart Home Gadgets', 'link' => 'https://affiliate.example.com/deal4', 'description' => 'Upgrade your home with smart gadgets.'),
            array('title' => 'Exclusive Laptop Deals', 'link' => 'https://affiliate.example.com/deal5', 'description' => 'Save big on high-performance laptops.'),
            array('title' => 'Fitness Tracker Sale', 'link' => 'https://affiliate.example.com/deal6', 'description' => 'Track your health with discounts on fitness trackers.'),
            array('title' => 'Discounts on Kitchen Appliances', 'link' => 'https://affiliate.example.com/deal7', 'description' => 'Cook smarter with these deals on appliances.'),
            array('title' => 'Save on Outdoor Gear', 'link' => 'https://affiliate.example.com/deal8', 'description' => 'Great discounts on camping and hiking gear.'),
            array('title' => 'Top DSLR Cameras Discount', 'link' => 'https://affiliate.example.com/deal9', 'description' => 'Capture every moment with camera deals.'),
            array('title' => 'Gaming Console Offers', 'link' => 'https://affiliate.example.com/deal10', 'description' => 'Latest gaming consoles at discounted prices.')
        );

        // Filter deals by keywords if provided
        if (!empty($keywords)) {
            $keyword_lower = array_map('strtolower', $keywords);
            $filtered = array();
            foreach ($all_deals as $deal) {
                foreach ($keyword_lower as $kw) {
                    if (stripos($deal['title'], $kw) !== false || stripos($deal['description'], $kw) !== false) {
                        $filtered[] = $deal;
                        break;
                    }
                }
            }
            $deals = !empty($filtered) ? $filtered : $all_deals;
        } else {
            $deals = $all_deals;
        }

        // Limit number of deals
        return array_slice($deals, 0, $count);
    }

    public function display_deals_shortcode() {
        $deals = get_transient($this->deals_cache_key);
        if ($deals === false) {
            $deals = $this->fetch_deals();
            set_transient($this->deals_cache_key, $deals, $this->cache_duration);
        }

        if (empty($deals)) {
            return '<p>No deals currently available.</p>';
        }

        $output = '<div class="affilideals-deals">';
        $output .= '<ul>';
        foreach ($deals as $deal) {
            $output .= sprintf(
                '<li><a href="%s" target="_blank" rel="nofollow noopener">%s</a><br/><small>%s</small></li>',
                esc_url($deal['link']),
                esc_html($deal['title']),
                esc_html($deal['description'])
            );
        }
        $output .= '</ul></div>';
        return $output;
    }
}

new AffiliDealsCurator();
