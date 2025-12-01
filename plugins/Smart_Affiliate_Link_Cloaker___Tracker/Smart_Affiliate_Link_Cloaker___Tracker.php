<?php
/*
Plugin Name: Smart Affiliate Link Cloaker & Tracker
Plugin URI: https://example.com/smart-affiliate-link-cloaker
Description: Automatically cloak, manage, and track affiliate links with built-in analytics to maximize affiliate revenue.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Cloaker___Tracker.php
License: GPL2
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class SmartAffiliateCloaker {

    private $option_name = 'salc_options';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_shortcode('affiliate_link', array($this, 'shortcode_affiliate_link'));
        add_action('template_redirect', array($this, 'handle_redirect'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'track_js')); 
        add_action('wp_ajax_salc_track_click', array($this, 'ajax_track_click'));
        add_action('wp_ajax_nopriv_salc_track_click', array($this, 'ajax_track_click'));
        register_activation_hook(__FILE__, array($this, 'on_activation'));
    }

    public function on_activation() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'salc_clicks';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
          id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
          affiliate_slug varchar(100) NOT NULL,
          clicked_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          ip_address varchar(45) DEFAULT NULL,
          user_agent text DEFAULT NULL,
          PRIMARY KEY  (id),
          KEY affiliate_slug (affiliate_slug)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function add_admin_menu() {
        add_menu_page('Affiliate Cloaker', 'Affiliate Cloaker', 'manage_options', 'salc_plugin', array($this, 'options_page'), 'dashicons-admin-links');
    }

    public function settings_init() {
        register_setting('salc_plugin', $this->option_name, array('sanitize_callback' => array($this, 'sanitize_options')));

        add_settings_section('salc_section_main', 'Affiliate Links Management', null, 'salc_plugin');

        add_settings_field(
            'salc_affiliates',
            'Affiliate Links',
            array($this, 'field_affiliates_render'),
            'salc_plugin',
            'salc_section_main'
        );
    }

    public function sanitize_options($input) {
        if (isset($input['affiliates'])) {
            $clean = array();
            foreach ($input['affiliates'] as $slug => $url) {
                $slug = sanitize_title($slug);
                $url = esc_url_raw(trim($url));
                if ($slug && $url) {
                    $clean['affiliates'][$slug] = $url;
                }
            }
            return $clean;
        }
        return array();
    }

    public function field_affiliates_render() {
        $options = get_option($this->option_name);
        $affiliates = isset($options['affiliates']) ? $options['affiliates'] : array();
        echo '<p>Define your affiliate links slug and target URLs to cloak and track them.</p>';
        echo '<table><tr><th>Slug (unique)</th><th>Affiliate URL</th></tr>';
        for ($i = 0; $i < 5; $i++) {
            $slug = isset(array_keys($affiliates)[$i]) ? esc_attr(array_keys($affiliates)[$i]) : '';
            $url = isset($affiliates[$slug]) ? esc_url($affiliates[$slug]) : '';
            echo '<tr><td><input type="text" name="'.$this->option_name.'[affiliates]['.$i.'][slug]" value="'.$slug.'" placeholder="my-affiliate" /></td>';
            echo '<td><input style="width:400px;" type="url" name="'.$this->option_name.'[affiliates]['.$i.'][url]" value="'.$url.'" placeholder="https://affiliate.example.com/ref=123" /></td></tr>';
        }
        echo '</table>';
        echo '<small>Add or update these affiliate links and save. Use shortcode [affiliate_link slug="your-slug"] to link or direct URL /go/your-slug to redirect and track.</small>';
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Link Cloaker & Tracker</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('salc_plugin');
                do_settings_sections('salc_plugin');
                submit_button();
                ?>
            </form>
            <h2>Affiliate Link Click Stats</h2>
            <?php $this->display_click_stats(); ?>
            <p>Use <code>[affiliate_link slug="your-slug"]Your Link Text[/affiliate_link]</code> shortcode or link to <code>/go/your-slug</code> on your site.</p>
        </div>
        <?php
    }

    public function display_click_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'salc_clicks';
        $results = $wpdb->get_results("SELECT affiliate_slug, COUNT(*) as clicks FROM $table GROUP BY affiliate_slug ORDER BY clicks DESC");
        if (!$results) {
            echo '<p>No clicks tracked yet.</p>';
            return;
        }
        echo '<table class="widefat striped"><thead><tr><th>Affiliate Slug</th><th>Clicks</th></tr></thead><tbody>';
        foreach ($results as $row) {
            echo '<tr><td>' . esc_html($row->affiliate_slug) . '</td><td>' . intval($row->clicks) . '</td></tr>';
        }
        echo '</tbody></table>';
    }

    public function shortcode_affiliate_link($atts, $content = null) {
        $atts = shortcode_atts(array('slug' => ''), $atts, 'affiliate_link');
        $slug = sanitize_title($atts['slug']);
        if (!$slug) return '';
        $url = $this->get_affiliate_url($slug);
        if (!$url) return '';
        $text = $content ? $content : $url;
        $link = esc_url(home_url('/go/' . $slug));
        return '<a href="' . $link . '" class="salc-affiliate-link" data-slug="' . esc_attr($slug) . '" target="_blank" rel="nofollow noopener">' . esc_html($text) . '</a>';
    }

    private function get_affiliate_url($slug) {
        $options = get_option($this->option_name);
        if (!empty($options['affiliates'][$slug])) {
            return $options['affiliates'][$slug];
        }
        return false;
    }

    public function handle_redirect() {
        $request_uri = trim($_SERVER['REQUEST_URI'], '/');
        if (preg_match('#^go/([a-zA-Z0-9_-]+)$#', $request_uri, $matches)) {
            $slug = sanitize_title($matches[1]);
            $url = $this->get_affiliate_url($slug);
            if ($url) {
                $this->record_click($slug);
                wp_redirect($url, 302);
                exit;
            }
        }
    }

    private function record_click($slug) {
        global $wpdb;
        $table = $wpdb->prefix . 'salc_clicks';

        $wpdb->insert($table, array(
            'affiliate_slug' => $slug,
            'clicked_at' => current_time('mysql'),
            'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : null,
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : null
        ));
    }

    public function enqueue_scripts() {
        wp_register_script('salc-tracker', plugins_url('/salc-tracker.js', __FILE__), array('jquery'), '1.0', true);
        wp_enqueue_script('salc-tracker');
        wp_localize_script('salc-tracker', 'salc_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php')
        ));
    }

    public function track_js() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('.salc-affiliate-link').on('click', function() {
                var slug = $(this).data('slug');
                $.post(salc_ajax.ajax_url, { action: 'salc_track_click', slug: slug });
            });
        });
        </script>
        <?php
    }

    public function ajax_track_click() {
        if (!isset($_POST['slug'])) {
            wp_send_json_error('Missing slug');
        }
        $slug = sanitize_title($_POST['slug']);
        if ($slug) {
            $this->record_click($slug);
            wp_send_json_success();
        } else {
            wp_send_json_error('Invalid slug');
        }
        wp_die();
    }
}

new SmartAffiliateCloaker();
