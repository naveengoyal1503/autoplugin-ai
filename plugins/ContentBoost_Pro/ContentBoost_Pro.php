<?php
/*
Plugin Name: ContentBoost Pro
Plugin URI: https://contentboostpro.com
Description: AI-powered content optimization and multi-format repurposing for WordPress
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentBoost_Pro.php
License: GPL v2 or later
Text Domain: contentboost-pro
*/

if (!defined('ABSPATH')) {
    exit;
}

define('CONTENTBOOST_PRO_VERSION', '1.0.0');
define('CONTENTBOOST_PRO_PATH', plugin_dir_path(__FILE__));
define('CONTENTBOOST_PRO_URL', plugin_dir_url(__FILE__));

class ContentBoostPro {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('contentboost-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_boost_analyze_content', array($this, 'analyze_content'));
        add_action('wp_ajax_boost_export_content', array($this, 'export_content'));
        add_action('post_row_actions', array($this, 'add_post_actions'), 10, 2);
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'contentboost_exports';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            export_type varchar(50) NOT NULL,
            export_data longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option('contentboost_pro_activated', current_time('mysql'));
    }

    public function deactivate() {
        delete_option('contentboost_pro_activated');
    }

    public function add_admin_menu() {
        add_menu_page(
            'ContentBoost Pro',
            'ContentBoost Pro',
            'manage_options',
            'contentboost-pro',
            array($this, 'render_dashboard'),
            'dashicons-chart-line',
            25
        );

        add_submenu_page(
            'contentboost-pro',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'contentboost-pro',
            array($this, 'render_dashboard')
        );

        add_submenu_page(
            'contentboost-pro',
            'Settings',
            'Settings',
            'manage_options',
            'contentboost-pro-settings',
            array($this, 'render_settings')
        );
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'contentboost-pro') === false) {
            return;
        }

        wp_enqueue_style('contentboost-admin', CONTENTBOOST_PRO_URL . 'assets/admin.css', array(), CONTENTBOOST_PRO_VERSION);
        wp_enqueue_script('contentboost-admin', CONTENTBOOST_PRO_URL . 'assets/admin.js', array('jquery'), CONTENTBOOST_PRO_VERSION, true);

        wp_localize_script('contentboost-admin', 'ContentBoostPro', array(
            'nonce' => wp_create_nonce('contentboost-pro-nonce'),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'premium' => $this->is_premium_user()
        ));
    }

    public function analyze_content() {
        check_ajax_referer('contentboost-pro-nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $post = get_post($post_id);

        if (!$post) {
            wp_send_json_error('Post not found');
        }

        $content = $post->post_content;
        $title = $post->post_title;

        $analysis = array(
            'word_count' => str_word_count($content),
            'reading_time' => ceil(str_word_count($content) / 200),
            'headings' => substr_count($content, '<h'),
            'links' => substr_count($content, '<a href'),
            'images' => substr_count($content, '<img'),
            'seo_score' => $this->calculate_seo_score($title, $content),
            'recommendations' => $this->generate_recommendations($title, $content)
        );

        wp_send_json_success($analysis);
    }

    public function export_content() {
        check_ajax_referer('contentboost-pro-nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        if (!$this->is_premium_user()) {
            wp_send_json_error('Premium feature');
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $export_type = isset($_POST['export_type']) ? sanitize_text_field($_POST['export_type']) : 'social';
        $post = get_post($post_id);

        if (!$post) {
            wp_send_json_error('Post not found');
        }

        $exported = $this->generate_export($post, $export_type);

        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'contentboost_exports',
            array(
                'post_id' => $post_id,
                'export_type' => $export_type,
                'export_data' => serialize($exported)
            )
        );

        wp_send_json_success(array('export' => $exported, 'type' => $export_type));
    }

    public function generate_export($post, $type) {
        $title = $post->post_title;
        $excerpt = wp_trim_words($post->post_content, 30);

        switch ($type) {
            case 'twitter':
                return array(
                    'platform' => 'Twitter',
                    'content' => substr($title . ' ' . $excerpt . ' #WordPress', 0, 280)
                );
            case 'linkedin':
                return array(
                    'platform' => 'LinkedIn',
                    'content' => $title . '\n\n' . $excerpt . '\n\nRead the full article on my blog.'
                );
            case 'email':
                return array(
                    'platform' => 'Email',
                    'subject' => 'New: ' . $title,
                    'body' => $excerpt . '\n\nRead more: ' . get_permalink($post->ID)
                );
            default:
                return array(
                    'platform' => 'Social',
                    'content' => $title . ' ' . $excerpt
                );
        }
    }

    private function calculate_seo_score($title, $content) {
        $score = 0;
        $max_score = 100;

        if (strlen($title) >= 30 && strlen($title) <= 60) $score += 25;
        if (strlen($content) >= 300) $score += 25;
        if (substr_count($content, '<h2') > 0) $score += 25;
        if (substr_count($content, '<a href') > 0) $score += 25;

        return min($score, $max_score);
    }

    private function generate_recommendations($title, $content) {
        $recommendations = array();

        if (strlen($title) < 30) {
            $recommendations[] = 'Increase title length to 30-60 characters';
        }
        if (strlen($content) < 300) {
            $recommendations[] = 'Add more content (aim for 300+ words)';
        }
        if (substr_count($content, '<h2') === 0) {
            $recommendations[] = 'Add subheadings to improve readability';
        }
        if (substr_count($content, '<img') === 0) {
            $recommendations[] = 'Add images to increase engagement';
        }

        return $recommendations;
    }

    public function add_post_actions($actions, $post) {
        if (current_user_can('manage_options')) {
            $actions['boost-analyze'] = '<a href="#" class="boost-analyze" data-post-id="' . $post->ID . '">Boost Analysis</a>';
        }
        return $actions;
    }

    private function is_premium_user() {
        $user_id = get_current_user_id();
        $subscription = get_user_meta($user_id, 'contentboost_subscription', true);
        return !empty($subscription) && strtotime($subscription) > current_time('timestamp');
    }

    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1>ContentBoost Pro Dashboard</h1>
            <div class="contentboost-dashboard">
                <div class="postbox">
                    <h2 class="hndle">Quick Actions</h2>
                    <div class="inside">
                        <p>Select a post to analyze and optimize:</p>
                        <?php
                        $posts = get_posts(array('numberposts' => 10));
                        echo '<select id="post-select" style="padding: 8px; margin-right: 10px;">';
                        echo '<option value="">Select a post...</option>';
                        foreach ($posts as $post) {
                            echo '<option value="' . $post->ID . '">' . $post->post_title . '</option>';
                        }
                        echo '</select>';
                        ?>
                        <button class="button button-primary" id="analyze-btn">Analyze</button>
                    </div>
                </div>
                <div id="analysis-results" style="display:none; margin-top: 20px;" class="postbox">
                    <h2 class="hndle">Analysis Results</h2>
                    <div class="inside" id="results-content"></div>
                </div>
            </div>
        </div>
        <style>
            .contentboost-dashboard { margin-top: 20px; }
            .postbox { margin-bottom: 20px; }
        </style>
        <script>
            jQuery(document).ready(function($) {
                $('#analyze-btn').on('click', function() {
                    var postId = $('#post-select').val();
                    if (!postId) {
                        alert('Please select a post');
                        return;
                    }
                    $.post(ContentBoostPro.ajaxurl, {
                        action: 'boost_analyze_content',
                        post_id: postId,
                        nonce: ContentBoostPro.nonce
                    }, function(response) {
                        if (response.success) {
                            var data = response.data;
                            var html = '<p><strong>SEO Score:</strong> ' + data.seo_score + '/100</p>';
                            html += '<p><strong>Reading Time:</strong> ' + data.reading_time + ' min</p>';
                            html += '<p><strong>Word Count:</strong> ' + data.word_count + '</p>';
                            html += '<p><strong>Recommendations:</strong></p><ul>';
                            data.recommendations.forEach(function(rec) {
                                html += '<li>' + rec + '</li>';
                            });
                            html += '</ul>';
                            $('#results-content').html(html);
                            $('#analysis-results').show();
                        }
                    });
                });
            });
        </script>
        <?php
    }

    public function render_settings() {
        ?>
        <div class="wrap">
            <h1>ContentBoost Pro Settings</h1>
            <div class="postbox">
                <h2 class="hndle">Account</h2>
                <div class="inside">
                    <p>Upgrade to Premium to unlock multi-format content repurposing, advanced analytics, and social scheduling.</p>
                    <a href="#" class="button button-primary">Upgrade to Premium ($9.99/month)</a>
                </div>
            </div>
        </div>
        <?php
    }
}

ContentBoostPro::get_instance();
?>