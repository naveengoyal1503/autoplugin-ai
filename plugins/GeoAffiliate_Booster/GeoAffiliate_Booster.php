<?php
/*
Plugin Name: GeoAffiliate Booster
Description: Automatically insert and cloak affiliate links with geolocation targeting and scheduled promotions to boost commissions.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=GeoAffiliate_Booster.php
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class GeoAffiliateBooster {
    private $option_name = 'geoaffiliate_booster_options';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_filter('the_content', array($this, 'insert_affiliate_links'));

        // Ajax to get visitor IP country (simplified using PHP geoip extension if available)
    }

    public function add_admin_menu() {
        add_options_page('GeoAffiliate Booster', 'GeoAffiliate Booster', 'manage_options', 'geoaffiliate_booster', array($this, 'options_page'));
    }

    public function settings_init() {
        register_setting('geoaffiliate_booster', $this->option_name);

        add_settings_section(
            'geoaffiliate_section',
            'Affiliate Link Settings',
            null,
            'geoaffiliate_booster'
        );

        add_settings_field(
            'affiliate_links',
            'Affiliate Links',
            array($this, 'affiliate_links_render'),
            'geoaffiliate_booster',
            'geoaffiliate_section'
        );

        add_settings_field(
            'geo_rules',
            'Geolocation Rules',
            array($this, 'geo_rules_render'),
            'geoaffiliate_booster',
            'geoaffiliate_section'
        );

        add_settings_field(
            'schedule_rules',
            'Scheduled Promotions',
            array($this, 'schedule_rules_render'),
            'geoaffiliate_booster',
            'geoaffiliate_section'
        );
    }

    public function affiliate_links_render() {
        $options = get_option($this->option_name);
        $links = isset($options['affiliate_links']) ? $options['affiliate_links'] : '';
        echo '<textarea name="'.$this->option_name.'[affiliate_links]" rows="10" cols="50" placeholder="keyword|url|'cloaked_slug'. Example:\nProductA|https://affiliate.com/linkA|proda">'.esc_textarea($links).'</textarea>';
        echo '<p class="description">Enter one affiliate link per line as: <strong>keyword|URL|cloaked_slug</strong></p>';
    }

    public function geo_rules_render() {
        $options = get_option($this->option_name);
        $rules = isset($options['geo_rules']) ? $options['geo_rules'] : '';
        echo '<textarea name="'.$this->option_name.'[geo_rules]" rows="8" cols="50" placeholder="country_code|cloaked_slug|start_date|end_date. Example:\nUS|proda|2025-12-01|2025-12-31">'.esc_textarea($rules).'</textarea>';
        echo '<p class="description">Set geolocation rules per line: <strong>country_code|cloaked_slug|YYYY-MM-DD|YYYY-MM-DD</strong></p>';
    }

    public function schedule_rules_render() {
        $options = get_option($this->option_name);
        $schedules = isset($options['schedule_rules']) ? $options['schedule_rules'] : '';
        echo '<textarea name="'.$this->option_name.'[schedule_rules]" rows="8" cols="50" placeholder="cloaked_slug|start_date|end_date. Example:\nproda|2025-12-01|2025-12-15">'.esc_textarea($schedules).'</textarea>';
        echo '<p class="description">Set scheduled promotions: <strong>cloaked_slug|YYYY-MM-DD|YYYY-MM-DD</strong></p>';
    }

    public function options_page() {
        ?>
        <form action='options.php' method='post'>
            <h2>GeoAffiliate Booster Settings</h2>
            <?php
            settings_fields('geoaffiliate_booster');
            do_settings_sections('geoaffiliate_booster');
            submit_button();
            ?>
        </form>
        <?php
    }

    private function get_visitor_country() {
        $ip = $_SERVER['REMOTE_ADDR'];

        if (function_exists('geoip_country_code_by_name')) {
            $country = geoip_country_code_by_name($ip);
            return $country ?: 'XX';
        }

        return 'XX'; // Fallback unknown
    }

    private function is_date_in_range($start, $end) {
        $today = new DateTime('now', wp_timezone());
        $start_date = DateTime::createFromFormat('Y-m-d', $start, wp_timezone());
        $end_date = DateTime::createFromFormat('Y-m-d', $end, wp_timezone());

        if (!$start_date || !$end_date) return false;

        return ($today >= $start_date && $today <= $end_date);
    }

    public function insert_affiliate_links($content) {
        $options = get_option($this->option_name);
        if (empty($options['affiliate_links'])) return $content;

        $affiliate_links_raw = explode("\n", $options['affiliate_links']);
        $geo_rules_raw = !empty($options['geo_rules']) ? explode("\n", $options['geo_rules']) : [];
        $schedule_rules_raw = !empty($options['schedule_rules']) ? explode("\n", $options['schedule_rules']) : [];

        $visitor_country = $this->get_visitor_country();

        // Parse affiliate links
        $affiliate_links = [];
        foreach ($affiliate_links_raw as $line) {
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) === 3) {
                list($keyword, $url, $slug) = $parts;
                $affiliate_links[$slug] = ['keyword' => $keyword, 'url' => $url];
            }
        }

        // Parse geo rules
        $geo_rules = [];
        foreach ($geo_rules_raw as $line) {
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) === 4) {
                list($country_code, $slug, $start_date, $end_date) = $parts;
                $geo_rules[] = compact('country_code', 'slug', 'start_date', 'end_date');
            }
        }

        // Parse schedule rules
        $schedule_rules = [];
        foreach ($schedule_rules_raw as $line) {
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) === 3) {
                list($slug, $start_date, $end_date) = $parts;
                $schedule_rules[] = compact('slug', 'start_date', 'end_date');
            }
        }

        // Determine which affiliate links to use based on geolocation & schedule
        $active_slugs = [];

        foreach ($geo_rules as $rule) {
            if (strtoupper($rule['country_code']) === $visitor_country && $this->is_date_in_range($rule['start_date'], $rule['end_date'])) {
                $active_slugs[] = $rule['slug'];
            }
        }

        foreach ($schedule_rules as $rule) {
            if ($this->is_date_in_range($rule['start_date'], $rule['end_date'])) {
                if (!in_array($rule['slug'], $active_slugs)) {
                    $active_slugs[] = $rule['slug'];
                }
            }
        }

        if (empty($active_slugs)) {
            // No active promotions, use all available
            $active_slugs = array_keys($affiliate_links);
        }

        // Insert affiliate links into content by keywords
        foreach ($active_slugs as $slug) {
            if (!isset($affiliate_links[$slug])) continue;
            $keyword = preg_quote($affiliate_links[$slug]['keyword'], '/');
            $url = esc_url($affiliate_links[$slug]['url']);

            // Cloaked link: site_url('/go/' . $slug)
            $cloaked_url = esc_url(site_url('/go/' . $slug));

            // Replace first occurrence of keyword outside links
            $content = preg_replace_callback(
                '/(?<![>\w])(\b' . $keyword . '\b)(?![^<]*<\/a>)/i',
                function($matches) use ($cloaked_url, $keyword) {
                    return '<a href="' . $cloaked_url . '" target="_blank" rel="nofollow noopener">' . $matches . '</a>';
                },
                $content, 1
            );
        }

        return $content;
    }

}

// Cloaking redirect handler
add_action('init', function() {
    if (isset($_SERVER['REQUEST_URI']) && preg_match('#^/go/([a-zA-Z0-9-_]+)/?$#', $_SERVER['REQUEST_URI'], $matches)) {
        $slug = sanitize_text_field($matches[1]);
        $options = get_option('geoaffiliate_booster_options');
        if (empty($options['affiliate_links'])) {
            wp_die('Affiliate link not found', 404);
        }
        $affiliate_links_raw = explode("\n", $options['affiliate_links']);
        foreach ($affiliate_links_raw as $line) {
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) === 3 && $parts[2] === $slug) {
                wp_redirect($parts[1], 301);
                exit;
            }
        }
        wp_die('Affiliate link not found', 404);
    }
});

new GeoAffiliateBooster();
