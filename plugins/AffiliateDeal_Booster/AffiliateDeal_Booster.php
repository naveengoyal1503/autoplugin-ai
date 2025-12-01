<?php
/*
Plugin Name: AffiliateDeal Booster
Description: Aggregates and displays targeted affiliate coupons and deals to increase affiliate revenue.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateDeal_Booster.php
*/

if (!defined('ABSPATH')) exit;

class AffiliateDealBooster{
    private $option_name = 'adb_deals';

    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affiliate_deals', array($this, 'display_deals'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('adb-style', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function admin_menu() {
        add_menu_page('AffiliateDeal Booster', 'AffiliateDeal Booster', 'manage_options', 'affiliate-deal-booster', array($this, 'settings_page'), 'dashicons-megaphone');
    }

    public function settings_init() {
        register_setting('adb_settings', $this->option_name);

        add_settings_section('adb_section', 'Deal Sources & Settings', null, 'affiliate-deal-booster');

        add_settings_field('adb_sources', 'Affiliate Deal Sources (JSON Feed URLs)', array($this, 'render_sources'), 'affiliate-deal-booster', 'adb_section');

        add_settings_field('adb_title', 'Widget Title', array($this, 'render_title'), 'affiliate-deal-booster', 'adb_section');
    }

    public function render_sources() {
        $options = get_option($this->option_name);
        $sources = isset($options['sources']) ? esc_textarea($options['sources']) : "";
        echo '<textarea name="'.$this->option_name.'[sources]" rows="6" style="width:100%;" placeholder="Enter JSON feed URLs one per line">' . $sources . '</textarea>';
        echo '<p class="description">Enter one JSON feed URL per line to aggregate affiliate coupons and deals.</p>';
    }

    public function render_title() {
        $options = get_option($this->option_name);
        $title = isset($options['title']) ? esc_attr($options['title']) : 'Latest Deals';
        echo '<input type="text" name="'.$this->option_name.'[title]" value="' . $title . '" class="regular-text" />';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
        <h1>AffiliateDeal Booster Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('adb_settings');
            do_settings_sections('affiliate-deal-booster');
            submit_button();
            ?>
        </form>
        <p>Use shortcode <code>[affiliate_deals]</code> to display curated affiliate deals anywhere on your site.</p>
        </div>
        <?php
    }

    private function fetch_deals() {
        $options = get_option($this->option_name);
        if (empty($options['sources'])) {
            return array();
        }
        $sources = explode("\n", $options['sources']);
        $deals = array();

        foreach ($sources as $url) {
            $url = trim($url);
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                $response = wp_remote_get($url, array('timeout' => 5));
                if (!is_wp_error($response)) {
                    $body = wp_remote_retrieve_body($response);
                    $json = json_decode($body, true);
                    if (is_array($json)) {
                        foreach ($json as $deal) {
                            if (isset($deal['title']) && isset($deal['link'])) {
                                $deals[] = $deal;
                            }
                        }
                    }
                }
            }
        }
        // Shuffle and return a max of 10 deals
        shuffle($deals);
        return array_slice($deals, 0, 10);
    }

    public function display_deals() {
        $options = get_option($this->option_name);
        $title = !empty($options['title']) ? esc_html($options['title']) : 'Latest Deals';
        $deals = $this->fetch_deals();

        if (empty($deals)) {
            return '<p>No deals found.</p>';
        }

        $out = "<div class='adb-widget'><h3>{$title}</h3><ul class='adb-deal-list'>";
        foreach ($deals as $deal) {
            $deal_title = esc_html($deal['title']);
            $deal_link = esc_url(add_query_arg('ref', 'affbooster', $deal['link']));
            $description = isset($deal['description']) ? esc_html($deal['description']) : '';
            $out .= "<li><a href='{$deal_link}' target='_blank' rel='nofollow noopener'>{$deal_title}</a>";
            if($description) {
                $out .= "<br><small>{$description}</small>";
            }
            $out .= "</li>";
        }
        $out .= '</ul></div>';
        return $out;
    }
}

new AffiliateDealBooster();
