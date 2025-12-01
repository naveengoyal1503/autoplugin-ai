<?php
/*
Plugin Name: ContentAI Optimizer Pro
Plugin URI: https://contentaioptimizer.com
Description: AI-powered content optimization with SEO analysis, readability scoring, and engagement metrics
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentAI_Optimizer_Pro.php
License: GPL v2 or later
Text Domain: contentai-optimizer
Domain Path: /languages
*/

if (!defined('ABSPATH')) exit;

define('CONTENTAI_VERSION', '1.0.0');
define('CONTENTAI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CONTENTAI_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentAI_Optimizer {
    private static $instance = null;
    private $db_version = '1.0';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_contentai_analyze_post', array($this, 'ajax_analyze_post'));
        add_action('wp_ajax_contentai_get_subscription', array($this, 'ajax_get_subscription'));
        add_action('add_meta_boxes', array($this, 'add_metabox'));
    }
    
    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $tables = array(
            $wpdb->prefix . 'contentai_analysis' => "
                CREATE TABLE IF NOT EXISTS {$wpdb->prefix}contentai_analysis (
                    id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    post_id bigint(20) NOT NULL,
                    seo_score int(3),
                    readability_score int(3),
                    engagement_score int(3),
                    word_count int(5),
                    analysis_data longtext,
                    created_at datetime DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY post_id (post_id)
                ) $charset_collate;
            ",
            $wpdb->prefix . 'contentai_subscriptions' => "
                CREATE TABLE IF NOT EXISTS {$wpdb->prefix}contentai_subscriptions (
                    id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    user_id bigint(20) NOT NULL,
                    plan varchar(50) DEFAULT 'free',
                    status varchar(20) DEFAULT 'active',
                    monthly_limit int(5) DEFAULT 5,
                    used_count int(5) DEFAULT 0,
                    reset_date datetime,
                    created_at datetime DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY user_id (user_id)
                ) $charset_collate;
            "
        );
        
        foreach ($tables as $sql) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        
        update_option('contentai_db_version', $this->db_version);
    }
    
    public function deactivate() {
        wp_clear_scheduled_hook('contentai_daily_reset');
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'ContentAI Optimizer',
            'ContentAI',
            'manage_options',
            'contentai-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-chart-line',
            30
        );
        
        add_submenu_page(
            'contentai-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'contentai-dashboard',
            array($this, 'render_dashboard')
        );
        
        add_submenu_page(
            'contentai-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'contentai-settings',
            array($this, 'render_settings')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'contentai') === false) return;
        
        wp_enqueue_script(
            'contentai-admin',
            CONTENTAI_PLUGIN_URL . 'assets/admin.js',
            array('jquery', 'wp-api'),
            CONTENTAI_VERSION
        );
        
        wp_enqueue_style(
            'contentai-admin',
            CONTENTAI_PLUGIN_URL . 'assets/admin.css',
            array(),
            CONTENTAI_VERSION
        );
        
        wp_localize_script('contentai-admin', 'contentaiConfig', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('contentai_nonce'),
            'rest_url' => rest_url('contentai/v1/')
        ));
    }
    
    public function add_metabox() {
        add_meta_box(
            'contentai_analyzer',
            'ContentAI Optimizer',
            array($this, 'render_metabox'),
            'post',
            'normal',
            'high'
        );
    }
    
    public function render_metabox($post) {
        wp_nonce_field('contentai_analyze_nonce');
        ?>
        <div id="contentai-metabox">
            <button type="button" class="button button-primary" id="contentai-analyze-btn">
                Analyze Content
            </button>
            <div id="contentai-results" style="margin-top: 20px;"></div>
        </div>
        <?php
    }
    
    public function ajax_analyze_post() {
        check_ajax_referer('contentai_nonce');
        
        if (!isset($_POST['post_id'])) {
            wp_send_json_error('No post ID provided');
        }
        
        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error('Post not found');
        }
        
        $user_id = get_current_user_id();
        $subscription = $this->get_user_subscription($user_id);
        
        if (!$this->can_analyze($user_id, $subscription)) {
            wp_send_json_error('Monthly limit reached. Upgrade to premium for unlimited analysis.');
        }
        
        $analysis = $this->analyze_content($post->post_content, $post_id);
        $this->increment_usage($user_id);
        
        wp_send_json_success($analysis);
    }
    
    public function ajax_get_subscription() {
        check_ajax_referer('contentai_nonce');
        
        $user_id = get_current_user_id();
        $subscription = $this->get_user_subscription($user_id);
        
        wp_send_json_success($subscription);
    }
    
    private function analyze_content($content, $post_id) {
        global $wpdb;
        
        $words = str_word_count($content);
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $avg_word_length = array_sum(array_map('strlen', explode(' ', $content))) / count(explode(' ', $content));
        
        $seo_score = $this->calculate_seo_score($content);
        $readability_score = $this->calculate_readability_score($words, count($sentences), $avg_word_length);
        $engagement_score = $this->calculate_engagement_score($content);
        
        $analysis_data = array(
            'word_count' => $words,
            'sentence_count' => count($sentences),
            'paragraph_count' => substr_count($content, '<p>'),
            'avg_sentence_length' => round($words / max(1, count($sentences))),
            'avg_word_length' => round($avg_word_length, 2),
            'headings' => preg_match_all('/<h[1-6]/', $content),
            'links' => preg_match_all('/<a\s+/', $content),
            'images' => preg_match_all('/<img\s+/', $content),
            'suggestions' => $this->get_suggestions($content, $seo_score, $readability_score)
        );
        
        $wpdb->replace(
            $wpdb->prefix . 'contentai_analysis',
            array(
                'post_id' => $post_id,
                'seo_score' => $seo_score,
                'readability_score' => $readability_score,
                'engagement_score' => $engagement_score,
                'word_count' => $words,
                'analysis_data' => json_encode($analysis_data)
            ),
            array('%d', '%d', '%d', '%d', '%d', '%s')
        );
        
        return array(
            'post_id' => $post_id,
            'seo_score' => $seo_score,
            'readability_score' => $readability_score,
            'engagement_score' => $engagement_score,
            'overall_score' => round(($seo_score + $readability_score + $engagement_score) / 3),
            'details' => $analysis_data
        );
    }
    
    private function calculate_seo_score($content) {
        $score = 50;
        
        if (preg_match('/<h1/', $content)) $score += 15;
        if (preg_match_all('/<h[2-6]/', $content) >= 3) $score += 10;
        if (preg_match_all('/<strong>|<b>/', $content) >= 3) $score += 10;
        if (strlen($content) > 500) $score += 10;
        if (preg_match_all('/<img\s+/', $content) >= 2) $score += 5;
        
        return min(100, $score);
    }
    
    private function calculate_readability_score($words, $sentences, $avg_word_length) {
        if ($sentences === 0) return 0;
        
        $avg_sentence_length = $words / $sentences;
        $flesch_kincaid = 0.39 * $avg_sentence_length + 11.8 * ($avg_word_length / 5) - 15.59;
        $score = max(0, min(100, 100 - ($flesch_kincaid * 5)));
        
        return round($score);
    }
    
    private function calculate_engagement_score($content) {
        $score = 50;
        
        if (preg_match_all('/<img\s+/', $content) >= 3) $score += 20;
        if (preg_match_all('/<a\s+/', $content) >= 5) $score += 15;
        if (strpos($content, '<ul>') !== false || strpos($content, '<ol>') !== false) $score += 10;
        if (preg_match_all('/<blockquote/', $content) >= 1) $score += 5;
        
        return min(100, $score);
    }
    
    private function get_suggestions($content, $seo_score, $readability_score) {
        $suggestions = array();
        
        if ($seo_score < 70) {
            if (!preg_match('/<h1/', $content)) {
                $suggestions[] = 'Add an H1 heading to improve SEO structure';
            }
            if (preg_match_all('/<h[2-6]/', $content) < 3) {
                $suggestions[] = 'Add more subheadings (H2-H6) to improve content structure';
            }
        }
        
        if ($readability_score < 60) {
            $suggestions[] = 'Use shorter sentences to improve readability';
            $suggestions[] = 'Consider using simpler vocabulary';
        }
        
        if (!preg_match_all('/<img\s+/', $content) >= 2) {
            $suggestions[] = 'Add more images to increase engagement';
        }
        
        if (preg_match_all('/<a\s+/', $content) < 3) {
            $suggestions[] = 'Add internal and external links to improve SEO';
        }
        
        return $suggestions;
    }
    
    private function get_user_subscription($user_id) {
        global $wpdb;
        
        $subscription = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}contentai_subscriptions WHERE user_id = %d",
                $user_id
            )
        );
        
        if (!$subscription) {
            $wpdb->insert(
                $wpdb->prefix . 'contentai_subscriptions',
                array(
                    'user_id' => $user_id,
                    'plan' => 'free',
                    'status' => 'active',
                    'monthly_limit' => 5,
                    'used_count' => 0,
                    'reset_date' => date('Y-m-d H:i:s', strtotime('+1 month'))
                )
            );
            
            $subscription = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}contentai_subscriptions WHERE user_id = %d",
                    $user_id
                )
            );
        }
        
        return $subscription;
    }
    
    private function can_analyze($user_id, $subscription) {
        if ($subscription->plan === 'premium') return true;
        
        if ($subscription->used_count >= $subscription->monthly_limit) {
            if (strtotime($subscription->reset_date) <= time()) {
                $this->reset_monthly_limit($user_id);
                return true;
            }
            return false;
        }
        
        return true;
    }
    
    private function increment_usage($user_id) {
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->prefix}contentai_subscriptions SET used_count = used_count + 1 WHERE user_id = %d",
                $user_id
            )
        );
    }
    
    private function reset_monthly_limit($user_id) {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'contentai_subscriptions',
            array(
                'used_count' => 0,
                'reset_date' => date('Y-m-d H:i:s', strtotime('+1 month'))
            ),
            array('user_id' => $user_id)
        );
    }
    
    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1>ContentAI Optimizer Dashboard</h1>
            <div class="contentai-dashboard">
                <h2>Your Subscription</h2>
                <div id="contentai-subscription-info"></div>
                
                <h2>Recent Analyses</h2>
                <div id="contentai-recent-analyses"></div>
                
                <h2>Upgrade to Premium</h2>
                <p>Get unlimited content analysis, advanced insights, and priority support.</p>
                <a href="#" class="button button-primary">Upgrade Now - $9.99/month</a>
            </div>
        </div>
        <?php
    }
    
    public function render_settings() {
        ?>
        <div class="wrap">
            <h1>ContentAI Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('contentai_settings'); ?>
                <?php do_settings_sections('contentai_settings'); ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

function contentai_optimizer_init() {
    return ContentAI_Optimizer::get_instance();
}

add_action('plugins_loaded', 'contentai_optimizer_init');
?>