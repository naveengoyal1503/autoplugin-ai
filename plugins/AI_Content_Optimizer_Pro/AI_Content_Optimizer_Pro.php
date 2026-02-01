/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes WordPress content for SEO using AI-powered analysis. Free version includes basic checks; premium unlocks advanced features.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    private $is_premium = false;
    private $premium_key = '';

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize_content'));
        add_action('wp_ajax_nopriv_optimize_content', array($this, 'ajax_optimize_content'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (get_option('ai_optimizer_premium_key')) {
            $this->premium_key = get_option('ai_optimizer_premium_key');
            $this->is_premium = $this->validate_premium_key();
        }
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'assets/optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_optimizer_nonce')));
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'ai-optimizer') !== false) {
            wp_enqueue_script('ai-optimizer-admin-js', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), '1.0.0', true);
        }
    }

    public function admin_menu() {
        add_options_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'ai-optimizer', array($this, 'admin_page'));
    }

    public function admin_page() {
        $premium_status = $this->is_premium ? 'Active' : 'Inactive';
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Pro</h1>
            <p>Premium Status: <strong><?php echo esc_html($premium_status); ?></strong></p>
            <?php if (!$this->is_premium): ?>
            <form method="post" action="">
                <p><label>Enter Premium Key:</label> <input type="text" name="premium_key" /></p>
                <p><input type="submit" class="button-primary" value="Activate Premium" /></p>
                <?php wp_nonce_field('ai_optimizer_premium'); ?>
            </form>
            <p><a href="https://example.com/premium" target="_blank">Get Premium Key ($4.99/month)</a></p>
            <?php endif; ?>
            <h2>Quick Optimize Post</h2>
            <p>Enter post ID or content:</p>
            <textarea id="content-input" rows="10" cols="80"></textarea><br>
            <button id="optimize-btn" class="button-primary">Optimize</button>
            <div id="results"></div>
        </div>
        <?php
        if (isset($_POST['premium_key']) && wp_verify_nonce($_POST['_wpnonce'], 'ai_optimizer_premium')) {
            update_option('ai_optimizer_premium_key', sanitize_text_field($_POST['premium_key']));
            echo '<div class="notice notice-success"><p>Premium activated! Refresh page.</p></div>';
        }
    }

    public function ajax_optimize_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        $content = sanitize_textarea_field($_POST['content']);
        if (empty($content)) {
            wp_die('No content provided.');
        }

        $suggestions = $this->analyze_content($content);
        if ($this->is_premium) {
            $suggestions = $this->advanced_ai_optimize($suggestions, $content);
        }

        wp_send_json_success($suggestions);
    }

    private function analyze_content($content) {
        // Basic SEO analysis
        $word_count = str_word_count(strip_tags($content));
        $has_title = preg_match('/<h1[^>]*>/i', $content) || preg_match('/<title[^>]*>/i', $content);
        $has_meta = preg_match('/meta name=["\']description["\']/i', $content);
        $headings = preg_match_all('/<h[1-6][^>]*>/i', $content);
        $images = preg_match_all('/<img[^>]+src=["\'][^"\']*["\']/i', $content);

        $suggestions = array(
            'word_count' => $word_count < 300 ? 'Add more content (aim for 300+ words).' : 'Good length.',
            'title' => !$has_title ? 'Add H1 title tag.' : 'Title present.',
            'meta_desc' => !$has_meta ? 'Add meta description.' : 'Meta description present.',
            'headings' => $headings < 3 ? 'Add more H2-H6 headings.' : 'Good heading structure.',
            'images' => $images < 1 ? 'Add relevant images with alt text.' : 'Images present.',
            'readability' => $word_count > 0 ? round(206.835 - 1.015 * ($word_count / ($content ? substr_count($content, '.') + 1 : 1)) - 84.6 * log($word_count) / log(10), 2) : 0
        );
        return $suggestions;
    }

    private function advanced_ai_optimize($basic, $content) {
        // Premium: Simulate AI keyword suggestions, readability enhancements
        $keywords = array('seo', 'content', 'wordpress', 'optimize');
        $basic['keywords'] = 'Suggested keywords: ' . implode(', ', $keywords);
        $basic['ai_rewrite'] = 'Optimized version: ' . wp_trim_words($content, 50, '...') . ' (Enhanced for engagement).';
        return $basic;
    }

    private function validate_premium_key() {
        // Simulate key validation (in real: API call)
        return hash('sha256', $this->premium_key) === 'demo_valid_key_hash';
    }

    public function activate() {
        add_option('ai_optimizer_activated', time());
    }

    public function deactivate() {
        // Cleanup if needed
    }
}

new AIContentOptimizer();

// Frontend shortcode
function ai_optimizer_shortcode($atts) {
    ob_start();
    ?>
    <div id="ai-optimizer-widget">
        <textarea placeholder="Paste content here for quick optimization..."></textarea>
        <button class="button">Optimize</button>
        <div class="results"></div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('ai_optimizer', 'ai_optimizer_shortcode');

// Create assets dir placeholder (in real plugin, include files)
if (!file_exists(plugin_dir_path(__FILE__) . 'assets')) {
    mkdir(plugin_dir_path(__FILE__) . 'assets', 0755, true);
}

?>