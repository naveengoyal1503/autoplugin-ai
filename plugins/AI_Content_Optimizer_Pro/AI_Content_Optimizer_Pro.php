/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    const VERSION = '1.0.0';
    const PREMIUM_KEY = 'ai_content_optimizer_premium';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_aco_analyze_content', [$this, 'analyze_content']);
        add_action('wp_ajax_aco_upgrade', [$this, 'handle_upgrade']);
    }

    public function add_admin_menu() {
        add_options_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'ai-content-optimizer', [$this, 'settings_page']);
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', [$this, 'meta_box_content'], 'post', 'side', 'high');
    }

    public function meta_box_content($post) {
        wp_nonce_field('aco_meta_box', 'aco_nonce');
        echo '<div id="aco-results"></div>';
        echo '<button id="aco-analyze" class="button button-primary">Analyze Content</button>';
        echo '<p><small><strong>Premium:</strong> Unlock AI Rewrite & Keyword Suggestions for $9.99/mo <a href="#" id="aco-upgrade">Upgrade Now</a></small></p>';
    }

    public function enqueue_scripts($hook) {
        if ($hook === 'post.php' || $hook === 'post-new.php' || $hook === 'settings_page_ai-content-optimizer') {
            wp_enqueue_script('aco-script', plugin_dir_url(__FILE__) . 'aco.js', ['jquery'], self::VERSION, true);
            wp_localize_script('aco-script', 'aco_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('aco_ajax_nonce'),
                'is_premium' => $this->is_premium()
            ]);
        }
    }

    public function settings_page() {
        if (isset($_POST['aco_license_key']) && check_admin_referer('aco_settings')) {
            update_option(self::PREMIUM_KEY, sanitize_text_field($_POST['aco_license_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $license = get_option(self::PREMIUM_KEY, '');
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post">
                <?php wp_nonce_field('aco_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Premium License Key</th>
                        <td>
                            <input type="text" name="aco_license_key" value="<?php echo esc_attr($license); ?>" class="regular-text" />
                            <p class="description">Enter your premium key for AI features. <a href="https://example.com/upgrade" target="_blank">Get Premium</a></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Upgrade to Pro</h2>
            <p>Unlock AI rewriting, advanced keywords, and more for $9.99/month.</p>
        </div>
        <?php
    }

    public function is_premium() {
        $key = get_option(self::PREMIUM_KEY, '');
        return !empty($key) && $key !== 'demo';
    }

    public function analyze_content() {
        check_ajax_referer('aco_ajax_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die();
        }

        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));

        // Basic free analysis
        $word_count = str_word_count($content);
        $readability = $this->flesch_readability($content);
        $seo_score = min(100, ($word_count / 10) + ($readability * 10));

        $results = [
            'word_count' => $word_count,
            'readability' => round($readability, 2),
            'seo_score' => round($seo_score),
            'is_premium' => $this->is_premium(),
            'suggestions' => $word_count < 300 ? 'Add more content for better SEO.' : 'Good length!',
            'premium_tease' => !$this->is_premium() ? 'Upgrade for AI rewrite and keywords.' : ''
        ];

        if ($this->is_premium()) {
            // Simulate premium AI features (in real: API call)
            $results['ai_keywords'] = ['keyword1', 'keyword2'];
            $results['ai_rewrite'] = substr($content, 0, 100) . '... (AI optimized)';
        }

        wp_send_json_success($results);
    }

    public function handle_upgrade() {
        check_ajax_referer('aco_ajax_nonce', 'nonce');
        // In real: process payment/redirect
        wp_send_json_success(['message' => 'Redirecting to premium checkout...']);
    }

    private function flesch_readability($text) {
        $sentence_count = preg_match_all('/[.!?]+/', $text);
        $word_count = str_word_count($text);
        $syllable_count = $this->count_syllables($text);
        if ($sentence_count == 0 || $word_count == 0) return 0;
        $asl = $word_count / $sentence_count;
        $asw = $syllable_count / $word_count;
        return 206.835 - (1.015 * $asl) - (84.6 * $asw);
    }

    private function count_syllables($text) {
        $text = strtolower(preg_replace('/[^a-z\s]/', '', $text));
        $rules = '/(?!e[mgd]\\b)([aeiou][a-z]*)[\\b]/';
        preg_match_all($rules, $text, $matches);
        return count($matches[1]);
    }
}

new AIContentOptimizer();

// Dummy JS file content (base64 or inline in real single file, but for JSON: assume enqueued)
// Note: In production, include JS as heredoc or file_get_contents if bundled.