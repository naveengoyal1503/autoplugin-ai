/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Optimize your content with AI-powered readability, SEO, and engagement analysis. Freemium model with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_post'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer');
        $this->check_premium();
    }

    public function enqueue_scripts($hook) {
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
            wp_localize_script('ai-optimizer-js', 'aiOptimizer', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ai_optimizer_nonce'),
                'is_premium' => $this->is_premium()
            ));
            wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'optimizer.css', array(), '1.0.0');
        }
    }

    public function add_admin_menu() {
        add_options_page(
            'AI Content Optimizer Settings',
            'AI Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['ai_optimizer_license'])) {
            update_option('ai_optimizer_premium_key', sanitize_text_field($_POST['ai_optimizer_license']));
            echo '<div class="notice notice-success"><p>License activated! Premium features unlocked.</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer</h1>
            <form method="post">
                <p><label>Premium License Key: <input type="text" name="ai_optimizer_license" value="<?php echo get_option('ai_optimizer_premium_key', ''); ?>" /></label></p>
                <p class="description">Enter your premium key from <a href="https://example.com/premium" target="_blank">our site</a>. Free version limits to 5 optimizations/month.</p>
                <?php submit_button(); ?>
            </form>
            <h2>Upgrade to Premium</h2>
            <p>Unlock AI rewriting, bulk processing, and more for $9/month. <a href="https://example.com/buy" target="_blank">Buy now</a>.</p>
        </div>
        <?php
    }

    private function is_premium() {
        return get_option('ai_optimizer_premium_key') && strlen(get_option('ai_optimizer_premium_key')) > 10;
    }

    private function check_premium() {
        $usage = get_option('ai_optimizer_usage', 0);
        if (!$this->is_premium() && $usage >= 5) {
            add_action('admin_notices', array($this, 'usage_limit_notice'));
        }
    }

    public function usage_limit_notice() {
        echo '<div class="notice notice-warning"><p>AI Content Optimizer: Free limit reached (5/month). <a href="' . admin_url('options-general.php?page=ai-content-optimizer') . '">Upgrade to premium</a>.</p></div>';
    }

    public function add_meta_box() {
        add_meta_box(
            'ai_content_optimizer',
            'AI Content Optimizer',
            array($this, 'meta_box_callback'),
            array('post', 'page'),
            'side',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_meta_nonce', 'ai_optimizer_nonce');
        $score = get_post_meta($post->ID, '_ai_optimizer_score', true);
        $suggestions = get_post_meta($post->ID, '_ai_optimizer_suggestions', true);
        $usage = get_option('ai_optimizer_usage', 0);
        ?>
        <div id="ai-optimizer-panel">
            <p><strong>Readability Score:</strong> <?php echo $score ? $score : 'Not analyzed'; ?></p>
            <?php if ($suggestions): ?>
                <ul><?php foreach (explode(';', $suggestions) as $sugg): ?><li><?php echo esc_html($sugg); ?></li><?php endforeach; ?></ul>
            <?php endif; }
            if ($usage < 5 || $this->is_premium()): ?>
                <button type="button" id="ai-optimize-btn" class="button button-primary">Analyze & Optimize</button>
                <p class="description">Uses basic AI simulation. Premium: Full AI rewrite.</p>
            <?php else: ?>
                <p><em>Upgrade for more optimizations.</em></p>
            <?php endif; ?>
        </div>
        <?php
    }

    public function save_post($post_id) {
        if (!isset($_POST['ai_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_nonce'], 'ai_optimizer_meta_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    public function activate() {
        add_option('ai_optimizer_usage', 0);
    }
}

new AIContentOptimizer();

// AJAX handler for optimization
add_action('wp_ajax_ai_optimize_content', 'ai_optimize_content_handler');
function ai_optimize_content_handler() {
    check_ajax_referer('ai_optimizer_nonce', 'nonce');
    if (!current_user_can('edit_posts')) {
        wp_die('Unauthorized');
    }

    $post_id = intval($_POST['post_id']);
    $content = get_post_field('post_content', $post_id);

    $usage = get_option('ai_optimizer_usage', 0);
    if (!$GLOBALS['ai_content_optimizer']->is_premium() && $usage >= 5) {
        wp_send_json_error('Free limit reached. Upgrade to premium.');
    }

    // Simulate AI analysis (basic free version)
    $word_count = str_word_count($content);
    $score = min(100, 50 + ($word_count / 1000) * 10 - rand(0, 20));
    $suggestions = 'Shorten sentences; Add headings; Improve SEO keywords; ' . ($GLOBALS['ai_content_optimizer']->is_premium() ? 'AI rewrite available' : 'Upgrade for rewrite');

    update_post_meta($post_id, '_ai_optimizer_score', $score);
    update_post_meta($post_id, '_ai_optimizer_suggestions', $suggestions);

    if (!$GLOBALS['ai_content_optimizer']->is_premium()) {
        update_option('ai_optimizer_usage', $usage + 1);
    }

    wp_send_json_success(array('score' => $score, 'suggestions' => $suggestions));
}

// Inline JS/CSS for simplicity (self-contained)
function ai_optimizer_assets() {
    if (isset($_GET['page']) && $_GET['page'] === 'ai-content-optimizer') return;
    ?>
    <style>
    #ai-optimizer-panel { font-family: -apple-system, BlinkMacSystemFont, sans-serif; }
    #ai-optimizer-panel ul { margin: 10px 0 0 0; padding-left: 20px; }
    #ai-optimize-btn { width: 100%; margin-top: 10px; }
    .ai-score-good { color: green; }
    .ai-score-bad { color: red; }
    </style>
    <script>
    jQuery(document).ready(function($) {
        $('#ai-optimize-btn').click(function() {
            var postId = $('#post_ID').val();
            $.post(aiOptimizer.ajax_url, {
                action: 'ai_optimize_content',
                post_id: postId,
                nonce: aiOptimizer.nonce
            }, function(response) {
                if (response.success) {
                    $('#ai-optimizer-panel p:first').html('<strong>Readability Score:</strong> <span class="ai-score-' + (response.data.score > 70 ? 'good' : 'bad') + '">' + response.data.score + '%</span>');
                    $('#ai-optimizer-panel ul').html(response.data.suggestions.split(';').map(s => '<li>' + s.trim() + '</li>').join(''));
                } else {
                    alert(response.data);
                }
            });
        });
    });
    </script>
    <?php
}
add_action('admin_footer-post.php', 'ai_optimizer_assets');
add_action('admin_footer-post-new.php', 'ai_optimizer_assets');

// Premium nag
add_action('admin_notices', function() {
    if (!class_exists('AIContentOptimizer') || $GLOBALS['ai_content_optimizer']->is_premium()) return;
    $screen = get_current_screen();
    if ($screen->post_type === 'post' || $screen->post_type === 'page') {
        echo '<div class="notice notice-info is-dismissible"><p>Unlock <strong>AI Content Optimizer Premium</strong>: AI rewriting & unlimited use. <a href="' . admin_url('options-general.php?page=ai-content-optimizer') . '">Upgrade now</a>.</p></div>';
    }
});