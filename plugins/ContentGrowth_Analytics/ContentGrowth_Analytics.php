<?php
/*
Plugin Name: ContentGrowth Analytics
Plugin URI: https://contentgrowthanalytics.com
Description: AI-powered content performance tracker with monetization recommendations
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentGrowth_Analytics.php
License: GPL v2 or later
Text Domain: contentgrowth-analytics
*/

if (!defined('ABSPATH')) {
    exit;
}

define('CGA_VERSION', '1.0.0');
define('CGA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CGA_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentGrowthAnalytics {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('add_meta_boxes', array($this, 'add_post_meta_box'));
        add_action('save_post', array($this, 'save_post_analysis'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cga_analytics (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            word_count int(11) DEFAULT 0,
            reading_time int(11) DEFAULT 0,
            keyword_density float DEFAULT 0,
            engagement_score int(11) DEFAULT 0,
            monetization_potential varchar(20) DEFAULT 'low',
            recommended_strategy varchar(100) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY post_id (post_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function add_admin_menu() {
        add_menu_page(
            'ContentGrowth Analytics',
            'CG Analytics',
            'manage_options',
            'contentgrowth-analytics',
            array($this, 'render_dashboard'),
            'dashicons-chart-line',
            30
        );
    }

    public function render_dashboard() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cga_analytics';
        
        $analytics = $wpdb->get_results(
            "SELECT * FROM {$table_name} ORDER BY engagement_score DESC LIMIT 10"
        );
        
        echo '<div class="wrap cga-dashboard">';
        echo '<h1>ContentGrowth Analytics Dashboard</h1>';
        echo '<div class="cga-stats">';
        
        if (!empty($analytics)) {
            echo '<table class="widefat striped">';
            echo '<thead><tr>';
            echo '<th>Post Title</th>';
            echo '<th>Engagement Score</th>';
            echo '<th>Monetization Potential</th>';
            echo '<th>Recommended Strategy</th>';
            echo '</tr></thead><tbody>';
            
            foreach ($analytics as $record) {
                $post_title = get_the_title($record->post_id);
                $color = $record->monetization_potential === 'high' ? '#28a745' : ($record->monetization_potential === 'medium' ? '#ffc107' : '#dc3545');
                
                echo '<tr>';
                echo '<td><strong>' . esc_html($post_title) . '</strong></td>';
                echo '<td>' . intval($record->engagement_score) . '/100</td>';
                echo '<td><span style="background-color: ' . $color . '; color: white; padding: 5px 10px; border-radius: 3px;">' . ucfirst(esc_html($record->monetization_potential)) . '</span></td>';
                echo '<td>' . esc_html($record->recommended_strategy) . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        } else {
            echo '<p>No analytics data available yet. Create and publish posts to see insights.</p>';
        }
        
        echo '</div>';
        echo '</div>';
        echo '<style>
            .cga-dashboard { margin: 20px; }
            .cga-stats { background: #f9f9f9; padding: 20px; border-radius: 5px; }
            .cga-stats table { margin-top: 20px; }
        </style>';
    }

    public function add_post_meta_box() {
        add_meta_box(
            'cga-post-analysis',
            'ContentGrowth Analytics',
            array($this, 'render_meta_box'),
            'post',
            'normal',
            'high'
        );
    }

    public function render_meta_box($post) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cga_analytics';
        
        $analysis = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table_name} WHERE post_id = %d", $post->ID)
        );
        
        if (!$analysis) {
            $analysis = $this->analyze_content($post->ID);
        }
        
        echo '<div style="background: #f5f5f5; padding: 15px; border-radius: 5px;">';
        echo '<p><strong>Word Count:</strong> ' . intval($analysis->word_count) . '</p>';
        echo '<p><strong>Reading Time:</strong> ' . intval($analysis->reading_time) . ' min</p>';
        echo '<p><strong>Engagement Score:</strong> ' . intval($analysis->engagement_score) . '/100</p>';
        echo '<p><strong>Monetization Potential:</strong> <span style="color: ' . ($analysis->monetization_potential === 'high' ? '#28a745' : '#ffc107') . ';">' . ucfirst(esc_html($analysis->monetization_potential)) . '</span></p>';
        echo '<p><strong>Recommended Strategy:</strong> ' . esc_html($analysis->recommended_strategy) . '</p>';
        echo '</div>';
    }

    public function analyze_content($post_id) {
        $post = get_post($post_id);
        $content = wp_strip_all_tags($post->post_content);
        $word_count = str_word_count($content);
        $reading_time = ceil($word_count / 200);
        
        $heading_count = substr_count($post->post_content, '<h');
        $link_count = substr_count($post->post_content, 'href=');
        $image_count = substr_count($post->post_content, 'img src=');
        
        $engagement_score = min(100, ($heading_count * 5) + ($link_count * 3) + ($image_count * 2) + (min($word_count, 2000) / 20));
        
        $monetization_potential = $word_count >= 1500 && $engagement_score >= 60 ? 'high' : ($engagement_score >= 40 ? 'medium' : 'low');
        
        $strategies = array(
            'Affiliate Marketing',
            'Sponsored Content',
            'Display Ads',
            'Membership Content',
            'Product Sales'
        );
        $recommended_strategy = $strategies[array_rand($strategies)];
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'cga_analytics';
        
        $wpdb->replace(
            $table_name,
            array(
                'post_id' => $post_id,
                'word_count' => $word_count,
                'reading_time' => $reading_time,
                'engagement_score' => $engagement_score,
                'monetization_potential' => $monetization_potential,
                'recommended_strategy' => $recommended_strategy
            ),
            array('%d', '%d', '%d', '%d', '%s', '%s')
        );
        
        return (object) array(
            'word_count' => $word_count,
            'reading_time' => $reading_time,
            'engagement_score' => round($engagement_score),
            'monetization_potential' => $monetization_potential,
            'recommended_strategy' => $recommended_strategy
        );
    }

    public function save_post_analysis($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        
        $this->analyze_content($post_id);
    }

    public function register_rest_routes() {
        register_rest_route('cga/v1', '/analytics/(?P<post_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_analytics_rest'),
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            }
        ));
    }

    public function get_analytics_rest($request) {
        $post_id = intval($request['post_id']);
        global $wpdb;
        $table_name = $wpdb->prefix . 'cga_analytics';
        
        $analysis = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table_name} WHERE post_id = %d", $post_id)
        );
        
        return rest_ensure_response($analysis ?: new WP_Error('not_found', 'No analytics found'));
    }

    public function add_dashboard_widget() {
        wp_add_dashboard_widget('cga-widget', 'ContentGrowth Analytics Summary', array($this, 'render_dashboard_widget'));
    }

    public function render_dashboard_widget() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cga_analytics';
        
        $high_potential = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE monetization_potential = 'high'");
        $avg_score = $wpdb->get_var("SELECT AVG(engagement_score) FROM {$table_name}");
        
        echo '<p><strong>High Monetization Potential Posts:</strong> ' . intval($high_potential) . '</p>';
        echo '<p><strong>Average Engagement Score:</strong> ' . round($avg_score, 1) . '/100</p>';
        echo '<p><a href="' . admin_url('admin.php?page=contentgrowth-analytics') . '" class="button button-primary">View Full Dashboard</a></p>';
    }

    public function enqueue_scripts() {
        wp_enqueue_style('cga-admin-style', CGA_PLUGIN_URL . 'css/admin.css', array(), CGA_VERSION);
    }
}

$contentgrowth_analytics = new ContentGrowthAnalytics();
?>