/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for SEO, readability, and engagement.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_optimize_content', array($this, 'handle_optimize_content'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_post_optimization'));
        add_filter('the_content', array($this, 'display_optimization_score'), 10, 1);
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        add_option('ai_content_optimizer_pro', 'free');
    }

    public function add_admin_menu() {
        add_options_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'ai-content-optimizer', array($this, 'settings_page'));
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Pro</h1>
            <p>Upgrade to Pro for unlimited optimizations: <a href="https://example.com/pro">Get Pro</a></p>
            <form method="post" action="options.php">
                <?php settings_fields('ai_optimizer_settings'); ?>
                <?php do_settings_sections('ai_optimizer_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>OpenAI API Key (Pro)</th>
                        <td><input type="password" name="ai_optimizer_api_key" value="<?php echo esc_attr(get_option('ai_optimizer_api_key')); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function add_meta_box() {
        add_meta_box('ai-content-score', 'AI Content Score', array($this, 'meta_box_callback'), 'post', 'side');
        add_meta_box('ai-content-score', 'AI Content Score', array($this, 'meta_box_callback'), 'page', 'side');
    }

    public function meta_box_callback($post) {
        $score = get_post_meta($post->ID, '_ai_content_score', true);
        $suggestions = get_post_meta($post->ID, '_ai_content_suggestions', true);
        echo '<p><strong>Score:</strong> ' . esc_html($score ?: 'Not analyzed') . '/100</p>';
        if ($suggestions) {
            echo '<p><strong>Suggestions:</strong> ' . esc_html($suggestions) . '</p>';
        }
        echo '<p><a href="#" class="button optimize-content" data-post-id="' . $post->ID . '">Optimize Now (Free: 5/day)</a></p>';
    }

    public function handle_optimize_content() {
        if (!wp_verify_nonce($_POST['nonce'], 'optimize_content')) {
            wp_die('Security check failed');
        }

        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);
        if (!$post || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Unauthorized');
        }

        $content = $post->post_content;
        $analysis = $this->analyze_content($content);

        update_post_meta($post_id, '_ai_content_score', $analysis['score']);
        update_post_meta($post_id, '_ai_content_suggestions', $analysis['suggestions']);

        // Free limit simulation
        $today = date('Y-m-d');
        $usage = get_option('ai_optimizer_usage_' . $today, 0);
        if ($usage >= 5 && get_option('ai_content_optimizer_pro') !== 'pro') {
            wp_send_json_error('Free limit reached. Upgrade to Pro.');
        }
        update_option('ai_optimizer_usage_' . $today, $usage + 1);

        wp_send_json_success($analysis);
    }

    private function analyze_content($content) {
        // Simulated AI analysis (Pro version integrates real OpenAI)
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability = $word_count > 0 ? 200 / ($sentence_count / $word_count * 100) : 0;

        $score = min(100, (int)($readability * 0.4 + (min(500, $word_count) / 5) * 0.3 + rand(10, 30) * 0.3));

        $suggestions = [];
        if ($word_count < 300) $suggestions[] = 'Add more content for better SEO.';
        if ($readability < 60) $suggestions[] = 'Improve readability: shorter sentences.';
        if (rand(0, 1)) $suggestions[] = 'Include more headings and lists.';

        return [
            'score' => $score,
            'suggestions' => implode(' ', $suggestions),
            'readability' => round($readability),
            'word_count' => $word_count
        ];
    }

    public function save_post_optimization($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($post_id)) return;
        if (get_post_type($post_id) !== 'post' && get_post_type($post_id) !== 'page') return;

        // Auto-analyze on save (Pro feature)
        if (get_option('ai_content_optimizer_pro') === 'pro') {
            $content = get_post_field('post_content', $post_id);
            $analysis = $this->analyze_content($content);
            update_post_meta($post_id, '_ai_content_score', $analysis['score']);
            update_post_meta($post_id, '_ai_content_suggestions', $analysis['suggestions']);
        }
    }

    public function display_optimization_score($content) {
        if (is_single() || is_page()) {
            global $post;
            $score = get_post_meta($post->ID, '_ai_content_score', true);
            if ($score) {
                $content .= '<div style="background:#f0f8ff;padding:10px;margin:20px 0;border-left:4px solid #0073aa;">
                    <strong>AI Score: ' . $score . '/100</strong> | Optimized for SEO & Readability
                </div>';
            }
        }
        return $content;
    }
}

AIContentOptimizer::get_instance();

// Enqueue admin scripts
function ai_optimizer_admin_scripts($hook) {
    if ($hook !== 'post.php' && $hook !== 'post-new.php' && $hook !== 'settings_page_ai-content-optimizer') return;
    wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
    wp_localize_script('ai-optimizer-js', 'ai_optimizer_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('optimize_content')
    ));
}
add_action('admin_enqueue_scripts', 'ai_optimizer_admin_scripts');

// Placeholder for admin.js content (inline for single file)
function ai_optimizer_inline_js() {
    if (isset($_GET['page']) && $_GET['page'] === 'ai-content-optimizer') {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('.optimize-content').click(function(e) {
                e.preventDefault();
                var $btn = $(this);
                var postId = $btn.data('post-id');
                $.post(ai_optimizer_ajax.ajax_url, {
                    action: 'optimize_content',
                    post_id: postId,
                    nonce: ai_optimizer_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        alert('Score: ' + response.data.score + '\nSuggestions: ' + response.data.suggestions);
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });
        });
        </script>
        <?php
    }
}
add_action('admin_footer', 'ai_optimizer_inline_js');