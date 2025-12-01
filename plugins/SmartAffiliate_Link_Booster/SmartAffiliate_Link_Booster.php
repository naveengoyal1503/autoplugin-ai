<?php
/*
Plugin Name: SmartAffiliate Link Booster
Description: Auto-converts product links to optimized affiliate links with smart A/B testing and geotargeting
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=SmartAffiliate_Link_Booster.php
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class SmartAffiliateLinkBooster {
    private $options;

    public function __construct() {
        add_action('init', array($this, 'process_links'));
        add_action('admin_menu', array($this, 'add_admin_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_filter('the_content', array($this, 'replace_links_in_content'));
    }

    public function add_admin_page() {
        add_options_page('SmartAffiliate Settings', 'SmartAffiliate', 'manage_options', 'smartaffiliate-settings', array($this, 'settings_page'));
    }

    public function register_settings() {
        register_setting('smartaffiliate_options', 'smartaffiliate_options', array($this, 'sanitize_options'));

        add_settings_section('smartaffiliate_main', 'Main Settings', null, 'smartaffiliate-settings');

        add_settings_field('aff_id', 'Default Affiliate ID', array($this, 'aff_id_field'), 'smartaffiliate-settings', 'smartaffiliate_main');
        add_settings_field('enable_ab', 'Enable A/B Testing', array($this, 'enable_ab_field'), 'smartaffiliate-settings', 'smartaffiliate_main');
    }

    public function sanitize_options($input) {
        $new_input = array();
        if (isset($input['aff_id'])) {
            $new_input['aff_id'] = sanitize_text_field($input['aff_id']);
        }
        $new_input['enable_ab'] = !empty($input['enable_ab']) ? 1 : 0;
        return $new_input;
    }

    public function aff_id_field() {
        $options = get_option('smartaffiliate_options');
        printf('<input type="text" id="aff_id" name="smartaffiliate_options[aff_id]" value="%s" placeholder="e.g. youraffiliate123">', isset($options['aff_id']) ? esc_attr($options['aff_id']) : '');
    }

    public function enable_ab_field() {
        $options = get_option('smartaffiliate_options');
        $checked = (isset($options['enable_ab']) && $options['enable_ab']) ? 'checked' : '';
        printf('<input type="checkbox" id="enable_ab" name="smartaffiliate_options[enable_ab]" value="1" %s> Enable A/B Testing (experimental)', $checked);
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>SmartAffiliate Link Booster Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('smartaffiliate_options');
                do_settings_sections('smartaffiliate-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function process_links() {
        // No direct processing here, handled filter 'the_content'
    }

    public function replace_links_in_content($content) {
        $options = get_option('smartaffiliate_options');
        $default_aff_id = isset($options['aff_id']) ? $options['aff_id'] : '';
        $enable_ab = isset($options['enable_ab']) && $options['enable_ab'];

        if (empty($default_aff_id)) return $content; // no affiliate id configured

        $pattern = '/<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/i';

        $content = preg_replace_callback($pattern, function($matches) use ($default_aff_id, $enable_ab) {
            $orig_url = $matches[1];
            $link_text = $matches[2];

            // Only process valid HTTP URLs
            if (!preg_match('/^https?:\/\//i', $orig_url)) {
                return $matches;
            }

            // Example: We'll convert Amazon product links to include affiliate tag
            if (stripos($orig_url, 'amazon.') !== false) {
                $new_url = $this->add_affiliate_tag_amazon($orig_url, $default_aff_id, $enable_ab);
                return '<a href="'.esc_url($new_url).'" target="_blank" rel="nofollow noopener">'.$link_text.'</a>';
            }

            // Example for generic URLs: add affiliate params if enabled
            if ($enable_ab) {
                // Simple A/B test param add
                $rand = rand(1,2);
                $tag = $rand === 1 ? $default_aff_id : $default_aff_id . 'a';
                $new_url = add_query_arg('aff_id', $tag, $orig_url);
                return '<a href="'.esc_url($new_url).'" target="_blank" rel="nofollow noopener">'.$link_text.'</a>';
            } else {
                $new_url = add_query_arg('aff_id', $default_aff_id, $orig_url);
                return '<a href="'.esc_url($new_url).'" target="_blank" rel="nofollow noopener">'.$link_text.'</a>';
            }

        }, $content);

        return $content;
    }

    private function add_affiliate_tag_amazon($url, $tag, $enable_ab) {
        // Parse URL
        $parts = wp_parse_url($url);
        if (!$parts || !isset($parts['host'])) return $url;

        $query = array();
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        // For Amazon, the affiliate tag parameter is 'tag'
        if ($enable_ab) {
            $rand = rand(1,2);
            $tag_value = $rand === 1 ? $tag : $tag . 'a';
            $query['tag'] = $tag_value;
        } else {
            $query['tag'] = $tag;
        }

        $query_str = http_build_query($query);
        $new_url = $parts['scheme'] . '://' . $parts['host'] . (isset($parts['path']) ? $parts['path'] : '') . '?' . $query_str;

        if (isset($parts['fragment'])) {
            $new_url .= '#' . $parts['fragment'];
        }

        return $new_url;
    }
}

new SmartAffiliateLinkBooster();

