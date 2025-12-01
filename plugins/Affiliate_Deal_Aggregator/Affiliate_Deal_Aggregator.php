<?php
/*
Plugin Name: Affiliate Deal Aggregator
Description: Auto-aggregate and display affiliate coupons and deals with affiliate links.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Aggregator.php
*/

if (!defined('ABSPATH')) exit;

class AffiliateDealAggregator {
    private $feed_url = 'https://example.com/affiliate-deals-feed.xml'; // Placeholder feed URL
    private $cache_key = 'ada_deals_cache';
    private $cache_ttl = 3600; // 1 hour cache

    public function __construct() {
        add_action('init', array($this, 'register_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function register_shortcode() {
        add_shortcode('affiliate_deals', array($this, 'render_deals'));
    }

    public function admin_menu() {
        add_options_page('Affiliate Deal Aggregator', 'Affiliate Deal Aggregator', 'manage_options', 'affiliate-deal-aggregator', array($this, 'settings_page'));
    }

    public function register_settings() {
        register_setting('ada_settings_group', 'ada_feed_url', array(
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => $this->feed_url
        ));
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h2>Affiliate Deal Aggregator Settings</h2>
            <form method="post" action="options.php">
                <?php settings_fields('ada_settings_group'); ?>
                <?php do_settings_sections('ada_settings_group'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Affiliate Deals Feed URL</th>
                        <td><input type="text" name="ada_feed_url" value="<?php echo esc_attr(get_option('ada_feed_url', $this->feed_url)); ?>" size="50" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    private function get_feed_url() {
        return get_option('ada_feed_url', $this->feed_url);
    }

    private function fetch_deals() {
        $cached = get_transient($this->cache_key);
        if ($cached !== false) {
            return $cached;
        }

        $url = $this->get_feed_url();
        $response = wp_remote_get($url, array('timeout' => 10));
        if (is_wp_error($response)) {
            return array();
        }

        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            return array();
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        if (!$xml) {
            return array();
        }

        $deals = array();
        foreach ($xml->deal as $deal) {
            $deals[] = array(
                'title' => (string)$deal->title,
                'link' => (string)$deal->link,
                'description' => (string)$deal->description
            );
            if (count($deals) >= 10) break; // Limit to 10 deals
        }

        set_transient($this->cache_key, $deals, $this->cache_ttl);

        return $deals;
    }

    public function render_deals($atts) {
        $deals = $this->fetch_deals();
        if (empty($deals)) {
            return '<p>No deals available at the moment. Please check back later.</p>';
        }

        $output = '<div class="affiliate-deal-aggregator"><ul>';
        foreach ($deals as $deal) {
            $title = esc_html($deal['title']);
            $link = esc_url($deal['link']);
            $desc = esc_html($deal['description']);
            $output .= "<li><a href='$link' target='_blank' rel='nofollow noopener'>$title</a><br/><small>$desc</small></li>";
        }
        $output .= '</ul></div>';

        return $output;
    }
}

new AffiliateDealAggregator();
