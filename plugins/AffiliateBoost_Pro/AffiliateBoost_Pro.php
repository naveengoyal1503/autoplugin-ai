/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateBoost_Pro.php
*/
<?php
/**
 * Plugin Name: AffiliateBoost Pro
 * Description: Affiliate marketing and dynamic content recommendation plugin to increase affiliate revenues.
 * Version: 1.0
 * Author: Plugin Dev
 * License: GPL2
 */

if (!defined('ABSPATH')) exit;

class AffiliateBoostPro {

    private $option_name = 'affiliateboost_pro_settings';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_shortcode('affiliateboost_links', array($this, 'render_affiliate_links'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function add_admin_menu() {
        add_menu_page('AffiliateBoost Pro', 'AffiliateBoost Pro', 'manage_options', 'affiliateboost_pro', array($this, 'admin_page'), 'dashicons-chart-line');
    }

    public function settings_init() {
        register_setting('affiliateboost_pro_group', $this->option_name);

        add_settings_section(
            'affiliateboost_pro_section',
            __('AffiliateBoost Pro Settings', 'affiliateboost_pro'),
            null,
            'affiliateboost_pro'
        );

        add_settings_field(
            'affiliateboost_pro_links',
            __('Affiliate Links (JSON Array)', 'affiliateboost_pro'),
            array($this, 'render_links_field'),
            'affiliateboost_pro',
            'affiliateboost_pro_section'
        );
    }

    public function render_links_field() {
        $options = get_option($this->option_name);
        $links = isset($options['links']) ? $options['links'] : '[]';
        echo '<textarea name="' . esc_attr($this->option_name) . '[links]" rows="10" cols="50" placeholder="[{\"name\":\"Product A\",\"url\":\"http:\/\/example.com\"}]">' . esc_textarea($links) . '</textarea><p class="description">Enter affiliate links as a JSON array with \"name\" and \"url\" keys.</p>';
    }

    public function admin_page() {
        ?>
        <form action='options.php' method='post'>
            <h2>AffiliateBoost Pro</h2>
            <?php
            settings_fields('affiliateboost_pro_group');
            do_settings_sections('affiliateboost_pro');
            submit_button();
            ?>
        </form>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliateboost-pro-style', plugin_dir_url(__FILE__) . 'affiliateboost-pro.css');
    }

    private function parse_links() {
        $options = get_option($this->option_name);
        $json = isset($options['links']) ? $options['links'] : '[]';
        $links = json_decode($json, true);
        if (!is_array($links)) $links = array();
        return $links;
    }

    public function render_affiliate_links($atts = array()) {
        $links = $this->parse_links();
        if(empty($links)) {
            return '<p>No affiliate links configured.</p>';
        }

        // Simple smart shuffle to simulate dynamic personalization
        shuffle($links);
        $output = '<div class="affiliateboost-pro-links">';
        foreach ($links as $link) {
            if (isset($link['name']) && isset($link['url'])) {
                $name = esc_html($link['name']);
                $url = esc_url($link['url']);
                $output .= "<div class='affiliateboost-link-item'><a href='$url' target='_blank' rel='nofollow noopener noreferrer'>$name</a></div>";
            }
        }
        $output .= '</div>';
        return $output;
    }
}

new AffiliateBoostPro();
