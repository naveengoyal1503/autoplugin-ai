/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Booster.php
*/
<?php
/**
 * Plugin Name: Affiliate Deal Booster
 * Description: Auto-curate and display personalized affiliate coupon deals to boost affiliate income.
 * Version: 1.0
 * Author: YourName
 * License: GPL2
 */

if (!defined('ABSPATH')) exit;

class AffiliateDealBooster {
    private $option_name = 'adb_options';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('affiliate_deals', array($this, 'display_deals'));

        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function enqueue_assets() {
        wp_enqueue_style('adb-styles', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function add_admin_menu() {
        add_options_page('Affiliate Deal Booster', 'Affiliate Deal Booster', 'manage_options', 'affiliate-deal-booster', array($this, 'settings_page'));
    }

    public function register_settings() {
        register_setting($this->option_name, $this->option_name);
        add_settings_section('adb_main_section', 'Settings', null, $this->option_name);
        add_settings_field('adb_affiliate_id', 'Default Affiliate ID', array($this, 'affiliate_id_field'), $this->option_name, 'adb_main_section');
        add_settings_field('adb_coupon_sources', 'Coupon Sources', array($this, 'coupon_sources_field'), $this->option_name, 'adb_main_section');
    }

    public function affiliate_id_field() {
        $options = get_option($this->option_name);
        $value = isset($options['affiliate_id']) ? esc_attr($options['affiliate_id']) : '';
        echo '<input type="text" name="'.$this->option_name.'[affiliate_id]" value="'.$value.'" placeholder="e.g. myaffid123" style="width: 300px;" />';
        echo '<p class="description">Your default affiliate ID to append to all links.</p>';
    }

    public function coupon_sources_field() {
        $options = get_option($this->option_name);
        $value = isset($options['coupon_sources']) ? esc_textarea($options['coupon_sources']) : "https://example-coupon-api.com/api?category=tech\nhttps://another-coupon-source.com/rss";
        echo '<textarea name="'.$this->option_name.'[coupon_sources]" rows="5" cols="50" placeholder="One URL per line">'.$value.'</textarea>';
        echo '<p class="description">Enter coupon feed URLs or APIs, one per line.</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Deal Booster Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields($this->option_name);
                do_settings_sections($this->option_name);
                submit_button();
                ?>
            </form>
            <p>Use shortcode <code>[affiliate_deals]</code> in pages or posts to show deals.</p>
        </div>
        <?php
    }

    private function get_cached_deals() {
        $cache = get_transient('adb_cached_deals');
        if ($cache !== false) {
            return $cache;
        }
        $deals = $this->fetch_deals();
        set_transient('adb_cached_deals', $deals, 12 * HOUR_IN_SECONDS);
        return $deals;
    }

    private function fetch_deals() {
        $options = get_option($this->option_name);
        if (!isset($options['coupon_sources'])) {
            return array();
        }
        $sources = array_filter(array_map('trim', explode("\n", $options['coupon_sources'])));
        $deals = array();
        foreach ($sources as $url) {
            $response = wp_remote_get($url, array('timeout' => 5));
            if (is_wp_error($response)) {
                continue;
            }
            $body = wp_remote_retrieve_body($response);
            if (empty($body)) {
                continue;
            }
            // Attempt JSON decoding
            $json = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                foreach ($json as $item) {
                    if (isset($item['title']) && isset($item['url'])) {
                        $deals[] = array(
                            'title' => sanitize_text_field($item['title']),
                            'url'   => esc_url_raw($item['url']),
                            'desc'  => isset($item['description']) ? sanitize_text_field($item['description']) : '',
                            'expiry'=> isset($item['expiry']) ? sanitize_text_field($item['expiry']) : ''
                        );
                    }
                }
                continue;
            }
            // Try simple RSS parsing
            if (strpos($body, '<rss') === 0 || strpos($body, '<?xml') === 0) {
                $xml = simplexml_load_string($body);
                if ($xml && isset($xml->channel->item)) {
                    foreach ($xml->channel->item as $item) {
                        $deals[] = array(
                            'title' => (string) $item->title,
                            'url'   => (string) $item->link,
                            'desc'  => (string) $item->description,
                            'expiry'=> ''
                        );
                    }
                }
            }
        }
        return $this->filter_and_prepare_deals($deals);
    }

    private function filter_and_prepare_deals($deals) {
        $options = get_option($this->option_name);
        $affid = isset($options['affiliate_id']) ? trim($options['affiliate_id']) : '';
        $prepared = array();
        foreach ($deals as $deal) {
            $title = $deal['title'];
            $desc = $deal['desc'];
            $url = $deal['url'];
            if ($affid) {
                // Append affiliate ID as query param
                $url = $this->append_affiliate_id($url, $affid);
            }
            $prepared[] = array('title' => $title, 'desc' => $desc, 'url' => esc_url($url));
        }
        return $prepared;
    }

    private function append_affiliate_id($url, $affid) {
        $parsed = parse_url($url);
        $query = isset($parsed['query']) ? $parsed['query'] : '';
        parse_str($query, $params);
        $params['aff_id'] = $affid;
        $query_new = http_build_query($params);

        $scheme = isset($parsed['scheme']) ? $parsed['scheme'] . '://' : '';
        $host = isset($parsed['host']) ? $parsed['host'] : '';
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $path = isset($parsed['path']) ? $parsed['path'] : '';

        return $scheme . $host . $port . $path . '?' . $query_new;
    }

    public function display_deals() {
        $deals = $this->get_cached_deals();
        if (empty($deals)) {
            return '<p>No deals available at the moment. Please check back later.</p>';
        }
        $html = '<div class="adb-deals-container"><ul class="adb-deal-list">';
        foreach ($deals as $deal) {
            $title = esc_html($deal['title']);
            $desc = esc_html($deal['desc']);
            $url = esc_url($deal['url']);
            $html .= "<li class='adb-deal-item'><a href='{$url}' target='_blank' rel='nofollow noopener'>{$title}</a>";
            if ($desc) {
                $html .= "<p class='adb-deal-desc'>{$desc}</p>";
            }
            $html .= '</li>';
        }
        $html .= '</ul></div>';
        return $html;
    }
}

new AffiliateDealBooster();
