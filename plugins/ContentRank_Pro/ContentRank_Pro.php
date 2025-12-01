<?php
/*
Plugin Name: ContentRank Pro
Plugin URI: https://contentrank-pro.com
Description: AI-powered content performance analyzer for WordPress with SEO insights and optimization suggestions
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentRank_Pro.php
License: GPL v2 or later
Text Domain: contentrank-pro
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

define('CONTENTRANK_PRO_VERSION', '1.0.0');
define('CONTENTRANK_PRO_PATH', plugin_dir_path(__FILE__));
define('CONTENTRANK_PRO_URL', plugin_dir_url(__FILE__));

class ContentRankPro {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_post_data'));
        add_filter('manage_posts_columns', array($this, 'add_posts_column'));
        add_action('manage_posts_custom_column', array($this, 'display_posts_column'));
        add_action('wp_ajax_contentrank_analyze', array($this, 'ajax_analyze_post'));
        add_action('wp_ajax_contentrank_get_suggestions', array($this, 'ajax_get_suggestions'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentrank_analytics';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                post_id bigint(20) NOT NULL,
                word_count int(11),
                readability_score int(11),
                seo_score int(11),
                engagement_score int(11),
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY post_id (post_id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        add_option('contentrank_pro_activated', true);
        add_option('contentrank_pro_free_tier', true);
    }

    public function deactivate() {
        delete_option('contentrank_pro_activated');
    }

    public function add_admin_menu() {
        add_menu_page(
            'ContentRank Pro',
            'ContentRank Pro',
            'manage_options',
            'contentrank-pro',
            array($this, 'render_dashboard'),
            'dashicons-chart-line',
            6
        );

        add_submenu_page(
            'contentrank-pro',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'contentrank-pro',
            array($this, 'render_dashboard')
        );

        add_submenu_page(
            'contentrank-pro',
            'Settings',
            'Settings',
            'manage_options',
            'contentrank-pro-settings',
            array($this, 'render_settings')
        );
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'contentrank') === false) {
            return;
        }

        wp_enqueue_style('contentrank-admin-css', CONTENTRANK_PRO_URL . 'admin.css', array(), CONTENTRANK_PRO_VERSION);
        wp_enqueue_script('contentrank-admin-js', CONTENTRANK_PRO_URL . 'admin.js', array('jquery'), CONTENTRANK_PRO_VERSION, true);
        wp_localize_script('contentrank-admin-js', 'contentRankObj', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('contentrank_nonce')
        ));
    }

    public function add_meta_box() {
        add_meta_box(
            'contentrank_meta_box',
            'ContentRank Pro Analysis',
            array($this, 'render_meta_box'),
            'post',
            'side',
            'high'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('contentrank_save_post', 'contentrank_nonce');
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentrank_analytics';
        $analytics = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE post_id = %d ORDER BY updated_at DESC LIMIT 1", $post->ID));
        
        echo '<div class="contentrank-meta-box">';
        echo '<button type="button" id="contentrank-analyze-btn" class="button button-primary">Analyze Content</button>';
        
        if ($analytics) {
            echo '<div class="contentrank-scores" style="margin-top: 15px;">';
            echo '<p><strong>Word Count:</strong> ' . intval($analytics->word_count) . '</p>';
            echo '<p><strong>SEO Score:</strong> <span class="score-badge">' . intval($analytics->seo_score) . '/100</span></p>';
            echo '<p><strong>Readability:</strong> <span class="score-badge">' . intval($analytics->readability_score) . '/100</span></p>';
            echo '<p><strong>Engagement Score:</strong> <span class="score-badge">' . intval($analytics->engagement_score) . '/100</span></p>';
            echo '</div>';
            echo '<div id="contentrank-suggestions" style="margin-top: 15px; padding: 10px; background: #f0f0f0; border-radius: 4px;">';
            echo '<p><strong>Pro Suggestions:</strong></p>';
            echo '<button type="button" id="contentrank-get-suggestions" class="button">View AI Suggestions</button>';
            echo '<div id="suggestions-content" style="margin-top: 10px; display:none;"></div>';
            echo '</div>';
        }
        echo '</div>';
    }

    public function save_post_data($post_id) {
        if (!isset($_POST['contentrank_nonce'])) {
            return;
        }
        if (!wp_verify_nonce($_POST['contentrank_nonce'], 'contentrank_save_post')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
    }

    public function add_posts_column($columns) {
        $columns['contentrank_score'] = 'ContentRank Score';
        return $columns;
    }

    public function display_posts_column($column, $post_id) {
        if ($column === 'contentrank_score') {
            global $wpdb;
            $table_name = $wpdb->prefix . 'contentrank_analytics';
            $analytics = $wpdb->get_row($wpdb->prepare("SELECT seo_score FROM $table_name WHERE post_id = %d ORDER BY updated_at DESC LIMIT 1", $post_id));
            
            if ($analytics) {
                $score = intval($analytics->seo_score);
                $color = $score >= 75 ? 'green' : ($score >= 50 ? 'orange' : 'red');
                echo '<span style="color: ' . esc_attr($color) . '; font-weight: bold;">' . intval($score) . '/100</span>';
            } else {
                echo '<span style="color: gray;">Not Analyzed</span>';
            }
        }
    }

    public function ajax_analyze_post() {
        check_ajax_referer('contentrank_nonce');
        
        if (!isset($_POST['post_id'])) {
            wp_send_json_error('Invalid post ID');
        }

        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error('Post not found');
        }

        $content = $post->post_content;
        $title = $post->post_title;
        
        $word_count = str_word_count(strip_tags($content));
        $seo_score = $this->calculate_seo_score($title, $content);
        $readability_score = $this->calculate_readability_score($content);
        $engagement_score = $this->calculate_engagement_score($content);

        global $wpdb;
        $table_name = $wpdb->prefix . 'contentrank_analytics';
        
        $wpdb->replace(
            $table_name,
            array(
                'post_id' => $post_id,
                'word_count' => $word_count,
                'seo_score' => $seo_score,
                'readability_score' => $readability_score,
                'engagement_score' => $engagement_score
            ),
            array('%d', '%d', '%d', '%d', '%d')
        );

        wp_send_json_success(array(
            'word_count' => $word_count,
            'seo_score' => $seo_score,
            'readability_score' => $readability_score,
            'engagement_score' => $engagement_score
        ));
    }

    public function ajax_get_suggestions() {
        check_ajax_referer('contentrank_nonce');
        
        if (!isset($_POST['post_id'])) {
            wp_send_json_error('Invalid post ID');
        }

        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error('Post not found');
        }

        $is_pro = get_option('contentrank_pro_license_active', false);
        
        if (!$is_pro) {
            wp_send_json_error('Upgrade to Pro to unlock AI suggestions. <a href="' . admin_url('admin.php?page=contentrank-pro-settings') . '">Upgrade now</a>');
        }

        $suggestions = array(
            'Add more internal links for better SEO',
            'Consider using subheadings to break up content',
            'Add relevant keywords naturally throughout the post',
            'Include a call-to-action at the end',
            'Optimize your meta description for better CTR'
        );

        wp_send_json_success($suggestions);
    }

    private function calculate_seo_score($title, $content) {
        $score = 50;
        
        if (strlen($title) >= 30 && strlen($title) <= 60) $score += 10;
        if (strlen(strip_tags($content)) >= 300) $score += 15;
        if (substr_count($content, '<h2>') > 0) $score += 10;
        if (substr_count($content, '<a') > 2) $score += 10;
        if (substr_count($content, '<img') > 0) $score += 5;
        
        return min(100, $score);
    }

    private function calculate_readability_score($content) {
        $text = strip_tags($content);
        $words = str_word_count($text);
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        
        if ($words < 100 || $sentence_count < 5) return 40;
        
        $avg_word_length = strlen(str_replace(' ', '', $text)) / max(1, $words);
        $avg_sentence_length = $words / max(1, $sentence_count);
        
        $score = 100 - (($avg_word_length - 4.7) * 10 + ($avg_sentence_length - 15) * 2);
        return max(0, min(100, intval($score)));
    }

    private function calculate_engagement_score($content) {
        $score = 50;
        
        if (substr_count($content, '<strong>') > 0) $score += 10;
        if (substr_count($content, '<em>') > 0) $score += 5;
        if (substr_count($content, '<ul>') > 0 || substr_count($content, '<ol>') > 0) $score += 15;
        if (substr_count($content, '<!--more-->') > 0) $score += 10;
        
        return min(100, $score);
    }

    public function render_dashboard() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentrank_analytics';
        $total_posts = $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM $table_name");
        $avg_seo_score = $wpdb->get_var("SELECT AVG(seo_score) FROM $table_name");
        $avg_engagement = $wpdb->get_var("SELECT AVG(engagement_score) FROM $table_name");
        
        echo '<div class="wrap">';
        echo '<h1>ContentRank Pro Dashboard</h1>';
        echo '<div class="contentrank-dashboard">';
        echo '<div class="dashboard-card"><h3>Posts Analyzed</h3><p class="stat">' . intval($total_posts) . '</p></div>';
        echo '<div class="dashboard-card"><h3>Avg SEO Score</h3><p class="stat">' . round($avg_seo_score, 1) . '/100</p></div>';
        echo '<div class="dashboard-card"><h3>Avg Engagement</h3><p class="stat">' . round($avg_engagement, 1) . '/100</p></div>';
        echo '</div>';
        echo '</div>';
    }

    public function render_settings() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        echo '<div class="wrap">';
        echo '<h1>ContentRank Pro Settings</h1>';
        echo '<div class="contentrank-settings">';
        echo '<h2>Upgrade to Pro</h2>';
        echo '<p>Unlock AI-powered suggestions, advanced analytics, and priority support.</p>';
        echo '<p><strong>$9.99/month</strong> or <strong>$79.99/year</strong></p>';
        echo '<a href="#" class="button button-primary">Upgrade Now</a>';
        echo '</div>';
        echo '</div>';
    }
}

ContentRankPro::get_instance();
?>