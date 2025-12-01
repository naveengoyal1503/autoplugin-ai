<?php
/*
Plugin Name: ContentFlow Pro
Description: Convert blog posts into multiple formats and track affiliate performance
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentFlow_Pro.php
*/

if (!defined('ABSPATH')) exit;

define('CONTENTFLOW_VERSION', '1.0.0');
define('CONTENTFLOW_DIR', plugin_dir_path(__FILE__));
define('CONTENTFLOW_URL', plugin_dir_url(__FILE__));

class ContentFlowPro {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_shortcode('contentflow_stats', array($this, 'stats_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'ContentFlow Pro',
            'ContentFlow Pro',
            'manage_options',
            'contentflow-pro',
            array($this, 'admin_dashboard'),
            'dashicons-share',
            80
        );
        add_submenu_page(
            'contentflow-pro',
            'Repurpose Content',
            'Repurpose',
            'manage_options',
            'contentflow-repurpose',
            array($this, 'repurpose_page')
        );
        add_submenu_page(
            'contentflow-pro',
            'Analytics',
            'Analytics',
            'manage_options',
            'contentflow-analytics',
            array($this, 'analytics_page')
        );
        add_submenu_page(
            'contentflow-pro',
            'Settings',
            'Settings',
            'manage_options',
            'contentflow-settings',
            array($this, 'settings_page')
        );
    }

    public function admin_dashboard() {
        $stats = $this->get_dashboard_stats();
        echo '<div class="wrap">';
        echo '<h1>ContentFlow Pro Dashboard</h1>';
        echo '<div style="margin-top: 20px;">';
        echo '<p>Total Posts Repurposed: <strong>' . esc_html($stats['repurposed']) . '</strong></p>';
        echo '<p>Total Affiliate Clicks: <strong>' . esc_html($stats['affiliate_clicks']) . '</strong></p>';
        echo '<p>Active Repurposing Tasks: <strong>' . esc_html($stats['active_tasks']) . '</strong></p>';
        echo '</div>';
        echo '</div>';
    }

    public function repurpose_page() {
        $posts = get_posts(array('numberposts' => 50, 'post_type' => 'post'));
        echo '<div class="wrap">';
        echo '<h1>Repurpose Content</h1>';
        echo '<form method="post" action="">';
        wp_nonce_field('contentflow_repurpose');
        echo '<select name="post_id" id="post-select" style="width: 300px; padding: 8px;">';
        echo '<option value="">Select a post to repurpose</option>';
        foreach ($posts as $post) {
            echo '<option value="' . esc_attr($post->ID) . '">' . esc_html($post->post_title) . '</option>';
        }
        echo '</select>';
        echo '<br><br>';
        echo '<label><input type="checkbox" name="formats[]" value="twitter"> Twitter Threads</label><br>';
        echo '<label><input type="checkbox" name="formats[]" value="linkedin"> LinkedIn Posts</label><br>';
        echo '<label><input type="checkbox" name="formats[]" value="email"> Email Newsletter</label><br>';
        echo '<label><input type="checkbox" name="formats[]" value="video_script"> Video Script</label><br><br>';
        echo '<button type="submit" class="button button-primary">Generate Repurposed Content</button>';
        echo '</form>';
        echo '</div>';
    }

    public function analytics_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'contentflow_analytics';
        $clicks = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE metric_type='click'");
        $impressions = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE metric_type='impression'");
        
