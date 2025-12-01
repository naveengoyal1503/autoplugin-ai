/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://aicontentoptimizer.com
 * Description: AI-powered content analysis and optimization for WordPress
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL2
 */

if (!defined('ABSPATH')) exit;

define('AICO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AICO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AICO_VERSION', '1.0.0');

class AIContentOptimizer {
    private static $instance = null;
    private $db_version = '1.0';

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_analyze_content', array($this, 'analyze_content'));
        add_action('wp_ajax_get_optimization_report', array($this, 'get_report'));
        add_filter('the_content', array($this, 'add_optimization_box'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aico_analysis (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            analysis_data longtext NOT NULL,
            score INT DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        update_option('aico_db_version', $this->db_version);
        update_option('aico_activated', true);
    }

    public function deactivate() {
        delete_option('aico_activated');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('aico-frontend', AICO_PLUGIN_URL . 'js/frontend.js', array('jquery'), AICO_VERSION);
        wp_enqueue_style('aico-frontend', AICO_PLUGIN_URL . 'css/frontend.css', array(), AICO_VERSION);
        wp_localize_script('aico-frontend', 'aicoData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aico_nonce')
        ));
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'aico') === false) return;
        
        wp_enqueue_script('aico-admin', AICO_PLUGIN_URL . 'js/admin.js', array('jquery'), AICO_VERSION);
        wp_enqueue_style('aico-admin', AICO_PLUGIN_URL . 'css/admin.css', array(), AICO_VERSION);
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js');
        wp_localize_script('aico-admin', 'aicoAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aico_admin_nonce'),
            'isPro' => $this->is_pro_active()
        ));
    }

    public function add_admin_menu() {
        add_menu_page(
            'AI Content Optimizer',
            'Content Optimizer',
            'manage_options',
            'aico_dashboard',
            array($this, 'render_dashboard'),
            'dashicons-chart-line'
        );
        
        add_submenu_page(
            'aico_dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'aico_dashboard',
            array($this, 'render_dashboard')
        );
        
        add_submenu_page(
            'aico_dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'aico_settings',
            array($this, 'render_settings')
        );
    }

    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Dashboard</h1>
            <div class="aico-dashboard">
                <div class="aico-stats">
                    <div class="stat-card">
                        <h3>Average Score</h3>
                        <p id="avg-score">--</p>
                    </div>
                    <div class="stat-card">
                        <h3>Posts Analyzed</h3>
                        <p id="posts-count">--</p>
                    </div>
                    <div class="stat-card">
                        <h3>Optimization Potential</h3>
                        <p id="optimization-potential">--</p>
                    </div>
                </div>
                <div class="aico-chart-container">
                    <canvas id="scoreChart"></canvas>
                </div>
                <div class="aico-pro-banner">
                    <h3>Unlock Pro Features</h3>
                    <p>Get advanced AI analysis, batch processing, and priority support</p>
                    <a href="#" class="button button-primary">Upgrade to Pro</a>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_settings() {
        ?>
        <div class="wrap">
            <h1>Content Optimizer Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('aico_settings'); ?>
                <?php do_settings_sections('aico_settings'); ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function analyze_content() {
        check_ajax_referer('aico_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $content = isset($_POST['content']) ? sanitize_textarea_field($_POST['content']) : get_post_field('post_content', $post_id);
        
        $analysis = $this->perform_analysis($content);
        $score = $this->calculate_score($analysis);
        
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'aico_analysis', array(
            'post_id' => $post_id,
            'analysis_data' => wp_json_encode($analysis),
            'score' => $score
        ), array('%d', '%s', '%d'));
        
        wp_send_json_success(array(
            'score' => $score,
            'analysis' => $analysis
        ));
    }

    private function perform_analysis($content) {
        $analysis = array(
            'word_count' => str_word_count(strip_tags($content)),
            'readability' => $this->check_readability($content),
            'keyword_density' => $this->analyze_keywords($content),
            'heading_structure' => $this->check_headings($content),
            'link_count' => $this->count_links($content),
            'image_count' => $this->count_images($content),
            'paragraph_length' => $this->analyze_paragraphs($content),
            'mobile_friendly' => true
        );
        return $analysis;
    }

    private function check_readability($content) {
        $sentences = count(preg_split('/[.!?]+/', strip_tags($content)));
        $words = str_word_count(strip_tags($content));
        
        if ($words === 0) return 0;
        
        $flesch_kincaid = max(0, min(100, (0.39 * ($words / max(1, $sentences)) + 11.8 * (strlen($content) / max(1, $words)) - 15.59)));
        return round($flesch_kincaid, 1);
    }

    private function analyze_keywords($content) {
        $words = str_word_count(strtolower(strip_tags($content)), 1);
        $word_freq = array_count_values($words);
        arsort($word_freq);
        return array_slice($word_freq, 0, 5);
    }

    private function check_headings($content) {
        preg_match_all('/<h[1-6]/', $content, $matches);
        return count($matches[0]) > 0 ? count($matches[0]) : 0;
    }

    private function count_links($content) {
        preg_match_all('/<a\s/', $content, $matches);
        return count($matches[0]);
    }

    private function count_images($content) {
        preg_match_all('/<img\s/', $content, $matches);
        return count($matches[0]);
    }

    private function analyze_paragraphs($content) {
        preg_match_all('/<p>/', $content, $matches);
        return count($matches[0]) > 0 ? count($matches[0]) : 1;
    }

    private function calculate_score($analysis) {
        $score = 50;
        
        if ($analysis['word_count'] >= 300 && $analysis['word_count'] <= 2000) $score += 10;
        if ($analysis['heading_structure'] >= 3) $score += 10;
        if ($analysis['link_count'] >= 2) $score += 10;
        if ($analysis['image_count'] >= 1) $score += 10;
        if ($analysis['readability'] >= 50) $score += 10;
        
        return min(100, $score);
    }

    public function get_report() {
        check_ajax_referer('aico_nonce', 'nonce');
        
        global $wpdb;
        $reports = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}aico_analysis ORDER BY created_at DESC LIMIT 10"
        );
        
        wp_send_json_success($reports);
    }

    public function add_optimization_box($content) {
        if (!is_admin() && is_singular('post')) {
            global $post;
            $analysis = $this->get_post_analysis($post->ID);
            if ($analysis) {
                $box = '<div class="aico-optimization-box"><h4>Content Score: ' . intval($analysis->score) . '/100</h4></div>';
                return $content . $box;
            }
        }
        return $content;
    }

    private function get_post_analysis($post_id) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}aico_analysis WHERE post_id = %d ORDER BY created_at DESC LIMIT 1",
                $post_id
            )
        );
    }

    private function is_pro_active() {
        return get_option('aico_pro_active', false);
    }
}

$AIContentOptimizer = AIContentOptimizer::getInstance();
?>