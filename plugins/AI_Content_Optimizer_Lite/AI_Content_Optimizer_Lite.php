/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your post content for SEO and readability using AI-powered insights. Freemium model with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define plugin constants
define('AICONTENTOPTIMIZER_VERSION', '1.0.0');
define('AICONTENTOPTIMIZER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AICONTENTOPTIMIZER_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Premium check function
function aicontentoptimizer_is_premium() {
    // Simulate premium check (replace with actual license validation)
    return false; // Free version
}

// Admin menu
add_action('admin_menu', 'aicontentoptimizer_admin_menu');
function aicontentoptimizer_admin_menu() {
    add_options_page(
        'AI Content Optimizer',
        'AI Content Optimizer',
        'manage_options',
        'ai-content-optimizer',
        'aicontentoptimizer_admin_page'
    );
}

// Admin page
function aicontentoptimizer_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1>AI Content Optimizer Lite</h1>
        <p>Optimize your content for better SEO and engagement!</p>
        <?php if (!aicontentoptimizer_is_premium()): ?>
        <div class="notice notice-info">
            <p><strong>Go Premium</strong> for unlimited scans, advanced AI suggestions, and auto-optimization. <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p>
        </div>
        <?php endif; ?>
        <form method="post" action="">
            <?php wp_nonce_field('aicontentoptimizer_optimize', 'aicontentoptimizer_nonce'); ?>
            <textarea name="content" rows="10" cols="80" placeholder="Paste your content here..."><?php echo isset($_POST['content']) ? esc_textarea($_POST['content']) : ''; ?></textarea><br>
            <input type="submit" name="optimize_content" class="button-primary" value="Optimize Content">
        </form>
        <?php
        if (isset($_POST['optimize_content']) && wp_verify_nonce($_POST['aicontentoptimizer_nonce'], 'aicontentoptimizer_optimize')) {
            $content = sanitize_textarea_field($_POST['content']);
            if (!empty($content)) {
                echo '<h3>Optimization Results:</h3>';
                echo aicontentoptimizer_analyze_content($content);
            }
        }
        ?>
    </div>
    <?php
}

// Analyze content function
function aicontentoptimizer_analyze_content($content) {
    $word_count = str_word_count(strip_tags($content));
    $readability = aicontentoptimizer_calculate_flesch($content);
    $seo_score = min(100, (int)($word_count / 10 + $readability));

    $output = '<div class="card">';
    $output .= '<p><strong>Word Count:</strong> ' . $word_count . '</p>';
    $output .= '<p><strong>Readability Score (Flesch):</strong> ' . round($readability, 2) . ' <small>(Higher is better)</small></p>';
    $output .= '<p><strong>SEO Score:</strong> ' . $seo_score . '%</p>';

    $suggestions = [];
    if ($word_count < 300) {
        $suggestions[] = 'Add more content to reach at least 300 words for better SEO.';
    }
    if ($readability < 60) {
        $suggestions[] = 'Improve readability by using shorter sentences and simpler words.';
    }
    if (!preg_match('/<h2/i', $content)) {
        $suggestions[] = 'Add H2 headings to improve structure.';
    }

    if (!empty($suggestions)) {
        $output .= '<h4>Suggestions:</h4><ul>';
        foreach ($suggestions as $sugg) {
            $output .= '<li>' . esc_html($sugg) . '</li>';
        }
        $output .= '</ul>';
    }

    $output .= '</div>';

    if (aicontentoptimizer_is_premium()) {
        // Premium: Auto-optimized content
        $optimized = aicontentoptimizer_optimize_content($content);
        $output .= '<h4>Optimized Content:</h4><div class="optimized-content">' . $optimized . '</div>';
    } else {
        $output .= '<p><em>Premium feature: Auto-optimization available in Pro version.</em></p>';
    }

    return $output;
}

// Simple Flesch Reading Ease calculation
function aicontentoptimizer_calculate_flesch($text) {
    $text = strip_tags($text);
    $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    $sentence_count = count($sentences);
    $words = preg_split('/\s+/', trim($text));
    $word_count = count($words);
    $syllables = 0;
    foreach ($words as $word) {
        $syllables += aicontentoptimizer_count_syllables($word);
    }
    if ($sentence_count == 0 || $word_count == 0) return 0;
    $asl = $word_count / $sentence_count;
    $asw = $syllables / $word_count;
    return 206.835 - 1.015 * $asl - 84.6 * $asw;
}

// Simple syllable counter
function aicontentoptimizer_count_syllables($word) {
    $word = strtolower($word);
    $count = 0;
    $vowels = '[aeiouy]';
    if (preg_match('/^$vowels/', $word)) $count++;
    preg_match_all('/$vowels+/', $word, $matches);
    $count += count($matches);
    if (ends_with($word, 'e')) $count--;
    return max(1, $count);
}

function aicontentoptimizer_optimize_content($content) {
    // Premium placeholder: Return enhanced content
    return wpautop($content . '<p>Premium optimized version here.</p>');
}

// Gutenberg block integration
add_action('enqueue_block_editor_assets', 'aicontentoptimizer_block_assets');
function aicontentoptimizer_block_assets() {
    wp_enqueue_script(
        'aicontentoptimizer-block',
        AICONTENTOPTIMIZER_PLUGIN_URL . 'block.js',
        ['wp-blocks', 'wp-element', 'wp-editor'],
        AICONTENTOPTIMIZER_VERSION
    );
}

// Enqueue styles
add_action('admin_enqueue_scripts', 'aicontentoptimizer_styles');
function aicontentoptimizer_styles($hook) {
    if ('settings_page_ai-content-optimizer' !== $hook) return;
    wp_enqueue_style('aicontentoptimizer-admin', AICONTENTOPTIMIZER_PLUGIN_URL . 'admin.css', [], AICONTENTOPTIMIZER_VERSION);
}

// Add admin CSS
add_action('admin_head', 'aicontentoptimizer_admin_css');
function aicontentoptimizer_admin_css() {
    echo '<style>.card { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; background: #f9f9f9; }.optimized-content { background: white; padding: 15px; border: 1px solid #ccc; white-space: pre-wrap; }</style>';
}

// Activation hook
register_activation_hook(__FILE__, 'aicontentoptimizer_activate');
function aicontentoptimizer_activate() {
    add_option('aicontentoptimizer_version', AICONTENTOPTIMIZER_VERSION);
}

// Plugin row meta
add_filter('plugin_row_meta', 'aicontentoptimizer_row_meta', 10, 2);
function aicontentoptimizer_row_meta($links, $file) {
    if (strpos($file, '/ai-content-optimizer.php') !== false) {
        $links[] = '<a href="https://example.com/premium" target="_blank">Go Premium</a>';
        $links[] = '<a href="https://example.com/docs" target="_blank">Docs</a>';
    }
    return $links;
}

?>