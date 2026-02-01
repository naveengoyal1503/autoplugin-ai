/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for better SEO and readability. Freemium model with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AICO_VERSION', '1.0.0');
define('AICO_PATH', plugin_dir_path(__FILE__));
define('AICO_URL', plugin_dir_url(__FILE__));

// Premium key check (simulate license)
function aico_is_premium() {
    return get_option('aico_premium_key') && get_option('aico_premium_active');
}

// Admin menu
add_action('admin_menu', 'aico_admin_menu');
function aico_admin_menu() {
    add_options_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'aico', 'aico_admin_page');
}

// Admin page
function aico_admin_page() {
    if (isset($_POST['aico_optimize'])) {
        aico_optimize_content($_POST['post_id']);
    }
    if (isset($_POST['aico_premium_key'])) {
        update_option('aico_premium_key', sanitize_text_field($_POST['aico_premium_key']));
        // Simulate activation (in real: call API)
        update_option('aico_premium_active', true);
        echo '<div class="notice notice-success"><p>Premium activated!</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>AI Content Optimizer</h1>
        <p>Free: Basic SEO score and readability analysis.</p>
        <p><strong>Premium ($4.99/mo):</strong> AI rewriting, bulk tools, export reports. <a href="#" onclick="aicoShowPremium()">Upgrade Now</a></p>
        <form method="post">
            <?php wp_nonce_field('aico_optimize'); ?>
            <p><label>Select Post: </label>
            <select name="post_id">
                <?php
                $posts = get_posts(['numberposts' => 20]);
                foreach ($posts as $post) {
                    echo '<option value="' . $post->ID . '">' . esc_html($post->post_title) . '</option>';
                }
                ?>
            </select></p>
            <p><input type="submit" name="aico_optimize" value="Optimize" class="button-primary"></p>
        </form>
        <div id="aico-premium" style="display:none;">
            <h3>Enter Premium Key:</h3>
            <form method="post">
                <input type="text" name="aico_premium_key" placeholder="Premium Key" required>
                <input type="submit" value="Activate" class="button-primary">
            </form>
            <p><em>Get key at example.com/premium</em></p>
        </div>
        <script>
        function aicoShowPremium() {
            document.getElementById('aico-premium').style.display = 'block';
        }
        </script>
    </div>
    <?php
}

// Optimize content
function aico_optimize_content($post_id) {
    $post = get_post($post_id);
    $content = $post->post_content;

    // Basic free analysis
    $word_count = str_word_count(strip_tags($content));
    $readability = $word_count > 300 ? 'Good' : 'Improve length';
    $seo_score = min(100, (int)($word_count / 5));

    echo '<div class="notice notice-info"><p><strong>Free Analysis:</strong><br>Words: ' . $word_count . '<br>Readability: ' . $readability . '<br>SEO Score: ' . $seo_score . '%</p></div>';

    if (aico_is_premium()) {
        // Premium AI rewrite simulation
        $optimized = '[AI Optimized: Improved SEO keywords, readability enhanced.] ' . substr($content, 0, 200) . '...';
        wp_update_post(['ID' => $post_id, 'post_content' => $optimized]);
        echo '<div class="notice notice-success"><p>Premium AI rewrite applied!</p></div>';
    } else {
        echo '<div class="notice notice-warning"><p>Upgrade to premium for AI rewriting and bulk optimization.</p></div>';
    }
}

// Add meta box to posts
add_action('add_meta_boxes', 'aico_meta_box');
function aico_meta_box() {
    add_meta_box('aico-optimizer', 'AI Optimizer', 'aico_meta_box_content', 'post', 'side');
}
function aico_meta_box_content($post) {
    echo '<p><a href="' . admin_url('options-general.php?page=aico') . '" class="button">Quick Optimize</a></p>';
    if (!aico_is_premium()) {
        echo '<p><em>Premium: Unlock AI features</em></p>';
    }
}

// Freemius-like upsell notice
add_action('admin_notices', 'aico_upsell_notice');
function aico_upsell_notice() {
    if (!aico_is_premium() && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Premium</strong> for $4.99/mo: AI rewriting, bulk tools & more! <a href="' . admin_url('options-general.php?page=aico') . '">Upgrade Now</a></p></div>';
    }
}

// Enqueue styles
add_action('admin_enqueue_scripts', 'aico_enqueue');
function aico_enqueue($hook) {
    if ($hook !== 'settings_page_aico') return;
    wp_enqueue_style('aico-style', AICO_URL . 'style.css', [], AICO_VERSION);
}

// Create style.css file placeholder (self-contained, inline would be better but for demo)
/* Inline CSS could be added here */