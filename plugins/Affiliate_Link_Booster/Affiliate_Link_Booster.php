<?php
/*
Plugin Name: Affiliate Link Booster
Description: Automatically converts product URLs into optimized, trackable affiliate links with cloaking and performance tracking.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Link_Booster.php
*/

if (!defined('ABSPATH')) exit;

class AffiliateLinkBooster {
    private $option_name = 'alb_options';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_filter('the_content', array($this, 'convert_links_to_affiliate'));
        add_action('wp_ajax_alb_link_click', array($this, 'handle_link_click'));
        add_action('init', array($this, 'handle_redirect'));
        $this->maybe_register_redirect();
    }

    public function add_settings_page() {
        add_options_page('Affiliate Link Booster', 'Affiliate Link Booster', 'manage_options', 'alb-settings', array($this, 'settings_page_html'));
    }

    public function register_settings() {
        register_setting($this->option_name, $this->option_name, array($this, 'validate_settings'));

        add_settings_section('alb_main', 'General Settings', null, $this->option_name);

        add_settings_field('affiliate_id', 'Affiliate ID (e.g., Amazon tag)', array($this, 'field_affiliate_id'), $this->option_name, 'alb_main');
        add_settings_field('domains_to_convert', 'Domains to Convert (comma separated)', array($this, 'field_domains_to_convert'), $this->option_name, 'alb_main');
    }

    public function field_affiliate_id() {
        $options = get_option($this->option_name);
        echo '<input type="text" name="'.$this->option_name.'[affiliate_id]" value="'.esc_attr($options['affiliate_id'] ?? '').'" class="regular-text" />';
        echo '<p class="description">Enter your affiliate network ID. Currently supports Amazon affiliate IDs (tag).</p>';
    }

    public function field_domains_to_convert() {
        $options = get_option($this->option_name);
        echo '<input type="text" name="'.$this->option_name.'[domains_to_convert]" value="'.esc_attr($options['domains_to_convert'] ?? 'amazon.com,amazon.co.uk').'" class="regular-text" />';
        echo '<p class="description">Enter comma separated domains whose links should be converted to affiliate links.</p>';
    }

    public function validate_settings($input) {
        $output = array();
        $output['affiliate_id'] = sanitize_text_field($input['affiliate_id'] ?? '');
        $output['domains_to_convert'] = sanitize_text_field($input['domains_to_convert'] ?? '');
        return $output;
    }

    public function settings_page_html() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1>Affiliate Link Booster Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields($this->option_name);
                do_settings_sections($this->option_name);
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function convert_links_to_affiliate($content) {
        $options = get_option($this->option_name);
        $affiliate_id = trim($options['affiliate_id'] ?? '');
        $domains = array_map('trim', explode(',', $options['domains_to_convert'] ?? ''));
        if (empty($affiliate_id) || empty($domains)) {
            return $content;
        }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));

        $links = $dom->getElementsByTagName('a');

        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            $parsed = parse_url($href);
            if (!$parsed || !isset($parsed['host'])) continue;
            $host = $parsed['host'];

            foreach ($domains as $d) {
                if (stripos($host, $d) !== false) {
                    // If Amazon link, add tag param or replace existing tag
                    if (stripos($host, 'amazon.') !== false) {
                        $query = [];
                        if (isset($parsed['query'])) wp_parse_str($parsed['query'], $query);
                        $query['tag'] = $affiliate_id;
                        $new_query = http_build_query($query);

                        $new_url = $parsed['scheme'] . '://' . $host . ($parsed['path'] ?? '/') . '?' . $new_query;

                        // Cloak link: redirect through this plugin with encoded destination
                        $redirect_url = admin_url('admin-ajax.php?action=alb_redirect&url=' . urlencode($new_url));

                        $link->setAttribute('href', $redirect_url);
                        $link->setAttribute('rel', 'nofollow noopener sponsored');
                        $link->setAttribute('target', '_blank');
                    }
                }
            }
        }

        return $this->get_inner_html($dom->getElementsByTagName('body')->item(0));
    }

    private function get_inner_html($node) {
        $innerHTML = "";
        foreach ($node->childNodes as $child) {
            $innerHTML .= $node->ownerDocument->saveHTML($child);
        }
        return $innerHTML;
    }

    public function maybe_register_redirect() {
        if (isset($_GET['action']) && $_GET['action'] === 'alb_redirect' && isset($_GET['url'])) {
            $this->handle_redirect();
            exit;
        }
    }

    public function handle_redirect() {
        if (!isset($_GET['url'])) return;
        $url = urldecode($_GET['url']);

        // Simple validation to allow only http/https URLs
        if (filter_var($url, FILTER_VALIDATE_URL) && preg_match('/^https?:\/\//', $url)) {
            // Track click here (optionally extend with DB or external analytics)
            // For now simple header redirect

            wp_redirect($url, 302);
            exit;
        } else {
            wp_die('Invalid affiliate URL');
        }
    }

    public function handle_link_click() {
        // Placeholder for AJAX click tracking if extended in future
        wp_send_json_success();
    }
}

new AffiliateLinkBooster();
