<?php
/*
Plugin Name: ContentRevenue Pro
Plugin URI: https://contentrevenuepro.com
Description: Monetize your WordPress site with integrated affiliate tracking, sponsored content management, and membership gatekeeping
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentRevenue_Pro.php
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if (!defined('ABSPATH')) {
    exit;
}

define('CRP_VERSION', '1.0.0');
define('CRP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CRP_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentRevenuePro {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_shortcode('crp_affiliate_link', array($this, 'affiliate_link_shortcode'));
        add_shortcode('crp_gated_content', array($this, 'gated_content_shortcode'));
        add_shortcode('crp_sponsored_badge', array($this, 'sponsored_badge_shortcode'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
    }

    public function activate_plugin() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $affiliate_table = $wpdb->prefix . 'crp_affiliate_links';
        $gated_table = $wpdb->prefix . 'crp_gated_content';
        $clicks_table = $wpdb->prefix . 'crp_link_clicks';
        
        $sql_affiliate = "CREATE TABLE IF NOT EXISTS $affiliate_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            link_id varchar(255) NOT NULL UNIQUE,
            original_url text NOT NULL,
            short_slug varchar(100) NOT NULL UNIQUE,
            program_name varchar(255),
            commission_rate float,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        $sql_gated = "CREATE TABLE IF NOT EXISTS $gated_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            membership_level varchar(100),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        $sql_clicks = "CREATE TABLE IF NOT EXISTS $clicks_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            link_id varchar(255) NOT NULL,
            user_id bigint(20),
            ip_address varchar(45),
            referrer text,
            clicked_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY link_id (link_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_affiliate);
        dbDelta($sql_gated);
        dbDelta($sql_clicks);
        
        update_option('crp_version', CRP_VERSION);
    }

    public function add_admin_menu() {
        add_menu_page(
            'ContentRevenue Pro',
            'ContentRevenue Pro',
            'manage_options',
            'crp_dashboard',
            array($this, 'render_dashboard'),
            'dashicons-money-alt',
            25
        );
        
        add_submenu_page(
            'crp_dashboard',
            'Affiliate Links',
            'Affiliate Links',
            'manage_options',
            'crp_affiliate_links',
            array($this, 'render_affiliate_links')
        );
        
        add_submenu_page(
            'crp_dashboard',
            'Gated Content',
            'Gated Content',
            'manage_options',
            'crp_gated_content',
            array($this, 'render_gated_content')
        );
        
        add_submenu_page(
            'crp_dashboard',
            'Analytics',
            'Analytics',
            'manage_options',
            'crp_analytics',
            array($this, 'render_analytics')
        );
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'crp_') === false) {
            return;
        }
        wp_enqueue_style('crp-admin-css', CRP_PLUGIN_URL . 'admin/css/style.css', array(), CRP_VERSION);
        wp_enqueue_script('crp-admin-js', CRP_PLUGIN_URL . 'admin/js/script.js', array('jquery'), CRP_VERSION, true);
        wp_localize_script('crp-admin-js', 'crpAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('crp_nonce')
        ));
    }

    public function enqueue_frontend_assets() {
        wp_enqueue_style('crp-frontend-css', CRP_PLUGIN_URL . 'frontend/css/style.css', array(), CRP_VERSION);
        wp_enqueue_script('crp-frontend-js', CRP_PLUGIN_URL . 'frontend/js/script.js', array('jquery'), CRP_VERSION, true);
    }

    public function register_rest_routes() {
        register_rest_route('crp/v1', '/affiliate-links', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_affiliate_link'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ));
        
        register_rest_route('crp/v1', '/click-track', array(
            'methods' => 'POST',
            'callback' => array($this, 'track_click'),
            'permission_callback' => '__return_true'
        ));
    }

    public function create_affiliate_link($request) {
        $params = $request->get_json_params();
        global $wpdb;
        
        $link_id = uniqid('crp_');
        $short_slug = sanitize_title($params['program_name'] ?? 'link') . '_' . substr(md5($link_id), 0, 6);
        
        $inserted = $wpdb->insert(
            $wpdb->prefix . 'crp_affiliate_links',
            array(
                'link_id' => $link_id,
                'original_url' => esc_url_raw($params['url']),
                'short_slug' => $short_slug,
                'program_name' => sanitize_text_field($params['program_name']),
                'commission_rate' => floatval($params['commission_rate'] ?? 0)
            ),
            array('%s', '%s', '%s', '%s', '%f')
        );
        
        if ($inserted) {
            return new WP_REST_Response(array(
                'success' => true,
                'link_id' => $link_id,
                'short_slug' => $short_slug
            ), 201);
        }
        
        return new WP_REST_Response(array('success' => false), 400);
    }

    public function track_click($request) {
        global $wpdb;
        $params = $request->get_json_params();
        $link_id = sanitize_text_field($params['link_id']);
        
        $link = $wpdb->get_row($wpdb->prepare(
            "SELECT original_url FROM {$wpdb->prefix}crp_affiliate_links WHERE link_id = %s",
            $link_id
        ));
        
        if ($link) {
            $wpdb->insert(
                $wpdb->prefix . 'crp_link_clicks',
                array(
                    'link_id' => $link_id,
                    'user_id' => get_current_user_id(),
                    'ip_address' => sanitize_text_field($_SERVER['REMOTE_ADDR']),
                    'referrer' => sanitize_url($_SERVER['HTTP_REFERER'] ?? '')
                ),
                array('%s', '%d', '%s', '%s')
            );
            
            return new WP_REST_Response(array(
                'success' => true,
                'redirect_url' => $link->original_url
            ), 200);
        }
        
        return new WP_REST_Response(array('success' => false), 404);
    }

    public function affiliate_link_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'text' => 'Click here',
            'class' => 'crp-affiliate-link'
        ), $atts);
        
        return sprintf(
            '<a href="#" class="%s" data-link-id="%s">%s</a>',
            esc_attr($atts['class']),
            esc_attr($atts['id']),
            esc_html($atts['text'])
        );
    }

    public function gated_content_shortcode($atts, $content = '') {
        $atts = shortcode_atts(array(
            'level' => 'premium'
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<div class="crp-gated-message">Please <a href="' . esc_url(wp_login_url()) . '">log in</a> to view this content.</div>';
        }
        
        return do_shortcode($content);
    }

    public function sponsored_badge_shortcode($atts) {
        $atts = shortcode_atts(array(
            'sponsor' => 'Sponsored',
            'url' => ''
        ), $atts);
        
        $badge = '<span class="crp-sponsored-badge">';
        if (!empty($atts['url'])) {
            $badge .= '<a href="' . esc_url($atts['url']) . '" target="_blank">';
        }
        $badge .= esc_html($atts['sponsor']);
        if (!empty($atts['url'])) {
            $badge .= '</a>';
        }
        $badge .= '</span>';
        
        return $badge;
    }

    public function render_dashboard() {
        global $wpdb;
        $total_clicks = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}crp_link_clicks");
        $total_links = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}crp_affiliate_links");
        
        echo '<div class="wrap">';
        echo '<h1>ContentRevenue Pro Dashboard</h1>';
        echo '<div class="crp-dashboard-stats">';
        echo '<div class="stat-box"><h3>Total Affiliate Links</h3><p>' . intval($total_links) . '</p></div>';
        echo '<div class="stat-box"><h3>Total Clicks</h3><p>' . intval($total_clicks) . '</p></div>';
        echo '</div>';
        echo '</div>';
    }

    public function render_affiliate_links() {
        echo '<div class="wrap">';
        echo '<h1>Manage Affiliate Links</h1>';
        echo '<button class="button button-primary" id="crp-add-link">Add New Link</button>';
        echo '<table class="wp-list-table widefat striped" id="crp-links-table"><thead><tr><th>Program</th><th>Short Slug</th><th>Commission</th><th>Clicks</th><th>Actions</th></tr></thead><tbody></tbody></table>';
        echo '</div>';
    }

    public function render_gated_content() {
        echo '<div class="wrap">';
        echo '<h1>Gated Content Manager</h1>';
        echo '<p>Select posts to gate behind membership levels.</p>';
        echo '<div id="crp-gated-selector"></div>';
        echo '</div>';
    }

    public function render_analytics() {
        global $wpdb;
        $clicks_data = $wpdb->get_results("SELECT DATE(clicked_at) as date, COUNT(*) as count FROM {$wpdb->prefix}crp_link_clicks GROUP BY DATE(clicked_at) ORDER BY date DESC LIMIT 30");
        
        echo '<div class="wrap">';
        echo '<h1>Analytics</h1>';
        echo '<div id="crp-analytics-chart"></div>';
        echo '<table class="wp-list-table widefat striped"><thead><tr><th>Date</th><th>Clicks</th></tr></thead><tbody>';
        foreach ($clicks_data as $row) {
            echo '<tr><td>' . esc_html($row->date) . '</td><td>' . intval($row->count) . '</td></tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    }
}

ContentRevenuePro::get_instance();
?>