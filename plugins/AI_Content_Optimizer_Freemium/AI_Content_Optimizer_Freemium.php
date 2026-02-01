/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Freemium.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Freemium
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes post content for SEO and readability. Freemium: Free basic, premium for advanced.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

// Premium check (simulate license - in real, integrate with Freemius or similar)
function ai_content_optimizer_is_premium() {
    // TODO: Replace with actual license check
    return false; // Free version by default
}

// Add admin menu
add_action('admin_menu', 'ai_content_optimizer_menu');
function ai_content_optimizer_menu() {
    add_posts_page(
        'AI Content Optimizer',
        'AI Optimizer',
        'edit_posts',
        'ai-content-optimizer',
        'ai_content_optimizer_page'
    );
}

// Admin page
function ai_content_optimizer_page() {
    $post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
    $post = $post_id ? get_post($post_id) : null;
    $content = $post ? $post->post_content : '';
    $analysis = '';
    $suggestions = [];
    $usage = get_option('ai_optimizer_usage', 0);
    $limit = ai_content_optimizer_is_premium() ? 999 : 5;

    if (isset($_POST['analyze']) && $content) {
        if ($usage >= $limit) {
            echo '<div class="notice notice-error"><p>Free limit reached (' . $limit . '/month). <a href="#premium">Upgrade to premium</a>!</p></div>';
        } else {
            $analysis = ai_content_optimizer_analyze($content);
            $suggestions = ai_content_optimizer_suggest($content);
            update_option('ai_optimizer_usage', $usage + 1);
        }
    }

    ?>
    <div class="wrap">
        <h1>AI Content Optimizer</h1>
        <?php if (!$post_id): ?>
            <p><a href="<?php echo admin_url('edit.php?post_type=post'); ?>" class="button">Select a Post</a></p>
        <?php else: ?>
            <form method="post">
                <h2>Post: <?php echo esc_html($post->post_title); ?></h2>
                <textarea name="content" rows="10" cols="80" style="width:100%;"><?php echo esc_textarea($content); ?></textarea>
                <p><input type="submit" name="analyze" class="button-primary" value="Analyze Content"></p>
            </form>
            <?php if ($analysis): ?>
                <h3>Analysis Results</h3>
                <p><strong>Readability Score:</strong> <?php echo esc_html($analysis['readability']); ?>/10</p>
                <p><strong>SEO Score:</strong> <?php echo esc_html($analysis['seo']); ?>/10</p>
                <p><strong>Word Count:</strong> <?php echo esc_html($analysis['words']); ?></p>
                <h3>Suggestions (Free: Basic | Premium: Advanced)</h3>
                <ul>
                    <?php foreach ($suggestions as $sugg): ?>
                        <li><?php echo esc_html($sugg); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php if (!ai_content_optimizer_is_premium()): ?>
                    <div style="background:#fff3cd;padding:15px;border:1px solid #ffeaa7;margin:20px 0;">
                        <h4>Unlock Premium Features</h4>
                        <ul>
                            <li>Unlimited analyses</li>
                            <li>Auto-optimize button</li>
                            <li>Advanced AI suggestions</li>
                            <li>Priority support</li>
                        </ul>
                        <a href="https://example.com/premium" class="button-primary" target="_blank">Upgrade Now - $9.99/mo</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
        <p><strong>Usage this month:</strong> <?php echo $usage; ?>/<?php echo $limit; ?></p>
    </div>
    <?php
}

// Mock AI analysis
function ai_content_optimizer_analyze($content) {
    $words = str_word_count(strip_tags($content));
    $sentences = preg_split('/[.!?]+/', $content);
    $sentences = array_filter($sentences);
    $avg_sentence = $words / count($sentences);
    $readability = $avg_sentence > 25 ? 4 : 8; // Simple mock
    $seo = rand(5, 10); // Mock
    return [
        'readability' => $readability,
        'seo' => $seo,
        'words' => $words
    ];
}

// Mock suggestions
function ai_content_optimizer_suggest($content) {
    $suggestions = [
        'Add more subheadings for better structure.',
        'Include 2-3 target keywords naturally.',
        'Shorten some sentences for readability.'
    ];
    if (ai_content_optimizer_is_premium()) {
        $suggestions[] = 'Premium: Auto-generated meta description suggested.';
    }
    return $suggestions;
}

// Reset usage monthly
add_action('wp', 'ai_content_optimizer_check_monthly_reset');
function ai_content_optimizer_check_monthly_reset() {
    $last_reset = get_option('ai_optimizer_last_reset', 0);
    if (date('n') != date('n', $last_reset)) {
        update_option('ai_optimizer_usage', 0);
        update_option('ai_optimizer_last_reset', time());
    }
}

// Enqueue styles
add_action('admin_head', 'ai_content_optimizer_styles');
function ai_content_optimizer_styles() {
    echo '<style>.wrap h1 { color: #0073aa; }</style>';
}