        echo '<div class="wrap">';
        echo '<h1>ContentFlow Analytics</h1>';
        echo '<div style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin-top: 20px;">';
        echo '<p>Total Clicks: <strong>' . esc_html($clicks) . '</strong></p>';
        echo '<p>Total Impressions: <strong>' . esc_html($impressions) . '</strong></p>';
        if ($impressions > 0) {
            $ctr = round(($clicks / $impressions) * 100, 2);
            echo '<p>Click-Through Rate: <strong>' . esc_html($ctr) . '%</strong></p>';
        }
        echo '</div>';
        echo '</div>';
    }

    public function settings_page() {
        echo '<div class="wrap">';
        echo '<h1>ContentFlow Pro Settings</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('contentflow_settings_group');
        do_settings_sections('contentflow_settings_page');
        echo '<table class="form-table">';
        echo '<tr><th scope="row"><label for="affiliate_api">Affiliate API Key</label></th>';
        echo '<td><input type="password" id="affiliate_api" name="contentflow_affiliate_api" value="' . esc_attr(get_option('contentflow_affiliate_api')) . '" style="width: 300px;"></td></tr>';
        echo '<tr><th scope="row"><label for="api_endpoint">API Endpoint</label></th>';
        echo '<td><input type="text" id="api_endpoint" name="contentflow_api_endpoint" value="' . esc_attr(get_option('contentflow_api_endpoint')) . '" style="width: 300px;"></td></tr>';
        echo '</table>';
        submit_button();
        echo '</form>';
        echo '</div>';
    }

    public function stats_shortcode() {
        $stats = $this->get_dashboard_stats();
        return '<div style="padding: 15px; background: #f0f0f0; border-radius: 5px;">' .
               'Repurposed: ' . esc_html($stats['repurposed']) . ' | ' .
               'Affiliate Clicks: ' . esc_html($stats['affiliate_clicks']) . '</div>';
    }

    public function register_rest_routes() {
        register_rest_route('contentflow/v1', '/repurpose', array(
            'methods' => 'POST',
            'callback' => array($this, 'repurpose_via_api'),
            'permission_callback' => function() { return current_user_can('manage_options'); }
        ));
    }

    public function repurpose_via_api($request) {
        $post_id = $request->get_param('post_id');
        $formats = $request->get_param('formats');
        
        if (!$post_id || !$formats) {
            return new WP_Error('invalid_params', 'Missing parameters', array('status' => 400));
        }

        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error('post_not_found', 'Post not found', array('status' => 404));
        }

        $repurposed = array();
        foreach ($formats as $format) {
            $repurposed[$format] = $this->generate_format($post, $format);
        }

        $this->log_repurposing($post_id, $formats);

        return new WP_REST_Response($repurposed, 200);
    }

    private function generate_format($post, $format) {
        $content = wp_strip_all_tags($post->post_content);
        $excerpt = substr($content, 0, 100) . '...';
        
        switch ($format) {
            case 'twitter':
                return array(
                    'format' => 'Twitter Thread',
                    'content' => $excerpt . ' #WordPress #ContentMarketing',
                    'character_count' => strlen($excerpt)
                );
            case 'linkedin':
                return array(
                    'format' => 'LinkedIn Post',
                    'content' => 'Key Insight: ' . $excerpt,
                    'word_count' => str_word_count($excerpt)
                );
            case 'email':
                return array(
                    'format' => 'Email Subject',
                    'content' => 'Must Read: ' . $post->post_title,
                    'preview' => $excerpt
                );
            case 'video_script':
                return array(
                    'format' => 'Video Script Outline',
                    'intro' => 'Today we discuss: ' . $post->post_title,
                    'main_points' => explode('. ', $excerpt),
                    'cta' => 'Learn more at [your blog link]'
                );
            default:
                return array('error' => 'Unknown format');
        }
    }

    private function log_repurposing($post_id, $formats) {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'contentflow_repurposing', array(
            'post_id' => $post_id,
            'formats' => implode(',', $formats),
            'timestamp' => current_time('mysql')
        ));
    }

    private function get_dashboard_stats() {
        global $wpdb;
        $repurposed = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->prefix . "contentflow_repurposing");
        $clicks = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->prefix . "contentflow_analytics WHERE metric_type='click'");
        $active = get_transient('contentflow_active_tasks') ?: 0;
        
        return array(
            'repurposed' => $repurposed ?: 0,
            'affiliate_clicks' => $clicks ?: 0,
            'active_tasks' => $active
        );
    }

    public function enqueue_admin_assets() {
        wp_enqueue_style('contentflow-admin', CONTENTFLOW_URL . 'assets/admin.css');
        wp_enqueue_script('contentflow-admin', CONTENTFLOW_URL . 'assets/admin.js', array('jquery'));
    }

    public function enqueue_frontend_assets() {
        wp_enqueue_style('contentflow-frontend', CONTENTFLOW_URL . 'assets/frontend.css');
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $table1 = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "contentflow_repurposing (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            formats longtext NOT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        $table2 = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "contentflow_analytics (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            affiliate_link varchar(500) NOT NULL,
            metric_type varchar(50) NOT NULL,
            source varchar(100),
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($table1);
        dbDelta($table2);
        
        update_option('contentflow_version', CONTENTFLOW_VERSION);
    }

    public function deactivate() {
        delete_transient('contentflow_active_tasks');
    }
}

$contentflow = new ContentFlowPro();
?>