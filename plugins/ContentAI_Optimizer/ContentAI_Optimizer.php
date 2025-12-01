<?php
/*
Plugin Name: ContentAI Optimizer
Plugin URI: https://contentaioptimizer.com
Description: AI-powered content optimization and SEO enhancement for WordPress
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentAI_Optimizer.php
License: GPL v2 or later
Text Domain: contentai-optimizer
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

define('CONTENTAI_VERSION', '1.0.0');
define('CONTENTAI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CONTENTAI_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentAIOptimizer {
    private static $instance = null;
    private $db_version = '1.0';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_optimization_data'));
        add_action('wp_ajax_contentai_analyze_post', array($this, 'ajax_analyze_post'));
        add_action('wp_ajax_contentai_get_suggestions', array($this, 'ajax_get_suggestions'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_name = $wpdb->prefix . 'contentai_analyses';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            user_id mediumint(9) NOT NULL,
            seo_score int(3),
            readability_score int(3),
            engagement_score int(3),
            suggestions longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option('contentai_db_version', $this->db_version);
        update_option('contentai_free_analyses', 5);
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function add_admin_menu() {
        add_menu_page(
            'ContentAI Optimizer',
            'ContentAI',
            'manage_options',
            'contentai-optimizer',
            array($this, 'render_dashboard'),
            'dashicons-chart-line',
            30
        );

        add_submenu_page(
            'contentai-optimizer',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'contentai-optimizer',
            array($this, 'render_dashboard')
        );

        add_submenu_page(
            'contentai-optimizer',
            'Settings',
            'Settings',
            'manage_options',
            'contentai-settings',
            array($this, 'render_settings')
        );
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'contentai') !== false || strpos($hook, 'post.php') !== false) {
            wp_enqueue_script('contentai-admin', CONTENTAI_PLUGIN_URL . 'admin/js/admin.js', array('jquery'), CONTENTAI_VERSION);
            wp_enqueue_style('contentai-admin', CONTENTAI_PLUGIN_URL . 'admin/css/admin.css', array(), CONTENTAI_VERSION);

            wp_localize_script('contentai-admin', 'contentaiAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('contentai-nonce')
            ));
        }
    }

    public function add_meta_boxes() {
        add_meta_box(
            'contentai-optimizer-meta',
            'ContentAI Optimizer',
            array($this, 'render_meta_box'),
            array('post', 'page'),
            'side',
            'high'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('contentai_save_meta', 'contentai_nonce');
        echo '<button type="button" id="contentai-analyze-btn" class="button button-primary" style="width: 100%;">Analyze Content</button>';
        echo '<div id="contentai-results" style="margin-top: 15px;"></div>';
    }

    public function render_dashboard() {
        ?>
        <div class="wrap contentai-dashboard">
            <h1>ContentAI Optimizer Dashboard</h1>
            <div class="contentai-stats-grid">
                <div class="stat-card">
                    <h3>Total Analyses</h3>
                    <p class="stat-number" id="total-analyses">0</p>
                </div>
                <div class="stat-card">
                    <h3>Average SEO Score</h3>
                    <p class="stat-number" id="avg-seo-score">0</p>
                </div>
                <div class="stat-card">
                    <h3>Average Readability</h3>
                    <p class="stat-number" id="avg-readability">0</p>
                </div>
                <div class="stat-card">
                    <h3>Analyses Used</h3>
                    <p class="stat-number" id="analyses-used">0/5</p>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_settings() {
        ?>
        <div class="wrap">
            <h1>ContentAI Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('contentai_settings_group'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">API Key</th>
                        <td><input type="password" name="contentai_api_key" value="<?php echo esc_attr(get_option('contentai_api_key')); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Enable Readability Analysis</th>
                        <td><input type="checkbox" name="contentai_readability" value="1" <?php checked(get_option('contentai_readability'), 1); ?> /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function ajax_analyze_post() {
        check_ajax_referer('contentai-nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }

        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);

        if (!$post) {
            wp_send_json_error('Post not found');
        }

        $analysis = $this->analyze_content($post);
        wp_send_json_success($analysis);
    }

    public function ajax_get_suggestions() {
        check_ajax_referer('contentai-nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }

        $post_id = intval($_POST['post_id']);
        $suggestions = $this->get_ai_suggestions($post_id);
        wp_send_json_success($suggestions);
    }

    private function analyze_content($post) {
        $content = wp_strip_all_tags($post->post_content);
        $word_count = str_word_count($content);

        $seo_score = $this->calculate_seo_score($post);
        $readability_score = $this->calculate_readability_score($content);
        $engagement_score = $this->calculate_engagement_score($post);

        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'contentai_analyses',
            array(
                'post_id' => $post->ID,
                'user_id' => get_current_user_id(),
                'seo_score' => $seo_score,
                'readability_score' => $readability_score,
                'engagement_score' => $engagement_score,
            )
        );

        return array(
            'seo_score' => $seo_score,
            'readability_score' => $readability_score,
            'engagement_score' => $engagement_score,
            'word_count' => $word_count,
        );
    }

    private function calculate_seo_score($post) {
        $score = 0;

        if (strlen($post->post_title) >= 30 && strlen($post->post_title) <= 60) {
            $score += 25;
        }

        if (!empty($post->post_excerpt) && strlen($post->post_excerpt) >= 120 && strlen($post->post_excerpt) <= 160) {
            $score += 25;
        }

        $content = wp_strip_all_tags($post->post_content);
        if (strpos($content, $post->post_title) !== false) {
            $score += 20;
        }

        $heading_count = substr_count($content, '#');
        if ($heading_count >= 2) {
            $score += 15;
        }

        if (str_word_count($content) >= 300) {
            $score += 15;
        }

        return min(100, $score);
    }

    private function calculate_readability_score($content) {
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $words = str_word_count($content);
        $syllables = $this->count_syllables($content);

        $sentence_count = count($sentences);

        if ($sentence_count == 0) return 0;

        $flesch_kincaid = 0.39 * ($words / $sentence_count) + 11.8 * ($syllables / $words) - 15.59;
        $score = max(0, min(100, 100 - $flesch_kincaid * 2.5));

        return intval($score);
    }

    private function count_syllables($text) {
        $text = strtolower($text);
        $syllable_count = 0;
        $vowels = 'aeiouy';
        $previous_was_vowel = false;

        for ($i = 0; $i < strlen($text); $i++) {
            $is_vowel = strpos($vowels, $text[$i]) !== false;
            if ($is_vowel && !$previous_was_vowel) {
                $syllable_count++;
            }
            $previous_was_vowel = $is_vowel;
        }

        return max(1, $syllable_count);
    }

    private function calculate_engagement_score($post) {
        $score = 0;

        if (has_post_thumbnail($post->ID)) {
            $score += 20;
        }

        $content = wp_strip_all_tags($post->post_content);
        $link_count = preg_match_all('/https?:\/\//', $content);
        if ($link_count >= 3) {
            $score += 20;
        }

        if (str_word_count($content) >= 500) {
            $score += 20;
        }

        $categories = get_the_terms($post->ID, 'category');
        if ($categories) {
            $score += 20;
        }

        $tags = get_the_terms($post->ID, 'post_tag');
        if ($tags && count($tags) >= 3) {
            $score += 20;
        }

        return min(100, $score);
    }

    private function get_ai_suggestions($post_id) {
        $post = get_post($post_id);
        $suggestions = array();

        $seo_analysis = $this->analyze_content($post);

        if ($seo_analysis['seo_score'] < 70) {
            $suggestions[] = array(
                'type' => 'seo',
                'message' => 'Improve your title length (30-60 characters recommended)',
                'severity' => 'high'
            );
        }

        if ($seo_analysis['readability_score'] < 60) {
            $suggestions[] = array(
                'type' => 'readability',
                'message' => 'Consider breaking up longer sentences and paragraphs',
                'severity' => 'medium'
            );
        }

        if ($seo_analysis['engagement_score'] < 50) {
            $suggestions[] = array(
                'type' => 'engagement',
                'message' => 'Add more internal links and multimedia to increase engagement',
                'severity' => 'medium'
            );
        }

        if (str_word_count(wp_strip_all_tags($post->post_content)) < 300) {
            $suggestions[] = array(
                'type' => 'content',
                'message' => 'Expand your content to at least 300 words for better SEO',
                'severity' => 'medium'
            );
        }

        return $suggestions;
    }

    public function save_optimization_data($post_id) {
        if (!isset($_POST['contentai_nonce']) || !wp_verify_nonce($_POST['contentai_nonce'], 'contentai_save_meta')) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }
}

// Initialize the plugin
add_action('plugins_loaded', function() {
    ContentAIOptimizer::get_instance();
});
