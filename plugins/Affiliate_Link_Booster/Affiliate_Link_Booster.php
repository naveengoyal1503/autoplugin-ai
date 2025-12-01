<?php
/*
Plugin Name: Affiliate Link Booster
Description: Automatically cloaks, categorizes, geotargets, and schedules affiliate links for higher commissions.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Link_Booster.php
*/

if (!defined('ABSPATH')) exit;

class AffiliateLinkBooster {
    private $option_name = 'alb_links';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_filter('the_content', array($this, 'replace_links_in_content'));
    }

    public function add_admin_menu() {
        add_menu_page('Affiliate Link Booster', 'Affiliate Link Booster', 'manage_options', 'affiliate_link_booster', array($this, 'options_page'));
    }

    public function settings_init() {
        register_setting('alb_plugin', $this->option_name);

        add_settings_section(
            'alb_plugin_section',
            __('Affiliate Links Management', 'alb'),
            null,
            'alb_plugin'
        );

        add_settings_field(
            'alb_links_field',
            __('Affiliate Links (JSON format)', 'alb'),
            array($this, 'links_field_render'),
            'alb_plugin',
            'alb_plugin_section'
        );
    }

    public function links_field_render() {
        $options = get_option($this->option_name, '{}');
        echo '<textarea cols="60" rows="10" name="'.$this->option_name.'">'.esc_textarea($options).'</textarea>';
        echo '<p class="description">Enter your links as a JSON array. Each entry: {"keyword":"keyword", "url":"https://affiliate.link", "category":"cat", "countries":["US","CA"], "start_date":"YYYY-MM-DD", "end_date":"YYYY-MM-DD"}</p>';
    }

    public function options_page() {
        ?>
        <form action='options.php' method='post'>
            <h1>Affiliate Link Booster</h1>
            <?php
            settings_fields('alb_plugin');
            do_settings_sections('alb_plugin');
            submit_button();
            ?>
        </form>
        <?php
    }

    private function get_active_links() {
        $json = get_option($this->option_name, '{}');
        $links = json_decode($json, true);
        if (!is_array($links)) return array();

        $now = new DateTime();
        $user_country = $this->get_user_country();

        $active_links = array();
        foreach ($links as $link) {
            // Validate fields
            if (!isset($link['keyword']) || !isset($link['url'])) continue;

            // Date filtering
            if (isset($link['start_date'])) {
                $start = DateTime::createFromFormat('Y-m-d', $link['start_date']);
                if ($start && $now < $start) continue;
            }
            if (isset($link['end_date'])) {
                $end = DateTime::createFromFormat('Y-m-d', $link['end_date']);
                if ($end && $now > $end) continue;
            }

            // Geo-filtering
            if (isset($link['countries']) && is_array($link['countries'])) {
                if (!in_array($user_country, $link['countries'])) continue;
            }

            $active_links[] = $link;
        }
        return $active_links;
    }

    public function replace_links_in_content($content) {
        $links = $this->get_active_links();
        if (empty($links)) return $content;

        foreach ($links as $link) {
            $keyword = preg_quote($link['keyword'], '/');
            $url = esc_url($link['url']);

            // Cloaking with internal affiliate redirect
            $cloak_url = admin_url('admin-post.php?action=alb_redirect&url='.urlencode($url));

            // Replace only first occurrence per keyword
            $pattern = '/(?<!<a[^>]*?>)\b(' . $keyword . ')\b(?!<\/a>)/i';
            $replacement = '<a href="' . esc_attr($cloak_url) . '" target="_blank" rel="nofollow noopener">$1</a>';
            $content = preg_replace($pattern, $replacement, $content, 1);
        }
        return $content;
    }

    public function redirect_handler() {
        if (!isset($_GET['url'])) wp_die('Missing URL parameter');

        $url = esc_url_raw($_GET['url']);

        // Optional: Add tracking, logging, or nonce check here
        wp_redirect($url, 302);
        exit;
    }

    private function get_user_country() {
        // Simple geoIP based on HTTP header for demonstration
        if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
            return strtoupper(sanitize_text_field($_SERVER['HTTP_CF_IPCOUNTRY']));
        }
        // Default/fallback
        return '';
    }
}

$affiliateLinkBooster = new AffiliateLinkBooster();
add_action('admin_post_alb_redirect', array($affiliateLinkBooster, 'redirect_handler'));
add_action('admin_post_nopriv_alb_redirect', array($affiliateLinkBooster, 'redirect_handler'));