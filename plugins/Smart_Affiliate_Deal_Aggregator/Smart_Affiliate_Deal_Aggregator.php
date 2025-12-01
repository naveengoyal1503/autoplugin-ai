/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Deal_Aggregator.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Deal Aggregator
 * Description: Aggregates affiliate coupon codes and deals dynamically to increase affiliate earnings.
 * Version: 1.0
 * Author: Plugin Dev
 */

if (!defined('ABSPATH')) exit;

class SmartAffiliateDealAggregator {
    private $feed_urls = [
        'https://example.com/feed1.json',
        'https://example.com/feed2.json'
    ];
    private $cache_key = 'sada_deals_cache';
    private $cache_time = 3600; // 1 hour cache

    public function __construct() {
        add_shortcode('sada_deal_list', [$this, 'render_deals']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
    }

    public function enqueue_styles() {
        wp_enqueue_style('sada-styles', plugin_dir_url(__FILE__) . 'sada-styles.css');
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Deals', 'Smart Affiliate Deals', 'manage_options', 'sada-settings', [$this, 'settings_page']);
    }

    public function register_settings() {
        register_setting('sada_options', 'sada_feed_urls');
        add_settings_section('sada_main', 'Feed Settings', null, 'sada-settings');
        add_settings_field('sada_feed_urls', 'Feed URLs (one per line)', [$this, 'feed_urls_field'], 'sada-settings', 'sada_main');
    }

    public function feed_urls_field() {
        $feed_urls = get_option('sada_feed_urls', implode("\n", $this->feed_urls));
        echo '<textarea name="sada_feed_urls" rows="5" cols="50" class="large-text code">' . esc_textarea($feed_urls) . '</textarea>';
        echo '<p class="description">Enter JSON feed URLs to aggregate affiliate deals from, one URL per line.</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Deal Aggregator Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('sada_options');
                do_settings_sections('sada-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    private function fetch_feeds() {
        $urls = get_option('sada_feed_urls', implode("\n", $this->feed_urls));
        $urls = explode("\n", trim($urls));
        $deals = [];
        foreach ($urls as $url) {
            $url = trim($url);
            if (!$url) continue;
            $response = wp_remote_get($url, ['timeout' => 5]);
            if (is_wp_error($response)) continue;
            $body = wp_remote_retrieve_body($response);
            $json = json_decode($body, true);
            if (!is_array($json)) continue;
            foreach ($json as $deal) {
                if (isset($deal['title'], $deal['link'], $deal['affiliate_link'], $deal['description'])) {
                    $deals[] = $deal;
                }
            }
        }
        return $deals;
    }

    private function get_cached_deals() {
        $deals = get_transient($this->cache_key);
        if ($deals === false) {
            $deals = $this->fetch_feeds();
            set_transient($this->cache_key, $deals, $this->cache_time);
        }
        return $deals;
    }

    public function render_deals($atts) {
        $atts = shortcode_atts(['count' => 10], $atts, 'sada_deal_list');
        $deals = $this->get_cached_deals();
        if (empty($deals)) return '<p>No deals available at the moment.</p>';

        $deals = array_slice($deals, 0, intval($atts['count']));

        $output = '<div class="sada-deal-list">';
        foreach ($deals as $deal) {
            $title = esc_html($deal['title']);
            $description = esc_html($deal['description']);
            $link = esc_url($deal['affiliate_link']);
            $output .= "<div class='sada-deal-item'><h3><a href='$link' target='_blank' rel='nofollow noopener'>$title</a></h3><p>$description</p></div>";
        }
        $output .= '</div>';
        return $output;
    }
}

new SmartAffiliateDealAggregator();

// Basic CSS styles injected inline for self-containment
add_action('wp_head', function() {
    echo '<style>.sada-deal-list{border:1px solid #ddd;padding:10px;background:#fafafa}.sada-deal-item{margin-bottom:15px}.sada-deal-item h3{margin:0 0 5px 0;font-size:1.2em}.sada-deal-item p{margin:0;color:#333}</style>';
});
