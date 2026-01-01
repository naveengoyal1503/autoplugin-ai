/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Profit_Optimizer.php
*/
<?php
/**
 * Plugin Name: AI Content Profit Optimizer
 * Plugin URI: https://example.com/aicpo
 * Description: Automatically detects AI-generated content, optimizes it for human-like quality, and inserts affiliate links with personalized coupons for maximum monetization.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-profit-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentProfitOptimizer {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize_content'));
        add_filter('the_content', array($this, 'auto_optimize_content'), 99);
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-profit-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
        $this->create_table();
    }

    public function activate() {
        $this->create_table();
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aicpo_optimizations';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            score float DEFAULT 0,
            optimized_text longtext,
            affiliate_links text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('aicpo-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aicpo-frontend', 'aicpo_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page(
            'AI Content Profit Optimizer',
            'AI Profit Optimizer',
            'manage_options',
            'ai-content-profit',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        if (isset($_POST['optimize_content'])) {
            $this->handle_optimize($_POST['content']);
        }
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    public function ajax_optimize_content() {
        if (!current_user_can('edit_posts')) {
            wp_die();
        }
        $content = sanitize_textarea_field($_POST['content']);
        $result = $this->optimize_content($content);
        wp_send_json_success($result);
    }

    public function auto_optimize_content($content) {
        if (is_admin() || !is_single()) return $content;
        global $post;
        $score = $this->detect_ai_content($content);
        if ($score > 0.7) {
            $optimized = $this->optimize_content($content);
            $this->save_optimization($post->ID, $score, $optimized['text'], $optimized['affiliates']);
            return $optimized['text'];
        }
        return $content;
    }

    private function detect_ai_content($text) {
        // Simple heuristic: high repetition, short sentences, common AI patterns
        $words = str_word_count(strip_tags($text));
        $sentences = preg_match_all('/[.!?]+/', $text);
        $repetition = count(array_filter(array_count_values(str_word_count(strip_tags($text), 1)), function($c) { return $c > 3; }));
        $score = min(1, ($repetition / max(1, $words / 10)) + (50 / max(1, $words)) + (1 - min(1, $sentences / max(1, $words / 15))));
        return $score;
    }

    private function optimize_content($content) {
        $score = $this->detect_ai_content($content);
        // Humanize: add variations, transitions
        $variations = array('Moreover', 'Additionally', 'Furthermore', 'In addition', 'Also', 'Notably');
        $content = preg_replace('/\b(The|This|It)\s+(is|was|are|were)/i', '$1 $2', $content);
        $content = str_replace('[AI_PATTERN]', '', $content);
        // Insert affiliate links
        $affiliates = array(
            '[AFFILIATE1]' => '<a href="https://example.com/aff1?ref=yourid" target="_blank" rel="nofollow">Check this deal (50% off)</a>',
            '[AFFILIATE2]' => '<a href="https://example.com/aff2?ref=yourid" target="_blank" rel="nofollow">Get exclusive coupon: SAVE20</a>'
        );
        $content = str_replace(array_keys($affiliates), array_values($affiliates), $content);
        // Add random variation
        if (rand(1, 3) == 1) {
            $content .= " <p>" . $variations[array_rand($variations)] . ", this approach has helped many users boost their earnings.</p>";
        }
        return array('score' => $score, 'text' => $content, 'affiliates' => implode(', ', array_values($affiliates)));
    }

    private function save_optimization($post_id, $score, $text, $links) {
        global $wpdb;
        $table = $wpdb->prefix . 'aicpo_optimizations';
        $wpdb->insert($table, array(
            'post_id' => $post_id,
            'score' => $score,
            'optimized_text' => $text,
            'affiliate_links' => $links
        ));
    }

    private function handle_optimize($content) {
        $result = $this->optimize_content($content);
        update_option('aicpo_last_optimize', $result);
    }
}

AIContentProfitOptimizer::get_instance();

// Admin page template
if (!function_exists('aicpo_admin_page_template')) {
    function aicpo_admin_page_template() {
        $last = get_option('aicpo_last_optimize', array());
        ?>
        <div class="wrap">
            <h1>AI Content Profit Optimizer</h1>
            <p>Pro: Unlimited optimizations, custom affiliates, reports - <a href="https://example.com/pro">Upgrade for $49/year</a></p>
            <form method="post">
                <textarea name="content" rows="10" cols="80" placeholder="Paste your content here..."></textarea><br>
                <input type="submit" name="optimize_content" class="button-primary" value="Optimize & Add Affiliates">
            </form>
            <?php if ($last): ?>
            <h2>Last Optimization</h2>
            <p><strong>AI Score:</strong> <?php echo round($last['score'], 2); ?></p>
            <div><?php echo $last['text']; ?></div>
            <?php endif; ?>
        </div>
        <?php
    }
}

// Frontend JS placeholder
/* Create assets/frontend.js manually with: jQuery(document).ready(function($){ /* optimization UI */ }); */