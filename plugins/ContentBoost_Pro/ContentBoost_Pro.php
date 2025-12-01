/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentBoost_Pro.php
*/
<?php
/**
 * Plugin Name: ContentBoost Pro
 * Plugin URI: https://contentboostpro.com
 * Description: AI-powered content optimizer for SEO and monetization insights
 * Version: 1.0.0
 * Author: ContentBoost Team
 * License: GPL2
 */

if (!defined('ABSPATH')) exit;

class ContentBoostPro {
    private $plugin_slug = 'contentboost-pro';
    private $version = '1.0.0';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_cba_analyze_content', array($this, 'ajax_analyze_content'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentboost_analysis';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            seo_score int(11),
            readability_score int(11),
            monetization_score int(11),
            recommendations longtext,
            analysis_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function add_admin_menu() {
        add_menu_page(
            'ContentBoost Pro',
            'ContentBoost Pro',
            'manage_options',
            $this->plugin_slug,
            array($this, 'admin_page'),
            'dashicons-chart-line'
        );
    }

    public function add_meta_box() {
        add_meta_box(
            'contentboost_analysis',
            'ContentBoost Analysis',
            array($this, 'meta_box_callback'),
            'post',
            'side'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('contentboost_nonce', 'contentboost_nonce');
        echo '<button id="contentboost-analyze" class="button button-primary">Analyze Post</button>';
        echo '<div id="contentboost-results" style="margin-top: 15px;"></div>';
    }

    public function enqueue_scripts() {
        wp_enqueue_script(
            'contentboost-admin',
            plugins_url('js/admin.js', __FILE__),
            array('jquery'),
            $this->version
        );
        wp_localize_script('contentboost-admin', 'contentboostAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('contentboost_nonce')
        ));
    }

    public function ajax_analyze_content() {
        check_ajax_referer('contentboost_nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }

        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error('Post not found');
        }

        $content = $post->post_content;
        $word_count = str_word_count($content);
        $paragraphs = count(array_filter(explode('\n', $content)));
        
        $seo_score = $this->calculate_seo_score($post);
        $readability_score = $this->calculate_readability_score($content);
        $monetization_score = $this->calculate_monetization_score($post, $content);
        
        $recommendations = $this->generate_recommendations($post, $content, $seo_score, $readability_score, $monetization_score);
        
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'contentboost_analysis',
            array(
                'post_id' => $post_id,
                'seo_score' => $seo_score,
                'readability_score' => $readability_score,
                'monetization_score' => $monetization_score,
                'recommendations' => json_encode($recommendations)
            )
        );

        wp_send_json_success(array(
            'seo_score' => $seo_score,
            'readability_score' => $readability_score,
            'monetization_score' => $monetization_score,
            'word_count' => $word_count,
            'recommendations' => $recommendations
        ));
    }

    private function calculate_seo_score($post) {
        $score = 0;
        $title_length = strlen($post->post_title);
        
        if ($title_length >= 30 && $title_length <= 60) $score += 25;
        if (strlen($post->post_excerpt) > 50) $score += 25;
        if (preg_match_all('/^#+/m', $post->post_content) >= 3) $score += 25;
        if (preg_match_all('/\[\w+\]/i', $post->post_content) > 0) $score += 25;
        
        return min(100, $score);
    }

    private function calculate_readability_score($content) {
        $words = str_word_count($content);
        $sentences = preg_match_all('/[.!?]+/', $content);
        $paragraphs = max(1, count(array_filter(explode('\n', $content))));
        
        $avg_sentence_length = $sentences > 0 ? $words / $sentences : 0;
        $avg_para_length = $words / $paragraphs;
        
        $score = 100;
        if ($avg_sentence_length > 20) $score -= 15;
        if ($avg_para_length > 150) $score -= 10;
        if (preg_match_all('/^#+/m', $content) < 2) $score -= 15;
        
        return max(0, min(100, $score));
    }

    private function calculate_monetization_score($post, $content) {
        $score = 0;
        
        if (str_word_count($content) >= 1000) $score += 20;
        if (preg_match_all('/https?:\/\//i', $content) >= 3) $score += 20;
        if (preg_match_all('/affiliate|sponsored/i', $content)) $score += 20;
        if (get_post_meta($post->ID, '_featured_image', true)) $score += 20;
        if (get_post_meta($post->ID, '_cta_button', true)) $score += 20;
        
        return min(100, $score);
    }

    private function generate_recommendations($post, $content, $seo_score, $readability_score, $monetization_score) {
        $recommendations = array();
        
        if ($seo_score < 70) {
            $recommendations[] = array(
                'type' => 'seo',
                'priority' => 'high',
                'suggestion' => 'Improve your SEO by adding more headers and meta descriptions'
            );
        }
        
        if ($readability_score < 60) {
            $recommendations[] = array(
                'type' => 'readability',
                'priority' => 'high',
                'suggestion' => 'Break up long paragraphs and use shorter sentences for better readability'
            );
        }
        
        if ($monetization_score < 50) {
            $recommendations[] = array(
                'type' => 'monetization',
                'priority' => 'medium',
                'suggestion' => 'Add affiliate links, CTAs, or sponsored content opportunities to boost monetization'
            );
        }
        
        if (str_word_count($content) < 800) {
            $recommendations[] = array(
                'type' => 'content',
                'priority' => 'medium',
                'suggestion' => 'Expand your post to at least 1000 words for better SEO and engagement'
            );
        }
        
        return $recommendations;
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>ContentBoost Pro Dashboard</h1>
            <p>Select posts to analyze for SEO, readability, and monetization opportunities.</p>
            <div id="contentboost-dashboard"></div>
        </div>
        <?php
    }
}

new ContentBoostPro();
?>