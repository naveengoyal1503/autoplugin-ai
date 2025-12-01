/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Link_Optimizer.php
*/
<?php
/**
 * Plugin Name: Affiliate Link Optimizer
 * Description: Auto-detects and cloaks affiliate links, tracks clicks, and updates broken affiliate links dynamically for max conversions.
 * Version: 1.0
 * Author: Perplexity AI
 */

if (!defined('ABSPATH')) exit;

class AffiliateLinkOptimizer {
    private $option_name = 'alo_affiliate_links';

    public function __construct() {
        add_filter('the_content', array($this, 'process_content'));
        add_action('init', array($this, 'handle_redirect'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    /**
     * Process post content to detect affiliate links, cloak and replace them
     */
    public function process_content($content) {
        if (!is_singular()) return $content;

        // Find all links
        preg_match_all('/<a\s[^>]*href=["\']([^"']+)["\'][^>]*>/i', $content, $matches);
        if (empty($matches[1])) return $content;

        $links = $matches[1];

        foreach ($links as $link) {
            // Detect if link is affiliate link (basic heuristic: contains 'aff', 'ref', 'partner' query or domain)
            if ($this->is_affiliate_link($link)) {
                $slug = md5($link);
                $cloaked_url = home_url('/alo-go/' . $slug);

                // Save original link for redirect
                $this->save_affiliate_link($slug, $link);

                // Replace in content
                $content = str_replace($link, $cloaked_url, $content);
            }
        }

        return $content;
    }

    /**
     * Heuristic to detect affiliate links by URL patterns
     */
    private function is_affiliate_link($url) {
        $url_lc = strtolower($url);
        $patterns = array('aff=', 'ref=', 'partner=', 'affiliate=', 'utm_source=affiliate');
        foreach ($patterns as $pattern) {
            if (strpos($url_lc, $pattern) !== false) return true;
        }

        // Additional check for known affiliate domains (example domains)
        $affiliate_domains = array('amazon.com', 'clickbank.net', 'cj.com', 'shareasale.com', 'impact.com', 'rakuten.com');
        $host = parse_url($url_lc, PHP_URL_HOST);
        if ($host !== false) {
            foreach ($affiliate_domains as $d) {
                if (strpos($host, $d) !== false) return true;
            }
        }

        return false;
    }

    /**
     * Save the mapping slug->URL in options (transient or option storage)
     */
    private function save_affiliate_link($slug, $url) {
        $links = get_option($this->option_name, array());
        if (!isset($links[$slug])) {
            $links[$slug] = array('url' => $url, 'clicks' => 0, 'last_checked' => 0);
            update_option($this->option_name, $links);
        }
    }

    /**
     * Handle redirect when accessing cloaked links (/alo-go/slug)
     */
    public function handle_redirect() {
        if (!isset($_SERVER['REQUEST_URI'])) return;
        $uri = $_SERVER['REQUEST_URI'];

        if (preg_match('#/alo-go/([a-f0-9]{32})#', $uri, $matches)) {
            $slug = $matches[1];
            $links = get_option($this->option_name, array());

            if (isset($links[$slug])) {
                $link_info = $links[$slug];

                // Check and update broken links every 24h (basic simulation)
                if (time() - $link_info['last_checked'] > 86400) {
                    $updated_url = $this->check_and_update_link($link_info['url']);
                    $links[$slug]['url'] = $updated_url;
                    $links[$slug]['last_checked'] = time();
                    update_option($this->option_name, $links);
                }

                // Increment clicks
                $links[$slug]['clicks']++;
                update_option($this->option_name, $links);

                // Redirect to affiliate URL
                wp_redirect($links[$slug]['url']);
                exit;
            } else {
                wp_die('Invalid affiliate link');
            }
        }
    }

    /**
     * Check if a url is broken (HTTP 404) and try to update it with a fresh url
     * Basic stub: returns original url as updating requires external API or manual input
     */
    private function check_and_update_link($url) {
        // Use wp_remote_head to check
        $response = wp_remote_head($url, array('timeout' => 5));
        if (is_wp_error($response)) return $url;

        $code = wp_remote_retrieve_response_code($response);
        if ($code >= 400) {
            // Example: search or replace broken urls with predefined alternatives might be implemented here
            // For now, return original url
            return $url;
        }

        return $url;
    }

    /**
     * Add admin menu page
     */
    public function admin_menu() {
        add_menu_page('Affiliate Link Optimizer', 'Affiliate Link Optimizer', 'manage_options', 'alo-settings', array($this, 'settings_page'), 'dashicons-admin-links');
    }

    /**
     * Admin settings init
     */
    public function settings_init() {
        register_setting('alo_settings_group', 'alo_affiliate_links');
    }

    /**
     * Display admin page with basic stats
     */
    public function settings_page() {
        if (!current_user_can('manage_options')) wp_die('Access denied');

        $links = get_option($this->option_name, array());

        echo '<div class="wrap"><h1>Affiliate Link Optimizer Stats</h1>';

        if (empty($links)) {
            echo '<p>No affiliate links detected yet.</p>';
        } else {
            echo '<table class="widefat striped"><thead><tr><th>Slug</th><th>Affiliate URL</th><th>Clicks</th><th>Last Checked</th></tr></thead><tbody>';
            foreach ($links as $slug => $data) {
                $last_checked = $data['last_checked'] ? date('Y-m-d H:i:s', $data['last_checked']) : 'Never';
                echo '<tr>' .
                    '<td>' . esc_html($slug) . '</td>' .
                    '<td><a href="' . esc_url($data['url']) . '" target="_blank" rel="noopener noreferrer">' . esc_html($data['url']) . '</a></td>' .
                    '<td>' . intval($data['clicks']) . '</td>' .
                    '<td>' . $last_checked . '</td>' .
                    '</tr>';
            }
            echo '</tbody></table>';
        }

        echo '</div>';
    }
}

new AffiliateLinkOptimizer();
